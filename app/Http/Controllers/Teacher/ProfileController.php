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
    $user = User::findOrFail($user_id);

    $roleId   = (int) ($user->role_id ?? 0);
    $roleName = (string) ($user->role ?? '');
    $isTeacher = ($roleId === 2 || $roleName === 'teacher');
    if (!$isTeacher) abort(404);

        // ★ skills は status=1 のコースだけ読み込む
        $user = User::with([
            'skills' => function ($q) {
                $q->where('status', 1)         // 有効コースのみ
                  ->select('courses.id', 'title'); // 必要なカラムだけ（任意）
            },
        ])->findOrFail($user_id);

        if ($user->status !== 'active') {
        return view('teacher.profile', [
            'user'       => $user,
            'isInactive' => true,
            'courses'    => collect(),
            'allCourses' => collect(),
        ]);
    }

        $viewer = auth()->user();
        $viewerRoleId = (int) ($viewer->role_id ?? 0);
        $viewerIsAdmin = $viewerRoleId === 1 || ($viewer->role ?? null) === 'admin';

        $allCourses = collect();

        if ($viewerIsAdmin) {
            // すでに紐づいているコースID（= status=1 のみ）を取得
            $assignedIds = $user->skills->pluck('id')->all();

            // ★ 追加候補も status=1 のみ
            $allCourses = Course::select('id', 'title')
                ->where('status', 1)
                ->when(!empty($assignedIds), function ($q) use ($assignedIds) {
                    $q->whereNotIn('id', $assignedIds);
                })
                ->orderBy('title')
                ->get();
        }

        return view('teacher.profile', [
            'user'       => $user,
            'courses'    => $user->skills,   // status=1 のみ
            'allCourses' => $allCourses,     // status=1 かつ未登録のみ
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
