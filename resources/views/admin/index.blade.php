@extends('layouts.app')
@section('title', 'Hello World')

@section('content')
    <style>
        /* ===== 視認性アップ & 枠線強化（完全版） ===== */

        /* 0) ベース色（必要なら調整） */
        :root {
            --ink: #0f172a;
            /* 濃い文字色 */
            --muted: #64748b;
            /* 補助文字 */
            --border: rgba(2, 6, 23, .12);
            --border-strong: rgba(2, 6, 23, .18);
            --white: #fff;

            --stu: #ff3b6a;
            --stu-tint: #ffe2ea;
            --tea: #12d09b;
            --tea-tint: #e2fff2;
            --crs: #5b7aff;
            --crs-tint: #e3e8ff;
        }

        /* 1) ページ背景（軽めの色×グラデ） */
        body {
            background:
                radial-gradient(800px 420px at -10% -10%, #ffe3ea 0, transparent 55%),
                radial-gradient(900px 520px at 110% -15%, #e6fff4 0, transparent 60%),
                radial-gradient(900px 520px at 110% 110%, #e6ecff 0, transparent 55%),
                linear-gradient(135deg, #fce0e7 0, #e2fff4 40%, #e3e8ff 100%) !important;
            color: var(--ink);
        }

        /* 2) 共通ユーティリティ */
        .muted {
            color: var(--muted);
        }

        .truncate-1 {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .list-compact .list-group-item {
            padding: .55rem .75rem;
        }

        .title-row {
            gap: .6rem;
        }

        .count-pill {
            margin-left: auto;
            padding: .1rem .5rem;
            font-weight: 600;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: #fff;
            color: #334155;
        }

        .view-more {
            display: inline-block;
            padding: .35rem .75rem;
            border-radius: 10px;
            border: 1px solid var(--border);
            text-decoration: none;
            font-weight: 600;
        }

        .dot {
            font-size: 1.35rem;
            line-height: 1;
        }

        /* 3) 枠カード（外側もしっかり枠線） */
        .frame-card {
            border: 1px solid var(--border-strong);
            border-radius: 16px;
            background: var(--white);
            box-shadow: 0 8px 18px rgba(0, 0, 0, .05);
            transition: .18s ease;
        }

        .frame-card:hover {
            transform: translateY(-2px);
        }

        .frame-card>.inner {
            border-top: 1px solid var(--border);
            border-radius: inherit;
            padding: 14px 14px 12px 14px;
            background: linear-gradient(180deg, rgba(255, 255, 255, .96), rgba(255, 255, 255, 1));
        }

        /* セクション別の淡い背色＆影色 */
        .frame-stu>.inner {
            background: linear-gradient(180deg, rgba(255, 107, 129, .08), #fff 65%);
        }

        .frame-tea>.inner {
            background: linear-gradient(180deg, rgba(18, 208, 155, .10), #fff 65%);
        }

        .frame-crs>.inner {
            background: linear-gradient(180deg, rgba(91, 122, 255, .10), #fff 65%);
        }

        .frame-stu {
            box-shadow: 0 10px 22px rgba(255, 59, 106, .16), 0 6px 12px rgba(0, 0, 0, .04);
        }

        .frame-tea {
            box-shadow: 0 10px 22px rgba(18, 208, 155, .16), 0 6px 12px rgba(0, 0, 0, .04);
        }

        .frame-crs {
            box-shadow: 0 10px 22px rgba(91, 122, 255, .18), 0 6px 12px rgba(0, 0, 0, .04);
        }

        /* タイトル色 */
        .frame-stu h4 {
            color: var(--stu)
        }

        .frame-tea h4 {
            color: var(--tea)
        }

        .frame-crs h4 {
            color: var(--crs)
        }

        /* 4) リスト：行の区切り線＋交互の淡色帯 */
        .list-group-flush .list-group-item {
            border: 0;
            border-top: 1px dashed var(--border);
            background: transparent;
        }

        .list-group-flush .list-group-item:first-child {
            border-top: 0;
        }

        .frame-stu .list-group-item:nth-child(odd) {
            background: linear-gradient(90deg, rgba(255, 59, 106, .05), transparent);
        }

        .frame-tea .list-group-item:nth-child(odd) {
            background: linear-gradient(90deg, rgba(18, 208, 155, .06), transparent);
        }

        .frame-crs .list-group-item:nth-child(odd) {
            background: linear-gradient(90deg, rgba(91, 122, 255, .06), transparent);
        }

        /* 編集「…」リンクのホバー枠 */
        .item-actions .btn {
            border: 1px solid transparent !important;
            border-radius: 8px;
        }

        .item-actions .btn:hover {
            border-color: var(--border-strong) !important;
            background: #fff !important;
        }

        /* 5) 下段パネル（Recent / Quick / Summary） */
        .panel {
            background: linear-gradient(90deg, #ffd7e1, #c9ffe9, #cad4ff);
        }

        .panel>.inner {
            background: linear-gradient(180deg, rgba(255, 255, 255, .92), rgba(255, 255, 255, .98));
        }

        /* タイムライン（左に点線＋各行に枠） */
        .timeline {
            position: relative;
            padding-left: 18px;
        }

        .timeline::before {
            content: "";
            position: absolute;
            inset: 0 0 0 8px;
            background: repeating-linear-gradient(to bottom,
                    rgba(148, 163, 184, .35) 0 10px,
                    rgba(148, 163, 184, .15) 10px 20px);
            width: 2px;
            border-radius: 2px;
        }

        .tl {
            position: relative;
            margin-bottom: .65rem;
            padding: .45rem .6rem .45rem .75rem;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .03);
        }

        .tl::before {
            content: "";
            position: absolute;
            left: -12px;
            top: 12px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #94a3b8;
            border: 1px solid #fff;
            box-shadow: 0 0 0 2px #e2e8f0;
        }

        .tl-stu::before {
            background: var(--stu);
        }

        .tl-tea::before {
            background: var(--tea);
        }

        .tl-crs::before {
            background: var(--crs);
        }

        /* 6) CTAボタン：枠＋フォーカスリング */
        .btn-cta {
            display: block;
            text-align: center;
            padding: .55rem .8rem;
            font-weight: 700;
            border-radius: 12px;
            border: 1px solid var(--border-strong);
            text-decoration: none;
            background: #fff;
        }

        .cta-stu {
            box-shadow: 0 8px 16px rgba(255, 59, 106, .16);
        }

        .cta-tea {
            box-shadow: 0 8px 16px rgba(18, 208, 155, .16);
        }

        .cta-crs {
            box-shadow: 0 8px 16px rgba(91, 122, 255, .18);
        }

        .btn-cta:focus-visible {
            outline: 3px solid rgba(41, 82, 255, .35);
            outline-offset: 2px;
        }

        /* 7) サマリーメーター：枠付き */
        .meter-wrap {
            height: 8px;
            border: 1px solid var(--border-strong);
            border-radius: 999px;
            background: linear-gradient(90deg, #ffe9ef, #edfff7, #eef1ff);
            overflow: hidden;
        }

        .meter {
            height: 100%;
            border-radius: inherit;
        }

        .meter-stu {
            background: linear-gradient(90deg, var(--stu), #ffa3b6);
        }

        .meter-tea {
            background: linear-gradient(90deg, var(--tea), #9ff0d4);
        }

        .meter-crs {
            background: linear-gradient(90deg, var(--crs), #b8c4ff);
        }

        /* 8) サイドバーをカラフルにしたい場合（任意） */
        .sidebar-colorful {
            background: linear-gradient(180deg, #c7f3ff 0, #c8ffe9 60%, #d9ddff 100%);
            border-right: 1px solid var(--border);
        }
    </style>

    {{-- ===== 上段：3カード ===== --}}
    <div class="row g-4">
        {{-- Students --}}
        <div class="col-md-4">
            <div class="frame-card frame-stu">
                <div class="inner">
                    <div class="d-flex align-items-center title-row mb-2">
                        <span class="rounded-3 d-inline-flex align-items-center justify-content-center"
                            style="width:34px;height:34px;background:var(--stu-tint);">👥</span>
                        <h4 class="m-0">Students</h4>
                        <span class="count-pill">{{ $studentsCount }}</span>
                    </div>

                    <ul class="list-group list-group-flush list-compact">
                        @forelse(($latestStudents ?? collect())->take(5) as $s)
                            <li class="list-group-item d-flex align-items-center justify-content-between">
                                <a href="{{ route('students.profile.show', $s->id) }}"
                                    class="fw-semibold text-dark text-decoration-none truncate-1">
                                    {{ $s->name ?? 'User Name' }}
                                </a>
                                <span class="item-actions">
                                    <a href="{{ route('admin.students.edit', $s->id) }}"
                                        class="btn btn-sm p-0 border-0 shadow-none text-dark"
                                        style="background:transparent;">
                                        <span class="dot">&hellip;</span>
                                    </a>
                                </span>
                            </li>
                        @empty
                            <li class="list-group-item text-center muted">No students yet</li>
                        @endforelse
                    </ul>

                    <div class="text-center mt-2 mb-1">
                        <a href="{{ route('admin.students.index') }}" class="view-more"
                            style="background:#fff5f7;border-color:#ffb2c2;color:var(--stu);">View More</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Teachers --}}
        <div class="col-md-4">
            <div class="frame-card frame-tea">
                <div class="inner">
                    <div class="d-flex align-items-center title-row mb-2">
                        <span class="rounded-3 d-inline-flex align-items-center justify-content-center"
                            style="width:34px;height:34px;background:var(--tea-tint);">👨‍🏫</span>
                        <h4 class="m-0">Teachers</h4>
                        <span class="count-pill">{{ $teachersCount }}</span>
                    </div>

                    <ul class="list-group list-group-flush list-compact">
                        @forelse(($latestTeachers ?? collect())->take(5) as $t)
                            <li class="list-group-item d-flex align-items-center justify-content-between">
                                <a href="{{ route('teachers.profile', $t->id) }}"
                                    class="fw-semibold text-dark text-decoration-none truncate-1">
                                    {{ $t->name ?? 'User Name' }}
                                </a>
                                <span class="item-actions">
                                    <a href="{{ route('admin.teachers.edit', $t->id) }}"
                                        class="btn btn-sm p-0 border-0 shadow-none text-dark"
                                        style="background:transparent;">
                                        <span class="dot">&hellip;</span>
                                    </a>
                                </span>
                            </li>
                        @empty
                            <li class="list-group-item text-center muted">No teachers yet</li>
                        @endforelse
                    </ul>

                    <div class="text-center mt-2 mb-1">
                        <a href="{{ route('admin.teachers.index') }}" class="view-more"
                            style="background:#effff8;border-color:#9af7cf;color:#0f9d74;">View More</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Courses --}}
        <div class="col-md-4">
            <div class="frame-card frame-crs">
                <div class="inner">
                    <div class="d-flex align-items-center title-row mb-2">
                        <span class="rounded-3 d-inline-flex align-items-center justify-content-center"
                            style="width:34px;height:34px;background:var(--crs-tint);">📚</span>
                        <h4 class="m-0">Courses</h4>
                        <span class="count-pill">{{ $coursesCount }}</span>
                    </div>

                    <ul class="list-group list-group-flush list-compact">
                        @forelse(($latestCourses ?? collect())->take(5) as $course)
                            <li class="list-group-item d-flex align-items-center justify-content-between">
                                <span class="fw-semibold truncate-1">{{ $course->title ?? 'Course' }}</span>
                                <span class="item-actions">
                                    <a href="{{ route('admin.courses.edit', $course->id) }}"
                                        class="btn btn-sm p-0 border-0 shadow-none text-dark"
                                        style="background:transparent;">
                                        <span class="dot">&hellip;</span>
                                    </a>
                                </span>
                            </li>
                        @empty
                            <li class="list-group-item text-center muted">No courses yet</li>
                        @endforelse
                    </ul>

                    <div class="text-center mt-2 mb-1">
                        <a href="{{ route('admin.courses.index') }}" class="view-more"
                            style="background:#f2f5ff;border-color:#b2c3ff;color:#3758ff;">View More</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== 下段：Recent / Quick / Summary ===== --}}
    {{-- @php
  $recentActivities = $recentActivities ?? [
    ['type'=>'student','text'=>'New student registered: User10','time'=>'2 min ago'],
    ['type'=>'teacher','text'=>'Teacher profile updated: User15','time'=>'1 hour ago'],
    ['type'=>'course','text'=>'Course published: Basic Python','time'=>'Yesterday'],
  ];
  $activeStudentRate = isset($activeStudents,$studentsCount)&&$studentsCount?round($activeStudents/$studentsCount*100):80;
  $activeTeacherRate = isset($activeTeachers,$teachersCount)&&$teachersCount?round($activeTeachers/$teachersCount*100):90;
  $activeCourseRate  = isset($activeCourses,$coursesCount)&&$coursesCount?round($activeCourses/$coursesCount*100):70;
@endphp --}}

    {{-- <div class="row g-4 mt-2"> --}}
    {{-- Recent Activity --}}
    {{-- <div class="col-lg-8">
    <div class="frame-card panel">
      <div class="inner">
        <div class="d-flex align-items-center mb-2">
          <h5 class="m-0">Recent Activity</h5>
          <span class="ms-2 badge text-bg-light border">last 24h</span>
        </div>

        <div class="timeline">
          @foreach ($recentActivities as $a)
            @php $cls = $a['type']==='student'?'tl-stu':($a['type']==='teacher'?'tl-tea':'tl-crs'); @endphp
            <div class="tl {{ $cls }}">
              <div class="fw-semibold">{{ $a['text'] }}</div>
              <div class="small muted">{{ $a['time'] ?? '' }}</div>
            </div>
          @endforeach
        </div>

        <div class="text-end">
          <a href="{{ route('admin.students.index') }}" class="text-decoration-none fw-semibold">See all logs →</a>
        </div>
      </div>
    </div>
  </div> --}}

    {{-- Quick + Summary --}}
    {{-- <div class="col-lg-4">
    <div class="frame-card panel mb-3">
      <div class="inner">
        <h5 class="mb-3">Quick Actions</h5>
        <div class="d-grid gap-2">
          <a href="{{ route('admin.students.create') }}" class="btn-cta cta-stu">＋ Add Student</a>
          <a href="{{ route('admin.teachers.create') }}" class="btn-cta cta-tea">＋ Add Teacher</a>
          <a href="{{ route('admin.courses.create') }}" class="btn-cta cta-crs">＋ Add Course</a>
        </div>
      </div>
    </div> --}}

    {{-- <div class="frame-card panel">
      <div class="inner">
        <h5 class="mb-3">Summary</h5>

        <div class="mb-3">
          <div class="d-flex justify-content-between">
            <span class="fw-semibold">Active Students</span>
            <span class="fw-semibold">{{ $activeStudentRate }}%</span>
          </div>
          <div class="meter-wrap"><div class="meter meter-stu" style="width:{{ $activeStudentRate }}%"></div></div>
        </div>

        <div class="mb-3">
          <div class="d-flex justify-content-between">
            <span class="fw-semibold">Active Teachers</span>
            <span class="fw-semibold">{{ $activeTeacherRate }}%</span>
          </div>
          <div class="meter-wrap"><div class="meter meter-tea" style="width:{{ $activeTeacherRate }}%"></div></div>
        </div>

        <div>
          <div class="d-flex justify-content-between">
            <span class="fw-semibold">Active Courses</span>
            <span class="fw-semibold">{{ $activeCourseRate }}%</span>
          </div>
          <div class="meter-wrap"><div class="meter meter-crs" style="width:{{ $activeCourseRate }}%"></div></div>
        </div>
      </div>
    </div>
  </div>
</div> --}}
@endsection
