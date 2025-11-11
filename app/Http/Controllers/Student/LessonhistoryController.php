<?php

namespace App\Http\Controllers\Student;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Booking;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LessonhistoryController extends Controller
{
    public function show(Request $request, User $student)
    {
        $now = Carbon::now();

        // フィルタ値（独立）
        $courseId  = $request->query('course_id');
        $teacherId = $request->query('teacher_id');

        $today = $now->toDateString();
        $nowT  = $now->format('H:i:s');

        /*
        |--------------------------------------------------------------------------
        | フィルタ用：この生徒の過去レッスンに紐づく Course 一覧
        |--------------------------------------------------------------------------
        */
        $courseIds = Booking::query()
            ->where('student_id', $student->id)
            ->whereNotNull('course_id')
            ->where(function ($q) use ($today, $nowT) {
                $q->where('date', '<', $today)
                  ->orWhere(function ($q2) use ($today, $nowT) {
                      $q2->where('date', $today)
                         ->where('time', '<', $nowT);
                  });
            })
            ->pluck('course_id')
            ->unique()
            ->filter()
            ->values();

        $courses = Course::whereIn('id', $courseIds)
            ->orderBy('title')
            ->get(['id', 'title']);

        /*
        |--------------------------------------------------------------------------
        | フィルタ用：この生徒の過去レッスンに登場した Teacher 一覧
        |--------------------------------------------------------------------------
        */
        $teacherIds = Booking::query()
            ->where('student_id', $student->id)
            ->whereNotNull('teacher_id')
            ->where(function ($q) use ($today, $nowT) {
                $q->where('date', '<', $today)
                  ->orWhere(function ($q2) use ($today, $nowT) {
                      $q2->where('date', $today)
                         ->where('time', '<', $nowT);
                  });
            })
            ->pluck('teacher_id')
            ->unique()
            ->filter()
            ->values();

        $teachers = User::whereIn('id', $teacherIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        /*
        |--------------------------------------------------------------------------
        | レッスン履歴本体
        |--------------------------------------------------------------------------
        */
        $bookings = Booking::query()
            ->select(['id', 'student_id', 'teacher_id', 'course_id', 'topic_id', 'date', 'time'])
            ->with([
                'course:id,title,image_url',
                'topic:id,course_id,name',
                'teacher:id,name',
                'report:booking_id,status,next_topic',
                'report.nextTopic:id,name',
            ])
            ->where('student_id', $student->id)
            ->where(function ($q) use ($today, $nowT) {
                $q->where('date', '<', $today)
                  ->orWhere(function ($q2) use ($today, $nowT) {
                      $q2->where('date', $today)
                         ->where('time', '<', $nowT);
                  });
            })
            // Course フィルタ
            ->when($courseId, function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            })
            // Teacher フィルタ
            ->when($teacherId, function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId);
            })
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate(5)
            ->appends($request->query());

        return view('student.lessonhistory', [
            'bookings'   => $bookings,
            'student'    => $student,
            'courses'    => $courses,
            'teachers'   => $teachers,
            'courseId'   => $courseId,
            'teacherId'  => $teacherId,
        ]);
    }
}