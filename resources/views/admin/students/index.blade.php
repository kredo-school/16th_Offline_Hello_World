@extends('layouts.app')
@section('title', 'Students')

@section('content')
    <style>
        /* ================== Cool Admin Theme (Students) ================== */
        :root {
            --mint: #75E6DA;
            --mint-2: #57D9C5;
            --ink: #002B36;
            --ink-2: #00343A;
            --bg: #F7FBFC;
            --card: #FFFFFFCC;
            /* glass */
            --stroke: #D9EFF0;
            /* card border */
            --shadow: 0 10px 24px rgba(0, 0, 0, .07), 0 2px 5px rgba(0, 0, 0, .04);
        }

        body {
            background:
                radial-gradient(900px 480px at -10% -20%, #E9FFFA 0, transparent 60%),
                radial-gradient(1000px 520px at 110% 120%, #E6F1FF 0, transparent 55%),
                linear-gradient(180deg, #FAFEFF, #F6FBFF 60%, #F3FBF9);
        }

        /* page title row */
        .page-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .page-head .title {
            font-weight: 800;
            color: var(--ink);
            letter-spacing: .3px;
        }

        /* glass card */
        .glass-card {
            background: var(--card);
            backdrop-filter: blur(6px);
            border: 1.5px solid var(--stroke);
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        /* table header */
        .table thead th {
            background: linear-gradient(90deg, var(--mint), var(--mint-2));
            color: var(--ink-2);
            border: 0 !important;
            font-weight: 800;
            letter-spacing: .6px;
        }

        .table thead th:first-child {
            padding-left: 22px;
        }

        .table tbody td:first-child {
            padding-left: 22px;
        }

        /* row */
        .table tbody tr {
            --row-bg: #ffffff;
            background: var(--row-bg);
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .table tbody tr:not(:last-child) td {
            border-bottom: 1px dashed #E8F4F5;
        }

        .table tbody tr:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
            --row-bg: #F7FFFE;
        }

        /* avatar + name */
        .avatar-ring {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 0 3px #E3FBF7;
            object-fit: cover;
        }

        .name-txt {
            font-weight: 700;
            color: #14383B;
        }

        /* status buttons */
        .btn-pill {
            border-radius: 999px;
            padding: 6px 14px;
            font-weight: 700;
            letter-spacing: .2px;
            box-shadow: inset 0 -2px 0 rgba(0, 0, 0, .08);
        }

        .btn-activate {
            background: #1FB66A;
            border-color: #1FB66A;
            color: #fff;
        }

        .btn-deactivate {
            background: #E74D4D;
            border-color: #E74D4D;
            color: #fff;
        }

        .btn-activate:hover {
            filter: brightness(.95);
        }

        .btn-deactivate:hover {
            filter: brightness(.95);
        }

        /* three-dots (more) */
        .more-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 1px dashed #D9E9EA;
            color: #4B5A5B;
            text-decoration: none;
            transition: .15s;
        }

        .more-link:hover {
            background: #F2FEFD;
            color: #111;
        }

        /* utilities */
        .badge-more {
            font-size: .825rem;
            color: #7b8b8c;
        }

        .searchbar {
            display: flex;
            gap: 8px;
            align-items: center;
            background: #ffffff;
            border: 1.5px solid #E2F2F2;
            border-radius: 999px;
            padding: 6px 12px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .04) inset;
        }

        .searchbar input {
            border: 0;
            outline: none;
            width: 220px;
            background: transparent;
        }

        .add-btn {
            border-radius: 999px;
            font-weight: 800;
            padding: 8px 14px;
            background: linear-gradient(90deg, var(--mint), var(--mint-2));
            border: 0;
            color: #033;
            box-shadow: 0 6px 18px rgba(87, 217, 197, .35);
        }

        .add-btn:hover {
            filter: brightness(.97);
        }
    </style>

    <div class="page-head">
        <h2 class="title m-0">Students</h2>

        {{-- optional actions (ダミーのUI。不要なら削除OK) --}}
        <div class="d-flex align-items-center gap-2">
            <div class="searchbar d-none d-md-flex">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search name or email"
                    oninput="
        const q=this.value.toLowerCase();
        document.querySelectorAll('tbody.data-rows tr').forEach(tr=>{
          tr.style.display = tr.dataset.key.includes(q) ? '' : 'none';
        });">
        </div>
            {{-- <a href="{{ route('admin.students.create') }}" class="btn add-btn d-none d-sm-inline-flex">＋ Add Student</a> --}}
        </div>
    </div>

    <div class="glass-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>NAME</th>
                        <th>EMAIL</th>
                        <th>COURSES</th>
                        <th class="text-end">STATUS</th>
                        <th class="text-end" style="width:60px;">&nbsp;</th>
                    </tr>
                </thead>

                <tbody class="data-rows">
                    @forelse ($students as $row)
                        @php
                            $avatar = $row->avatar ? asset('storage/' . $row->avatar) : asset('images/avatar1.jpg');
                            $firstTwo = $row->courses->take(2);
                            $courseNames = $firstTwo->pluck('title')->join(', ');
                            $extra = max(($row->courses_count ?? 0) - $firstTwo->count(), 0);
                            $rowKey = strtolower(($row->name ?? '') . ' ' . ($row->email ?? ''));
                        @endphp

                        <tr data-key="{{ $rowKey }}">
                            {{-- NAME --}}
                            <td>
                                <a href="{{ route('students.profile.show', $row->id) }}" class="text-decoration-none">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ $avatar }}" alt="avatar" class="avatar-ring">
                                        <div class="d-flex flex-column">
                                            <span class="name-txt">{{ $row->name }}</span>
                                            <span class="text-muted small">ID: {{ $row->id }}</span>
                                        </div>
                                    </div>
                                </a>
                            </td>

                            {{-- EMAIL --}}
                            <td class="text-muted">{{ $row->email }}</td>

                            {{-- COURSES --}}
                            <td>
                                @if ($courseNames)
                                    {{ $courseNames }}
                                    @if ($extra > 0)
                                        <span class="badge-more">+{{ $extra }} more</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- STATUS --}}
                            <td class="text-end">
                                <form action="{{ route('admin.students.toggle', $row) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    @if ($row->status === 'active')
                                        <button type="submit"
                                            class="btn btn-sm btn-deactivate btn-pill">Deactivate</button>
                                    @else
                                        <button type="submit" class="btn btn-sm btn-activate btn-pill">Activate</button>
                                    @endif
                                </form>
                            </td>

                            {{-- EDIT ( … ) --}}
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="more-btn btn btn-sm p-0 border-0 bg-transparent"
                                        data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions">…</button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.students.edit', $row->id) }}"> Edit</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">No students yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if (method_exists($students, 'links'))
        <div class="d-flex justify-content-center mt-3">
            {{ $students->links('pagination::bootstrap-5') }}
        </div>
    @endif
@endsection
