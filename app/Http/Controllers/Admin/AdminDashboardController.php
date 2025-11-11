<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $studentsCount = User::where('role_id', 3)->count();
        $teachersCount = User::where('role_id', 2)->count();
        $coursesCount  = Course::count();

        $forumsCount = 0;

        // ★ id も取得（pluck禁止 / 配列でなくモデルのまま）
        $latestStudents = User::where('role_id', 3)
            ->orderByDesc('id')->limit(5)->get(['id','name']);

        $latestTeachers = User::where('role_id', 2)
            ->orderByDesc('id')->limit(5)->get(['id','name']);

        $latestCourses = Course::orderByDesc('id')
            ->limit(5)->get(['id','title']);

        return view('admin.index', compact(
            'studentsCount',
            'teachersCount',
            'coursesCount',
            'forumsCount',
            'latestStudents',
            'latestTeachers',
            'latestCourses'
        ));
    }

    // （おまけ）下の一覧ページも、後でEditに飛ぶなら id を追加しておくと便利
    public function courses()
    {
        $items = Course::orderByDesc('id')
            ->get(['id','title','description','language','level']);
        return view('admin.courses', compact('items'));
    }

    public function students()
    {
        $items = User::where('role_id', 3)
            ->orderByDesc('id')->get(['id','name','email']);
        return view('admin.students', compact('items'));
    }

    public function teachers()
    {
        $items = User::where('role_id', 2)
            ->orderByDesc('id')->get(['id','name','email']);
        return view('admin.teachers', compact('items'));
    }
}
