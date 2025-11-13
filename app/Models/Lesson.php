<?php

namespace App\Models;

use Database\Seeders\TopicSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

class Lesson extends Model
{
    protected $fillable = [
        'topic_id',
        'title',
        'content',
        'video',
        'images',
        'thumbs',
        'pages',
        'video'
    ];

    protected $casts = [
        'images' => 'array',
        'thumbs' => 'array',
    ];

     public function course()
    {
        return $this->belongsTo(\App\Models\Course::class, 'course_id');
    }

    /* ---------- リレーション ---------- */

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'lesson_user')
            ->withPivot('is_completed', 'completed_at', 'study_time')
            ->withTimestamps();
    }

    public function completedByUsers()
    {
        return $this->belongsToMany(User::class, 'lesson_user')
            ->withPivot('completed_at')
            ->withTimestamps();
    }

    public function progress()
    {
        return $this->hasMany(Progress::class);
    }


    /**
     * 指定ユーザーがこのレッスンを完了しているか判定
     */
    public function isCompletedBy($user)
    {
        return $this->progress()
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->exists();
    }

    /**
     * ページ画像のサムネイルを返す
     * @param int $pageIndex 0スタート
     * @return string|null URL
     */
    public function getThumbnail($pageIndex)
    {
        if (!$this->images || !isset($this->images[$pageIndex])) {
            return null;
        }

        $original = public_path('images/lessons/' . $this->images[$pageIndex]);
        $thumbDir = public_path('images/lessons/thumbs');

        if (!File::exists($thumbDir)) {
            File::makeDirectory($thumbDir, 0755, true);
        }

        $thumbPath = $thumbDir . '/' . $this->id . '_' . $pageIndex . '_thumb.png';

        if (!File::exists($thumbPath)) {
            $img = Image::make($original)->fit(50, 35);
            $img->save($thumbPath);
        }

        return asset('images/lessons/thumbs/' . $this->id . '_' . $pageIndex . '_thumb.png');
    }
}
