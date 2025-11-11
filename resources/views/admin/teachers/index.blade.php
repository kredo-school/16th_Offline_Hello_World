@extends('layouts.app')
@section('title', 'Teachers')

@section('content')
    <style>
        /* ===== Shared theme (match Students) ===== */
        :root {
            --mint: #75E6DA;
            --mint-2: #57D9C5;
            --ink: #002B36;
            --ink-2: #00343A;
            --card: #FFFFFFCC;
            --stroke: #D9EFF0;
            --shadow: 0 10px 24px rgba(0, 0, 0, .07), 0 2px 5px rgba(0, 0, 0, .04);
        }

        body {
            background:
                radial-gradient(900px 480px at -10% -20%, #E9FFFA 0, transparent 60%),
                radial-gradient(1000px 520px at 110% 120%, #E6F1FF 0, transparent 55%),
                linear-gradient(180deg, #FAFEFF, #F6FBFF 60%, #F3FBF9);
        }

        /* page head */
        .page-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px
        }

        .page-head .title {
            font-weight: 800;
            color: var(--ink);
            letter-spacing: .3px
        }

        /* glass card */
        .glass-card {
            background: var(--card);
            backdrop-filter: blur(6px);
            border: 1.5px solid var(--stroke);
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden
        }

        /* table */
        .table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: linear-gradient(90deg, var(--mint), var(--mint-2));
            color: var(--ink-2);
            border: 0 !important;
            font-weight: 800;
            letter-spacing: .6px;
        }

        .table thead th:first-child {
            padding-left: 22px
        }

        .table tbody td:first-child {
            padding-left: 22px
        }

        .table tbody tr {
            --row-bg: #fff;
            background: var(--row-bg);
            transition: transform .18s, box-shadow .18s, background .18s
        }

        .table tbody tr:not(:last-child) td {
            border-bottom: 1px dashed #E8F4F5
        }

        .table tbody tr:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
            --row-bg: #F7FFFE
        }

        /* avatar & name */
        .avatar-ring {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 0 3px #E3FBF7;
            object-fit: cover
        }

        .name-txt {
            font-weight: 700;
            color: #14383B
        }

        /* chips */
        .chip {
            display: inline-block;
            padding: .2rem .5rem;
            border-radius: 999px;
            border: 1px solid #CFEDEC;
            background: #F6FFFE;
            font-weight: 600;
            font-size: .8rem;
            color: #0f4a4e;
            margin-right: .35rem
        }

        /* status pills */
        .btn-pill {
            border-radius: 999px;
            padding: 6px 14px;
            font-weight: 700;
            letter-spacing: .2px;
            box-shadow: inset 0 -2px 0 rgba(0, 0, 0, .08)
        }

        .btn-activate {
            background: #1FB66A;
            border-color: #1FB66A;
            color: #fff
        }

        .btn-deactivate {
            background: #E74D4D;
            border-color: #E74D4D;
            color: #fff
        }

        .btn-activate:hover,
        .btn-deactivate:hover {
            filter: brightness(.95)
        }

        /* more dropdown */
        .more-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 1px dashed #D9E9EA;
            color: #4B5A5B;
            background: #fff
        }

        .more-btn:hover {
            background: #F2FEFD;
            color: #111
        }

        /* search / add */
        .searchbar {
            display: flex;
            gap: 8px;
            align-items: center;
            background: #fff;
            border: 1.5px solid #E2F2F2;
            border-radius: 999px;
            padding: 6px 12px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .04) inset
        }

        .searchbar input {
            border: 0;
            outline: none;
            width: 220px;
            background: transparent
        }

        .add-btn {
            border-radius: 999px;
            font-weight: 800;
            padding: 8px 14px;
            background: linear-gradient(90deg, var(--mint), var(--mint-2));
            border: 0;
            color: #033;
            box-shadow: 0 6px 18px rgba(87, 217, 197, .35)
        }

        .add-btn:hover {
            filter: brightness(.97)
        }

        .badge-more {
            font-size: .825rem;
            color: #7b8b8c
        }
    </style>

    <div class="page-head">
        <h2 class="title m-0">Teachers</h2>

        <div class="d-flex align-items-center gap-2">
            {{-- クライアントサイド検索（name/email） --}}
            <div class="searchbar d-none d-md-flex" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="text" aria-label="Search teachers by name or email" placeholder="Search name or email"
                    oninput="
        const q=this.value.toLowerCase();
        document.querySelectorAll('tbody.data-rows tr').forEach(tr=>{
          tr.style.display = tr.dataset.key.includes(q) ? '' : 'none';
        });">
            </div>
            <button type="button" class="btn add-btn d-none d-sm-inline-flex" data-bs-toggle="modal"
                data-bs-target="#addTeacherModal">
                ＋ Add
            </button>
        </div>
    </div>

    {{-- flash --}}
    @if (session('success') || session('status'))
        <div class="alert alert-success py-2 rounded-3 shadow-sm">{{ session('success') ?? session('status') }}</div>
    @endif

    <div class="glass-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>NAME</th>
                        <th class="d-none d-md-table-cell">EMAIL</th>
                        <th>COURSES</th>
                        <th class="text-end">STATUS</th>
                        <th class="text-end" style="width:64px;">&nbsp;</th>
                    </tr>
                </thead>
                <tbody class="data-rows">
                    @forelse ($teachers as $t)
                        @php
                            $avatar = $t->avatar ? asset('storage/' . $t->avatar) : asset('images/avatar1.jpg');
                            $firstTwo = $t->coursesTaught?->take(2) ?? collect(); // モデルに合わせて courses / coursesTaught を選択
                            $extraCount = max(($t->courses_taught_count ?? $firstTwo->count()) - $firstTwo->count(), 0);
                            $rowKey = strtolower(($t->name ?? '') . ' ' . ($t->email ?? ''));
                            $isActive = $t->status === 'active';
                        @endphp
                        <tr data-key="{{ $rowKey }}">
                            {{-- NAME --}}
                            <td>
                                <a href="{{ route('teachers.profile', $t->id) }}" class="text-decoration-none">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ $avatar }}" alt="avatar" class="avatar-ring">
                                        <div class="d-flex flex-column">
                                            <span class="name-txt">{{ $t->name }}</span>
                                            <span class="text-muted small">ID: {{ $t->id }}</span>
                                        </div>
                                    </div>
                                </a>
                            </td>

                            {{-- EMAIL --}}
                            <td class="text-muted d-none d-md-table-cell">{{ $t->email }}</td>

                            {{-- COURSES --}}
                            <td>
                                @if ($firstTwo->isNotEmpty())
                                    @foreach ($firstTwo as $c)
                                        <span class="chip">{{ $c->title }}</span>
                                    @endforeach
                                    @if ($extraCount > 0)
                                        <span class="badge-more">+{{ $extraCount }} more</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- STATUS --}}
                            <td class="text-end">
                                <form action="{{ route('admin.teachers.toggle', $t->id) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    @if ($isActive)
                                        <button type="submit"
                                            class="btn btn-sm btn-deactivate btn-pill">Deactivate</button>
                                    @else
                                        <button type="submit" class="btn btn-sm btn-activate btn-pill">Activate</button>
                                    @endif
                                </form>
                            </td>

                            {{-- MORE --}}
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="more-btn" data-bs-toggle="dropdown" aria-expanded="false"
                                        aria-label="More actions">…</button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li><a class="dropdown-item"
                                                href="{{ route('admin.teachers.edit', $t->id) }}">Edit</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">No teachers yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if (method_exists($teachers, 'links'))
        <div class="d-flex justify-content-center mt-3">
            {{ $teachers->links('pagination::bootstrap-5') }}
        </div>
    @endif

    {{-- Add Modal --}}
    {{-- <div class="modal fade" id="addTeacherModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius:16px;border:1.5px solid var(--stroke)">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold text-dark">Add a teacher</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('admin.teachers.store') }}">
        @csrf
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label fw-semibold">Name</label>
            <input name="name" type="text" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input name="email" type="email" class="form-control" required>
          </div>
          <div class="mb-1">
            <label class="form-label fw-semibold">Password</label>
            <input name="password" type="password" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button class="btn add-btn" type="submit">＋ Add</button>
        </div>
      </form>
    </div>
  </div>
</div> --}}
    {{-- Add Modal --}}
    <div class="modal fade" id="addTeacherModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius:16px;border:1.5px solid var(--stroke)">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-dark">Add a teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form method="POST" action="{{ route('admin.teachers.store') }}">
                    @csrf
                    <div class="modal-body pt-0">

                        {{-- Name --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Name</label>
                            <input name="name" type="text" class="form-control" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Courses（複数選択） --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Courses</label>
                            <select name="course_ids[]" class="form-select" multiple size="5">
                                @php $selected = collect(old('course_ids', []))->map(fn($v)=>(int)$v)->all(); @endphp
                                @foreach ($courses as $c)
                                    <option value="{{ $c->id }}"
                                        {{ in_array($c->id, $selected) ? 'selected' : '' }}>
                                        {{ $c->title ?? $c->name }} 
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text"></div>
                            @error('course_ids')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input name="email" type="email" class="form-control" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <input name="password" type="password" class="form-control" required>
                            @error('password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <div class="modal-footer border-0">
                        <button class="btn add-btn" type="submit">＋ Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
