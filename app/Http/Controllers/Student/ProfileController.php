<?php

namespace App\Http\Controllers\Student;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;

class ProfileController extends Controller
{
    /**
     * プロフィール表示
     */
    public function show(Request $request, User $user)
    {
        /*
        |--------------------------------------------------------------------------
        | Enrolled courses（enrollments.status で Active / Completed 分類）
        |--------------------------------------------------------------------------
        |
        | 前提:
        | - users <-> courses は enrollments ピボット
        | - enrollments.status:
        |     'completed' -> Completed
        |     それ以外(null, 'active', など) -> Active
        */

        $enrolledCourses = $user->courses()
            ->with('topics:id,course_id,name')
            ->withPivot('status')
            ->get();

        $activeCollection = collect();
        $completedCollection = collect();

        foreach ($enrolledCourses as $course) {
            $pivotStatus = strtolower((string) ($course->pivot->status ?? ''));

            if ($pivotStatus === 'completed') {
                $completedCollection->push($course);
            } else {
                $activeCollection->push($course);
            }
        }

        // ==== ページネーション（各タブ 5件ずつ） ====
        $activePage = (int) $request->query('active_page', 1);
        $completedPage = (int) $request->query('completed_page', 1);
        $perPageCourses = 5;
        $basePath = $request->url();

        $activeCourses = new LengthAwarePaginator(
            $activeCollection->forPage($activePage, $perPageCourses)->values(),
            $activeCollection->count(),
            $perPageCourses,
            $activePage,
            [
                'path' => $basePath,
                'pageName' => 'active_page',
                'query' => $request->query(),
            ]
        );

        $completedCourses = new LengthAwarePaginator(
            $completedCollection->forPage($completedPage, $perPageCourses)->values(),
            $completedCollection->count(),
            $perPageCourses,
            $completedPage,
            [
                'path' => $basePath,
                'pageName' => 'completed_page',
                'query' => $request->query(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Lesson history（過去レッスン 5件ページネーション）
        |  - todo/pending前提のロジックは使わない
        |--------------------------------------------------------------------------
        */

        $now   = Carbon::now();
        $today = $now->toDateString();
        $nowT  = $now->format('H:i:s');

        $history = Booking::query()
            ->select([
                'id',
                'student_id',
                'teacher_id',
                'course_id',
                'topic_id',
                'date',
                'time',
            ])
            ->with([
                'course:id,title,image_url',
                'topic:id,course_id,name',
                'teacher:id,name',
                'report:booking_id,status,next_topic,feedback',
                'report.nextTopic:id,name',
            ])
            ->where('student_id', $user->id)
            // 過去レッスンのみ
            ->where(function ($q) use ($today, $nowT) {
                $q->where('date', '<', $today)
                  ->orWhere(function ($q2) use ($today, $nowT) {
                      $q2->where('date', $today)
                         ->where('time', '<', $nowT);
                  });
            })
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate(5, ['*'], 'history_page')
            ->appends($request->query());

        return view('student.profile', [
            'user'             => $user,
            'activeCourses'    => $activeCourses,
            'completedCourses' => $completedCourses,
            'history'          => $history,
        ]);
    }

    /**
     * プロフィール更新
     */
    public function update(Request $request, User $user)
{
    $viewer = $request->user();

    // ログインしていない or 自分以外 → 403
    if (!$viewer || $viewer->id !== $user->id) {
        abort(403);
    }

    $validated = $request->validate([
        'name'  => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        'about' => ['nullable', 'string', 'max:1000'],
    ]);

    $user->update($validated);

    return redirect()
        ->route('students.profile.show', ['user' => $user->id])
        ->with('status', 'Profile updated.');
}

public function updatePhoto(Request $request, User $user)
{
    $viewer = $request->user();

    // ログインしていない or 自分以外 → 403
    if (!$viewer || $viewer->id !== $user->id) {
        abort(403);
    }

    $request->validate([
        'photo' => ['required', 'image', 'max:2048'],
    ]);

    if (!empty($user->avatar_path)) {
        Storage::disk('public')->delete($user->avatar_path);
    }

    $path = $request->file('photo')->store('avatars', 'public');
    $user->avatar_path = $path;
    $user->save();

    return redirect()
        ->route('students.profile.show', ['user' => $user->id])
        ->with('status', 'Photo updated.');
}
}
