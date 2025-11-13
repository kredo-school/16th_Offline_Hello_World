@extends('layouts.admin')
@section('title', 'Edit Teacher')

@section('content')
    <h2 class="mb-4 fw-bold text-dark">Edit Teacher</h2>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.teachers.update', $teacher->id) }}">
                @csrf
                @method('PUT')

                {{-- Name --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $teacher->name) }}"
                        required>
                    @error('name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $teacher->email) }}"
                        required>
                    @error('email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Avatar URL --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Avatar URL</label>
                    <input type="url" name="avatar" class="form-control" value="{{ old('avatar', $teacher->avatar) }}"
                        placeholder="https://…">
                    @error('avatar')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Courses --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Courses</label>
                    <select name="course_ids[]" multiple class="form-select" size="5">
                        @foreach ($courses as $c)
                            <option value="{{ $c->id }}" @selected(collect(old('course_ids', $teacher->courses->pluck('id')))->contains($c->id))>
                                {{ $c->title }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Ctrl / Command を押しながら複数選択できます</div>
                </div>

                {{-- Active --}}
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="activeSwitch" name="active"
                        {{ old('active', $teacher->active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="activeSwitch">Active</label>
                </div>


                <div class="d-flex gap-2">
                    <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn px-4" style="background-color:#189AB4;color:#fff;">Update</button>
                </div>
            </form>
        </div>
    </div>
@endsection
