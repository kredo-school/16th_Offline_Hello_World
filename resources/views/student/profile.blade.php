@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    @php
        /** @var \App\Models\User|null $viewer */
        $viewer = auth()->user();

        $roleId = (int) ($viewer->role_id ?? 0);
        $roleName = (string) ($viewer->role ?? '');

        $isAdmin = $roleId === 1 || $roleName === 'admin';
        $isTeacher = $roleId === 2 || $roleName === 'teacher';
        $isSelf = (int) ($viewer->id ?? 0) === (int) ($user->id ?? -1);

        // プロフィールの基本情報表示権限（現状どおり Admin or 本人）
        $canSeeAll = $isAdmin || $isSelf;

        // Enrolled / History を見せてよい人：本人 or Teacher or Admin
        $canSeeProgress = $isSelf || $isTeacher || $isAdmin;

        // 編集権限：本人のみ
        $canEdit = $isSelf;

        use Illuminate\Support\Facades\Storage;

        // ...既存の権限判定の下あたりに追加
        $up = ltrim((string) ($user->avatar_path ?? ''), '/');
        $userHasAvatar = $up !== '' && Storage::disk('public')->exists($up);
        $userAvatarUrl = $userHasAvatar ? asset('storage/' . $up) : null;
    @endphp
    <style>
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
        }

        /* ===== Area wrappers: 3エリアをはっきり分ける枠 ===== */
        .area-block {
            border-radius: 1.5rem;
            border: 1px solid #e5e7eb;
            padding: 10px 10px 12px;
            background: #f9fafb;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
        }

        .area-block-inner {
            border-radius: 1rem;
            border: 1px solid #f3f4f6;
            background-color: #ffffff;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.02);
        }

        .area-label {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: #9ca3af;
            padding: 2px 10px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background-color: #ffffff;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .area-label i {
            font-size: 0.7rem;
            color: #9ca3af;
        }

        /* Common section header inside each block */
        .section-header {
            padding: 10px 14px 8px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .section-header-title {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.98rem;
            font-weight: 600;
            color: #111827;
        }

        .section-header-title i {
            font-size: 0.9rem;
        }

        .section-header-sub {
            font-size: 0.78rem;
            color: #9ca3af;
        }

        /* Profile avatar (縦長) - 枠なしで画像だけ */
        .profile-avatar-wrap {
            width: 150px;
            height: 180px;
            border-radius: 0.9rem;
            overflow: hidden;
            margin: 0 auto 0.75rem;
            background: transparent;
            box-shadow: none;
        }

        .profile-avatar {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .section-empty {
            font-size: 0.9rem;
            color: #6c757d;
        }

        /* アイコン画像（コース・レッスン）も枠なし */
        .course-thumb,
        .lesson-thumb {
            width: 40px;
            height: 40px;
            border-radius: 0.75rem;
            overflow: hidden;
            background: transparent;
            flex-shrink: 0;
        }

        .course-thumb img,
        .lesson-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .lesson-meta {
            font-size: 0.8rem;
            color: #6c757d;
        }

        /* Lesson history row: ボタンが次の行に落ちないようにする */
        .lesson-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: nowrap;
        }

        .lesson-row .lesson-text {
            min-width: 0;
            flex: 1 1 auto;
        }

        .lesson-row .details-wrap {
            flex-shrink: 0;
        }

        .lesson-row .title-line {
            font-size: 0.9rem;
        }

        @media (max-width: 767.98px) {
            .profile-container {
                max-width: 100%;
            }

            .lesson-row {
                align-items: flex-start;
            }
        }
    </style>

    <section class="py-4">
        <div class="container profile-container">
            @if (!empty($isInactive) && $isInactive)
                {{-- ★ status !== active の場合だけ表示するメッセージ（英語） --}}
                <div class="alert alert-warning">
                    This student account is currently <strong>inactive</strong>, so profile details and lesson history are
                    not available.
                </div>
            @else
                {{-- ===== Profile Area ===== --}}
                <div class="area-block mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="area-label">
                            <i class="fa-regular fa-user"></i>
                            PROFILE
                        </div>
                    </div>

                    <div class="area-block-inner">
                        <div class="card border-0 bg-transparent">
                            <div class="card-body p-4">

                                <div class="d-flex justify-content-between align-items-start mb-3 gap-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge rounded-pill bg-light text-secondary small border">
                                            Student
                                        </span>
                                        @if ($isAdmin)
                                            <span class="badge rounded-pill bg-dark text-white small">
                                                Admin view
                                            </span>
                                        @elseif($isSelf)
                                            <span class="badge rounded-pill bg-success-subtle text-success-emphasis small">
                                                It’s you
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="row g-4 align-items-center">

                                    {{-- Avatar --}}
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <div class="profile-avatar-wrap">
                                                <x-avatar :url="$userAvatarUrl" :name="$user->name" :w="150"
                                                    :h="180" rounded="md" fit="contain" />
                                            </div>

                                            @if ($canEdit)
                                                <form
                                                    action="{{ route('students.profile.photo.update', ['user' => $user->id]) }}"
                                                    method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    @method('PUT')
                                                    <input id="photo" name="photo" type="file" class="d-none"
                                                        accept="image/*" onchange="this.form.submit()">
                                                    <label for="photo"
                                                        class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                                                        Change photo
                                                    </label>
                                                </form>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Basic info --}}
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-start justify-content-between gap-3">
                                            <div>
                                                <h1 class="h5 mb-1 fw-semibold">{{ $user->name }}</h1>
                                                @if ($canSeeAll)
                                                    <div class="text-muted small">
                                                        <i class="fa-regular fa-envelope me-1"></i>{{ $user->email }}
                                                    </div>
                                                @endif
                                            </div>

                                            @if ($canEdit)
                                                <button type="button"
                                                    class="btn btn-primary btn-sm px-3 rounded-pill d-none d-md-inline-flex"
                                                    data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                                    Edit
                                                </button>
                                            @endif
                                        </div>

                                        <hr class="my-3">

                                        <div>
                                            <div class="text-uppercase text-muted small fw-semibold mb-1">About</div>
                                            <p class="mb-0 text-body" style="font-size:0.9rem;">
                                                {{ $user->about ?: 'No introduction yet.' }}
                                            </p>
                                        </div>

                                        @if ($canEdit)
                                            <div class="d-grid d-md-none mt-3">
                                                <button type="button" class="btn btn-primary btn-sm rounded-pill"
                                                    data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                                    Edit profile
                                                </button>
                                            </div>
                                        @endif
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== Enrolled & History Areas ===== --}}
                @if ($canSeeProgress)
                    <div class="row g-2">

                        {{-- ==== Enrolled Courses Area ==== --}}
                        <div class="col-md-5 col-12">
                            <div class="area-block h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="area-label">
                                        <i class="fa-solid fa-layer-group"></i>
                                        ENROLLED
                                    </div>
                                </div>

                                <div class="area-block-inner h-100">
                                    <div class="card border-0 bg-transparent h-100">
                                        <div class="card-body p-2 p-md-4 d-flex flex-column h-100">

                                            <div class="section-header">
                                                <div class="section-header-title">
                                                    <i class="fa-solid fa-layer-group text-primary"></i>
                                                    <span>Enrolled courses</span>
                                                </div>
                                                <div class="section-header-sub">Active &amp; completed</div>
                                            </div>

                                            <ul class="nav nav-pills mb-3 small bg-light rounded-pill p-1" id="courseTabs"
                                                role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active px-3 py-1 rounded-pill"
                                                        id="course-tab-active" data-bs-toggle="pill"
                                                        data-bs-target="#course-pane-active" type="button" role="tab">
                                                        Active
                                                    </button>
                                                </li>
                                                <li class="nav-item ms-1" role="presentation">
                                                    <button class="nav-link px-3 py-1 rounded-pill"
                                                        id="course-tab-completed" data-bs-toggle="pill"
                                                        data-bs-target="#course-pane-completed" type="button"
                                                        role="tab">
                                                        Completed
                                                    </button>
                                                </li>
                                            </ul>

                                            <div class="tab-content flex-grow-1 d-flex flex-column">

                                                {{-- Active --}}
                                                <div class="tab-pane fade show active flex-grow-1" id="course-pane-active"
                                                    role="tabpanel">
                                                    @if ($activeCourses->count())
                                                        <div class="vstack gap-2 mb-2">
                                                            @foreach ($activeCourses as $course)
                                                                @php
                                                                    $thumb = $course->image_url
                                                                        ? asset(
                                                                            'storage/' . ltrim($course->image_url, '/'),
                                                                        )
                                                                        : asset('images/placeholder-course.png');
                                                                @endphp
                                                                <a href="{{ route('courses.show', ['course' => $course->id]) }}"
                                                                    class="d-flex align-items-center gap-3 py-2 px-1 rounded-3 text-decoration-none bg-light-subtle border-0"
                                                                    style="transition:all .18s ease;">
                                                                    <div class="course-thumb">
                                                                        <img src="{{ $thumb }}" alt="Course">
                                                                    </div>
                                                                    <div class="flex-grow-1 text-truncate"
                                                                        style="font-size:0.9rem;">
                                                                        {{ $course->title }}
                                                                        <div class="text-muted small">In progress</div>
                                                                    </div>
                                                                    <i
                                                                        class="fa-solid fa-chevron-right text-muted small"></i>
                                                                </a>
                                                            @endforeach
                                                        </div>

                                                        <div class="mt-auto d-flex justify-content-center">
                                                            {{ $activeCourses->onEachSide(1)->links('pagination::bootstrap-5') }}
                                                        </div>
                                                    @else
                                                        <div class="section-empty">No active courses.</div>
                                                    @endif
                                                </div>

                                                {{-- Completed (Activeと同じフォーマット) --}}
                                                <div class="tab-pane fade flex-grow-1" id="course-pane-completed"
                                                    role="tabpanel">
                                                    @if ($completedCourses->count())
                                                        <div class="vstack gap-2 mb-2">
                                                            @foreach ($completedCourses as $course)
                                                                @php
                                                                    $thumb = $course->image_url
                                                                        ? asset(
                                                                            'storage/' . ltrim($course->image_url, '/'),
                                                                        )
                                                                        : asset('images/placeholder-course.png');
                                                                @endphp
                                                                <a href="{{ route('courses.show', ['course' => $course->id]) }}"
                                                                    class="d-flex align-items-center gap-3 py-2 px-1 rounded-3 text-decoration-none bg-light-subtle border-0"
                                                                    style="transition:all .18s ease;">
                                                                    <div class="course-thumb">
                                                                        <img src="{{ $thumb }}" alt="Course">
                                                                    </div>
                                                                    <div class="flex-grow-1 text-truncate"
                                                                        style="font-size:0.9rem;">
                                                                        {{ $course->title }}
                                                                        <div class="text-muted small">Completed</div>
                                                                    </div>
                                                                    <i
                                                                        class="fa-solid fa-chevron-right text-muted small"></i>
                                                                </a>
                                                            @endforeach
                                                        </div>

                                                        <div class="mt-auto d-flex justify-content-center">
                                                            {{ $completedCourses->onEachSide(1)->links('pagination::bootstrap-5') }}
                                                        </div>
                                                    @else
                                                        <div class="section-empty">No completed courses.</div>
                                                    @endif
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ==== Lesson History Area ==== --}}
                        <div class="col-md-7 col-12">
                            <div class="area-block h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="area-label">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                        HISTORY
                                    </div>
                                </div>

                                <div class="area-block-inner h-100">
                                    <div class="card border-0 bg-transparent h-100">
                                        <div class="card-body p-2 p-md-4 d-flex flex-column h-100">

                                            <div class="section-header">
                                                <div class="section-header-title">
                                                    <i class="fa-solid fa-clock-rotate-left text-primary"></i>
                                                    <span>Lesson history</span>
                                                </div>
                                                <div class="section-header-sub">Previous sessions</div>
                                            </div>

                                            <div class="vstack gap-2 flex-grow-1">
                                                @forelse ($history as $b)
                                                    @php
                                                        $srcTz = config('app.timezone', 'Asia/Manila');
                                                        $viewTz = 'Asia/Manila';

                                                        $rawDate = $b->getAttribute('date');
                                                        if ($rawDate instanceof \Carbon\Carbon) {
                                                            $dateStr = $rawDate->format('Y-m-d');
                                                        } else {
                                                            $dateStr = (string) $rawDate;
                                                            if (
                                                                preg_match(
                                                                    '/^\d{4}-\d2-\d2\s+\d{2}:\d{2}:\d{2}$/',
                                                                    $dateStr,
                                                                )
                                                            ) {
                                                                $dateStr = substr($dateStr, 0, 10);
                                                            }
                                                        }

                                                        $rawTime = $b->getAttribute('time');
                                                        if ($rawTime instanceof \Carbon\Carbon) {
                                                            $timeStr = $rawTime->format('H:i:s');
                                                        } else {
                                                            $timeStr = (string) $rawTime;
                                                            if (preg_match('/^\d{2}:\d{2}$/', $timeStr)) {
                                                                $timeStr .= ':00';
                                                            }
                                                        }

                                                        $dt = \Carbon\Carbon::createFromFormat(
                                                            'Y-m-d H:i:s',
                                                            "$dateStr $timeStr",
                                                            $srcTz,
                                                        )->setTimezone($viewTz);

                                                        $duration = $b->duration_minutes ?? 50;
                                                        $end = (clone $dt)->addMinutes($duration);

                                                        $course = $b->course->title ?? 'Course';
                                                        $topic = $b->topic->name ?? 'Topic';
                                                        $teacherN = $b->teacher->name ?? 'Teacher';

                                                        $iconUrl =
                                                            $b->course && $b->course->image_url
                                                                ? asset('storage/' . ltrim($b->course->image_url, '/'))
                                                                : asset('images/placeholder-course.png');

                                                        $whenStr =
                                                            $dt->format('D, M j H:i') . '–' . $end->format('H:i');

                                                        $status = $b->report->status ?? null;
                                                        $feedback = $b->report->feedback ?? '—';
                                                        $nextTopName = $b->report?->nextTopic?->name ?? '—';

                                                        $courseId = $b->course->id ?? null;
                                                        $teacherId = $b->teacher->id ?? null;
                                                    @endphp

                                                    <div class="card border-0 shadow-sm" style="border-radius:0.75rem;">
                                                        <div class="card-body py-2 px-2">
                                                            <div class="lesson-row">
                                                                <div class="lesson-thumb">
                                                                    <img src="{{ $iconUrl }}" alt="Course icon">
                                                                </div>

                                                                <div class="lesson-text">
                                                                    <div class="title-line text-truncate">
                                                                        @if ($courseId)
                                                                            <a href="{{ route('courses.show', ['course' => $courseId]) }}"
                                                                                class="text-decoration-none text-dark">
                                                                                {{ $course }}
                                                                            </a>
                                                                        @else
                                                                            {{ $course }}
                                                                        @endif
                                                                        <span class="text-muted">/
                                                                            {{ $topic }}</span>
                                                                    </div>
                                                                    <div class="lesson-meta text-truncate">
                                                                        <i
                                                                            class="fa-regular fa-calendar me-1"></i>{{ $whenStr }}
                                                                        &nbsp;|&nbsp;with
                                                                        @if ($teacherId)
                                                                            <a href="{{ route('teachers.profile', ['user_id' => $teacherId]) }}"
                                                                                class="text-decoration-none">
                                                                                {{ $teacherN }}
                                                                            </a>
                                                                        @else
                                                                            {{ $teacherN }}
                                                                        @endif
                                                                    </div>
                                                                </div>

                                                                <div class="details-wrap">
                                                                    <button type="button"
                                                                        class="btn btn-outline-secondary btn-sm px-3 rounded-pill"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#bookingDetails-{{ $b->id }}">
                                                                        Details
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Modal --}}
                                                    <div class="modal fade" id="bookingDetails-{{ $b->id }}"
                                                        tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content border-0 rounded-3 shadow-lg">
                                                                <div class="modal-header border-0 pb-0">
                                                                    <h5 class="modal-title">Lesson details</h5>
                                                                    <button type="button" class="btn-close"
                                                                        data-bs-dismiss="modal"
                                                                        aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body pt-2">
                                                                    <div class="mb-3">
                                                                        <div
                                                                            class="text-uppercase text-muted small fw-semibold mb-1">
                                                                            Booking
                                                                        </div>
                                                                        <ul class="list-group list-group-flush">
                                                                            <li class="list-group-item px-0">
                                                                                <div
                                                                                    class="d-flex justify-content-between small">
                                                                                    <span
                                                                                        class="text-secondary">Course</span>
                                                                                    <span
                                                                                        class="fw-semibold text-end text-truncate"
                                                                                        title="{{ $course }}">
                                                                                        @if ($courseId)
                                                                                            <a href="{{ route('courses.show', ['course' => $courseId]) }}"
                                                                                                class="text-dark text-decoration-none">
                                                                                                {{ $course }}
                                                                                            </a>
                                                                                        @else
                                                                                            {{ $course }}
                                                                                        @endif
                                                                                    </span>
                                                                                </div>
                                                                            </li>
                                                                            <li class="list-group-item px-0">
                                                                                <div
                                                                                    class="d-flex justify-content-between small">
                                                                                    <span
                                                                                        class="text-secondary">Topic</span>
                                                                                    <span
                                                                                        class="fw-semibold text-end text-truncate">
                                                                                        {{ $topic }}
                                                                                    </span>
                                                                                </div>
                                                                            </li>
                                                                            <li class="list-group-item px-0">
                                                                                <div
                                                                                    class="d-flex justify-content-between small">
                                                                                    <span
                                                                                        class="text-secondary">Teacher</span>
                                                                                    <span
                                                                                        class="fw-semibold text-end text-truncate">
                                                                                        @if ($teacherId)
                                                                                            <a href="{{ route('teachers.profile', ['user_id' => $teacherId]) }}"
                                                                                                class="text-dark text-decoration-none">
                                                                                                {{ $teacherN }}
                                                                                            </a>
                                                                                        @else
                                                                                            {{ $teacherN }}
                                                                                        @endif
                                                                                    </span>
                                                                                </div>
                                                                            </li>
                                                                            <li class="list-group-item px-0">
                                                                                <div
                                                                                    class="d-flex justify-content-between small">
                                                                                    <span class="text-secondary">Date &amp;
                                                                                        time</span>
                                                                                    <span
                                                                                        class="fw-semibold text-end text-truncate">
                                                                                        {{ $whenStr }}
                                                                                    </span>
                                                                                </div>
                                                                            </li>
                                                                        </ul>
                                                                    </div>

                                                                    <div>
                                                                        <div
                                                                            class="text-uppercase text-muted small fw-semibold mb-1">
                                                                            Report
                                                                        </div>
                                                                        <ul class="list-group list-group-flush">
                                                                            <li class="list-group-item px-0">
                                                                                <div
                                                                                    class="d-flex justify-content-between small">
                                                                                    <span
                                                                                        class="text-secondary">Status</span>
                                                                                    <span class="text-end">
                                                                                        {{ $status ?: '—' }}
                                                                                    </span>
                                                                                </div>
                                                                            </li>
                                                                            <li class="list-group-item px-0">
                                                                                <div
                                                                                    class="d-flex justify-content-between small">
                                                                                    <span class="text-secondary">Next
                                                                                        topic</span>
                                                                                    <span
                                                                                        class="fw-semibold text-end text-truncate">
                                                                                        {{ $nextTopName }}
                                                                                    </span>
                                                                                </div>
                                                                            </li>
                                                                            <li class="list-group-item px-0">
                                                                                <div
                                                                                    class="d-flex justify-content-between small">
                                                                                    <span
                                                                                        class="text-secondary">Comment</span>
                                                                                    <span
                                                                                        class="fw-semibold text-end text-wrap">
                                                                                        {{ $feedback ?: '—' }}
                                                                                    </span>
                                                                                </div>
                                                                            </li>
                                                                        </ul>
                                                                    </div>
                                                                </div>

                                                                <div class="modal-footer border-0">
                                                                    <button type="button" class="btn btn-light border"
                                                                        data-bs-dismiss="modal">
                                                                        Close
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div
                                                        class="alert alert-light border d-flex align-items-center gap-2 mb-0">
                                                        <i class="fa-regular fa-circle-info text-secondary"></i>
                                                        <span class="small">No lesson history yet.</span>
                                                    </div>
                                                @endforelse
                                            </div>

                                            @if ($history->hasPages())
                                                <div class="mt-3 d-flex justify-content-center">
                                                    {{ $history->onEachSide(1)->links('pagination::bootstrap-5') }}
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                @else
                    {{-- 権限がない閲覧者向けメッセージ（英語） --}}
                    <div class="mt-3 alert alert-light border small">
                        This user's enrolled courses and lesson history are visible only to the student, teachers, and
                        administrators.
                    </div>
                @endif

                {{-- ===== Edit Profile Modal ===== --}}
                @if ($canEdit)
                    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 rounded-3 shadow-lg">
                                <div class="modal-header border-0 pb-0">
                                    <h5 class="modal-title" id="editProfileModalLabel">Edit profile</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>

                                <form action="{{ route('students.profile.update', $user) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="modal-body pt-2">
                                        <div class="mb-3">
                                            <label for="edit-name" class="form-label">Name</label>
                                            <input type="text" id="edit-name" name="name" class="form-control"
                                                value="{{ old('name', $user->name) }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit-email" class="form-label">Email</label>
                                            <input type="email" id="edit-email" name="email" class="form-control"
                                                value="{{ old('email', $user->email) }}" required>
                                        </div>
                                        <div class="mb-0">
                                            <label for="edit-about" class="form-label">About</label>
                                            <textarea id="edit-about" name="about" class="form-control" rows="4" placeholder="Tell us about yourself.">{{ old('about', $user->about) }}</textarea>
                                        </div>
                                    </div>

                                    <div class="modal-footer border-0">
                                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                                            Cancel
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            Save changes
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </section>
@endsection
