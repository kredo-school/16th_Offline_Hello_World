<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    /**
     * 学生一覧
     */
    public function index(Request $request)
    {
        // role_id はあなたの定義に合わせて（例：Student=3）
        $students = User::where('role_id', 3)
            ->with(['courses' => function ($q) {
                // 一覧で使う項目だけに絞る（任意）
                $q->select('courses.id', 'title');
            }])
            ->withCount('courses')          // $row->courses_count を使えるように
            ->orderByDesc('id')
            ->paginate(10);                 // 量が少ないなら ->get() でもOK

        return view('admin.students.index', compact('students'));
    }

    /**
     * ステータス切替（Active/Inactive）
     */
    public function toggle(User $student)
    {
        abort_if($student->role_id !== 3, 404);

        $student->status = $student->status === 'active' ? 'inactive' : 'active';
        $student->save();

        return back()->with('status', 'Student status updated.');
    }

    /**
     * 編集フォーム
     */
    public function edit(User $student)
    {
        abort_if($student->role_id !== 3, 404);
        return view('admin.students.edit', compact('student'));
    }

    /**
     * 更新
     */
    public function update(Request $request, User $student)
    {
        abort_if($student->role_id !== 3, 404);

        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255', Rule::unique('users','email')->ignore($student->id)],
            'status'   => ['required', Rule::in(['active','inactive'])],
            'avatar'   => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
            'password' => ['nullable','string','min:8'],
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $student->update($data);

        return redirect()->route('admin.students.index')->with('status', 'Student updated successfully.');
    }
}
