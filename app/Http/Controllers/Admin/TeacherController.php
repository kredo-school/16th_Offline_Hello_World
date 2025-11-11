<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    /**
     * Teachers 一覧
     */
    public function index()
    {
        // role_id=2 を Teacher として扱う
        $teachers = User::where('role_id', 2)
            ->with(['courses:id,title'])   // courses リレーション
            ->withCount('courses')         // 担当コース数
            ->orderByDesc('id')
            ->paginate(10);

        // Add モーダル用（複数選択）
        $courses = Course::select('id', 'title')->orderBy('title')->get();

        return view('admin.teachers.index', compact('teachers', 'courses'));
    }

    /**
     * Teacher 作成（モーダルから）
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required','string','max:255'],
            'email'        => ['required','email','max:255','unique:users,email'],
            'password'     => ['required','string','min:8'],
            'course_ids'   => ['array'],
            'course_ids.*' => ['integer','exists:courses,id'],
        ]);

        $teacher = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id'  => 2, // Teacher
            'status'   => $request->input('status', 'active'), // 任意
        ]);

        // コース紐づけ
        $teacher->courses()->sync($data['course_ids'] ?? []);

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Teacher added.');
    }

    /**
     * ステータス切替（active / inactive）
     */
    public function toggle(User $teacher)
    {
        // Teacher 以外は 404
        abort_if($teacher->role_id !== 2, 404);

        $teacher->status = ($teacher->status === 'active') ? 'inactive' : 'active';
        $teacher->save();

        return redirect()
            ->route('admin.teachers.index')
            ->with('success', 'Status updated.');
    }

    /**
     * 単一コースを付与（一覧の行メニュー等から想定）
     */
    public function attach(Request $request, User $user)
    {
        // 権限チェックを使うならポリシー側で設定してこれを有効化
        // $this->authorize('admin-only');

        abort_if($user->role_id !== 2, 404);

        $data = $request->validate([
            'course_id' => ['required','exists:courses,id'],
        ]);

        $user->courses()->syncWithoutDetaching([$data['course_id']]);

    // skills リレーションで紐付け
    $user->skills()->syncWithoutDetaching([$data['course_id']]);

    /**
     * コース解除
     */
    public function detach(User $user, Course $course)
    {
        // $this->authorize('admin-only');

        abort_if($user->role_id !== 2, 404);

public function detach(User $user, Course $course)
{
    // 同様に middleware で保護済み
    $user->skills()->detach($course->id);

        return back()->with('status', 'Course removed.');
    }

    /**
     * 編集フォーム
     */
    public function edit(User $teacher)
    {
        abort_if($teacher->role_id !== 2, 404);

        // 編集画面でもセレクト表示したい場合は一覧と同様に渡す
        $courses = Course::select('id','title')->orderBy('title')->get();

        return view('admin.teachers.edit', compact('teacher','courses'));
    }

    /**
     * 更新処理
     */
    public function update(Request $request, User $teacher)
    {
        abort_if($teacher->role_id !== 2, 404);

        $data = $request->validate([
            'name'         => ['required','string','max:255'],
            'email'        => ['required','email','max:255','unique:users,email,' . $teacher->id],
            'status'       => ['nullable','in:active,inactive'],
            'course_ids'   => ['array'],
            'course_ids.*' => ['integer','exists:courses,id'],
            'password'     => ['nullable','string','min:8'], // 任意でパス変更
        ]);

        // 基本情報
        $teacher->name  = $data['name'];
        $teacher->email = $data['email'];

        if (isset($data['status'])) {
            $teacher->status = $data['status'];
        }
        if (!empty($data['password'])) {
            $teacher->password = Hash::make($data['password']);
        }

        $teacher->save();

        // コースの同期（編集画面に複数選択がある想定）
        if ($request->has('course_ids')) {
            $teacher->courses()->sync($data['course_ids'] ?? []);
        }

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Teacher updated.');
    }
}
