@extends('layouts.app')
@section('title', 'Lesson history')

@section('content')
    <section class="container py-4">
        {{-- タイトル --}}
        <div class="d-flex align-items-baseline gap-2 mb-3">
            @if ($teacher->id === Auth::id())
                <h1 class="h4 mb-0">Your lesson history</h1>
            @else
                <h1 class="h4 mb-0">{{ $teacher->name }}'s lesson history</h1>
            @endif
        </div>

        {{-- フィルタフォーム --}}
        <form id="lesson-filter-form" method="GET" class="row g-2 mb-3 align-items-end" style="max-width: 720px;">
            {{-- Course --}}
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted">Course</label>
                <select name="course_id" class="form-select js-auto-filter">
                    <option value="">All courses</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}" @selected(($courseId ?? null) == $c->id)>
                            {{ $c->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Student --}}
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted">Student</label>
                <select name="student_id" class="form-select js-auto-filter">
                    <option value="">All students</option>
                    @foreach ($students as $s)
                        <option value="{{ $s->id }}" @selected(($studentId ?? null) == $s->id)>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Reset --}}
            <div class="col-12 col-md-4 d-flex gap-2">
                <a href="{{ route('teachers.lessonhistory') }}" class="btn btn-outline-secondary">
                    Reset
                </a>
            </div>
        </form>

        {{-- Ajax差し替え領域 --}}
        <div id="lesson-list">
            @include('teacher.partials.lesson-history-list', ['bookings' => $bookings])
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('lesson-filter-form');
            const list = document.getElementById('lesson-list');

            if (!form || !list) return;

            function buildUrl() {
                const params = new URLSearchParams(new FormData(form));
                const base = "{{ route('teachers.lessonhistory') }}";
                const q = params.toString();
                return q ? `${base}?${q}` : base;
            }

            async function loadList(url) {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const html = await res.text();
                list.innerHTML = html;
                window.history.pushState({}, '', url);
                bindPagination();
            }

            function bindAutoFilter() {
                const selects = form.querySelectorAll('.js-auto-filter');
                selects.forEach(select => {
                    select.addEventListener('change', () => {
                        loadList(buildUrl());
                    });
                });
            }

            function bindPagination() {
                const links = list.querySelectorAll('.pagination a');
                links.forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const url = link.getAttribute('href');
                        if (!url) return;
                        loadList(url);
                    });
                });
            }

            bindAutoFilter();
            bindPagination();
        });
    </script>
@endpush