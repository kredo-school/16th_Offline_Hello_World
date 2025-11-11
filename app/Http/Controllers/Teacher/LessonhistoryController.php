<?php

namespace App\Http\Controllers\Teacher;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Course;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LessonHistoryController extends Controller
{
    public function index(Request $request)
    {
        $teacher = Auth::user();

        // 必要なら認可
        // abort_unless($teacher && $teacher->role === 'teacher', 403);

        $now   = Carbon::now();
        $today = $now->toDateString();
        $nowT  = $now->format('H:i:s');

        // フィルタ値（独立）
        $courseId  = $request->query('course_id');
        $studentId = $request->query('student_id');

        // フィルタ用コース一覧：この先生の過去レッスンに紐づくコース
        $courses = Course::whereHas('bookings', function ($q) use ($teacher, $today, $nowT) {
                $q->where('teacher_id', $teacher->id)
                  ->whereNotNull('student_id')
                  ->where(function ($q2) use ($today, $nowT) {
                      $q2->where('date', '<', $today)
                         ->orWhere(function ($q3) use ($today, $nowT) {
                             $q3->where('date', $today)
                                ->where('time', '<', $nowT);
                         });
                  });
            })
            ->orderBy('title')
            ->get();

        // フィルタ用生徒一覧：この先生の過去レッスンに登場した生徒
        $studentIds = Booking::query()
            ->where('teacher_id', $teacher->id)
            ->whereNotNull('student_id')
            ->where(function ($q) use ($today, $nowT) {
                $q->where('date', '<', $today)
                  ->orWhere(function ($q2) use ($today, $nowT) {
                      $q2->where('date', $today)
                         ->where('time', '<', $nowT);
                  });
            })
            ->pluck('student_id')
            ->unique()
            ->filter()
            ->values();

        $students = User::whereIn('id', $studentIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        // レッスン履歴本体
        $bookings = Booking::query()
            ->with([
                'student:id,name',
                'course:id,title,image_url',
                'topic:id,course_id,name',
                'report:booking_id,status,next_topic',
            ])
            ->where('teacher_id', $teacher->id)
            ->whereNotNull('student_id')
            ->where(function ($q) use ($today, $nowT) {
                $q->where('date', '<', $today)
                  ->orWhere(function ($q2) use ($today, $nowT) {
                      $q2->where('date', $today)
                         ->where('time', '<', $nowT);
                  });
            })
            ->when($courseId, function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            })
            ->when($studentId, function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate(5)
            ->appends($request->query());

        // Ajax のときは部分テンプレだけ返す
        if ($request->ajax()) {
            return view('teacher.partials.lesson-history-list', compact('bookings'))->render();
        }

        // 通常表示
        return view('teacher.lessonhistory', compact(
            'bookings',
            'teacher',
            'courses',
            'students',
            'courseId',
            'studentId'
        ));
    }
}