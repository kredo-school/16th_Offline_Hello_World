{{-- 左サイド共通パーツ --}}
@php
$query = request()->query();
$allQuery = $query;
unset($allQuery['status']); 
@endphp

{{-- 検索フォーム --}}
<form method="GET" action="{{ route('courses.index') }}" 
      class="mb-3 d-flex align-items-center video-search-bar" style="width: 250px;">
    @foreach(request()->except('search') as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endforeach
    <div class="input-group dashboard-search">
        <input type="text" 
               name="search" 
               class="form-control" 
               placeholder="Search"
               value="{{ request('search') }}">
        <button type="submit" class="input-group-text bg-white" style="cursor:pointer;">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div>
</form>



{{-- タブ --}}
@auth
    @if(Auth::user()->role_id != 2) {{-- Teacherにはタブ非表示 --}}
        <ul class="nav custom-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link {{ !request()->has('status') ? 'active' : '' }}" 
                   href="{{ route('courses.index', $allQuery) }}">All</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('status')=='active' ? 'active' : '' }}" 
                   href="{{ route('courses.index', array_merge($query, ['status'=>'active'])) }}">Active</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('status')=='completed' ? 'active' : '' }}" 
                   href="{{ route('courses.index', array_merge($query, ['status'=>'completed'])) }}">Completed</a>
            </li>
        </ul>
    @endif
@endauth



{{-- 言語フィルタ --}}
<div class="mb-3">
    <a href="{{ route('courses.index', array_merge($query, ['lang'=>'english'])) }}" 
       class="btn btn-outline-dark btn-sm me-1 {{ request('lang')=='english'?'active':'' }}">English</a>
    <a href="{{ route('courses.index', array_merge($query, ['lang'=>'it'])) }}" 
       class="btn btn-outline-dark btn-sm {{ request('lang')=='it'?'active':'' }}">IT</a>
</div>

@foreach($courses as $c)
    @php
        $isEnrolled = in_array($c->id, $enrolledCourseIds ?? []);
        $rate = $isEnrolled ? $c->completionRate(auth()->id()) : 0;

        if(request('status')=='active' && (!$isEnrolled || $rate==100)) continue;
        if(request('status')=='completed' && (!$isEnrolled || $rate<100)) continue;
        if(request('lang') && request('lang') != $c->language) continue;

        $isSelected = isset($course) && $course->id === $c->id;
    @endphp




    <a href="{{ route('courses.show', $c->id) }}" class="text-decoration-none text-dark">
        <div class="d-flex align-items-center mb-3 p-2 rounded border 
                    {{ $isSelected ? 'bg-light border-primary shadow-sm' : 'shadow-sm' }}">
            <img src="{{ $c->display_image }}"
                 alt="{{ $c->title }}"
                 class="rounded me-2" style="width:60px;height:60px;object-fit:cover;">
            <div class="flex-grow-1">
                <h6 class="mb-1 fw-bold">{{ $c->title }}</h6>
                @if($isEnrolled)
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar bg-info" style="width: {{ $rate }}%;"></div>
                    </div>
                    <small class="text-muted">{{ $rate }}% Finish</small>
                @else
                    <small class="badge bg-light text-dark border">{{ $c->language ?? 'English' }}</small>
                @endif
            </div>
        </div>
    </a>
@endforeach