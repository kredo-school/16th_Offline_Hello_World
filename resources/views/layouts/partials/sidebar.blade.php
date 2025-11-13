@php
    $auth = Auth::user();
    $roleId = Auth::user()->role_id ?? null;

    $isAdmin = (string) $roleId === '1';
    $isTeacher = (string) $roleId === '2';
    $isStudent = (string) $roleId === '3';

    // 物理ファイルの有無までチェック（storage:link 済み前提）
   $avatarPath = ltrim((string) ($auth->avatar_path ?? ''), '/');
   $hasAvatar  = $avatarPath !== '' && Storage::disk('public')->exists($avatarPath);
   $avatarUrl  = $hasAvatar ? asset('storage/'.$avatarPath) : null;
@endphp

@if ($isAdmin)
    <aside
        class="col-12 col-md-3 col-lg-2 d-flex flex-column align-items-center py-4
         vh-100 position-sticky top-0 sidebar-shell"
        style="background-color:#9CDBE2;">

        {{-- Brand / Logo（装飾なし） --}}
        <div class="mb-4">
            <img src="{{ asset('images/HELLO2.png') }}" alt="Hello World" class="brand-logo">
        </div>

        {{-- Main nav --}}
        <nav class="nav flex-column w-100 px-4 fw-semibold s-nav">
            <a class="nav-link s-link mb-1 {{ request()->routeIs('admin.index') ? 'active' : '' }}"
                href="{{ route('admin.index') }}"><i class="fa-solid fa-house-chimney me-2"></i> Home</a>
            <a class="nav-link s-link mb-1 {{ request()->routeIs('admin.students.index') ? 'active' : '' }}"
                href="{{ route('admin.students.index') }}"><i class="fa-solid fa-user-graduate me-2"></i> Students</a>
            <a class="nav-link s-link mb-1 {{ request()->routeIs('admin.teachers.index') ? 'active' : '' }}"
                href="{{ route('admin.teachers.index') }}"><i class="fa-solid fa-chalkboard-user me-2"></i> Teachers</a>
            <a class="nav-link s-link mb-1 {{ request()->routeIs('admin.courses.index') ? 'active' : '' }}"
                href="{{ route('admin.courses.index') }}"><i class="fa-solid fa-book-open me-2"></i> Courses</a>
            {{-- <a class="nav-link s-link" href="#">Forum</a> --}}
        </nav>

        {{-- Footer / User --}}
        <div class="mt-auto w-100">
            <div class="nav flex-column w-100 px-4 fw-semibold">
                {{-- <div>
                    <a class="nav-link s-link d-flex align-items-center gap-2 p-0 bg-transparent text-start w-100"
                        href="{{ route('teachers.profile', ['user_id' => Auth::id()]) }}">
                        <i class="fa-solid fa-user-circle"></i>
                        <span class="text-truncate">{{ Auth::user()->name }}</span>
                    </a>
                </div> --}}

                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit"
                        class="nav-link s-link s-link-danger p-0 bg-transparent border-0 text-start w-100">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </aside>
@elseif($isTeacher)
    <aside
        class="col-12 col-md-3 col-lg-2 d-flex flex-column align-items-center py-4
         vh-100 position-sticky top-0 sidebar-shell"
        style="background-color:#9CDBE2;">

        {{-- Brand / Logo（装飾なし） --}}
        <div class="mb-4">
            <img src="{{ asset('images/HELLO2.png') }}" alt="Hello World" class="brand-logo">
        </div>

        {{-- Main nav --}}
        <nav class="nav flex-column w-100 px-4 fw-semibold s-nav">
            <a class="nav-link s-link mb-1 {{ request()->routeIs('teachers.index') ? 'active' : '' }}"
                href="{{ route('teachers.index') }}"><i class="fa-solid fa-calendar-days me-2"></i> Schedule</a>
            <a class="nav-link s-link mb-1 {{ request()->routeIs('courses.index') ? 'active' : '' }}"
                href="{{ route('courses.index') }}"><i class="fa-solid fa-book-open me-2"></i> Courses</a>
            <a class="nav-link s-link mb-1 {{ request()->routeIs('teachers.lessonhistory') ? 'active' : '' }}"
                href="{{ route('teachers.lessonhistory', ['teacher' => Auth::id()]) }}">
                <i class="fa-solid fa-clock-rotate-left me-2"></i> Lesson History
            </a>
            {{-- <a class="nav-link s-link" href="#">Forum</a> --}}
        </nav>

        {{-- Footer / User --}}
        <div class="mt-auto w-100">
            <div class="nav flex-column w-100 px-4 fw-semibold s-nav">
                {{-- User (click to Profile) --}}
                <a href="{{ route('teachers.profile', ['user_id' => Auth::id()]) }}"
                    class="nav-link s-link d-flex align-items-center gap-2 p-0 text-start w-100">
                     <x-avatar :url="$avatarUrl" :name="$auth->name" size="36" rounded="md" />
                    <span class="text-truncate">{{ Auth::user()->name }}</span>
                </a>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit"
                        class="nav-link s-link s-link-danger p-0 bg-transparent border-0 text-start w-100">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </aside>
@elseif($isStudent)
    <aside
        class="col-12 col-md-3 col-lg-2 d-flex flex-column align-items-center py-4
         vh-100 position-sticky top-0 sidebar-shell"
        style="background-color:#9CDBE2;">

        {{-- Brand / Logo（装飾なし） --}}
        <div class="mb-4">
            <img src="{{ asset('images/HELLO2.png') }}" alt="Hello World" class="brand-logo">
        </div>

        {{-- Main nav --}}
        <nav class="nav flex-column w-100 px-4 fw-semibold s-nav">
            <a class="nav-link s-link mb-1 {{ request()->routeIs('students.index') ? 'active' : '' }}"
                href="{{ route('students.index') }}"><i class="fa-solid fa-house-chimney me-2"></i> Home</a>
            <a class="nav-link s-link mb-1 {{ request()->routeIs('courses.index') ? 'active' : '' }}"
                href="{{ route('courses.index') }}"><i class="fa-solid fa-book-open me-2"></i> Courses</a>
            <a class="nav-link s-link mb-1 {{ request()->routeIs('selflearning.index') ? 'active' : '' }}"
                href="{{ route('selflearning.index') }}">
                <i class="fa-solid fa-laptop-code me-2"></i> Self-learning
            </a>
            {{-- <a class="nav-link s-link" href="#">Forum</a> --}}
        </nav>

        {{-- Footer / User --}}
        <div class="mt-auto w-100">
            <div class="nav flex-column w-100 px-4 fw-semibold s-nav">
                <a href="{{ route('students.profile.show', ['user' => Auth::id()]) }}"
                    class="nav-link s-link d-flex align-items-center gap-2 p-0 text-start w-100">
                    <x-avatar :url="$avatarUrl" :name="$auth->name" size="36" rounded="md" />
                    <span class="text-truncate">{{ Auth::user()->name }}</span>
                </a>

                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit"
                        class="nav-link s-link s-link-danger p-0 bg-transparent border-0 text-start w-100">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </aside>
@else
    {{-- role不明時は何も表示しない（必要ならデフォルトを用意） --}}
@endif
