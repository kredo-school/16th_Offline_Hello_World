@extends('layouts.app')

@section('title', 'Lesson history')

@section('content')
    <section class="container py-4">

        {{-- タイトル＋誰の履歴か --}}
        <div class="d-flex align-items-baseline gap-2 mb-3">
            @if ($student->id === Auth::id())
                <h1 class="h4 mb-0">Your lesson history</h1>
            @else
                <h1 class="h4 mb-0">{{ $student->name }}'s lesson history</h1>
            @endif
        </div>

        {{-- フィルタフォーム --}}
        <form id="student-lesson-filter-form" method="GET" class="row g-2 mb-3 align-items-end" style="max-width: 720px;">

            {{-- Course filter --}}
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted">Course</label>
                <select name="course_id" class="form-select js-auto-filter">
                    <option value="">All courses</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}" @selected(($courseId ?? null) == $c->id)>
                            {{ $c->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Teacher filter --}}
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted">Teacher</label>
                <select name="teacher_id" class="form-select js-auto-filter">
                    <option value="">All teachers</option>
                    @foreach ($teachers as $t)
                        <option value="{{ $t->id }}" @selected(($teacherId ?? null) == $t->id)>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Reset --}}
            <div class="col-12 col-md-4 d-flex">
                <a href="{{ route('students.lessonhistory', ['student' => $student->id]) }}"
                    class="btn btn-outline-secondary">
                    Reset
                </a>
            </div>
        </form>

        {{-- レッスン履歴一覧 --}}
        <div class="vstack gap-3">
            @forelse ($bookings as $b)
                @php
                    $srcTz = config('app.timezone', 'Asia/Manila');
                    $viewTz = 'Asia/Manila';

                    // date 正規化
                    $rawDate = $b->getAttribute('date');
                    if ($rawDate instanceof \Carbon\Carbon) {
                        $dateStr = $rawDate->format('Y-m-d');
                    } else {
                        $dateStr = (string) $rawDate;
                        // 万が一 "Y-m-d H:i:s" で入ってたら先頭10桁だけ使う
                        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $dateStr)) {
                            $dateStr = substr($dateStr, 0, 10);
                        }
                    }

                    // time 正規化
                    $rawTime = $b->getAttribute('time');
                    if ($rawTime instanceof \Carbon\Carbon) {
                        $timeStr = $rawTime->format('H:i:s');
                    } else {
                        $timeStr = (string) $rawTime;
                        if (preg_match('/^\d{2}:\d{2}$/', $timeStr)) {
                            $timeStr .= ':00';
                        }
                    }

                    // 結合してパース
                    $dt = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', "$dateStr $timeStr", $srcTz)->setTimezone(
                        $viewTz,
                    );

                    $duration = $b->duration_minutes ?? 50;
                    $end = (clone $dt)->addMinutes($duration);

                    $course = $b->course->title ?? 'Course';
                    $topic = $b->topic->name ?? 'Topic';
                    $teacherN = $b->teacher->name ?? 'Teacher';

                    // コース画像：storage/public 配下 (例: avatars/xxx.png)
                             $img = $b->course->image ?? null; // DBに保存している値（パス or データURL想定）

    // 1) data: で始まる → そのまま使う
    if (is_string($img) && Str::startsWith($img, 'data:image/')) {
        $iconUrl = $img;

    // 2) ストレージへの相対パス（例: 'courses/xxx.png'）→ asset('storage/...')に変換
    } elseif (is_string($img) && !empty($img)) {
        $iconUrl = asset('storage/' . ltrim($img, '/'));

    // 3) 何もなければプレースホルダ
    } else {
        $iconUrl = asset('images/placeholder-course.png');
    }

                    $whenStr = $dt->format('D, M j H:i') . '–' . $end->format('H:i');

                    $status = $b->report->status ?? null;
                    $nextTop = $b->report->next_topic ?? '—';
                    $nextTopName = $b->report?->nextTopic?->name ?? '—';
                    $feedback = $b->report->feedback ?? '—';

                    $statusClass = match (strtolower((string) $status)) {
                        'done', 'completed' => 'text-bg-success',
                        'pending', 'todo' => 'text-bg-warning',
                        'missed', 'absent' => 'text-bg-danger',
                        default => 'text-bg-secondary',
                    };

                    // $courseId = $b->course->id ?? null;
                    // $teacherIdRow = $b->teacher->id ?? null;
                    $statusRaw = trim((string) ($b->report->status ?? ''));
                    $statusLower = strtolower($statusRaw);

                    if ($statusLower === 'canceled by teacher') {
                        $statusClass = 'badge bg-dark text-white';
                    } elseif ($statusRaw !== '') {
                        $statusClass = 'badge bg-success text-white';
                    } else {
                        $statusClass = '';
                    }

                    $nextTopName = $b->report?->nextTopic?->name ?? '—';
                    $feedback = $b->report->feedback ?? '—';

                    $courseId = $b->course->id ?? null;
                    $teacherIdRow = $b->teacher->id ?? null;
                @endphp

                {{-- カード --}}
                <div class="card shadow-sm">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex align-items-center gap-3 flex-wrap">

                            {{-- Left: Course icon --}}
                            <img src="{{ $iconUrl }}" alt="Course icon" class="rounded-3 border flex-shrink-0"
                                style="width:48px;height:48px;object-fit:cover;">

                            {{-- Middle: title + meta --}}
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold text-truncate">
                                    @if ($courseId)
                                        <a href="{{ route('courses.show', ['course' => $courseId]) }}"
                                            class="text-dark text-decoration-none">
                                            {{ $course }}
                                        </a>
                                    @else
                                        {{ $course }}
                                    @endif
                                    <span class="text-body-secondary">/</span>
                                    {{ $topic }}
                                </div>

                                <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                                    <span class="d-inline-flex align-items-center">
                                        <i class="fa-regular fa-calendar me-1"></i>{{ $whenStr }}
                                    </span>
                                    <span>
                                        with Teacher
                                        @if ($teacherIdRow)
                                            <a href="{{ route('teachers.profile', ['user_id' => $teacherIdRow]) }}"
                                                class="text-dark text-decoration-none fw-bold">
                                                {{ $teacherN }}
                                            </a>
                                        @else
                                            <span class="text-body fw-bold">{{ $teacherN }}</span>
                                        @endif
                                        @if ($statusRaw !== '')
                                            <span class="{{ $statusClass }} ms-2">
                                                {{ $statusRaw }}
                                            </span>
                                        @endif
                                    </span>
                                </div>
                            </div>

                            {{-- Right: Details button (モーダル起動) --}}
                            <div class="d-flex gap-2 ms-auto">
                                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-toggle="modal"
                                    data-bs-target="#bookingDetails-{{ $b->id }}">
                                    Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ▼▼ モーダル本体（必ず data-bs-target と id を一致させる） ▼▼ --}}
                <div class="modal fade" id="bookingDetails-{{ $b->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">

                            <div class="modal-header">
                                <h5 class="modal-title">Lesson details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body">

                                {{-- Booking block --}}
                                <div class="mb-3">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="fa-regular fa-calendar-check text-primary"></i>
                                        <span class="text-uppercase text-muted small fw-semibold">Booking</span>
                                    </div>

                                    <ul class="list-group list-group-flush">
                                        {{-- Course --}}
                                        <li class="list-group-item px-0">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-clone"></i><span>Course</span>
                                                </div>
                                                <div class="col-6 fw-semibold text-end text-truncate"
                                                    title="{{ $course }}">
                                                    @if ($courseId)
                                                        <a href="{{ route('courses.show', ['course' => $courseId]) }}"
                                                            class="text-dark text-decoration-none">
                                                            {{ $course }}
                                                        </a>
                                                    @else
                                                        {{ $course }}
                                                    @endif
                                                </div>
                                            </div>
                                        </li>

                                        {{-- Topic --}}
                                        <li class="list-group-item px-0">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-bookmark"></i><span>Topic</span>
                                                </div>
                                                <div class="col-6 fw-semibold text-end text-truncate"
                                                    title="{{ $topic }}">
                                                    {{ $topic }}
                                                </div>
                                            </div>
                                        </li>

                                        {{-- Teacher --}}
                                        <li class="list-group-item px-0">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-user"></i><span>Teacher</span>
                                                </div>
                                                <div class="col-6 fw-semibold text-end text-truncate"
                                                    title="{{ $teacherN }}">
                                                    @if ($teacherIdRow)
                                                        <a href="{{ route('teachers.profile', ['user_id' => $teacherIdRow]) }}"
                                                            class="text-dark text-decoration-none">
                                                            {{ $teacherN }}
                                                        </a>
                                                    @else
                                                        {{ $teacherN }}
                                                    @endif
                                                </div>
                                            </div>
                                        </li>

                                        {{-- Date & time --}}
                                        <li class="list-group-item px-0">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-clock"></i><span>Date & time</span>
                                                </div>
                                                <div class="col-6 fw-semibold text-end text-truncate"
                                                    title="{{ $whenStr }}">
                                                    {{ $whenStr }}
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>

                                {{-- Report block --}}
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="fa-regular fa-clipboard text-primary"></i>
                                        <span class="text-uppercase text-muted small fw-semibold">Report</span>
                                    </div>

                                    <ul class="list-group list-group-flush">
                                        {{-- Status --}}
                                        <li class="list-group-item px-0">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-flag"></i><span>Status</span>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <span class="badge {{ $statusClass }}">
                                                        {{ $status ?? '—' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </li>

                                        {{-- Next topic --}}
                                        <li class="list-group-item px-0">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-lightbulb"></i><span>Next topic</span>
                                                </div>
                                                <div class="col-6 fw-semibold text-end text-truncate">
                                                    {{ $nextTopName ?: '—' }}
                                                </div>
                                            </div>
                                        </li>

                                        {{-- Feedback --}}
                                        <li class="list-group-item px-0">
                                            <div class="row g-2 align-items-start">
                                                <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-comment-dots"></i><span>Comment</span>
                                                </div>
                                                <div class="col-6 fw-semibold text-end text-wrap">
                                                    {{ $feedback ?: '—' }}
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>

                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                                    Close
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
                {{-- ▲▲ モーダルここまで ▲▲ --}}

            @empty
                <div class="alert alert-light border d-flex align-items-center gap-2 mb-0">
                    <i class="fa-regular fa-circle-info text-secondary"></i>
                    <span class="small">No history yet.</span>
                </div>
            @endforelse
        </div>

        {{-- ページネーション --}}
        <div class="mt-3 d-flex justify-content-center">
            {{ $bookings->links() }}
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('student-lesson-filter-form');
            if (!form) return;

            const selects = form.querySelectorAll('.js-auto-filter');
            selects.forEach(select => {
                select.addEventListener('change', () => {
                    form.submit();
                });
            });
        });
    </script>
@endpush
