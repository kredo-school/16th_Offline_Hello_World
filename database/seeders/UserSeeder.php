<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\TeacherCourse;
use App\Models\Enrollment;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('en_US');

        # Admin
        DB::table('users')->insert([
            'name'              => 'Admin',
            'email'             => 'admin@helloworld.com',
            'password'          => Hash::make('helloworld@admin'),
            'role_id'           => 1,
            'about'             => 'System administrator account for testing.',
            'created_at'        => NOW(),
            'updated_at'        => NOW(),
        ]);
        
        # Teacher Faker
        $courses = [1,2,3,4];
        foreach(range(1,10) as $index){
            $teacher = User::create([
                'name'          => $faker->name(),
                'email'         => 'teacher' . $index . '@gmail.com',
                'password'      => Hash::make('helloworld@teacher'),
                'role_id'       => 2,
                'about'         => $faker->paragraph(3, true),
                'created_at'    => NOW(),
                'updated_at'    => NOW(),
            ]);

            TeacherCourse::create([
                'teacher_id' => $teacher->id,
                'course_id'  => $courses[array_rand($courses)],
            ]);
        }

        # Student Faker
        foreach(range(1,5) as $index){
            $student = User::create([
                'name'          => $faker->name(),
                'email'         => 'student' . $index . '@gmail.com',
                'password'      => Hash::make('helloworld@student'),
                'role_id'       => 3,
                'about'         => $faker->paragraph(3, true),
                'created_at'    => NOW(),
                'updated_at'    => NOW(),
            ]);

            Enrollment::create([
                'user_id'           => $student->id,
                'course_id'         => $courses[array_rand($courses)],
                'enrollment_date'   => NOW(),
            ]);
        }
    }
}