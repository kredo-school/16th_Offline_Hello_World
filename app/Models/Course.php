<?php

namespace App\Models;

use App\Models\Topic;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    //変更
    protected $table = 'courses';
    protected $fillable = ['title', 'description', 'image_url', 'language', 'level', 'image', 'category'];

    // コースに紐づくレッスン
    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }


    // App\Models\Course.php

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function enrolledUsers()
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot('status', 'progress')
            ->withTimestamps();
    }



    public function users()
    {
        return $this->belongsToMany(User::class, 'enrollments', 'course_id', 'user_id')
            ->withPivot(['status', 'enrollment_date'])
            ->withTimestamps();
    }


    public function completionRate($userId)
    {
        $totalLessons = $this->topics->flatMap->lessons->count();

        $completedLessons = User::find($userId)
            ->completedLessons()
            ->whereIn('lesson_id', $this->topics->flatMap->lessons->pluck('id'))
            ->count();

        return $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
    }

    public function teachers()
    {
        // 第2引数: テーブル名, 第3引数: 現在モデル側FK（course_id）, 第4引数: 相手側FK（teacher_id）
        return $this->belongsToMany(User::class, 'teacher_course', 'course_id', 'teacher_id');
    }

    public function getDisplayImageAttribute()
    {
        // Base64ならそのまま返す
        if ($this->image && str_starts_with($this->image, 'data:image')) {
            return $this->image;
        }

        // 通常のファイルパスならasset()で返す
        if ($this->image && file_exists(public_path('images/courses/' . $this->image))) {
            return asset('images/courses/' . $this->image);
        }

        // どちらもない場合はデフォルト画像
        return asset('images/default-course.jpg');
    }

    public function getImagePathAttribute()
    {
        if ($this->image && str_starts_with($this->image, 'data:image')) {
            // base64文字列そのまま返す
            return $this->image;
        }

        if ($this->image) {
            // 通常ファイルパス
            return asset('images/courses/' . $this->image);
        }

        // デフォルト画像
        return asset('images/courses/sample.jpg');
    }

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function bookings()
    {
        // bookings テーブルに course_id がある前提
        return $this->hasMany(Booking::class);
    }

}
