{{-- resources/views/admin/students/show.blade.php --}}
@extends('layouts.admin')
@section('title', 'Student Detail')

@section('content')
@php
    // 画像の安全な解決（URL/Storage/プレースホルダ）
    $rawAvatar = $student->avatar ?? '';
    $isUrl = Str::startsWith($rawAvatar, ['http://','https://']);
    $avatar = $rawAvatar
        ? ($isUrl ? $rawAvatar : (Storage::exists($rawAvatar) ? Storage::url($rawAvatar) : $rawAvatar))
        : "https://ui-avatars.com/api/?name=".urlencode($student->name ?? 'Student')."&background=E2E8F0&color=111827&size=128";

    // status/active のどちらでも対応
    $isActive = data_get($student, 'active', null);
    if (is_null($isActive)) {
        $isActive = data_get($student, 'status', null);
        // 文字列 'active' / 'inactive' にも対応
        if (is_string($isActive)) {
            $isActive = strtolower($isActive) === 'active';
        }
    }

    $created = optional($student->created_at)->format('Y-m-d H:i');
    $updated = optional($student->updated_at)->format('Y-m-d H:i');
@endphp

<h2 class="mb-4 fw-bold text-dark">Student Detail</h2>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <div class="d-flex align-items-center mb-4">
      <img src="{{ $avatar }}" alt="Avatar" class="rounded-circle me-3 border" width="72" height="72">
      <div>
        <h4 class="fw-bold mb-0">{{ $student->name }}</h4>
        @if(!empty($student->email))
          <p class="text-muted mb-0">{{ $student->email }}</p>
        @endif
      </div>

      <div class="ms-auto d-flex align-items-center gap-2">
        @if ($isActive)
          <span class="badge rounded-pill bg-success-subtle border border-success text-success px-3 py-2">Active</span>
        @else
          <span class="badge rounded-pill bg-secondary-subtle border border-secondary text-secondary px-3 py-2">Inactive</span>
        @endif

        {{-- 任意：有効/無効トグル（ルートはプロジェクトに合わせて） --}}
        @isset($student->id)
        <form method="POST" action="{{ route('admin.students.toggle', $student->id) }}">
          @csrf
          @method('PATCH')
          <button type="submit"
                  class="btn btn-sm {{ $isActive ? 'btn-outline-warning' : 'btn-outline-success' }}">
            {{ $isActive ? 'Deactivate' : 'Activate' }}
          </button>
        </form>
        @endisset
      </div>
    </div>

    <dl class="row mb-4">
      <dt class="col-sm-3">Created At</dt>
      <dd class="col-sm-9">{{ $created ?? '-' }}</dd>

      <dt class="col-sm-3">Updated At</dt>
      <dd class="col-sm-9">{{ $updated ?? '-' }}</dd>
    </dl>

    {{-- 関連（例：受講コース）がある場合だけ表示。controllerで ->load('courses') しておくと◎ --}}
    @if(method_exists($student, 'courses') && ($student->relationLoaded('courses') ? $student->courses->isNotEmpty() : false))
      <div class="mb-4">
        <h5 class="fw-bold mb-2">Enrolled Courses</h5>
        <ul class="list-group list-group-flush">
          @foreach($student->courses as $course)
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span>{{ $course->title }}</span>
              <a href="{{ route('admin.courses.edit', $course->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
            </li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="d-flex gap-2">
      <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary px-4">Back</a>
      <a href="{{ route('admin.students.edit', $student->id) }}" class="btn btn-outline-primary px-4">Edit</a>

      <form method="POST" action="{{ route('admin.students.destroy', $student->id) }}"
            onsubmit="return confirm('Are you sure to delete this student?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger px-4">Delete</button>
      </form>
    </div>
  </div>
</div>
@endsection
