@extends('layouts.app')
@section('title', 'Add Course')

@section('content')
    <div class="container my-5 d-flex justify-content-center">
        <div class="card shadow-lg" style="max-width: 600px; width: 100%;">
            <div class="card-header text-white" style="background-color:#9CDBE2; ">
                <h4 class="mb-0 text-center">Add New Course</h4>
            </div>
            <div class="card-body">

                {{-- エラーメッセージ --}}
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.courses.store') }}" enctype="multipart/form-data">
                    @csrf

                    {{-- Title --}}
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" value="{{ old('title') }}" required class="form-control">
                    </div>

                    {{-- Price --}}
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" name="price" value="{{ old('price', 5000) }}"
                            class="form-control">
                    </div>

                    {{-- Description --}}
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                    </div>

                    {{-- Category --}}
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <option value=""></option>
                            <option value="IT"
                                {{ old('category', $course->category ?? null) == 'IT' ? 'selected' : '' }}>IT</option>
                            <option value="English"
                                {{ old('category', $course->category ?? null) == 'English' ? 'selected' : '' }}>English
                           </option>
                            <option value="Japanese"
                                {{ old('category', $course->category ?? null) == 'Japanese' ? 'selected' : '' }}>Japanese
                            </option>
                        </select>
                    </div>

                    {{-- Level --}}
                    <div class="mb-3">
                        <label class="form-label">Level</label>
                        <select name="level" class="form-select">
                            <option value="basic" {{ old('level') == 'basic' ? 'selected' : '' }}>Basic</option>
                            <option value="advance" {{ old('level') == 'advance' ? 'selected' : '' }}>Advance</option>
                            <option value="expert" {{ old('level') == 'expert' ? 'selected' : '' }}>Expert</option>
                        </select>
                    </div>

                    {{-- Image Upload --}}
                    <div class="mb-3">
                        <label class="form-label">Course Image</label>
                        <input type="file" name="image_file" id="image_file" accept="image/*" class="form-control">
                    </div>

                    {{-- Preview --}}
                    <div class="text-center">
                        <img id="preview" src="{{ old('image') }}" alt="Preview"
                            style="max-width: 200px; display:none; border-radius:8px; margin-bottom:1rem;">
                    </div>

                    {{-- hidden base64 --}}
                    <input type="hidden" name="image" id="image">

                    {{-- Buttons --}}
                    <div class="d-flex justify-content-between">
                        <a class="btn btn-outline-secondary" href="{{ route('admin.courses.index') }}">Cancel</a>
                        <button class="btn btn-dark" type="submit"
                            style="background-color:#9CDBE2;  border:white;">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // 画像プレビューとBase64変換
        document.getElementById('image_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('image').value = event.target.result;
                const preview = document.getElementById('preview');
                preview.src = event.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    </script>
@endsection
