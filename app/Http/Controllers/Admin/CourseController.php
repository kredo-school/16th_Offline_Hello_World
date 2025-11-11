<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
// use App\Models\Course;
use App\Models\Topic;

class CourseController extends Controller
{
    /**
     * 一覧表示
     */
    public function index()
    {
        $courses = Course::with(['topics'])->get();
        return view('admin.courses.index', compact('courses'));
    }

    /**
     * 新規作成フォーム
     */
    public function create()
    {
        return view('admin.courses.create'); // Bladeの場所に合わせて
    }

    /**
     * 保存処理
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'nullable|numeric',
            'description' => 'nullable|string',
            'category' => 'required|string|in:IT,English,Japanese',
            'level' => 'required|string',
            'image' => 'nullable|string', // Base64
        ]);

        $course = new Course();
        $course->title = $request->title;
        $course->price = $request->price ?? 0;
        $course->description = $request->description;
        $course->category = $request->category;
        $course->level = $request->level;

        // Base64画像をStorageに保存
        if ($request->image) {
            $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $request->image);
            $imageData = str_replace(' ', '+', $imageData);
            $imageName = time() . '.png';
            Storage::disk('public')->put('courses/' . $imageName, base64_decode($imageData));
            $course->image = 'courses/' . $imageName;
        }

        $course->save();

        return redirect()->route('admin.courses.index')->with('success', 'Course created successfully!');
    }

    /**
     * 編集フォーム
     */
    public function edit(Course $course)
    {
        return view('admin.courses.edit', compact('course'));
    }

    /**
     * 更新処理
     */
    public function update(Request $request, Course $course)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'nullable|numeric',
            'description' => 'nullable|string',
            'category' => 'required|string|in:IT,English,Japanese',
            'level' => 'required|string',
            'image' => 'nullable|string', // Base64
        ]);

        $course->title = $request->title;
        $course->price = $request->price ?? 0;
        $course->description = $request->description;
        $course->category = $request->category;
        $course->level = $request->level;

        if ($request->image) {
            // 古い画像を削除
            if ($course->image && Storage::disk('public')->exists($course->image)) {
                Storage::disk('public')->delete($course->image);
            }
            $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $request->image);
            $imageData = str_replace(' ', '+', $imageData);
            $imageName = time() . '.png';
            Storage::disk('public')->put('courses/' . $imageName, base64_decode($imageData));
            $course->image = 'courses/' . $imageName;
        }

        $course->save();

        return redirect()->route('admin.courses')->with('success', 'Course updated successfully!');
    }

    /**
     * 削除処理
     */
    public function destroy(Course $course)
    {
        // 画像も削除
        if ($course->image && Storage::disk('public')->exists($course->image)) {
            Storage::disk('public')->delete($course->image);
        }

        $course->delete();

        return redirect()->route('admin.courses')->with('success', 'Course deleted successfully!');
    }
    // ★ 追加（ここが重要！）★
    public function show($id)
    {
        // topics を一緒に取る
        $course = Course::with('topics')->findOrFail($id);

        return view('admin.courses.show', compact('course'));
    }
    // cousrses Activate

    // コース個別
    public function toggle(Request $request, Course $course)
    {
        DB::transaction(function () use ($course) {
            $course->status = (bool) $course->status ? 0 : 1;
            $course->save();
            $course->topics()->update(['status' => $course->status]);
        });

        // ← ここを変更：開いておくIDをクエリと # に付けて返す
        return redirect()->to(
            route('admin.courses.index', ['open' => $course->id]) . '#heading-' . $course->id
        )->with(
                'success',
                $course->status
                ? 'Course & all topics activated.'
                : 'Course & all topics deactivated.'
            );
    }

    // 全体一括（押した行を開いたまま戻る）
// ※ 呼び出し元から open を送っていないなら「最初のコースID」を開くなどでもOK
    public function toggleAll(Request $request)
    {
        $to = strtolower($request->input('to', 'active'));
        $new = $to === 'active' ? 1 : 0;

        DB::transaction(function () use ($new) {
            Course::query()->update(['status' => $new]);
            Topic::query()->update(['status' => $new]);
        });

        // 直前のコースIDがあれば使う（hiddenで送る想定）。無ければそのまま。
        $openId = $request->input('open');

        return redirect()->to(
            $openId
            ? route('admin.courses.index', ['open' => $openId]) . '#heading-' . $openId
            : route('admin.courses.index')
        )->with('success', $new ? 'All courses & topics activated.' : 'All courses & topics deactivated.');
    }
}
