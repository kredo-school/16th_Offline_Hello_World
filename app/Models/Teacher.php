<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
    ];

    // public function courses()
    // {
    //     // ← テーブル名を teacher_course に、キーはデフォルト(teacher_id, course_id)なので省略OK
    //     return $this->belongsToMany(\App\Models\Course::class, 'teacher_course')->withTimestamps();
    // }
    public function courses()
{
    return $this->belongsToMany(Course::class, 'teacher_course');
}

}
