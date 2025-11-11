<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'about',
        'password',
        'role_id',
        'meeting_url',
        'avatar_path',
    ];

    //   public function getAvatarUrlAttribute(): string
    // {
    //     if (!empty($this->avatar_path)) {
    //         // avatar_path: "avatars/xxx.png" を想定
    //         // → /storage/avatars/xxx.png に変換
    //         return asset('storage/' . ltrim($this->avatar_path, '/'));
    //     }

    //     // 未設定時のデフォルト画像
    //     return asset('images/default-avatar.png');
    // }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role_id' => 'integer',
        ];
    }

    public function enrollments()
    {
        return $this->hasMany(\App\Models\Enrollment::class, 'user_id');
    }

    public function courses()
    {
        return $this->belongsToMany(\App\Models\Course::class, 'enrollments', 'user_id', 'course_id')
            ->withPivot(['status'])
            ->withTimestamps();
    }


    public function lessons()
    {
        return $this->belongsToMany(Lesson::class)
            ->withPivot('is_completed', 'completed_at')
            ->withTimestamps();
    }


    public function completedLessons()
    {
        return $this->belongsToMany(Lesson::class, 'lesson_user')
            ->withPivot('is_completed', 'completed_at', 'study_time')
            ->withTimestamps();
    }

    public function progress()
    {
        return $this->hasMany(Progress::class);
    }

    public function enrolledCourses()
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot('status', 'progress')
            ->withTimestamps();
    }

    public function coursesTaught()
    {
        // pivot名が違う場合は 'course_user' を合わせてください
        return $this->belongsToMany(\App\Models\Course::class, 'course_user', 'user_id', 'course_id');
    }

    public function skills()
{
    return $this->belongsToMany(
        \App\Models\Course::class,
        'teacher_course',   // pivot table 名
        'teacher_id',       // pivot 側の teacher 外部キー
        'course_id'         // pivot 側の course 外部キー
    );
}


// public function coursesTaught()
// {
//     // 第3引数: 現在モデル側FK（teacher_id）, 第4引数: 相手側FK（course_id）
//     return $this->belongsToMany(Course::class, 'teacher_course', 'teacher_id', 'course_id');
// }

}
