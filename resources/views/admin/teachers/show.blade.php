{{-- resources/views/admin/teachers/show.blade.php --}}
@extends('layouts.admin')
@section('title', 'Teacher Detail')

@section('content')
@php
    // 画像の安全な解決（URL/Storage/プレースホルダ）
    $raw = $teacher->avatar ?? '';
    $isUrl = \Illuminate\Support\Str::startsWith($raw, ['http://','https://']);
    $avatar = $raw
        ? ($isUrl ? $raw : (\Illuminate\Support\Facades\Storage::exists($raw) ? \Illuminate\Support\Facades\Storage::url($raw) : $raw))
        : "https://ui-avatars.com/api/?name=".urlencode($teacher->name ?? 'Teacher')."&background=DBEAFE&color=1F2937&size=128";

    // active / status どちらでも対応（status='active' 文字列もOK）
    $isActive = data_get($teacher, 'active', null);
    if (is_null($isActive)) {
        $isActive = data_get($teacher, 'status', null);
        if (is_string($isActive)) $isActive = strtolower($isActive) === 'active';
    }

    $created = optional($teacher->created_at)->format('Y-m-d H:i');
    $updated = optional($teacher->updated_at)->format('Y-m-d H:i');
@endphp

<h2 class="mb-4 fw-bold text-dark">Teacher Detail</h2>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <div class="d-flex align-items-center mb-4">
      <img src="{{ $avatar }}" alt="Avatar" class="rounded-circle me-3 border" width="72" height="72">
      <div>
        <h4 class="fw-bold mb-0">{{ $teacher->name }}</h4>
        @if(!empty($teacher->email))
          <p class="text-muted mb-0">{{ $teacher->email }}</p>
        @endif
      </div>
      <div class="ms-auto d-flex align-items-center gap-2">
        @if ($isActive)
          <span class="badge rounded-pill bg-success-subtle border border-success text-success px-3 py-2">Active</span>
        @else
          <span class="badge rounded-pill bg-secondary-subtle border border-secondary text-secondary px-3 py-2">Inactive</span>
        @endif

        {{-- 有効/無効トグル（PATCH想定） --}}
        <form method="POST" action="{{ route('admin.teachers.toggle', $teacher->id) }}">
          @csrf
          @method('PATCH')
          <button type="submit"
                  class="btn btn-sm {{ $isActive ? 'btn-outline-warning' : 'btn-outline-success' }}">
            {{ $isActive ? 'Deactivate' : 'Activate' }}
          </button>
        </form>
      </div>
    </div>

    <dl class="row mb-4">
      <dt class="col-sm-3">Created At</dt>
      <dd class="col-sm-9">{{ $created ?? '-' }}</dd>

      <dt class="col-sm-3">Updated At</dt>
      <dd class="col-sm-9">{{ $updated ?? '-' }}</dd>
    </dl>

    {{-- 担当コースがある場合のみ表示（controllerで ->load('courses') 済みだと◎） --}}
    @if(method_exists($teacher, 'courses') && ($teacher->relationLoaded('courses') ? $teacher->courses->isNotEmpty() : false))
      <div class="mb-4">
        <h5 class="fw-bold mb-2">Courses in Charge</h5>
        <ul class="list-group list-group-flush">
          @foreach($teacher->courses as $course)
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span>{{ $course->title }}</span>
              <a href="{{ route('admin.courses.edit', $course->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
            </li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="d-flex gap-2">
      <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline-secondary px-4">Back</a>
      <a href="{{ route('admin.teachers.edit', $teacher->id) }}" class="btn btn-outline-primary px-4">Edit</a>

      <form method="POST" action="{{ route('admin.teachers.destroy', $teacher->id) }}"
            onsubmit="return confirm('Delete this teacher?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger px-4">Delete</button>
      </form>
    </div>
  </div>
</div>
@endsection
