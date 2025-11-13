<?php

namespace App\Http\Controllers\Student;

use Carbon\Carbon;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class IndexController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $tz   = config('app.timezone', 'Asia/Manila');

        $courses = $user->courses()
            ->select('courses.id', 'courses.title', 'courses.status')
            ->with(['topics:id,course_id,name'])
            ->withPivot('status') // enrollments の status を見るため
            ->where('courses.status', 1) // ★ coursesテーブル側が有効(1)のものだけ
            ->orderBy('courses.title')
            ->get();

        // Up next booking（直近の予約1件）を取得追加
        $now     = Carbon::now($tz); // 日時を取得
        $nowDate = $now->toDateString();      // '2025-10-27'　日時のみ
        $nowTime = $now->format('H:i:s');     // '14:08:00'　時間のみ

        // 直前の予約（Up next）
$upNext = Booking::with(['course', 'topic', 'teacher'])
    ->where('student_id', $user->id)
    // ← 追加：講師キャンセルは除外
    ->whereDoesntHave('report', function ($q) {
        $q->whereRaw('LOWER(status) = ?', ['canceled by teacher']);
    })
    ->where(function ($q) use ($nowDate, $nowTime) {
        $q->where('date', '>', $nowDate)
          ->orWhere(function ($q) use ($nowDate, $nowTime) {
              $q->where('date', $nowDate)->where('time', '>=', $nowTime);
          });
    })
    ->orderBy('date')
    ->orderBy('time')
    ->first();

// 直近の過去レッスン（Lesson history）
$history = Booking::with([
        'course:id,title,image_url',
        'topic:id,course_id,name',
        'teacher:id,name',
        'report:booking_id,status,next_topic',
        'report.nextTopic:id,name',
    ])
    ->where('student_id', $user->id)
    // ← 追加：講師キャンセルは除外
    // ->whereDoesntHave('report', function ($q) {
    //     $q->whereRaw('LOWER(status) = ?', ['canceled by teacher']);
    // })
    ->where(function ($q) use ($nowDate, $nowTime) {
        $q->where('date', '<', $nowDate)
          ->orWhere(function ($q) use ($nowDate, $nowTime) {
              $q->where('date', $nowDate)->where('time', '<', $nowTime);
          });
    })
    ->orderByDesc('date')->orderByDesc('time')
    ->limit(3)
    ->get();

       // ▼ カレンダー用
$bookingsForCalendar = Booking::with([
    'course:id,title',
    'topic:id,course_id,name',
    'teacher:id,name',
    'report:booking_id,status,next_topic,feedback', // ← 追加
    'report.nextTopic:id,name',
])
->where('student_id', $user->id)
->orderBy('date')->orderBy('time')
->get();

$fcEvents = $bookingsForCalendar->map(function (Booking $b) {
    $startTokyo = $b->startCarbon('Asia/Tokyo');
    $endTokyo   = $b->endCarbon('Asia/Tokyo');

    return [
        'id'    => $b->id,
        'title' => trim(($b->course->title ?? '') . ' ' . ($b->topic->name ?? 'Lesson')) ?: 'Lesson',
        'start' => $startTokyo->toIso8601String(),
        'end'   => $endTokyo->toIso8601String(),
        'extendedProps' => [
            'teacher'        => $b->teacher->name ?? null,
            'teacher_id'      => $b->teacher_id,
            'course_name'    => $b->course->title ?? null,
            'topic_name'     => $b->topic->name ?? null,
            'course_id'      => $b->course_id,
            'topic_id'       => $b->topic_id,
            'has_report'     => (bool)$b->report,
            'report_status'  => optional($b->report)->status,
            'report_next'     => $b->report?->nextTopic?->name,   // ★ name を渡す
            'report_feedback'=> optional($b->report)->feedback,     // ← 追加
        ],
    ];
})->values();

        return view('student.index', compact('courses', 'upNext', 'history', 'fcEvents'));
    }
}
?>