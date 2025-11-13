@extends('layouts.app')

@push('styles')
<style>
.course-header-image {
    width: 100%;
    max-height: 300px;
    object-fit: cover;
}

.course-meta {
    color: #6c757d;
}

.lesson-item {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
}

.lesson-title {
    display: flex;
    align-items: center;
    flex: 1 1 auto;
    min-width: 200px;
    margin-bottom: 0.5rem;
}

.lesson-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.lesson-duration {
    font-size: 0.875rem;
    color: #6c757d;
}

@media (min-width: 768px) {
    .lesson-title {
        margin-bottom: 0;
    }
}
</style>
@endpush

@section('content')
<div class="container">

    {{-- 戻るボタン --}}
    <div class="mb-3">
        <a href="{{ route('selflearning.index') }}" class="btn btn-outline-secondary btn-sm selflearning-back-btn">
            <i class="fa-solid fa-arrow-left me-1"></i> Back
        </a>
    </div>

    {{-- コースヘッダー --}}
    <div class="mb-4">
        <img src="{{ $course->image_path }}" 
             class="course-header-image rounded mb-3">
        <h2 class="fw-bold">{{ $course->title }}</h2>
        <p class="course-meta">
            {{ $course->topics->count() }} topics ・
            {{ $course->topics->sum(fn($s) => $s->lessons->count()) }} lectures ・
            {{ $course->topics->sum(fn($s) => $s->lessons->sum('pages')) }} pages ・
            {{ gmdate('H', $course->topics->sum(fn($s) => $s->lessons->sum('duration')) * 60) }} minutes
        </p>
    </div>

    {{-- セクション一覧 --}}
    <div class="accordion course-accordion" id="courseAccordion">
        @foreach($course->topics as $index => $topic)
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading{{ $index }}">
                    <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}">
                        <span class="fw-bold">{{ $topic->title }}</span>
                      @php
                        // 各topicの全lessonのduration合計（秒）
                        $totalSeconds = $topic->lessons->sum('duration');
                        $minutes = floor($totalSeconds / 60);
                        $seconds = $totalSeconds % 60;
                        $formattedDuration = sprintf('%02d:%02d', $minutes, $seconds);
                    @endphp

                    <span class="ms-auto section-meta text-muted small">
                        {{ $topic->lessons->count() }} lectures ・
                        {{ $topic->lessons->sum('pages') }} pages ・
                        {{ $formattedDuration }}
                    </span>



                    </button>
                </h2>
                <div id="collapse{{ $index }}"
                     class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}"
                     data-bs-parent="#courseAccordion">
                    <div class="accordion-body">
                        @foreach($topic->lessons as $lesson)
                            <div class="lesson-item">
                                <span class="lesson-title">
                                    <i class="fa-solid fa-video me-2 text-dark"></i>
                                    <i class="fa-solid fa-book me-2 text-dark"></i>
                                    {{ $lesson->title }}
                                </span>
                                <div class="lesson-actions align-items-center">
                                    <a href="{{ route('selflearning.lessonVideo', ['courseId' => $course->id, 'lessonId' => $lesson->id]) }}" 
                                       class="btn btn-outline-primary shadow-sm ms-2">
                                        <i class="fa-solid fa-play"></i>
                                    </a>
                                    <a href="{{ route('selflearning.lesson.text', ['courseId' => $course->id, 'lessonId' => $lesson->id]) }}" 
                                       class="btn btn-outline-info shadow-sm ms-2">
                                        Text
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
