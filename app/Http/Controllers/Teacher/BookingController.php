<?php
namespace App\Http\Controllers\Teacher;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
   private function currentTeacherOrAbort()
{
    $user = Auth::user();

    if (!$user) {
        abort(403);
    }

    $roleId   = (int) ($user->role_id ?? 0);
    $roleName = (string) ($user->role ?? '');

    $isTeacher = ($roleId === 2 || $roleName === 'teacher');

    // 先生本人以外（admin含む）は利用禁止
    if (!$isTeacher) {
        abort(403);
    }

    return $user;
}

   public function store(Request $request)
{
    $teacher = $this->currentTeacherOrAbort();

    // 配列 times[]（必須）で受ける。クリックもドラッグも配列で来る前提に統一。
    $data = $request->validate([
        'date' => ['required','date_format:Y-m-d'],
        'times' => ['required','array','min:1'],
        'times.*' => ['regex:/^\d{2}:\d{2}(:\d{2})?$/'],
        'duration_minutes' => ['nullable','integer','min:15','max:600'],
    ]);

    $duration  = $data['duration_minutes'] ?? 50;
    $teacherId = $teacher->id;

    foreach ($data['times'] as $t) {
        $time = strlen($t) === 5 ? ($t.=':00') : $t; // HH:MM → HH:MM:00 に正規化
        Booking::firstOrCreate(
            ['teacher_id' => $teacherId, 'date' => $data['date'], 'time' => $time],
            ['duration_minutes' => $duration, 'student_id' => null]
        );
    }

    return response()->json(['ok' => true]);
}

public function show(Request $request)
{
    $teacher = $this->currentTeacherOrAbort();
    // 期間（date列ベース）
    $startStr = substr((string) $request->query('start'), 0, 10) ?: '1900-01-01';
    $endStr   = substr((string) $request->query('end'),   0, 10) ?: '2100-12-31';

    // レポート＆学生を事前読込（N+1回避）
    $rows = Booking::with([
            'report:id,booking_id,status',
            'student:id,name',
        ])
        ->where('teacher_id', $teacher->id)
        ->whereBetween('date', [$startStr, $endStr])
        ->get();

    $events = $rows->map(function ($b) {
        // ISO風（Zなし）で統一
        $date = $b->date instanceof \Carbon\Carbon ? $b->date->format('Y-m-d') : (string) $b->date;
        $time = $b->time instanceof \Carbon\Carbon ? $b->time->format('H:i:s') : (string) $b->time;
        if (preg_match('/^\d{2}:\d{2}$/', $time)) { $time .= ':00'; }

        $start = "{$date}T{$time}";
        $end   = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', "$date $time")
                    ->addMinutes($b->duration_minutes ?? 50)
                    ->format('Y-m-d\TH:i:s');

        // ★ 厳密なboolean（JSONでも true/false になる）
        $hasReport = $b->report !== null;

        return [
            'id'    => (string) $b->id,
            // ★ Booked表示を避けたいなら学生名を優先。なければ "Open"
            'title' => $b->student_id ? ($b->student->name ?? 'Booked') : 'Open',
            'start' => $start,
            'end'   => $end,
            'extendedProps' => [
                'student_id'    => $b->student_id,
                'has_report'    => $hasReport,                       // ← これをフロントで判定に使う
                'report_status' => $b->report->status ?? null,       // 表示用
            ],
        ];
    })->values();

    return response()->json($events);
}

// ---- 単体削除（Open のみ物理削除 / Booked はNG）----
    public function destroy($id)
    {
        $teacher = $this->currentTeacherOrAbort();
        $booking = Booking::where('id', $id)->where('teacher_id', $teacher->id)->firstOrFail();

        if ($booking->student_id) {
            return response()->json(['message' => 'This slot is booked. Use cancel endpoint with a reason.'], 422);
        }
        $booking->delete();
        return response()->noContent(); // 204
    }

    // ---- Open の複数削除（ids[] または date/from/to の範囲）----
    public function bulkDestroyOpen(Request $request)
{
     $teacher = $this->currentTeacherOrAbort();
    $data = $request->validate([
        'ids'   => ['sometimes','array'],
        'ids.*' => ['integer'],
        'date'  => ['sometimes','date'],
        'from'  => ['sometimes','regex:/^\d{2}:\d{2}(:\d{2})?$/'],
        'to'    => ['sometimes','regex:/^\d{2}:\d{2}(:\d{2})?$/'],
    ]);

    $tz     = config('app.timezone', 'Asia/Manila');
    $today  = now($tz)->toDateString();        // 'Y-m-d'
    $nowHms = now($tz)->format('H:i:s');       // 'H:i:s'

    $q = Booking::query()
        ->where('teacher_id', $teacher->id)
        ->whereNull('student_id');             // Open 限定

    // ★ 過去は絶対に消さない（開始が現在より前のものは除外）
    $q->where(function ($qq) use ($today, $nowHms) {
        $qq->where('date', '>', $today)
           ->orWhere(function ($qq2) use ($today, $nowHms) {
               $qq2->where('date', $today)
                   ->where('time', '>=', $nowHms);
           });
    });

    if (!empty($data['ids'])) {
        $q->whereIn('id', $data['ids']);
    } elseif (!empty($data['date']) && !empty($data['from']) && !empty($data['to'])) {
        $from = strlen($data['from']) === 5 ? $data['from'].':00' : $data['from'];
        $to   = strlen($data['to'])   === 5 ? $data['to'].':00'   : $data['to'];

        $q->where('date', $data['date'])
          ->where('time', '>=', $from);

        // 上限は排他（13:00 未満までを削除にする）
        if ($to !== '24:00:00') {
            $q->where('time', '<', $to);
        }
    } else {
        return response()->json(['message' => 'Provide ids[] or (date, from, to).'], 422);
    }

    $deleted = $q->delete();
    return response()->json(['deleted' => $deleted]);
}

    // ---- Booked の取り消し（理由必須＋通知＋report更新／予約は削除しない）----
public function cancelBooked(Request $request, $id)
{
    $teacher = $this->currentTeacherOrAbort();
    $data = $request->validate([
        'reason' => ['required','string','max:500'],
    ]);

    $booking = Booking::where('id', $id)
        ->where('teacher_id', $teacher->id)
        ->firstOrFail();

    if (!$booking->student_id) {
        return response()->json(['message' => 'This slot is open. Use delete.'], 422);
    }

    // Report を upsert：status を 'canceled by teacher'、feedback に理由を記録
    $report = \App\Models\Report::firstOrNew(['booking_id' => $booking->id]);
    $report->status = 'canceled by teacher';

    // 既存 feedback がある場合は改行で追記（好みに合わせて上書きでもOK）
    $reasonBlock = "Canceled by teacher. Reason: " . $data['reason'];
    $report->feedback = trim(($report->feedback ? ($report->feedback . "\n") : '') . $reasonBlock);
    $report->save();

    // 生徒に通知（例：メール）
    // $student = \App\Models\User::find($booking->student_id);
    // if ($student && $student->email) {
    //     \Mail::raw(
    //         "Your lesson was canceled by the teacher.\nDate: {$booking->date}\nTime: {$booking->time}\nReason: {$data['reason']}",
    //         fn($m) => $m->to($student->email)->subject('Lesson canceled')
    //     );
    // }

    // 予約レコードは削除しない（reports.status でキャンセル扱いを判定）
    return response()->json(['canceled' => true]);
}

public function move(Request $request, Booking $booking)
{
$teacher = $this->currentTeacherOrAbort();
    // 自分の枠以外は禁止
    if ($booking->teacher_id !== $teacher->id) {
        abort(403);
    }

    // 予約済み or report付きは動かさない（仕様上安全）
    if (!is_null($booking->student_id) || $booking->report()->exists()) {
        return response()->json([
            'message' => 'Only open slots without report can be moved.',
        ], 422);
    }

    // 入力チェック（"H:i"で送ってJSと揃える）
    $data = $request->validate([
        'date' => ['required', 'date'],
        'time' => ['required', 'date_format:H:i'],
    ]);

    // 新しい開始日時
    $start = Carbon::createFromFormat('Y-m-d H:i', $data['date'].' '.$data['time']);

    if ($start->isPast()) {
        return response()->json([
            'message' => 'Cannot move slot to the past.',
        ], 422);
    }

    // 同じ先生・同じ日時に既に open slot がないかチェック
    $exists = Booking::where('teacher_id', $booking->teacher_id)
        ->where('id', '!=', $booking->id)
        ->where('date', $data['date'])
        ->where('time', $data['time'].':00') // 保存形式が H:i:s の場合
        ->whereNull('student_id')
        ->exists();

    if ($exists) {
        return response()->json([
            'message' => 'Another open slot already exists at that time.',
        ], 422);
    }

    // 実際に更新
    $booking->date = $data['date'];
    $booking->time = $data['time'].':00'; // DBに合わせて
    $booking->save();

    return response()->json(['ok' => true]);
}

public function bulkDestroySelected(Request $request)
{
$teacher = $this->currentTeacherOrAbort();
    // $teacherId = Auth::id();

    $data = $request->validate([
        'booking_ids'   => ['required','array','min:1'],
        'booking_ids.*' => ['integer'],
    ]);

    $count = Booking::where('teacher_id', $teacher->id)
        ->whereIn('id', $data['booking_ids'])
        ->whereNull('student_id')   // 予約済みは消さない
        ->doesntHave('report')      // レポートありも消さない
        ->delete();

    return response()->json([
        'ok'    => true,
        'count' => $count,
    ]);
}
public function purgeFutureOpen(Request $request)
{
    $teacher = Auth::user();
    abort_unless($teacher && (int)$teacher->role_id === 2, 403); // teacherのみ

    // inactive のときだけ動作（誤操作防止）
    $status = strtolower((string)($teacher->status ?? ''));
    abort_unless($status === 'inactive', 403);

    // 未来の（今以降）Openスロットを削除
    $now   = Carbon::now(config('app.timezone', 'Asia/Manila'));
    $today = $now->toDateString();     // 'YYYY-MM-DD'
    $nowT  = $now->format('H:i:s');    // 'HH:MM:SS'

    $deleted = DB::table('bookings')
        ->where('teacher_id', $teacher->id)
        ->whereNull('student_id')  // Openのみ
        ->where(function ($q) use ($today, $nowT) {
            $q->where('date', '>', $today)
              ->orWhere(function ($q) use ($today, $nowT) {
                  $q->where('date', '=', $today)
                    ->where('time', '>=', $nowT);
              });
        })
        ->delete();

    return response()->json(['ok' => true, 'deleted' => $deleted]);
}
}