<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SelfLearningController extends Controller
{
    // 学習一覧ページ
  public function index(Request $request)
{
    $search = $request->input('search');
    $user = Auth::user();

    $coursesQuery = $user->courses();

    if ($search) {
        $coursesQuery->where(function ($query) use ($search) {
            $query->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    $myCourses = $coursesQuery->orderBy('courses.id')->get()->values();

    $completedCourses = $myCourses->filter(function ($course) use ($user) {
        return $course->completionRate($user->id) >= 100;
    })->count();

    $hoursLearned = $user->completedLessons()->sum('lesson_user.study_time');

    $recommendedCourses = Course::whereNotIn('id', $user->courses->pluck('id'))
        ->inRandomOrder()
        ->take(5)
        ->get();

    return view('selflearning.index', compact(
        'myCourses',
        'completedCourses',
        'hoursLearned',
        'recommendedCourses'
    ));
}


    private function formatTime($seconds)
    {
        if ($seconds < 60) {
            return $seconds . '秒';
        } elseif ($seconds < 3600) {
            return floor($seconds / 60) . '分' . ($seconds % 60) . '秒';
        } else {
            return floor($seconds / 3600) . '時間' . floor(($seconds % 3600) / 60) . '分' . ($seconds % 60) . '秒';
        }
    }

    // コース詳細
    public function show($id)
{
    $user = Auth::user();

    $course = $user->courses()
    ->with(['topics.lessons'])
    ->where('courses.id', $id)
    ->firstOrFail();


    return view('selflearning.show', compact('course'));
}


    // レッスン動画ページ
    public function lessonVideo($courseId, $lessonId)
    {
        $user = auth()->user();

        $course = $user->courses()
             ->with('topics.lessons')
            ->findOrFail($courseId);

        $lessons = $course->lessons;
        $currentLesson = $lessons->firstWhere('id', $lessonId);

        if (!$currentLesson) abort(404, 'Lesson not found');

        $currentIndex = $lessons->search(fn($l) => $l->id === $currentLesson->id);
        $previousLesson = $lessons->get($currentIndex - 1);
        $nextLesson = $lessons->get($currentIndex + 1);

        $totalSeconds = $user->completedLessons()->sum('lesson_user.study_time');
        $hoursLearned = $this->formatTime($totalSeconds);

        return view('selflearning.lesson-video', compact(
            'course', 'currentLesson', 'previousLesson', 'nextLesson', 'hoursLearned'
        ));
    }

    // レッスンテキストページ
    public function lessonText($courseId, $lessonId)
    {
        $user = auth()->user();

        $course = $user->courses()
            ->with('lessons')
            ->findOrFail($courseId);

        $lessons = $course->lessons()->orderBy('id')->get();
        $currentLesson = $lessons->firstWhere('id', $lessonId);

        if (!$currentLesson) abort(404, 'Lesson not found');

        $currentIndex = $lessons->search(fn($lesson) => $lesson->id == $lessonId);
        $totalLessons = $lessons->count();

        return view('selflearning.lesson-text', compact(
            'course',
            'currentLesson',
            'currentIndex',
            'totalLessons',
            'lessons'
        ));
    }

    // レッスン完了処理
    public function lessonDone($courseId, $lessonId)
    {
        $lesson = Lesson::findOrFail($lessonId);

        auth()->user()->completedLessons()->syncWithoutDetaching([
            $lesson->id => ['completed_at' => now(), 'is_completed' => true]
        ]);

        return redirect()->route('selflearning.lessonVideo', [$courseId, $lessonId])
            ->with('success', 'Lesson marked as completed!');
    }

    // Lesson 完了トグル + 秒単位記録
    public function toggleLesson($courseId, $lessonId)
    {
        $user = auth()->user();
        $lesson = Lesson::findOrFail($lessonId);
        $studyTime = request()->input('study_time', 30);

        if ($user->completedLessons->contains($lessonId)) {
            $user->completedLessons()->detach($lessonId);
            $status = 'unchecked';
        } else {
            $user->completedLessons()->attach($lessonId, [
                'is_completed' => true,
                'completed_at' => now(),
                'study_time' => $studyTime,
            ]);
            $status = 'checked';
        }

        $totalSeconds = $user->completedLessons()->sum('lesson_user.study_time');
        $hoursLearned = $this->formatTime($totalSeconds);

        return response()->json([
            'status' => $status,
            'hours_learned' => $hoursLearned,
        ]);
    }

    // study_time更新処理
    public function updateStudyTime(Request $request)
    {
        $user = Auth::user();
        $lessonId = (int) $request->input('lesson_id');
        $seconds = (int) $request->input('seconds', 0);

        if (!$lessonId || $seconds <= 0) {
            return response()->json(['error' => 'Invalid data'], 400);
        }

        $lesson = Lesson::find($lessonId);
        if (!$lesson) {
            return response()->json(['error' => 'Lesson not found'], 404);
        }

        $existing = $user->completedLessons()->where('lessons.id', $lessonId)->first();

        if ($existing) {
            $current = (int) ($existing->pivot->study_time ?? 0);
            $new = $current + $seconds;
            $user->completedLessons()->updateExistingPivot($lessonId, [
                'study_time' => $new,
                'updated_at' => now(),
            ]);
        } else {
            $user->completedLessons()->attach($lessonId, [
                'is_completed' => false,
                'study_time' => $seconds,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $totalSeconds = (int) $user->completedLessons()->sum('lesson_user.study_time');
        $formatted = $this->formatTime($totalSeconds);

        return response()->json([
            'total_study_time' => $totalSeconds,
            'formatted_time' => $formatted,
            'message' => '+' . $seconds . ' sec recorded!',
        ]);
    }
}
