<?php

namespace App\Http\Controllers\Teacher;

use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
   public function show($user_id)
{
    $user = User::with([
        'skills:id,title', // teacher_course 経由のコース
    ])->findOrFail($user_id);

    $viewer = auth()->user();
    $viewerRoleId = (int) ($viewer->role_id ?? 0);
    $viewerIsAdmin = $viewerRoleId === 1 || ($viewer->role ?? null) === 'admin';

    $allCourses = collect();

    if ($viewerIsAdmin) {
        // すでに紐づいているコースIDを取得（skills は既にロード済み）
        $assignedIds = $user->skills->pluck('id')->all();

        $allCourses = Course::select('id', 'title')
            ->when(!empty($assignedIds), function ($q) use ($assignedIds) {
                $q->whereNotIn('id', $assignedIds);
            })
            ->orderBy('title')
            ->get();
    }

    return view('teacher.profile', [
        'user'       => $user,
        'courses'    => $user->skills,   // 表示用（既に登録済み）
        'allCourses' => $allCourses,     // セレクト用（未登録のみ）
    ]);
}

public function update(Request $request, $user_id)
{
    $user   = User::findOrFail($user_id);
    $viewer = $request->user();

    // 未ログインなら即403
    if (!$viewer) {
        abort(403);
    }

    $roleId   = (int) ($viewer->role_id ?? 0);
    $roleName = (string) ($viewer->role ?? '');

    $isTeacher = $roleId === 2 || $roleName === 'teacher';

    // 「teacher本人のみOK」= それ以外（admin含む）は403
    if (!($isTeacher && $viewer->id === $user->id)) {
        abort(403);
    }

    $validated = $request->validate([
        'name'        => ['required','string','max:255'],
        'email'       => ['required','email','max:255'],
        'about'       => ['nullable','string','max:2000'],
        'meeting_url' => ['nullable','url','max:2048'],
    ]);

    $user->fill($validated)->save();

    return back()->with('status', 'Profile updated.');
}

public function updatePhoto(Request $request, $user_id)
{
    $user   = User::findOrFail($user_id);
    $viewer = $request->user();

    if (!$viewer) {
        abort(403);
    }

    $roleId   = (int) ($viewer->role_id ?? 0);
    $roleName = (string) ($viewer->role ?? '');

    $isTeacher = $roleId === 2 || $roleName === 'teacher';

    // 「teacher本人のみOK」
    if (!($isTeacher && $viewer->id === $user->id)) {
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
        ->route('teachers.profile', ['user_id' => $user->id])
        ->with('status', 'Photo updated.');
}
}
