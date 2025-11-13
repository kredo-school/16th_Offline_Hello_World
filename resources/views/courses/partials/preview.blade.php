<div class="course-preview">

    {{-- コース画像 --}}
    <div class="mb-3">
        <img src="{{ $course->display_image }}"
             class="course-header-image rounded"
             alt="{{ $course->title }}">
    </div>

</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h3 class="fw-bold mb-0">{{ $course->title }}</h3>

    @auth
        {{-- 生徒または一般ユーザーのみ「Enroll」ボタンを表示 --}}
        @if(in_array(Auth::user()->role_id, [3, 4]))
            @if(!in_array($course->id, $enrolledCourseIds ?? []))
                <form action="{{ route('courses.enroll', $course->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success fw-bold px-4 rounded-pill">
                        Enroll Now (¥{{ number_format($course->price) }})
                    </button>
                </form>
            @endif
        @endif
    @else
        <a href="{{ route('login') }}" class="btn btn-secondary fw-bold px-4 rounded-pill">
            Login to Enroll
        </a>
    @endauth
</div>

{{-- 説明文 --}}
<p class="text-muted">{{ $course->description ?? 'No description available.' }}</p>

{{-- コースコンテンツ --}}
<h5 class="fw-bold mt-4">Course's content 
    <span class="text-muted small">
        ({{ $course->topics->sum(fn($s) => $s->lessons->count()) }} lessons)
    </span>
</h5>

{{-- 各セクション --}}
<div class="mt-3">

    @foreach($course->topics as $topic)
        @php
            $user = Auth::user();
        @endphp

        {{-- Teacher の場合：自分が担当しているコースならプレビュー表示 --}}
        @if($user && $user->role_id == 2)
           @if($course->teachers->contains('id', $user->id))

                @foreach($topic->lessons as $lesson)
                    <div class="d-flex align-items-center mb-2 p-2 border rounded bg-white shadow-sm">
                        <img src="{{ $course->display_image }}"
                             alt="Lesson thumbnail"
                             class="rounded me-3"
                             style="width:60px;height:60px;object-fit:cover;">
                        <span class="fw-semibold">{{ $lesson->title }}</span>
                    </div>
                @endforeach
            @endif

        {{-- Student (3), Admin (1), Basic (4) → 全レッスン閲覧可 --}}
        @else
            @foreach($topic->lessons as $lesson)
                <div class="d-flex align-items-center mb-2 p-2 border rounded bg-white shadow-sm">
                    <img src="{{ $course->display_image }}"
                         alt="Lesson thumbnail"
                         class="rounded me-3"
                         style="width:60px;height:60px;object-fit:cover;">
                    <span class="fw-semibold">{{ $lesson->title }}</span>
                </div>
            @endforeach
        @endif
    @endforeach

</div>