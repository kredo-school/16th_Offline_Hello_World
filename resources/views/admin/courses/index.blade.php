@extends('layouts.app')
@section('title', 'Courses')

@section('content')
<style>
  /* ========= Design tokens ========= */
  :root{
    --ink:#0f172a;              /* 基本文字 */
    --muted:#64748b;            /* 補助文字 */
    --ok:#16a34a;               /* Active */
    --ng:#dc2626;               /* Deactivate */
    --brand:#05445E;            /* ボタン色 */
    --ring:rgba(15,23,42,.10);  /* 枠線 */
    --radius:14px;
    --fs-title:20px;            /* 見出し */
    --fs-body:15px;             /* 本文 */
    --lh:1.45;
  }

  /* ベース排版（読みやすさ） */
  .typo-body{
    font-size:var(--fs-body);
    line-height:var(--lh);
    color:var(--ink);
    letter-spacing:.01em;
  }
  .fw-900{ font-weight:900; }
  .chip{ padding:.28rem .7rem; border-radius:999px; font-weight:700; border:1px solid var(--ring); background:#fff; }

  /* ヘッダー：柔らかい多色グラデ＋影 */
  .page-head{
    background:
      radial-gradient(700px 260px at -15% -40%, #ffe8f0 0, transparent 60%),
      radial-gradient(700px 260px at 120% -20%, #e8f7ff 0, transparent 60%),
      linear-gradient(100deg,#fff 0,#f7fbff 40%,#fff6f0 100%);
    border:1px solid var(--ring);
    box-shadow:0 10px 26px rgba(15,23,42,.08);
    border-radius:18px;
  }

  /* リスト見出し帯：左右端まで通す */
  .list-head{
    background:linear-gradient(90deg,#fff4cc 0,#e0f7ff 50%,#ffe8f1 100%);
    border:1px solid var(--ring);
    border-radius:12px;
    color:#0b2030;
    font-weight:800;
    letter-spacing:.06em;
  }

  /* ========= Course card ========= */
  .course-item{
    --accent:#9AE6B4;         /* 行ごとの色（下で上書き） */
    --accent-strong:#10B981;
    --accent-weak:#F0FFF4;

    border:1px solid color-mix(in srgb, var(--accent-strong) 22%, #0000);
    border-radius:var(--radius) !important;
    background:
      linear-gradient(90deg, color-mix(in srgb, var(--accent) 22%, #fff) 0,
                             #fff 22% 100%);
    box-shadow:0 8px 22px color-mix(in srgb, var(--accent-strong) 12%, #0000);
    overflow:hidden;
  }
  .course-item:hover{ box-shadow:0 14px 36px color-mix(in srgb, var(--accent-strong) 18%, #0000); }

  .color-bar{ width:6px; background:linear-gradient(180deg, var(--accent), var(--accent-strong)); }

  /* アイコン・タイトル・列幅を固定して整列 */
  .avatar-56{ width:56px; height:56px; border-radius:50%; object-fit:cover;
    box-shadow:0 0 0 3px color-mix(in srgb, var(--accent) 55%, #fff); flex:0 0 56px; }
  .col-photo{ width:56px; }
  .col-status{ width:180px; }
  .col-action{ width:180px; }

  .course-title{
    font-size:var(--fs-title);
    font-weight:800;
    color:#0b2030;
    text-decoration:none;
    line-height:1.25;
    max-width:100%;
  }

  /* Toggle buttons（高さ・文字揃え統一） */
  .btn-toggle{
    border:1px solid color-mix(in srgb, var(--accent-strong) 40%, #0000);
    background:color-mix(in srgb, var(--accent-weak) 60%, #fff);
    color:var(--accent-strong);
    font-weight:800;
    padding:.36rem .8rem;
    height:34px;
  }
  .btn-toggle.ng{
    border-color: color-mix(in srgb, #ef4444 46%, #0000);
    color:#ef4444;
    background: color-mix(in srgb, #fee2e2 62%, #fff);
  }

  /* Pill（ステータス） */
  .pill{ border:1px solid; border-radius:999px; font-weight:800; padding:.2rem .6rem; }
  .pill-active{ color:var(--ok); border-color:var(--ok); background:#f0fdf4; }
  .pill-inactive{ color:#475569; border-color:#94a3b8; background:#f8fafc; }

  /* ========= Topics table ========= */
  .topics thead th{
    background:color-mix(in srgb, var(--accent) 38%, #fff);
    color:#0b2030; font-weight:900;
    border-bottom:1px solid color-mix(in srgb, var(--accent-strong) 28%, #0000);
    position:sticky; top:0; z-index:1;
  }
  .topics tbody tr:nth-child(odd){ background:color-mix(in srgb, var(--accent-weak) 65%, #fff); }
  .topics tbody tr:hover{ background:color-mix(in srgb, var(--accent) 28%, #fff); }

  /* ========= Responsive ========= */
  @media (max-width: 576px){
    .course-title{ font-size:17px; }
    .col-status,.col-action{ width:auto; }
    .w-sm-100{ width:100% !important; }
  }

  /* focusリングの視認性UP */
  .btn:focus,.accordion-button:focus{ box-shadow:0 0 0 .25rem rgba(5,68,94,.18) !important; }
</style>

<div class="container-fluid py-3 typo-body">
  {{-- Header --}}
  <div class="page-head px-3 px-md-4 py-3 mb-3 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <h2 class="m-0 fw-900">Courses</h2>
      <span class="chip">{{ $courses->count() }} total</span>
    </div>
    <a href="{{ route('admin.courses.create') }}" class="btn fw-bold rounded-pill px-3 text-white"
       style="background:var(--brand); border-color:var(--brand)">＋ Add a course</a>
  </div>

  {{-- List head --}}
  <div class="row g-0 px-3 py-2 mb-2 text-uppercase small align-items-center list-head">
    <div class="col-auto col-photo">Photo</div>
    <div class="col">Name</div>
    <div class="col-auto col-status">Status</div>
  </div>

  {{-- Body --}}
  <div class="accordion" id="coursesAccordion">
    @php
      $pal = [
        ['#93C5FD','#2563EB','#EFF6FF'], // Blue
        ['#FCA5A5','#EF4444','#FEF2F2'], // Red
        ['#A7F3D0','#10B981','#ECFDF5'], // Emerald
        ['#FDE68A','#D97706','#FFF7E6'], // Amber
        ['#C7D2FE','#4F46E5','#EEF2FF'], // Indigo
        ['#67E8F9','#0891B2','#ECFEFF'], // Cyan
        ['#F5D0FE','#9333EA','#FAE8FF'], // Violet
      ];
    @endphp

    @forelse ($courses as $course)
      @php
        $i = $loop->index % count($pal);
        [$ACC,$ACC_STRONG,$ACC_WEAK] = $pal[$i];
        $src = $course->image ? asset('storage/'.$course->image) : asset('images/default-course.png');
        $isOpen = request('open') == $course->id;
        $isCourseActive = (int)($course->status ?? $course->is_active) === 1;
      @endphp

      <div class="accordion-item course-item mb-3"
           style="--accent: {{ $ACC }}; --accent-strong: {{ $ACC_STRONG }}; --accent-weak: {{ $ACC_WEAK }};">
        <h2 class="accordion-header" id="heading-{{ $course->id }}">
          <div class="accordion-button {{ $isOpen ? '' : 'collapsed' }} bg-white py-0 px-0">

            <div class="d-flex align-items-stretch w-100">
              <div class="color-bar"></div>

              <div class="w-100 px-3 py-2 d-flex align-items-center gap-3 flex-wrap flex-md-nowrap"
                   data-bs-toggle="collapse"
                   data-bs-target="#collapse-{{ $course->id }}"
                   aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                   aria-controls="collapse-{{ $course->id }}">

                <img src="{{ $src }}" class="avatar-56" alt="{{ $course->title }}">

                <a href="{{ route('admin.courses.show', $course->id) }}"
                   class="course-title text-truncate flex-grow-1"
                   onclick="event.stopPropagation();">
                  {{ $course->title }}
                </a>

                {{-- Toggle --}}
                <form method="POST" action="{{ route('admin.courses.toggle', $course->id) }}"
                      class="ms-auto" onclick="event.stopPropagation();">
                  @csrf @method('PATCH')
                  @if($isCourseActive)
                    <button class="btn btn-sm btn-toggle ng">Deactivate</button>
                  @else
                    <button class="btn btn-sm btn-toggle">Activate</button>
                  @endif
                </form>

                {{-- Status --}}
                <div class="d-flex align-items-center gap-2 ms-2 flex-shrink-0">
                  @if($isCourseActive)
                    <span class="text-success">●</span>
                    <span class="pill pill-active">Active</span>
                  @else
                    <span class="text-secondary">●</span>
                    <span class="pill pill-inactive">Inactive</span>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </h2>

        {{-- Topics --}}
        <div id="collapse-{{ $course->id }}"
             class="accordion-collapse collapse {{ $isOpen ? 'show' : '' }}"
             aria-labelledby="heading-{{ $course->id }}"
             data-bs-parent="#coursesAccordion">
          <div class="accordion-body bg-white border-top">
            <div class="table-responsive">
              <table class="table align-middle mb-0 topics">
                <thead>
                <tr>
                  <th class="ps-4">Topic</th>
                  <th class="col-status">Status</th>
                  <th class="text-end col-action">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($course->topics as $topic)
                  @php $isTopicActive = (int) $topic->status === 1; @endphp
                  <tr>
                    <td class="ps-4 fw-semibold w-50">{{ $topic->name ?? ($topic->title ?? 'Topic #'.$topic->id) }}</td>
                    <td>
                      @if($isTopicActive)
                        <span class="text-success me-1">●</span>
                        <span class="pill pill-active">Active</span>
                      @else
                        <span class="text-secondary me-1">●</span>
                        <span class="pill pill-inactive">Inactive</span>
                      @endif
                    </td>
                    <td class="text-end">
                      <form method="POST" action="{{ route('admin.topics.toggle', $topic->id) }}" class="d-inline">
                        @csrf @method('PATCH')
                        @if($isTopicActive)
                          <button class="btn btn-sm btn-toggle ng">Deactivate</button>
                        @else
                          <button class="btn btn-sm btn-toggle">Activate</button>
                        @endif
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="3" class="text-center text-muted py-3">No topics yet.</td></tr>
                @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="alert alert-light border text-center py-4">No courses yet.</div>
    @endforelse
  </div>
</div>
@endsection
