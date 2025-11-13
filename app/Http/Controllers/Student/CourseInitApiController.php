<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Topic;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CourseInitApiController extends Controller
{
    public function show(Course $course)
    {
        $studentId = Auth::id();

        // 1) トピック一覧
        $topics = $course->topics()
            ->where('status', 1)
            ->select('id', 'name')
            ->orderBy('id')
            ->get();

        // 2) 「過去の予約の中で最新」から next_topic（無ければ最初のtopic）
        $suggested = $this->suggestedTopicId($studentId, $course->id, $topics);

        // 3) 対象コースを担当できる teacher の空きスロット（現在+1時間以降）
        $teacherIds = $course->teachers()
    ->where('users.status', 'active')  // ★ 文字列で判定
    ->pluck('users.id');

        $slots = Booking::query()
    ->whereIn('teacher_id', $teacherIds)
    ->whereNull('student_id') // 空き枠のみ
    ->whereRaw('TIMESTAMP(`date`,`time`) >= ?', [Carbon::now()->addHour()->toDateTimeString()])
    ->with(['teacher:id,name'])
    ->orderBy('date')->orderBy('time')
    ->get(['id','teacher_id','date','time'])
    ->map(function ($b) {
        // --- date を必ず 'Y-m-d' のプレーン文字列にする ---
        $rawDate = $b->getAttribute('date');
        if ($rawDate instanceof Carbon) {
            $date = $rawDate->format('Y-m-d');
        } else {
            // '2025-02-10' or '2025-02-10 00:00:00' 対応
            $date = substr((string) $rawDate, 0, 10);
        }

        // --- time を 'H:i:s' か 'H:i' に正規化 ---
        $rawTime = $b->getAttribute('time');
        if ($rawTime instanceof Carbon) {
            $time = $rawTime->format('H:i:s');
        } else {
            $time = (string) $rawTime;
            if (preg_match('/^\d{2}:\d{2}$/', $time)) {
                $time .= ':00';
            }
        }

        return [
            'booking_id'   => $b->id,
            'date'         => $date,                  // ★ ここが重要：もうISOにさせない
            'time'         => $time,                  // JS 側で normalizeHms() 済み
            'teacher_id'   => $b->teacher_id,
            'teacher_name' => $b->teacher?->name ?? 'Teacher',
        ];
    })
    ->values();

        return response()->json([
            'topics'    => $topics,     // [{id,name},...]
            'suggested' => $suggested,  // int|null
            'slots'     => $slots,      // [{booking_id,date,time,teacher_id,teacher_name},...]
        ]);
    }

    private function suggestedTopicId(int $studentId, int $courseId, $topics): ?int
    {
        if ($topics->isEmpty()) return null;

        $now = Carbon::now();

        // A) next_topicが入っている「過去の予約」の中で最新を優先
        $lastWithNext = Booking::where('student_id',$studentId)
            ->where('course_id',$courseId)
            ->whereRaw('TIMESTAMP(`date`,`time`) <= ?', [$now->toDateTimeString()])
            ->whereHas('report', fn($q)=>$q->whereNotNull('next_topic'))
            ->with('report:id,booking_id,next_topic')
            ->orderByDesc('date')->orderByDesc('time')
            ->first();

        if ($lastWithNext) {
            $next = (int) $lastWithNext->report->next_topic;
            $inCourse = Topic::where('id',$next)->where('course_id',$courseId)->where('status', 1)->exists();
            if ($inCourse) return $next;
        }

        // B) それが無ければ「最新の過去予約」のreportを見る
        $lastPast = \App\Models\Booking::where('student_id',$studentId)
            ->where('course_id',$courseId)
            ->whereRaw('TIMESTAMP(`date`,`time`) <= ?', [$now->toDateTimeString()])
            ->with('report:id,booking_id,next_topic')
            ->orderByDesc('date')->orderByDesc('time')
            ->first();

        if ($lastPast && optional($lastPast->report)->next_topic) {
            $next = (int) $lastPast->report->next_topic;
            $inCourse = Topic::where('id',$next)->where('course_id',$courseId)->where('status', 1) ->exists();
            if ($inCourse) return $next;
        }

        // C) フォールバック：最初のトピック
        return $topics->first()->id;
    }
}