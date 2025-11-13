<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Admin controllers
//use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\TeacherController as AdminTeacherController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\CourseTopicController;
//use App\Http\Controllers\Admin\ForumController as AdminForumController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\Admin\TeacherController;

// Front/controllers
use App\Http\Controllers\AuthController;
// use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ApiTestController;
use App\Http\Controllers\BookingController;
//use App\Http\Controllers\Admin\ForumController as AdminForumController;

// Front/controllers
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\SelfLearningController;
use App\Http\Controllers\Teacher\ReportController;
use App\Http\Controllers\Teacher\ProfileController;
use App\Http\Controllers\Student\MylearningController;

// Front/controllers
use App\Http\Controllers\Student\CourseInitApiController;
use App\Http\Controllers\Student\LessonhistoryController;
use App\Http\Controllers\Teacher\LessonhistoryController as TeacherLessonHistoryController;
use App\Http\Controllers\Student\IndexController as StudentIndexController;
use App\Http\Controllers\Teacher\IndexController as TeacherIndexController;
use App\Http\Controllers\Student\BookingController as StudentBookingController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Teacher\BookingController as TeacherBookingController;
use App\Http\Controllers\Teacher\ProfileController as TeacherProfileController;

Auth::routes();

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // all
    Route::get('/', function () {
        $role = Auth::user()->role_id;

        return match ($role) {
            1 => redirect()->route('admin.dashboard'),   // admin
            2 => redirect()->route('teachers.index'),      // teacher
            3 => redirect()->route('students.index'),      // student
            4 => redirect()->route('courses.index'),     // user
        };
    })->name('home');
});

/* ------------------- Admin ------------------- */
Route::prefix('admin')->middleware('can:admin')->name('admin.')->group(function () {
    // ダッシュボード
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');   // ← 1つ目
    Route::get('/dashboard', fn() => redirect()->route('admin.dashboard'))->name('index');

    // 一覧・CRUD
    Route::resource('students', AdminStudentController::class)->names('students');
    Route::resource('teachers', AdminTeacherController::class)->names('teachers');
    Route::resource('courses', AdminCourseController::class)->names('courses');

    // 追加のアクション
    Route::patch('teachers/{teacher}/toggle', [AdminTeacherController::class, 'toggle'])->name('teachers.toggle');
    Route::patch('courses/{course}/toggle', [AdminCourseController::class, 'toggle'])->name('courses.toggle');
    Route::patch('students/{student}/toggle', [AdminStudentController::class, 'toggle'])->name('students.toggle');

    //delete courses
    Route::delete('courses/{course}/topics/{topic}', [CourseTopicController::class, 'destroy'])->name('courses.topics.destroy');

    // Admin 内（prefix: admin, name: admin.）
    Route::patch('lessons/{lesson}/toggle', [LessonController::class, 'toggle'])
        ->name('lessons.toggle');   // フル名は admin.lessons.toggle

    Route::patch('topics/{topic}/toggle', [CourseTopicController::class, 'toggle'])
        ->name('topics.toggle'); // ← 追加
    Route::patch('courses/toggle-all', [AdminCourseController::class, 'toggleAll'])
        ->name('courses.toggleAll');
    //teacher 
    Route::patch('teachers/{teacher}/toggle', [AdminTeacherController::class, 'toggle'])
        ->name('teachers.toggle');

    Route::delete('courses/{course}/topics/{topic}', [CourseTopicController::class, 'destroy'])->name('courses.topics.destroy');

    Route::post('/teachers/{user}/courses', [AdminTeacherController::class, 'attach'])->name('courses.attach');
    Route::delete('/teachers/{user}/courses/{course}', [AdminTeacherController::class, 'detach'])->name('courses.detach');
    Route::patch('courses/{course}/toggle-all-topics', [CourseTopicController::class, 'toggleCourse'])
        ->name('courses.toggleAllTopics');
    Route::patch('topics/{topic}/toggle', [CourseTopicController::class, 'toggleTopic'])
        ->name('topics.toggle');
    Route::delete('courses/{course}/topics/{topic}', [CourseTopicController::class, 'destroy'])
        ->name('courses.topics.destroy');
    //teacher
    Route::patch('/admin/users/{user}/toggle', [AdminStudentController::class, 'toggle'])
        ->name('admin.users.toggle');
    Route::patch('/admin/users/{user}/toggle', [AdminStudentController::class, 'toggle'])
        ->name('admin.users.toggle');
    Route::get('/admin/teachers', [AdminTeacherController::class, 'index'])->name('admin.teachers.index');
    Route::patch('/admin/teachers/{teacher}/toggle', [AdminTeacherController::class, 'toggle'])
        ->name('admin.teachers.toggle');
    //teacher Edit
    Route::resource('teachers', TeacherController::class);

    // teacherに担当courseを割り当てる。
    // Route::post('/admin/teachers/{teacher}/attach', [AdminTeacherController::class, 'attach'])
    //     ->name('teachers.courses.attach');
    // Route::delete('/admin/teachers/{teacher}/detach', [AdminTeacherController::class, 'detach'])
    //     ->name('teachers.courses.dettach');

    Route::post('teachers/{user}/courses', [AdminTeacherController::class, 'attach'])
            ->name('teachers.courses.attach');

    // コース解除（status=1のみ）
    Route::delete('teachers/{user}/courses/{course}', [AdminTeacherController::class, 'detach'])
        ->name('teachers.courses.detach');

    //teacher(New)
    Route::resource('teachers', \App\Http\Controllers\Admin\TeacherController::class)
        ->except(['show']); // index/create/store/edit/update/destroy
    Route::post('teachers/{teacher}/toggle', [\App\Http\Controllers\Admin\TeacherController::class, 'toggle'])
        ->name('teachers.toggle');
    Route::post('teachers/{user}/attach', [\App\Http\Controllers\Admin\TeacherController::class, 'attach'])
        ->name('teachers.attach');
    Route::delete('teachers/{user}/detach/{course}', [\App\Http\Controllers\Admin\TeacherController::class, 'detach'])
        ->name('teachers.detach');

});

//student 
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/students', [AdminStudentController::class, 'index'])->name('admin.students.index');
    Route::get('/admin/students/{student}/edit', [AdminStudentController::class, 'edit'])->name('admin.students.edit');
    Route::put('/admin/students/{student}', [AdminStudentController::class, 'update'])->name('admin.students.update');
    Route::patch('/admin/students/{student}/toggle', [AdminStudentController::class, 'toggle'])->name('admin.students.toggle');
});

// Courses
Route::prefix('courses')->group(function () {
    Route::get('/', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/{course}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');
    Route::delete('/{course}/unenroll', [CourseController::class, 'unenroll'])->name('courses.unenroll');
    Route::post('/{course}/lessons/{lesson}/progress', [LessonController::class, 'updateProgress'])
        ->name('lessons.updateProgress');
    Route::post('/{course}/lessons/{lesson}/toggle', [LessonController::class, 'toggle'])
        ->name('lessons.toggle');
});

/* PayPal 支払い処理 */
Route::prefix('payment')->middleware('auth')->name('payment.')->group(function () {
    Route::get('/success', [CourseController::class, 'paymentSuccess'])->name('success');
    Route::get('/cancel', [CourseController::class, 'paymentCancel'])->name('cancel');
});

// Self-learning
Route::prefix('selflearning')->group(function () {
    Route::get('/', [SelfLearningController::class, 'index'])->name('selflearning.index');
    Route::get('/{courseId}/lesson/{lessonId}', [SelfLearningController::class, 'lessonVideo'])
        ->name('selflearning.lessonVideo');
    Route::get('/{courseId}/lesson/{lessonId}/text', [SelfLearningController::class, 'lessonText'])
        ->name('selflearning.lesson.text');
    Route::post('/{courseId}/lesson/{lessonId}/done', [SelfLearningController::class, 'lessonDone'])
        ->name('selflearning.lesson.done');
    Route::post('/{courseId}/lesson/{lessonId}/toggle', [SelfLearningController::class, 'toggleLesson'])
        ->name('selflearning.lesson.toggle');
    Route::post('/update-time', [SelfLearningController::class, 'updateStudyTime'])
        ->name('selflearning.updateTime');
    Route::get('/{id}', [SelfLearningController::class, 'show'])->name('selflearning.show');
});

/* ------------------- Student area ------------------- */
Route::prefix('students')->middleware('can:students')->name('students.')->group(function () {
    Route::get('/', [StudentIndexController::class, 'index'])->name('index');
    // Route::get('mylearning', [MylearningController::class, 'show'])->name('mylearning');
    // Route::get('lesson_history', [LessonhistoryController::class, 'show'])->name('lessonhistory');
    // Route::get('profile', [StudentProfileController::class, 'show'])->name('profile');

    // 予約フォーム表示 / 保存（既存）
    // Route::get('/bookings/create', [StudentBookingController::class, 'create'])
    //     ->name('bookings.create');
    Route::post('/bookings', [StudentBookingController::class, 'store'])
        ->name('bookings.store');

    // 追加のアクション
    Route::patch('teachers/{teacher}/toggle', [AdminTeacherController::class, 'toggle'])
        ->name('teachers.toggle');
    Route::patch('courses/{course}/toggle', [AdminCourseController::class, 'toggle'])
        ->name('courses.toggle');
});

/* ------------------- Student area ------------------- */
Route::prefix('students')->middleware('can:students')->name('students.')->group(function () {
    Route::get('/', [StudentIndexController::class, 'index'])->name('index');
    // Route::get('mylearning',      [MylearningController::class, 'show'])->name('mylearning');
    
    // Cancel (DELETE) an upcoming booking (student only)
    Route::delete('bookings/{booking}', [StudentBookingController::class, 'destroy'])
        ->name('bookings.cancel');

    // 予約フォーム表示 / 保存（既存）
    // Route::get('/bookings/create', [StudentBookingController::class, 'create'])
    //     ->name('bookings.create');
    Route::post('/bookings', [StudentBookingController::class, 'store'])
        ->name('bookings.store');

    // Ajax: 指定コースのトピック一覧 + 「次のTopic」候補（JSON）
    Route::get('/api/courses/{course}/init', [CourseInitApiController::class, 'show'])
        ->name('api.courses.init');

    // Route::get('/profile/{user}', [ProfileController::class, 'show'])
    // ->name('profile.show');

    // プロフィール更新（モーダルから）
        Route::put('/profile/{user}', [StudentProfileController::class, 'update'])
        ->name('profile.update');

    Route::put('/profile/{user}/photo', [StudentProfileController::class, 'updatePhoto'])
        ->name('profile.photo.update');
});

// Courses
Route::prefix('courses')->group(function () {
    Route::get('/', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/{course}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');
    Route::delete('/{course}/unenroll', [CourseController::class, 'unenroll'])->name('courses.unenroll');
    Route::post('/{course}/lessons/{lesson}/progress', [LessonController::class, 'updateProgress'])
        ->name('lessons.updateProgress');
    Route::post('/{course}/lessons/{lesson}/toggle', [LessonController::class, 'toggle'])
        ->name('lessons.toggle');
});

//  Self-learning
Route::prefix('selflearning')->group(function () {
    Route::get('/', [SelfLearningController::class, 'index'])->name('selflearning.index');
    Route::get('/{id}', [SelfLearningController::class, 'show'])->name('selflearning.show');
    Route::get('/{courseId}/lesson/{lessonId}', [SelfLearningController::class, 'lessonVideo'])
        ->name('selflearning.lessonVideo');
    Route::post('/{courseId}/lesson/{lessonId}/done', [SelfLearningController::class, 'lessonDone'])
        ->name('selflearning.lesson.done');
    Route::get('/{courseId}/lesson/{lessonId}/text', [SelfLearningController::class, 'lessonText'])
        ->name('selflearning.lesson.text');
    Route::post('/{courseId}/lesson/{lessonId}/toggle', [SelfLearningController::class, 'toggleLesson'])
        ->name('selflearning.lesson.toggle');
    Route::post('/update-time', [SelfLearningController::class, 'updateStudyTime'])
        ->name('selflearning.updateTime');
});

// Profile
Route::get('teachers/profile/{user_id}', [TeacherProfileController::class, 'show'])->name('teachers.profile');
// Route::get('students/profile/{user_id}', [StudentProfileController::class, 'show'])->name('students.profile');
Route::get('students/profile/{user}', [StudentProfileController::class, 'show'])->name('students.profile.show');

// Lesson history
Route::get('students/{student}/lesson_history', [LessonhistoryController::class, 'show'])
    ->name('students.lessonhistory');
Route::get('teachers/{teacher}/lesson_history', [TeacherLessonhistoryController::class, 'index'])->name('teachers.lessonhistory');

/* ------------------- Teacher area ------------------- */
Route::prefix('teachers')->middleware('can:teachers')->name('teachers.')->group(function () {
    Route::get('/', [TeacherIndexController::class, 'index'])->name('index');
    Route::post('/bookings/store', [TeacherBookingController::class, 'store'])->name('bookings.store');
    Route::get('/calendar/show', [TeacherBookingController::class, 'show'])->name('calendar.show');
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::post('/', [TeacherBookingController::class, 'store'])->name('store');
        Route::delete('/{id}', [TeacherBookingController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete', [TeacherBookingController::class, 'bulkDestroyOpen'])->name('bulkDelete');
        // Route::post('/{id}/cancel', [TeacherBookingController::class, 'cancelBooked'])->name('cancel');
        // ★ キャンセル（先生）：削除しない／report を更新＆通知
        Route::post('/{id}/cancel', [TeacherBookingController::class, 'cancelBooked'])->name('cancel');
    });

    // ★ Report 取得/更新（モーダル表示用）
    Route::get('reports/{booking}', [ReportController::class, 'show'])->name('reports.show');
    Route::patch('reports/{booking}', [ReportController::class, 'update'])->name('reports.update');
    // POST にしたい場合は ↓でもOK（JS 側の method を合わせる）
    // Route::post('reports/{booking}', [ReportController::class, 'update'])->name('reports.update');
    // topics 一覧（id 昇順）
    // Route::get('/teachers/next_topic', [ReportController::class, 'index'])->name('next_topic.index');
    Route::put('/{user}/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/{user}/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
     // ★ 追加: Open slot をドラッグで移動する用
        Route::patch('/{booking}/move', [TeacherBookingController::class, 'move'])
            ->name('bookings.move');
             // ★ 追加：選択した複数スロットを一括削除
        Route::post('/bulk-delete-selected', [TeacherBookingController::class, 'bulkDestroySelected'])
            ->name('bookings.bulkDeleteSelected');
    Route::post('bookings/purge-future-open',
    [\App\Http\Controllers\Teacher\BookingController::class, 'purgeFutureOpen']
)->name('bookings.purgeFutureOpen');
});
