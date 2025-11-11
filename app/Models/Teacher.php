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

    // 🔥 Courses との多対多リレーション
    public function courses()
    {
        return $this->belongsToMany(Course::class);
    }
}
