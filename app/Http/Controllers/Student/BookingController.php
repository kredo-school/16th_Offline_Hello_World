<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Booking;
use App\Models\Course;
use App\Models\Topic;

class BookingController extends Controller
{
    private function currentStudentOrAbort()
{
    $user = Auth::user();

    if (!$user) {
        abort(403, 'Login required.');
    }

    $roleId   = (int) ($user->role_id ?? 0);
    $roleName = (string) ($user->role ?? '');

    // ロール名・role_id の定義に合わせて調整
    $isStudent = ($roleId === 3 || $roleName === 'student');

    if (!$isStudent) {
        abort(403, 'Only students can perform this action.');
    }

    return $user;
}

    public function store(Request $request)
    {
         // ★ 本人 student 以外は 403
    $student = $this->currentStudentOrAbort();
    $studentId = $student->id;

        $validated = $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'course_id'  => ['required', 'integer', 'exists:courses,id'],
            'topic_id'   => ['required', 'integer', 'exists:topics,id'],
        ]);

        // $studentId = Auth::id();

        // 対象のbookingを取得（teacherがopenしている枠）
        $booking = Booking::where('id', $validated['booking_id'])->first();

        if (!$booking) {
            return back()->withErrors(['booking_id' => 'Invalid booking ID.'])->withInput();
        }

        // すでに他の生徒が予約していないかチェック
        if ($booking->student_id !== null) {
            return back()->withErrors(['booking_id' => 'This slot has already been booked.'])->withInput();
        }

        // 更新処理（既存レコードを埋める）
        $booking->update([
            'student_id' => $studentId,
            'course_id'  => $validated['course_id'],
            'topic_id'   => $validated['topic_id'],
        ]);

        return redirect()->back()->with('success', 'Booking completed successfully!');
    }

    /**
     * DELETE /students/bookings/{booking}
     * Cancels (deletes) an upcoming booking for the logged-in student.
     */
    public function destroy(Booking $booking, Request $request)
{
    // ★ 本人 student チェック
    $student = $this->currentStudentOrAbort();

    // 1) 自分の予約だけキャンセル可能
    if ((int) $booking->student_id !== (int) $student->id) {
        abort(403, 'You are not allowed to cancel this booking.');
    }

    // 2) 未来（または未開始の当日）だけキャンセル可能
    $now     = Carbon::now();                // app.timezone
    $today   = $now->toDateString();         // 'YYYY-MM-DD'
    $nowTime = $now->format('H:i:s');        // 'HH:MM:SS'
    $isFuture = ($booking->date > $today)
             || ($booking->date === $today && $booking->time >= $nowTime);

    if (!$isFuture) {
        return back()->with('error', 'Past lessons cannot be canceled.');
    }

    // 3) 予約を「空き枠」に戻す（student_id, course_id, topic_id, updated_at を NULL）
    DB::transaction(function () use ($booking) {
        // Eloquent の updated_at 自動更新を回避
        $booking->timestamps = false;

        $booking->student_id = null;
        $booking->course_id  = null;
        $booking->topic_id   = null;
        $booking->updated_at = null; // ← 明示的に NULL

        $booking->save();

        // 必要ならここで関連データも整理：
        // 例）レポートを未確定に戻す等
        // optional($booking->report)->update([...]);
    });

    return back()->with('success', 'The booking was canceled and the slot is now open.');
}
}
