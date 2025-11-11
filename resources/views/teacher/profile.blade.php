@extends('layouts.app')

@section('title', 'Home')

@section('content')
    @php
        /** @var \App\Models\User|null $viewer */
        $viewer = auth()->user();
        $viewerRoleId = (int) ($viewer->role_id ?? 0);

        // 役割フラグ
        $viewerIsAdmin   = $viewerRoleId === 1 || ($viewer->role ?? null) === 'admin';
        $viewerIsTeacher = $viewerRoleId === 2 || ($viewer->role ?? null) === 'teacher';

        // 自分のプロフィールか
        $isOwner = optional($viewer)->id === optional($user)->id;

        // プライベート項目表示可（admin or 自分自身のteacher）
        $canSeePrivate = $viewerIsAdmin || ($viewerIsTeacher && $isOwner);

        // 編集可（admin or 自分自身のteacher）
        $canEdit = ($viewerIsTeacher && $isOwner);
    @endphp

    <style>
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
        }

        /* ===== 共通：エリア枠（student版と合わせる） ===== */
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

        /* プロフィール画像：縦長長方形 / 枠なし（student版合わせ） */
        .profile-avatar-wrap {
            width: 150px;
            height: 180px;
            border-radius: 0.9rem;
            overflow: hidden;
            margin: 0 auto 0.75rem;
            background: transparent;
            box-shadow: none;
        }

        .profile-avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .profile-label {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #9ca3af;
        }

        .section-empty {
            font-size: 0.9rem;
            color: #6c757d;
        }

        /* コース行：シンプルなカード風（student Enrolledに寄せる） */
        .course-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.55rem 0.4rem;
            border-radius: 0.6rem;
            text-decoration: none;
            background: #f9fafb;
            transition: all .16s ease;
            font-size: 0.9rem;
        }

        .course-item:hover {
            background: #eef2f7;
        }

        .course-title {
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .course-actions {
            white-space: nowrap;
        }

        .btn-teal {
            background: #45dacd;
            border-color: #45dacd;
            color: #0b2a2e;
        }

        .btn-teal:hover {
            filter: brightness(.95);
        }

        @media (max-width: 767.98px) {
            .profile-container {
                max-width: 100%;
            }

            .course-item {
                padding: 0.6rem 0.5rem;
            }
        }
    </style>

    <section class="py-4">
        <div class="container profile-container">

            {{-- ===== PROFILE AREA ===== --}}
            <div class="area-block mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="area-label">
                        <i class="fa-regular fa-user"></i>
                        TEACHER PROFILE
                    </div>
                </div>

                <div class="area-block-inner">
                    <div class="card border-0 bg-transparent">
                        <div class="card-body p-4">
                            <div class="row g-4 align-items-center">

                                {{-- Avatar --}}
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <div class="profile-avatar-wrap">
                                            <img
                                                src="{{ $user->avatar_path ? asset('storage/' . $user->avatar_path) : asset('images/default-avatar.png') }}"
                                                alt="Profile photo">
                                        </div>

                                        @if ($canEdit)
                                            <form method="POST"
                                                  action="{{ route('teachers.profile.photo.update', ['user' => $user->id]) }}"
                                                  enctype="multipart/form-data" class="mb-0">
                                                @csrf
                                                @method('PUT')
                                                <label class="btn btn-outline-secondary btn-sm px-3 rounded-pill mb-0">
                                                    <input type="file" name="photo" accept="image/*" class="d-none"
                                                           onchange="this.form.submit()">
                                                    Change photo
                                                </label>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                {{-- Basic Info --}}
                                <div class="col-md-8">
                                    <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                                        <div>
                                            <h2 class="h5 mb-1 fw-semibold">
                                                {{ $user->name }}
                                            </h2>

                                            @if ($viewerIsAdmin)
                                                <span class="badge rounded-pill bg-dark text-white me-1">
                                                    Admin view
                                                </span>
                                            @elseif($isOwner && $viewerIsTeacher)
                                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis">
                                                    It’s you
                                                </span>
                                            @endif
                                        </div>

                                        @if ($canEdit)
                                            <button class="btn btn-primary btn-sm px-3 rounded-pill d-none d-md-inline-flex"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editProfileModal">
                                                Edit
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Email --}}
                                    @if ($canSeePrivate)
                                        <div class="mb-2">
                                            <div class="profile-label">Email</div>
                                            <div class="small text-break">
                                                {{ $user->email }}
                                            </div>
                                        </div>
                                    @endif

                                    {{-- About --}}
                                    <div class="mb-2">
                                        <div class="profile-label">About</div>
                                        <div class="small">
                                            {{ $user->about ?: 'No introduction yet.' }}
                                        </div>
                                    </div>

                                    {{-- Meeting URL --}}
                                    @if ($canSeePrivate)
                                        <div class="mb-0">
                                            <div class="profile-label">Meeting URL</div>
                                            <div class="small">
                                                @if ($user->meeting_url)
                                                    <a href="{{ $user->meeting_url }}" target="_blank" rel="noopener"
                                                       class="link-body-emphasis link-underline-opacity-0 link-underline-opacity-75-hover">
                                                        {{ $user->meeting_url }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Edit button (mobile) --}}
                                    @if ($canEdit)
                                        <div class="d-grid d-md-none mt-3">
                                            <button class="btn btn-primary btn-sm rounded-pill"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editProfileModal">
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

            {{-- ===== COURSES AREA ===== --}}
            <div class="area-block">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="area-label">
                        <i class="fa-solid fa-layer-group"></i>
                        COURSES
                    </div>
                </div>

                <div class="area-block-inner">
                    <div class="card border-0 bg-transparent">
                        <div class="card-body p-3 p-md-4">

                            <div class="section-header">
                                <div class="section-header-title">
                                    <i class="fa-solid fa-layer-group text-primary"></i>
                                    <span>Teachable Courses</span>
                                </div>
                                <div class="section-header-sub">
                                    Courses assigned to this teacher
                                </div>
                            </div>

                            {{-- Attach form (admin only) --}}
                            @if ($viewerIsAdmin)
                                <form method="POST"
                                      action="{{ route('admin.teachers.courses.attach', $user->id) }}"
                                      class="row g-2 align-items-center my-3">
                                    @csrf
                                    <div class="col-12 col-sm-6 col-md-5">
                                        <select name="course_id" class="form-select form-select-sm" required>
                                            <option value="" disabled selected>Add a course…</option>
                                            @foreach ($allCourses as $c)
                                                <option value="{{ $c->id }}">{{ $c->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-sm-auto">
                                        <button type="submit" class="btn btn-primary btn-sm px-3">
                                            Add
                                        </button>
                                    </div>
                                </form>
                            @endif

                            {{-- Course list（student Enrolled風のカードリスト） --}}
                            @if ($courses->count())
                                <div class="vstack gap-2 mt-2">
                                    @foreach ($courses as $course)
                                        <div class="course-item">
                                            <div class="flex-grow-1">
                                                <a href="{{ route('courses.show', ['course' => $course->id]) }}"
                                                   class="course-title text-decoration-none">
                                                    {{ $course->title }}
                                                </a>
                                            </div>

                                            @if ($viewerIsAdmin)
                                                <div class="course-actions">
                                                    <form method="POST"
                                                          action="{{ route('admin.teachers.courses.detach', [$user->id, $course->id]) }}"
                                                          onsubmit="return confirm('Remove this course from the teacher?');"
                                                          class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="btn btn-sm btn-outline-danger">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="section-empty mt-2">
                                    No courses.
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- ===== Edit Profile Modal ===== --}}
    @if ($canEdit)
        <div class="modal fade" id="editProfileModal" tabindex="-1"
             aria-labelledby="editProfileLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-3 shadow-lg">
                    <form method="POST" action="{{ route('teachers.profile.update', $user->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title" id="editProfileLabel">Edit Profile</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                        </div>

                        <div class="modal-body pt-2">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Name</label>
                                <input name="name" type="text" class="form-control"
                                       value="{{ old('name', $user->name ?? '') }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <input name="email" type="email" class="form-control"
                                       value="{{ old('email', $user->email ?? '') }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">About</label>
                                <textarea name="about" rows="4" class="form-control"
                                          placeholder="Tell us about yourself">{{ old('about', $user->about ?? '') }}</textarea>
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-bold">Meeting URL</label>
                                <input name="meeting_url" type="url" class="form-control"
                                       value="{{ old('meeting_url', $user->meeting_url ?? '') }}"
                                       placeholder="https://example.com/meet/your-room">
                            </div>
                        </div>

                        <div class="modal-footer border-0 justify-content-between">
                            <button type="button" class="btn btn-light border"
                                    data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary px-4">
                                Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
