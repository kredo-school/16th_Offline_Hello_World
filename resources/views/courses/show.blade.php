@extends('layouts.app')

@section('content')
<div class="container-fluid p-3">
    <div class="row">

      {{-- 右上ログアウト --}}
<div class="d-flex justify-content-end mb-3">
    @auth
        @if(Auth::user()->role_id == 4)
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    Logout
                </button>
            </form>
        @endif
    @endauth
</div>


    
        {{-- 左サイド --}}
        <div class="courses-col-md-3 col-md-3 col-12 border-end bg-white mb-4 mb-md-0" style="min-height:100vh;">
            <h3 class="fw-bold mb-3">
                <a href="{{ route('courses.index') }}" class="text-decoration-none text-dark">Courses</a>
            </h3>

            @include('courses.partials.left-sidebar', [
                'courses' => $courses, 
                'selectedCourse' => $course
            ])
        </div>

        {{-- 右サイド --}}
        <div class="col-md-9 col-12 ps-md-4">
            @if(in_array($course->id, $enrolledCourseIds ?? []))
                @include('courses.partials.enrolled', [
                    'course' => $course,
                    'sectionProgress' => $sectionProgress,
                    'coursePercent' => $coursePercent,
                    'completedLessonIds' => $completedLessonIds,
                ])
            @else
                @include('courses.partials.preview', ['course' => $course])
            @endif
        </div>

    </div>
</div>
@endsection
