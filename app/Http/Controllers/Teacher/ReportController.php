<?php

namespace App\Http\Controllers\Teacher;

use App\Models\Topic;
use App\Models\Report;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Enrollment;

class ReportController extends Controller
{
    // Report の status 候補（ビューの <select> と合わせる）
    public const ALLOWED_STATUSES = [
        'Attended',
        'Absent',
        'Canceled by teacher',
        'Other',
    ];

    /**
     * GET /teachers/reports/{booking}
     * Report モーダルの表示用データを返す（JSON）
     */
   public function show(Booking $booking)
{
    if ($booking->teacher_id !== Auth::id()) abort(403);

    $booking->load(['student:id,name,email', 'course:id,title', 'topic:id,name', 'report']);

    $date = $booking->date instanceof \Carbon\Carbon
        ? $booking->date->format('Y-m-d')
        : (string) $booking->date;

    $time = $booking->time instanceof \Carbon\Carbon
        ? $booking->time->format('H:i:s')
        : (string) $booking->time;
    if (preg_match('/^\d{2}:\d{2}$/', $time)) { $time .= ':00'; }

    $end = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', "{$date} {$time}")
            ->addMinutes($booking->duration_minutes ?? 50)
            ->format('Y-m-d H:i');

    // ★ 予約のコースに紐づくトピックのみ取得（id昇順）
    $topics = \App\Models\Topic::query()
        ->when($booking->course_id, fn($q) => $q->where('course_id', $booking->course_id))
        ->where('status', 1)
        ->orderBy('id')
        ->get(['id','name'])
        ->map(fn($t) => ['id' => $t->id, 'name' => $t->name ?? ''])
        ->values();

    // ★ 既定選択：report.next_topic があればそれ、無ければ予約の topic_id
    $preferredTopicId = optional($booking->report)->next_topic ?? $booking->topic_id;

    // topics(=status=1のみ) に含まれていない場合はリセット
if ($preferredTopicId && !$topics->contains(fn($t) => $t['id'] === $preferredTopicId)) {
    $preferredTopicId = $topics->first()['id'] ?? null;
}

        // ★ Enrollment（あれば）取得
    $enrollment = null;
    if ($booking->student_id && $booking->course_id) {
        $enrollment = Enrollment::where('user_id', $booking->student_id)
            ->where('course_id', $booking->course_id)
            ->first();
    }

    return response()->json([
        'booking' => [
            'id'    => $booking->id,
            'date'  => $date,
            'start' => substr($time, 0, 5),
            'end'   => \Carbon\Carbon::createFromFormat('Y-m-d H:i', $end)->format('H:i'),
        ],
        'student' => $booking->student ? [
            'id' => $booking->student->id,
            'name' => $booking->student->name,
            'email' => $booking->student->email,
        ] : null,
        'course' => $booking->course ? [
            'id' => $booking->course->id,
            'title' => $booking->course->title,
        ] : null,
        'topic' => $booking->topic ? [
            'id' => $booking->topic->id,
            'name' => $booking->topic->name,
        ] : null,
        'report' => $booking->report ? [
            'id'            => $booking->report->id,
            'status'        => $booking->report->status,
            'feedback'      => $booking->report->feedback,
            'next_topic' => $booking->report->next_topic, // ★ ここはID
        ] : [
            'id'            => null,
            'status'        => null,
            'feedback'      => null,
            'next_topic' => null,
        ],
        'topics' => $topics,
        'preferred_topic_id' => $preferredTopicId, // ★ JS側がそのまま初期選択できるように
        // ★ enrollment 情報をフロントに渡す
        'enrollment' => $enrollment ? [
            'id'     => $enrollment->id,
            'status' => $enrollment->status, // 'active' or 'completed' など
        ] : null,
    ]);
}

    /**
     * PATCH /teachers/reports/{booking}
     * Report の status / feedback を保存（Upsert）
     */
  public function update(Request $request, Booking $booking)
{
    if ($booking->teacher_id !== Auth::id()) abort(403);

    $data = $request->validate([
        'status'            => ['sometimes','nullable','string','in:' . implode(',', self::ALLOWED_STATUSES)],
        'feedback'          => ['sometimes','nullable','string','max:5000'],
        'next_topic'        => ['sometimes','nullable','integer','exists:topics,id'],
        // ★ チェックボックス用（completed / active）
        'enrollment_status' => ['sometimes','nullable','in:active,completed'],
    ]);

    $result = DB::transaction(function () use ($booking, $data) {

        // 1) Report upsert
        $report = Report::firstOrNew(['booking_id' => $booking->id]);

        if (array_key_exists('status', $data)) {
            $report->status = $data['status'];
        }
        if (array_key_exists('feedback', $data)) {
            $report->feedback = $data['feedback'];
        }
        if (array_key_exists('next_topic', $data)) {
            $report->next_topic = $data['next_topic'];
        }

        $report->save();

        // 2) Enrollment 更新（チェックボックスの状態をそのまま反映）
        $updatedEnrollment = null;
        if (
            array_key_exists('enrollment_status', $data)
            && $booking->student_id
            && $booking->course_id
            && $data['enrollment_status'] !== null
        ) {
            $enrollment = Enrollment::firstOrNew([
                'user_id'   => $booking->student_id,
                'course_id' => $booking->course_id,
            ]);
            $enrollment->status = $data['enrollment_status']; // 'active' or 'completed'
            $enrollment->save();

            $updatedEnrollment = $enrollment->only(['id','user_id','course_id','status']);
        }

        // 3) next_topic の伝播ロジック（元のまま）

        $nextTopicId = $data['next_topic'] ?? null;
        if (!$nextTopicId) {
            return [
                'updated_report' => $report->only(['id','booking_id','status','feedback','next_topic']),
                'enrollment'     => $updatedEnrollment,
                'touched'        => null,
                'note'           => 'next_topic not provided; only current report saved.',
            ];
        }

        $studentId = $booking->student_id;
        $courseId  = $booking->course_id;
        $curDate   = $booking->date;
        $curTime   = $booking->time;

        $future = Booking::query()
            ->where('student_id', $studentId)
            ->where('course_id',  $courseId)
            ->where(function ($q) use ($curDate, $curTime) {
                $q->where('date', '>', $curDate)
                  ->orWhere(function ($q) use ($curDate, $curTime) {
                      $q->where('date', $curDate)->where('time', '>', $curTime);
                  });
            })
            ->with('report')
            ->orderBy('date')->orderBy('time')
            ->get();

        $norm = fn($s) => strtolower(trim((string) $s));
        $isNonCanceled = function ($rep) use ($norm) {
            return $rep && $norm($rep->status) !== 'canceled by teacher';
        };

        $hasFutureNonCanceledWithReport = $future->first(
            fn($b) => $isNonCanceled($b->report)
        ) !== null;

        if ($hasFutureNonCanceledWithReport) {
            return [
                'updated_report' => $report->only(['id','booking_id','status','feedback','next_topic']),
                'enrollment'     => $updatedEnrollment,
                'touched'        => null,
                'note'           => 'future non-canceled booking with report exists; no propagation as requested.',
            ];
        }

        $firstNoReport = $future->first(fn($b) => !$b->report);
        if ($firstNoReport) {
            $firstNoReport->topic_id = $nextTopicId;
            $firstNoReport->save();

            return [
                'updated_report' => $report->only(['id','booking_id','status','feedback','next_topic']),
                'enrollment'     => $updatedEnrollment,
                'touched'        => [
                    'booking_id' => $firstNoReport->id,
                    'action'     => 'set booking.topic_id because no future non-canceled-with-report exists',
                ],
            ];
        }

        return [
            'updated_report' => $report->only(['id','booking_id','status','feedback','next_topic']),
            'enrollment'     => $updatedEnrollment,
            'touched'        => null,
            'note'           => 'no future target to propagate; nothing else to do.',
        ];
    });

    return response()->json(['ok' => true, 'result' => $result]);
}
}