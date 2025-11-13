@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom.css') }}">
@endpush

@section('content')
<div class="dashboard container-fluid p-4">

    {{-- 検索フォーム --}}
    <form id="search-form" method="GET" action="{{ route('selflearning.index') }}" 
          class="mb-3 d-flex align-items-center video-search-bar" style="max-width: 250px;">
        <div class="input-group dashboard-search">
            <input type="text" 
                   name="search" 
                   id="search-input"
                   class="form-control" 
                   placeholder="Search"
                   value="{{ request('search') }}">
            <button type="submit" class="input-group-text bg-white" style="cursor:pointer;">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </div>
    </form>

    <div class="row">
        {{-- 左カラム --}}
        <div class="col-lg-9 col-12 mb-4 mb-lg-0">
            {{-- Status --}}
            <div class="mb-4">
                <h5 class="fw-bold">Status</h5>
                <div class="row g-3">
                    <div class="col-md-4 col-12">
                        <div class="dashboard-status-card dashboard-status-enrolled">
                            <p class="mb-1">Courses enrolled</p>
                            <h2>{{ $myCourses->count() }}</h2>
                        </div>
                    </div>
                    <div class="col-md-4 col-12">
                        <div class="dashboard-status-card dashboard-status-completed">
                            <p class="mb-1">Courses completed</p>
                            <h2>{{ $completedCourses }}</h2>
                        </div>
                    </div>

                    {{-- ✅ Study Time --}}
                    @php
                        $hours = floor($hoursLearned / 3600);
                        $minutes = floor(($hoursLearned % 3600) / 60);
                        $seconds = $hoursLearned % 60;

                        $formattedTime = '';
                        if ($hours > 0) $formattedTime .= $hours . 'h ';
                        if ($minutes > 0 || $hours > 0) $formattedTime .= $minutes . 'm ';
                        $formattedTime .= $seconds . 's';
                    @endphp
                    <div class="col-md-4 col-12">
                        <div class="dashboard-status-card dashboard-status-hours">
                            <p class="mb-1">Study time</p>
                            <h2>{{ $formattedTime }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            {{-- My Courses --}}
            <div class="mb-4 dashboard-courses">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">
                        <a href="{{ route('selflearning.index') }}" class="text-decoration-none text-dark">
                            My courses
                        </a>
                    </h5>

                    {{-- Active / Completed / All タブ --}}
                    <div class="video-status-tabs mt-2 mt-lg-0">
                        <button class="tab-btn active" data-target="all">All</button>
                        <button class="tab-btn" data-target="active">Active</button>
                        <button class="tab-btn" data-target="completed">Completed</button>
                    </div>
                </div> 

                {{-- Courses Lists --}}
                @foreach(['active', 'completed', 'all'] as $status)
                <div id="{{ $status }}-courses" class="course-list {{ $status !== 'all' ? 'd-none' : '' }}">
                    @php
                        $courses = match($status) {
                            'active' => $myCourses->where('status', 'active'),
                            'completed' => $myCourses->where('status', 'completed'),
                            'all' => $myCourses
                        };
                    @endphp

                    @foreach($courses as $course)
                    <a href="{{ route('selflearning.show', $course->id) }}" class="text-decoration-none text-dark">
                        <div class="dashboard-course-card d-flex flex-row flex-wrap align-items-center mb-3">
                            <img src="{{ $course->image_path }}" 
                                 alt="course" class="me-3 rounded dashboard-course-img mb-2 mb-md-0">

                            <div class="flex-grow-1 w-100 w-md-auto">
                                <h6 class="mb-1 fw-bold">{{ $course->title }}</h6>
                                <div class="d-flex align-items-center flex-wrap">
                                    @php $rate = $course->completionRate(Auth::id()); @endphp
                                    <small class="me-2">{{ $rate }}% Finish</small>
                                    <div class="progress flex-grow-1" style="height:6px; min-width:120px;">
                                        <div class="progress-bar {{ $rate > 0 ? 'bg-info' : 'bg-secondary' }}" 
                                             style="width: {{ $rate }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @endforeach
            </div>
        </div>

        {{-- 右カラム --}}
        <div class="col-lg-3 col-12">
            {{-- Schedule --}}
            {{-- <div class="dashboard-side-card mb-4">
                <h6 class="fw-bold">Schedule</h6>
                <img src="{{ asset('images/calendar.jpg') }}" class="img-fluid rounded" alt="calendar">
            </div> --}}

          {{-- Recommended --}}
                <div class="dashboard-side-card">
                    <h6 class="fw-bold">Recommended courses</h6>
                    @foreach($recommendedCourses as $rec)
                    <a href="{{ route('courses.show', $rec->id) }}" class="text-decoration-none text-dark">
                        <div class="dashboard-recommend-item d-flex flex-row align-items-center mb-2">
                            <img src="{{ $rec->image_path }}" 
                                alt="course" class="me-2 rounded dashboard-recommend-img">
                            <div>
                                <h6 class="mb-0 small fw-bold">{{ $rec->title }}</h6>
                                <small class="text-muted">{{ Str::limit($rec->description, 30) }}</small>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.video-status-tabs .tab-btn');
    const lists = {
        active: document.getElementById('active-courses'),
        completed: document.getElementById('completed-courses'),
        all: document.getElementById('all-courses'),
    };

    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            buttons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            Object.values(lists).forEach(list => list.classList.add('d-none'));
            lists[this.dataset.target].classList.remove('d-none');
        });
    });
});
</script>
@endpush
