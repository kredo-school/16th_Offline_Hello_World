<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\User; 
use Illuminate\Support\Facades\Auth; 
use Srmklive\PayPal\Services\PayPal as PayPalClient; 
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
   public function index(Request $request)
{
    $user = Auth::user();
    $query = Course::where('status', 1)->with('topics.lessons');

    if ($request->filled('search')) {
        $query->where('title', 'like', '%' . $request->search . '%');
    }

    if ($request->filled('lang')) {
        $query->where('language', $request->lang);
    }

    if ($user && $user->role_id == 2) {
        $query->whereHas('teachers', function ($q) use ($user) {
            $q->where('teacher_id', $user->id);
        });
    }

    $courses = $query->get();

    // 以下はステータスフィルタなど既存のまま
    if ($request->status && $user) {
        $completedLessonIds = $user->lessons()->wherePivot('is_completed', true)->pluck('lessons.id')->toArray();

        if ($request->status === 'active') {
            $courses = $courses->filter(function ($c) use ($completedLessonIds) {
                $lessonIds = $c->topics->flatMap(fn($s) => $s->lessons)->pluck('id')->toArray();
                $total = count($lessonIds);
                $completed = $total ? count(array_intersect($lessonIds, $completedLessonIds)) : 0;
                return $total > 0 && $completed < $total;
            });
        } elseif ($request->status === 'completed') {
            $courses = $courses->filter(function ($c) use ($completedLessonIds) {
                $lessonIds = $c->topics->flatMap(fn($s) => $s->lessons)->pluck('id')->toArray();
                $total = count($lessonIds);
                $completed = $total ? count(array_intersect($lessonIds, $completedLessonIds)) : 0;
                return $total > 0 && $completed === $total;
            });
        }
    }

    $enrolledCourseIds = $user ? $user->courses()->pluck('courses.id')->toArray() : [];

    return view('courses.index', compact('courses', 'enrolledCourseIds'));
}


   
   public function show(Request $request, $id)
{
    $user = Auth::user();
    $course = Course::with('topics.lessons')->findOrFail($id);

    if ($user && $user->role_id == 2) {
        $isOwnCourse = $user->teachingCourses()->where('course_id', $course->id)->exists();
        if (!$isOwnCourse) {
            return redirect()->route('courses.index')
                ->with('error', 'You are not authorized to view this course.');
        }
    }

    $completedLessonIds = $user
        ? $user->lessons()->wherePivot('is_completed', true)->pluck('lessons.id')->toArray()
        : [];

    $enrolledCourseIds = $user
        ? $user->courses()->pluck('courses.id')->toArray()
        : [];

    if ($user && $user->role_id == 2) {
        $courses = $user->teachingCourses()->with('topics.lessons')->get();
    } else {
        $query = Course::with('topics.lessons');
        if ($request->filled('lang')) {
            $query->where('language', $request->lang);
        }
        $courses = $query->get();
    }

    $sectionProgress = [];
    $totalCourseLessons = 0;
    $completedCourseLessons = 0;

    foreach ($course->topics as $topic) {
        $lessonIds = $topic->lessons->pluck('id')->toArray();
        $total = count($lessonIds);
        $completed = $total ? count(array_intersect($lessonIds, $completedLessonIds)) : 0;
        $sectionProgress[$topic->id] = [
            'percent' => $total ? round($completed / $total * 100) : 0,
            'total' => $total,
            'completed' => $completed,
        ];
        $totalCourseLessons += $total;
        $completedCourseLessons += $completed;
    }

    $coursePercent = $totalCourseLessons ? round($completedCourseLessons / $totalCourseLessons * 100) : 0;

    return view('courses.show', compact(
        'course',
        'sectionProgress',
        'coursePercent',
        'completedLessonIds',
        'enrolledCourseIds',
        'courses'
    ));
}


    
    /**
     * コースの支払いを開始し、PayPalへリダイレクトします。
     */
    public function enroll(Course $course)
{
    $user = Auth::user();

   // すでに受講中ならリダイレクト
    if ($user->courses->contains($course->id)) {
        return redirect()->route('courses.show', $course->id)
                         ->with('info', 'このコースはすでに購入済みです。');
    }

    try {
        //  PayPalClient のインスタンスを生成
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal')); // ← setEnvironmentではなくこれ！

        //  アクセストークンを取得
        $paypalToken = $provider->getAccessToken();

        // 注文作成
        $data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => 'COURSE_' . $course->id . '_USER_' . $user->id,
                    'amount' => [
                        'currency_code' => config('paypal.currency', 'USD'),
                        'value' => number_format($course->price, 2, '.', ''),

                    ],
                    'description' => $course->title . ' の購入',
                ]
            ],
            'application_context' => [
                'return_url' => route('payment.success', ['course_id' => $course->id]),
                'cancel_url' => route('payment.cancel', ['course_id' => $course->id]),
                'shipping_preference' => 'NO_SHIPPING',
            ]
        ];

        $order = $provider->createOrder($data);

        if (isset($order['links'])) {
            foreach ($order['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return redirect($link['href']);
                }
            }
        }

        return redirect()->route('courses.show', $course->id)
                         ->with('error', 'PayPalへのリダイレクトリンクが見つかりませんでした。');

    } catch (\Exception $e) {
        Log::error('PayPal Enrollment Error: ' . $e->getMessage());
        return redirect()->route('courses.show', $course->id)
                         ->with('error', '決済システムの接続に失敗しました。時間をおいてお試しください。');
    }
}

    /**
     * PayPal決済成功後のコールバック処理
     * ユーザーロールを Basic (4) から Student (3) へ更新し、コースに登録します。
     */
    public function paymentSuccess(Request $request)
{
    $user = Auth::user();
    $courseId = $request->query('course_id');
    $token = $request->query('token');

    if (!$token || !$courseId) {
        return redirect()->route('courses.index')->with('error', '支払いのトークンが見つかりませんでした。');
    }

    try {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();

        // 注文詳細の取得
        $order = $provider->showOrderDetails($token);
        $orderId = $order['id'] ?? null;

        // 支払いキャプチャ
        $response = $provider->capturePaymentOrder($orderId);

        if (isset($response['status']) && $response['status'] === 'COMPLETED') {
            if ($user->role_id === 4) {
                $user->role_id = 3;
                $user->save();
            }

            if (!$user->courses->contains($courseId)) {
                $user->courses()->attach($courseId);
            }

            return redirect()->route('students.index')
                             ->with('success', 'コースの支払いが完了し、Studentアカウントにアップグレードされました！');
        }

        return redirect()->route('courses.show', $courseId)
                         ->with('error', '支払いは完了しましたが、確定処理中にエラーが発生しました。');
    } catch (\Exception $e) {
        Log::error('PayPal Success Callback Error: ' . $e->getMessage());
        return redirect()->route('courses.show', $courseId)
                         ->with('error', '支払いの検証中にエラーが発生しました。');
    }
}

    
    /**
     * PayPal決済キャンセル後のコールバック処理
     */
    public function paymentCancel(Request $request)
    {
        $courseId = $request->query('course_id');
        
        return redirect()->route('courses.show', $courseId ?? 1)
                         ->with('info', 'PayPalでの支払いがキャンセルされました。再度お試しください。');
    }

  public function unenroll($id)
{
    $user = Auth::user();

    // 管理者以外は禁止
    if ($user->role_id !== 1) {
        return redirect()->route('courses.index')
            ->with('error', 'You cannot unenroll from a purchased course.');
    }

    $course = Course::findOrFail($id);
    $user->courses()->detach($course->id);

    return redirect()->route('courses.index')
        ->with('success', 'The user has been unenrolled by admin.');
}


}
