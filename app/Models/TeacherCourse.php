<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherCourse extends Model
{
    protected $table = 'teacher_course';
    protected $fillable = ['teacher_id', 'course_id'];
    public $timestamps = false;
}
