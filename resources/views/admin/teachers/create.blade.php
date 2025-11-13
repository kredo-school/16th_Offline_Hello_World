@extends('layouts.app')
@section('title', 'Add Teacher')

@section('content')
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold m-0">Add Teacher</h3>
            <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline-secondary">← Back</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                {{-- 保存先：POST admin.teachers.store --}}
                <form method="POST" action="{{ route('admin.teachers.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                        @error('name')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                        @error('email')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" minlength="4" required>
                        @error('password')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Courses --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Courses</label>
                        <select name="course_ids[]" multiple class="form-select" size="5">
                            @foreach ($courses as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->title }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Ctrl / Command キーを押しながら複数選択できます</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">Create</button>
                        <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
