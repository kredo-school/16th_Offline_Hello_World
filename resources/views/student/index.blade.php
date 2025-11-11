@extends('layouts.app')

@section('title', 'Home')

@section('content')
    {{-- ===== Up next (date & time unified) ===== --}}
    <section class="container py-3">
        <h2 class="h4 mb-2">Up next</h2>

        @if ($upNext)
            @php
                $tz = config('app.timezone', 'Asia/Manila');

                // ▼ date と time を必ず文字列に正規化
                $rawDate = $upNext->getAttribute('date');
                $dateStr = $rawDate instanceof \Carbon\Carbon ? $rawDate->format('Y-m-d') : (string) $rawDate;

                $rawTime = $upNext->getAttribute('time');
                $timeStr = $rawTime instanceof \Carbon\Carbon ? $rawTime->format('H:i:s') : (string) $rawTime;
                if (preg_match('/^\d{2}:\d{2}$/', $timeStr)) {
                    $timeStr .= ':00';
                } // 'HH:MM' → 'HH:MM:SS'

                // ▼ ここで初めて結合
                $dt = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', "$dateStr $timeStr", $tz);

                $duration = $upNext->duration_minutes ?? 50;
                $end = (clone $dt)->addMinutes($duration);

                $course = $upNext->course->title ?? 'Course Name';
                $topic = $upNext->topic->name ?? 'Topic Name';
                $teacherModel = $upNext->teacher; // モデル（null の可能性あり）
                $teacherName = $teacherModel->name ?? 'Teacher';
                $meetingUrl = $teacherModel->meeting_url;
                $courseId = $upNext->course->id ?? null;
                $teacherId = $teacherModel->id ?? null;
                // $iconUrl = $upNext->course->image_url ?? asset('images/placeholder-course.png');
                $iconUrl =
                    $upNext->course && $upNext->course->image_url
                        ? asset('storage/' . ltrim($upNext->course->image_url, '/'))
                        : asset('images/placeholder-course.png');

                $whenStr = $dt->format('D, M j H:i') . '–' . $end->format('H:i');
                $isToday = $dt->isToday();

                $startTsMs = $dt->getTimestamp() * 1000;
            @endphp

            <div class="card shadow-sm">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center gap-3 flex-wrap">

                        {{-- 左：アイコン --}}

                        <img src="{{ $iconUrl }}" alt="" class="rounded-3 border flex-shrink-0"
                            style="width:48px;height:48px;object-fit:cover;">

                        {{-- 中央：タイトル（1行）＋メタ（1行） --}}
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold text-truncate">
                                @if ($courseId)
                                    <a href="{{ route('courses.show', ['course' => $courseId]) }}"
                                        class="text-dark text-decoration-none">
                                        {{ $course }}
                                    </a>
                                @else
                                    {{ $course }}
                                @endif
                                <span class="text-body-secondary">/</span>
                                {{ $topic }}
                            </div>

                            <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                                {{-- とき（日時を一体化） --}}
                                <span class="d-inline-flex align-items-center">
                                    <i class="fa-regular fa-calendar me-1"></i>{{ $whenStr }}
                                    @if ($isToday)
                                        <span class="badge text-bg-success ms-2">Today</span>
                                    @endif
                                </span>
                                {{-- Teacher --}}
                                <span>
                                    with Teacher
                                    @if ($teacherId)
                                        <a href="{{ route('teachers.profile', ['user_id' => $teacherId]) }}"
                                            class="text-dark text-decoration-none">
                                            <strong>{{ $teacherName }}</strong>
                                        </a>
                                    @else
                                        <span class="text-body"><strong>{{ $teacherName }}</strong></span>
                                    @endif
                                </span>

                                {{-- <span>•</span> --}}

                                {{-- <span>•</span> --}}

                                {{-- カウントダウン --}}
                                {{-- <span id="upnext-countdown" class="badge text-bg-secondary" aria-live="polite"
                                    data-start-ts="{{ $startTsMs }}"></span> --}}
                            </div>
                        </div>

                        {{-- Right: actions --}}
                        <div class="d-flex gap-2 ms-auto">
                            @if ($meetingUrl)
                                <a href="{{ $meetingUrl }}" target="_blank" rel="noopener"
                                    class="btn btn-primary btn-sm px-3">Enter classroom</a>
                            @else
                                <button type="button" class="btn btn-secondary btn-sm px-3" disabled>No meeting
                                    URL</button>
                            @endif

                            {{-- Cancel (DELETE with confirmation) --}}
                            <form method="POST" action="{{ route('students.bookings.cancel', $upNext->id) }}"
                                onsubmit="return confirm('Cancel this booking?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm px-3">Cancel a lesson</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div
                class="alert shadow-sm alert-light border d-flex align-items-center justify-content-between py-2 px-3 mb-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-regular fa-calendar-plus text-secondary"></i>
                    <span>No upcoming bookings</span>
                </div>
            </div>
        @endif
    </section>

    @push('scripts')
        <script>
            (function() {
                const el = document.getElementById('upnext-countdown');
                if (!el) return;

                // ← 文字列ISOではなく、ミリ秒の数値を使う
                const startAtMs = Number(el.dataset.startTs);

                function render() {
                    const diff = startAtMs - Date.now(); // どのTZでも正しい差分になる

                    if (diff <= -60 * 1000) {
                        el.textContent = 'Live';
                        el.classList.remove('text-bg-secondary');
                        el.classList.add('text-bg-danger');
                        return;
                    }
                    if (diff <= 0) {
                        el.textContent = 'Starting';
                        el.classList.remove('text-bg-secondary');
                        el.classList.add('text-bg-warning');
                        return;
                    }

                    const sec = Math.floor(diff / 1000);
                    const d = Math.floor(sec / 86400);
                    const h = Math.floor((sec % 86400) / 3600);
                    const m = Math.floor((sec % 3600) / 60);

                    let txt = 'in ';
                    if (d) txt += d + 'd ';
                    if (h) txt += h + 'h ';
                    txt += m + 'm';
                    el.textContent = txt.trim();
                }

                render();
                setInterval(render, 60 * 1000);
            })();
        </script>
    @endpush
    <section class="container py-2">
        <div class="row gx-4 gy-1">

            {{-- ▼ ここを新規追加：col-12 のレジェンド（先頭に置く） --}}
            <div class="col-12 mb-0">
                <div id="fcLegendStatic" class="d-flex flex-wrap justify-content-end gap-2 mb-0">
                    <span class="legend-item"><span class="legend-dot" style="--c:#d63384"></span> Scheduled</span>
                    <span class="legend-item"><span class="legend-dot" style="--c:#111827"></span> Teacher-canceled</span>
                    <span class="legend-item"><span class="legend-dot" style="--c:#16a34a"></span> Report submitted</span>
                    <span class="legend-item"><span class="legend-dot" style="--c:#6c757d"></span> Report pending</span>
                </div>
            </div>

            <!-- Book a class -->
            <div class="col-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header h5">
                        Book a class
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('students.bookings.store') }}" id="bookingForm">
                            @csrf

                            {{-- <div class="row"> --}}
                            {{-- Course --}}
                            {{-- <div class="mb-3 col-6"> --}}
                            <div class="mb-2">
                                <label for="course_id" class="form-label fw-semibold">Course</label>
                                <select name="course_id" id="course_id" class="form-select form-select-sm" required>
                                    <option value="" disabled {{ old('course_id') ? '' : 'selected' }}>Choose a
                                        course
                                    </option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}"
                                            {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                            {{ $course->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Topic --}}
                            {{-- <div class="mb-3 col-6"> --}}
                            <div class="mb-2">
                                <label for="topic_id" class="form-label fw-semibold">Topic</label>
                                <span id="topicHelp" class="form-text d-none">Next topic set automatically.
                                </span>
                                <select class="form-select form-select-sm" name="topic_id" id="topic_id" required disabled>
                                    <option value="" disabled selected>Select a topic</option>
                                </select>

                            </div>


                            {{-- </div> --}}

                            {{-- Date（空きスロットの日付） --}}
                            {{-- <div class="row"> --}}
                            {{-- <div class="col-6 mb-3"> --}}
                            <div class="mb-2">
                                <label for="date" class="form-label fw-semibold">Date</label>
                                {{-- ▼ 空きスロットが無い場合のメッセージ --}}
                                <span id="noSlotMessage" class="form-text text-danger d-none fw-semibold">
                                    No available slots for this course.
                                </span>
                                <select class="form-select form-select-sm" id="date" required disabled>
                                    <option value="" disabled selected>Select a date</option>
                                </select>
                            </div>

                            {{-- Time（選んだ日付のスロット + 教師名） --}}
                            {{-- <div class="col-6 mb-3"> --}}
                            <div class="mb-2">
                                <label for="time" class="form-label fw-semibold">Time</label>
                                <select class="form-select form-select-sm" id="time" required disabled>
                                    <option value="" disabled selected>Select a time</option>
                                </select>
                            </div>
                            {{-- </div> --}}

                            {{-- <div class="row"> --}}
                            {{-- ▼ 先生選択アクション（常に表示） --}}
                            <div id="teacherActions" class="">
                                <label class="form-label fw-semibold d-block mb-2">Teacher</label>
                                <div class="d-flex gap-2 align-items-center">
                                    {{-- デフォルトで "Automatically assigned" 状態 --}}
                                    <button type="button" id="btnRandom" class="btn btn-secondary btn-sm text-white">
                                        Automatically assigned
                                    </button>
                                    <button type="button" id="btnChoose" class="btn btn-outline-secondary btn-sm">
                                        <span id="btnChooseText">Choose a teacher</span>
                                        <span class="badge text-bg-secondary ms-1" id="teacherCount">0</span>
                                    </button>
                                </div>
                            </div>
                            {{-- </div> --}}
                            {{-- 空き先生ゼロのときの赤文字 --}}
                            <div id="noTeacherMsg" class="form-text text-danger d-none fw-semibold">
                                No teacher is available for that date/time.
                            </div>

                            {{-- 送信するのは booking_id / course_id / topic_id / teacher_id --}}
                            <input type="hidden" name="booking_id" id="booking_id">
                            <input type="hidden" name="teacher_id" id="teacher_id">

                            <button type="submit" class="btn btn-primary w-100 mt-4">Book a class</button>
                        </form>

                        {{-- ▼ 先生一覧モーダル（Bootstrap） --}}
                        <div class="modal fade" id="teacherModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        {{-- タイトルに選択中のDate/Timeを反映します --}}
                                        <h5 class="modal-title">
                                            Choose a teacher
                                            <small class="text-muted d-block" id="modalSubtitle"></small>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <ul id="teacherList" class="list-group"
                                            data-teacher-url-template="{{ route('teachers.profile', ['user_id' => '__ID__']) }}">
                                            {{-- JS で <li> を注入 --}}
                                        </ul>
                                    </div>
                                    <div class="modal-footer">
                                        <small class="text-muted">Select a teacher to confirm your slot.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            (function() {
                                const courseSel = document.getElementById('course_id');
                                const topicSel = document.getElementById('topic_id');
                                const topicHelp = document.getElementById('topicHelp');
                                const dateSel = document.getElementById('date');
                                const timeSel = document.getElementById('time');

                                const bidInput = document.getElementById('booking_id'); // ← DBに送る booking_id
                                const tidInput = document.getElementById('teacher_id'); // ← DBに送る teacher_id（手動選択時のみ）

                                const teacherActions = document.getElementById('teacherActions');
                                const teacherCountEl = document.getElementById('teacherCount');
                                const btnRandom = document.getElementById('btnRandom');
                                const btnChoose = document.getElementById('btnChoose');
                                const noTeacherMsg = document.getElementById('noTeacherMsg');
                                const teacherList = document.getElementById('teacherList');
                                const modalSubtitle = document.getElementById('modalSubtitle');

                                let slots = []; // API から取得 [{booking_id, date, time, teacher_id, teacher_name}, ...]
                                let currentTeachers = []; // 現在の date/time に空いている先生の枠
                                let selectedTeacherId = null; // 手動で選んだ teacher（date/time 変更で必ず破棄する）



                                function normalizeYmd(input) {
                                    if (!input) return '';

                                    const s = String(input).trim();

                                    // "YYYY-MM-DD" または "YYYY-MM-DD ..." の先頭10文字だけを使う
                                    const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
                                    if (!m) {
                                        return '';
                                    }

                                    return `${m[1]}-${m[2]}-${m[3]}`;
                                }

                                function normalizeHms(t) {
                                    const s = String(t || '');
                                    if (/^\d{2}:\d{2}:\d{2}$/.test(s)) return s; // HH:MM:SS
                                    if (/^\d{2}:\d{2}$/.test(s)) return s + ':00'; // HH:MM -> 補完
                                    return s;
                                }

                                function formatDateLabelYmdCommaWeekday(ymd, locale = 'en-US') {
                                    // ymd: 'YYYY-MM-DD'
                                    if (!ymd || !/^\d{4}-\d{2}-\d{2}$/.test(ymd)) return String(ymd || '');
                                    const [y, m, d] = ymd.split('-').map(Number);

                                    // UTCで日付を固定して曜日だけ取り出す（TZずれ対策）
                                    const utcDate = new Date(Date.UTC(y, m - 1, d));
                                    const weekday = utcDate.toLocaleDateString(locale, {
                                        weekday: 'short',
                                        timeZone: 'Asia/Manila'
                                    });

                                    return `${ymd}, ${weekday}`; // 例: "2025-10-31, Fri"
                                }

                                function formatTimeLabel(hms) {
                                    // 'HH:MM:SS' -> 'HH:MM' の表示用
                                    return String(hms).slice(0, 5);
                                }

                                // --- UI ヘルパー ---
                                function resetSelect(sel, ph) {
                                    sel.innerHTML = '';
                                    const opt = document.createElement('option');
                                    opt.value = '';
                                    opt.textContent = ph;
                                    opt.disabled = true;
                                    opt.selected = true;
                                    sel.appendChild(opt);
                                    sel.disabled = true;
                                }

                                function enable(sel) {
                                    sel.disabled = false;
                                }

                                function setUIRandomSelected(_name = null, count = 0) {
                                    btnRandom.classList.remove('btn-outline-secondary', 'disabled');
                                    btnRandom.classList.add('btn-secondary', 'text-white');
                                    btnRandom.textContent = 'Automatically assigned';

                                    btnChoose.classList.remove('btn-secondary');
                                    btnChoose.classList.add('btn-outline-secondary');
                                    document.getElementById('btnChooseText').textContent = 'Choose a teacher';

                                    teacherCountEl.classList.remove('d-none');
                                    teacherCountEl.textContent = String(count);
                                }

                                function setUITeacherSelected(teacherName) {
                                    btnRandom.classList.remove('btn-secondary', 'text-white');
                                    btnRandom.classList.add('btn-outline-secondary');
                                    btnRandom.textContent = 'Automatically assigned';

                                    btnChoose.classList.remove('btn-outline-secondary');
                                    btnChoose.classList.add('btn-secondary');
                                    document.getElementById('btnChooseText').textContent = `${teacherName} assigned`;

                                    teacherCountEl.classList.add('d-none');
                                }

                                // 重要：手動選択や以前の booking_id を完全クリアして「自動割当」モードに戻す
                                function clearTeacherSelectionToAuto() {
                                    setUIRandomSelected(null, currentTeachers.length || 0);
                                    selectedTeacherId = null;
                                    tidInput.value = '';
                                    bidInput.value = ''; // ← これが残っていると「以前の date/time の枠」で送信される
                                }

                                // --- モーダルのリスト描画（手動選択用） ---
                                function renderTeacherList(list) {
                                    teacherList.innerHTML = '';

                                    const teacherUrlTpl = teacherList.dataset.teacherUrlTemplate || '';

                                    list.forEach(item => {
                                        const li = document.createElement('li');
                                        li.className = 'list-group-item d-flex align-items-center justify-content-between';

                                        let nameHtml;
                                        if (teacherUrlTpl && item.teacher_id) {
                                            const url = teacherUrlTpl.replace('__ID__', item.teacher_id);
                                            nameHtml = `
                <a href="${url}" class="text-dark text-decoration-none">
                    ${item.teacher_name}
                </a>
            `;
                                        } else {
                                            nameHtml = item.teacher_name;
                                        }

                                        li.innerHTML = `
            <div><div class="fw-semibold">${nameHtml}</div></div>
            <button type="button" class="btn btn-sm btn-secondary">Select</button>
        `;

                                        li.querySelector('button').addEventListener('click', function() {
                                            // ここは既存の Select 処理そのままでOK
                                            selectedTeacherId = item.teacher_id;
                                            tidInput.value = item.teacher_id;

                                            const selectedDate = dateSel.value;
                                            const selectedTime = normalizeHms(timeSel.value);

                                            const slot = slots.find(s =>
                                                s.teacher_id === item.teacher_id &&
                                                normalizeYmd(s.date) === selectedDate &&
                                                normalizeHms(s.time) === selectedTime
                                            );

                                            if (slot) {
                                                bidInput.value = slot.booking_id;
                                                setUITeacherSelected(item.teacher_name);
                                            } else {
                                                clearTeacherSelectionToAuto();
                                                const choices = slots.filter(s =>
                                                    normalizeYmd(s.date) === selectedDate &&
                                                    normalizeHms(s.time) === selectedTime
                                                );
                                                if (choices.length) {
                                                    const rnd = choices[Math.floor(Math.random() * choices.length)];
                                                    bidInput.value = rnd.booking_id;
                                                    tidInput.value = rnd.teacher_id;
                                                    setUIRandomSelected(rnd.teacher_name, choices.length);
                                                } else {
                                                    bidInput.value = '';
                                                    tidInput.value = '';
                                                    noTeacherMsg.classList.remove('d-none');
                                                }
                                            }

                                            const modalEl = document.getElementById('teacherModal');
                                            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                                            modal.hide();
                                            modalEl.addEventListener('hidden.bs.modal', () => {
                                                document.body.classList.remove('modal-open');
                                                document.querySelectorAll('.modal-backdrop').forEach(el => el
                                                    .remove());
                                            }, {
                                                once: true
                                            });
                                        });

                                        teacherList.appendChild(li);
                                    });
                                }

                                // --- Choose 押下：date/time が未選択なら開かない ---
                                btnChoose.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    if (!dateSel.value || !timeSel.value) {
                                        alert('Please select a date and time first.');
                                        return;
                                    }
                                    document.body.classList.remove('modal-open');
                                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                                    const modalEl = document.getElementById('teacherModal');
                                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                                    modal.show();
                                });

                                // --- Random（自動割当）押下：現在の currentTeachers からランダム確定 ---
                                btnRandom.addEventListener('click', function() {
                                    if (!currentTeachers.length) {
                                        noTeacherMsg.classList.remove('d-none');
                                        return;
                                    }
                                    const pick = currentTeachers[Math.floor(Math.random() * currentTeachers.length)];
                                    bidInput.value = pick.booking_id;
                                    tidInput.value = pick.teacher_id;
                                    selectedTeacherId = null; // 自動割当なので手動選択はクリア
                                    setUIRandomSelected(pick.teacher_name, currentTeachers.length);
                                });

                                // ========== ハンドラ（関数）を名前付きで用意して、都度 remove → add できるように ==========
                                function onDateChange() {
                                    resetSelect(timeSel, 'Select a time');
                                    currentTeachers = [];
                                    clearTeacherSelectionToAuto();

                                    noTeacherMsg.classList.add('d-none');
                                    teacherList.innerHTML = '';
                                    teacherCountEl.textContent = '0';

                                    const selectedYmd = dateSel.value; // 'YYYY-MM-DD'
                                    const list = slots.filter(s => normalizeYmd(s.date) === selectedYmd);

                                    // 時刻も正規化してユニーク化
                                    const uniqueTimes = [...new Set(list.map(s => normalizeHms(s.time)))].sort();

                                    uniqueTimes.forEach(t => {
                                        const o = document.createElement('option');
                                        o.value = t; // 値は 'HH:MM:SS' で統一
                                        o.textContent = formatTimeLabel(t); // 表示は 'HH:MM'
                                        timeSel.appendChild(o);
                                    });
                                    if (uniqueTimes.length) enable(timeSel);
                                }

                                function onTimeChange() {
                                    clearTeacherSelectionToAuto();

                                    const selectedDate = dateSel.value; // 'YYYY-MM-DD'
                                    const selectedTime = normalizeHms(timeSel.value); // 'HH:MM:SS'

                                    currentTeachers = slots.filter(s =>
                                        normalizeYmd(s.date) === selectedDate &&
                                        normalizeHms(s.time) === selectedTime
                                    );

                                    modalSubtitle.textContent = `${selectedDate} ${formatTimeLabel(selectedTime)}`;

                                    if (currentTeachers.length > 0) {
                                        teacherCountEl.textContent = String(currentTeachers.length);
                                        noTeacherMsg.classList.add('d-none');
                                        renderTeacherList(currentTeachers);

                                        // デフォルトは自動割当：この date/time 群からランダム
                                        const slot = currentTeachers[Math.floor(Math.random() * currentTeachers.length)];
                                        bidInput.value = slot.booking_id;
                                        tidInput.value = slot.teacher_id;
                                        setUIRandomSelected(slot.teacher_name, currentTeachers.length);
                                    } else {
                                        teacherCountEl.textContent = '0';
                                        noTeacherMsg.classList.remove('d-none');
                                        teacherList.innerHTML = '';
                                    }
                                }

                                // --- course 変更時：全リセット → API 取込み → date セレクト再構築 ---
                                courseSel.addEventListener('change', async function() {
                                    resetSelect(topicSel, 'Select a topic');
                                    resetSelect(dateSel, 'Select a date');
                                    resetSelect(timeSel, 'Select a time');

                                    // 過去 state を完全クリア
                                    slots = [];
                                    currentTeachers = [];
                                    clearTeacherSelectionToAuto();

                                    topicHelp.classList.add('d-none');
                                    noTeacherMsg.classList.add('d-none');
                                    teacherList.innerHTML = '';
                                    teacherCountEl.textContent = '0';

                                    // 既存の change ハンドラを掃除（多重バインド防止）
                                    dateSel.removeEventListener('change', onDateChange);
                                    timeSel.removeEventListener('change', onTimeChange);

                                    const cid = this.value;
                                    if (!cid) return;

                                    const resp = await fetch(`/students/api/courses/${cid}/init`, {
                                        headers: {
                                            'Accept': 'application/json'
                                        }
                                    });
                                    if (!resp.ok) {
                                        resetSelect(topicSel, 'Failed to load');
                                        return;
                                    }
                                    const data = await resp.json();

                                    // topics
                                    if (Array.isArray(data.topics)) {
                                        data.topics.forEach(t => {
                                            const o = document.createElement('option');
                                            o.value = t.id;
                                            o.textContent = t.name;
                                            topicSel.appendChild(o);
                                        });
                                        enable(topicSel);
                                    }
                                    if (data.suggested) {
                                        const found = [...topicSel.options].find(o => Number(o.value) === Number(data
                                            .suggested));
                                        if (found) {
                                            found.selected = true;
                                            topicHelp.classList.remove('d-none');
                                        }
                                    }

                                    // slots
                                    slots = Array.isArray(data.slots) ? data.slots : [];

                                    // dates（正規化 → ユニーク → ソート）
                                    const dateSet = new Set();
                                    slots.forEach(s => dateSet.add(normalizeYmd(s.date)));
                                    const dates = Array.from(dateSet).filter(Boolean).sort();

                                    document.getElementById('noSlotMessage').classList.add('d-none');
                                    resetSelect(dateSel, 'Select a date');

                                    if (dates.length === 0) {
                                        document.getElementById('noSlotMessage').classList.remove('d-none');
                                        return;
                                    }

                                    // value は 'YYYY-MM-DD' のまま、表示だけ "YYYY-MM-DD, Fri"
                                    dates.forEach(ymd => {
                                        const o = document.createElement('option');
                                        o.value = ymd;
                                        o.textContent = formatDateLabelYmdCommaWeekday(ymd,
                                            'en-US'); // ←ここで "2025-10-31, Fri"
                                        dateSel.appendChild(o);
                                    });
                                    enable(dateSel);

                                    // ここで改めてハンドラをバインド
                                    dateSel.addEventListener('change', onDateChange);
                                    timeSel.addEventListener('change', onTimeChange);
                                });

                                // --- 送信前チェック：booking_id が空なら送信不可 ---
                                document.getElementById('bookingForm').addEventListener('submit', function(e) {
                                    if (!bidInput.value) {
                                        e.preventDefault();
                                        alert('Please select a date and time slot.');
                                    }
                                });
                            })();
                        </script>
                    </div>
                </div>
            </div>

            {{-- ★ 右側カレンダー枠：置き換え --}}
            <div class="col-8">
                <div id="myCalendar" class="rounded-3 border"></div>
            </div>
        </div>

        {{-- ★ クリック詳細用モーダル（再利用） --}}
        <div class="modal fade" id="calEventModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="calModalTitle">Lesson</h5>
                        <span id="calModalStatus" class="badge d-none ms-2"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <dl class="row mb-0">
                            <dt class="col-4">Course</dt>
                            <dd class="col-8" id="calModalCourse">—</dd>
                            <dt class="col-4">Topic</dt>
                            <dd class="col-8" id="calModalTopic">—</dd>
                            <dt class="col-4">Teacher</dt>
                            <dd class="col-8" id="calModalTeacher"
                                data-url-template="{{ route('teachers.profile', ['user_id' => '__ID__']) }}">
                                —
                            </dd>
                            <dt class="col-4">Date</dt>
                            <dd class="col-8" id="calModalDate">—</dd>
                            <dt class="col-4">Time</dt>
                            <dd class="col-8" id="calModalTime">—</dd>
                        </dl>
                    </div>

                    <div class="modal-footer justify-content-start gap-2 flex-wrap" id="calModalFooter"
                        data-view-url-template="{{ url('/students/bookings/__ID__') }}"
                        data-cancel-url-template="{{ route('students.bookings.cancel', ['booking' => '__ID__']) }}">

                        <a id="calModalViewBtn" href="#" class="btn btn-outline-secondary d-none">
                            View details
                        </a>

                        <form id="calModalCancelForm" class="d-none" method="POST" action="">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger"
                                onclick="return confirm('Cancel this booking?');">
                                Cancel a lesson
                            </button>
                        </form>

                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 共通：Lesson historyと同じフォーマットの詳細モーダル -->
        <div class="modal fade" id="calEventDetailsLikeHistory" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Lesson details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <!-- Booking block -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-regular fa-calendar-check text-primary"></i>
                                <span class="text-uppercase text-muted small fw-semibold">Booking</span>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-0">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                            <i class="fa-regular fa-clone"></i><span>Course</span>
                                        </div>
                                        <div class="col-6 fw-semibold text-end text-truncate" id="calLikeHistCourse">-
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item px-0">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                            <i class="fa-regular fa-bookmark"></i><span>Topic</span>
                                        </div>
                                        <div class="col-6 fw-semibold text-end text-truncate" id="calLikeHistTopic">-
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item px-0">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                            <i class="fa-regular fa-user"></i><span>Teacher</span>
                                        </div>
                                        <div class="col-6 fw-semibold text-end text-truncate" id="calLikeHistTeacher">
                                            -</div>
                                    </div>
                                </li>
                                <li class="list-group-item px-0">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                            <i class="fa-regular fa-clock"></i><span>Date & time</span>
                                        </div>
                                        <div class="col-6 fw-semibold text-end text-truncate" id="calLikeHistWhen">-
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- Report block -->
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-regular fa-clipboard text-primary"></i>
                                <span class="text-uppercase text-muted small fw-semibold">Report</span>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-0">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                            <i class="fa-regular fa-flag"></i><span>Status</span>
                                        </div>
                                        <div class="col-6 text-end">
                                            <span id="calLikeHistStatusBadge" class="badge text-bg-secondary">—</span>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item px-0">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                            <i class="fa-regular fa-lightbulb"></i><span>Next topic</span>
                                        </div>
                                        <div class="col-6 fw-semibold text-end text-truncate" id="calLikeHistNext">—
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item px-0">
                                    <div class="row g-2 align-items-start">
                                        <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                            <i class="fa-regular fa-comment-dots"></i><span>Feedback</span>
                                        </div>
                                        <div class="col-6 fw-semibold text-end text-wrap" id="calLikeHistFeedback">—
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <!-- 読取専用：操作ボタンなし -->
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        @push('styles')
            <style>
                /* ====== 基本：全体の文字サイズを小さめに統一、文字色は黒 ====== */
                :root {
                    --app-font-size: 0.92rem;
                }

                body {
                    font-size: var(--app-font-size);
                    color: #000;
                }

                h1,
                h2,
                h3,
                h4,
                h5,
                h6 {
                    color: #000;
                }

                /* ====== 非リンクの見た目：下線なし＆黒 ====== */
                a:not([href]),
                a[href="#"] {
                    text-decoration: none !important;
                    color: #000 !important;
                    cursor: default;
                }

                /* ====== FullCalendar の見た目調整 ====== */
                #myCalendar .fc {
                    /* トーン（必要に応じてブランド色に） */
                    --fc-page-bg-color: #fff;
                    --fc-neutral-bg-color: #f8f9fa;
                    --fc-border-color: rgba(0, 0, 0, .08);

                    --fc-button-text-color: #212529;
                    --fc-button-bg-color: #f8f9fa;
                    --fc-button-border-color: rgba(0, 0, 0, .12);
                    --fc-button-hover-bg-color: #e9ecef;
                    --fc-button-hover-border-color: rgba(0, 0, 0, .18);
                    --fc-button-active-bg-color: #e9ecef;
                    --fc-button-active-border-color: rgba(0, 0, 0, .18);

                    --fc-today-bg-color: rgba(13, 110, 253, .08);

                    /* --fc-event-bg-color: rgba(13, 110, 253, .10);
                                                                                                                                                                                                                                                                                --fc-event-border-color: rgba(13, 110, 253, .40);
                                                                                                                                                                                                                                                                                --fc-event-text-color: #0d6efd; */

                    font-size: 0.90rem;
                    /* カレンダー内を少し小さめ */
                    color: #000;
                }

                /* イベント等に使われる<a>の下線は見た目から外す */
                #myCalendar .fc a {
                    text-decoration: none;
                    color: inherit;
                }

                /* 罫線を軽やかに */
                #myCalendar .fc-theme-standard td,
                #myCalendar .fc-theme-standard th {
                    border-color: rgba(0, 0, 0, .08);
                }

                /* グリッドの角丸＋薄い内枠 */
                #myCalendar .fc .fc-scrollgrid {
                    border-radius: .75rem;
                    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .04);
                    overflow: hidden;
                }

                /* タイトル/ヘッダ/時刻のサイズと色 */
                #myCalendar .fc-toolbar-title {
                    font-size: .95rem;
                    font-weight: 600;
                    color: #000;
                }

                #myCalendar .fc-col-header-cell-cushion,
                #myCalendar .fc-daygrid-day-number {
                    color: #000;
                    font-size: .85rem;
                }

                #myCalendar .fc-timegrid-slot-label {
                    color: #6c757d;
                    font-size: .85rem;
                }

                /* イベント外観 */
                #myCalendar .fc-timegrid-event,
                #myCalendar .fc-daygrid-event {
                    border-radius: .5rem;
                    padding: .15rem .35rem;
                }

                /* スクロールバー控えめ（webkit系） */
                #myCalendar .fc-scroller::-webkit-scrollbar {
                    width: 10px;
                    height: 10px;
                }

                #myCalendar .fc-scroller::-webkit-scrollbar-thumb {
                    background: rgba(0, 0, 0, .12);
                    border-radius: 999px;
                }

                /* 月ビューのイベントで時間だけを見やすく（リンク下線消しも再確認） */
                #myCalendar .fc a {
                    text-decoration: none;
                    color: inherit;
                }

                /* クリックできるカーソル */
                #myCalendar .fc .fc-event {
                    cursor: pointer;
                }

                /* 滑らかなアニメーション */
                #myCalendar .fc .fc-daygrid-event .fc-event-main,
                #myCalendar .fc .fc-timegrid-event {
                    transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
                }

                /* ホバー時：少し持ち上げて影を濃く、わずかに明るく */
                #myCalendar .fc .fc-daygrid-event:hover .fc-event-main,
                #myCalendar .fc .fc-timegrid-event:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 6px 14px rgba(0, 0, 0, .16);
                    filter: brightness(1.05);
                }

                /* 既存の .legend-item / .legend-dot を使い回し */
                #fcLegendStatic .legend-item {
                    font-size: .85rem;
                    color: #000;
                    display: inline-flex;
                    align-items: center;
                    gap: .35rem;
                    white-space: nowrap;
                }

                #fcLegendStatic .legend-dot {
                    width: .8rem;
                    height: .8rem;
                    border-radius: 999px;
                    background: var(--c, #6c757d);
                    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .18);
                    display: inline-block;
                }

                /* モバイル時は中央揃えに */
                @media (max-width: 991.98px) {
                    #fcLegendStatic {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: .5rem;
                    }
                }
            </style>
        @endpush

        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
            <script>
                // Manila（UTC+8）での「今」をミリ秒で返す
                function nowMsInManila() {
                    // Date.UTC は常にUTCとして解釈されるので、UTCの「今」を作ってから +8h する
                    const nowUtcMs = Date.now();
                    // 固定オフセットでOK（Asia/Manila は通年 UTC+8）
                    return nowUtcMs + 8 * 60 * 60 * 1000;
                }
                document.addEventListener('DOMContentLoaded', function() {
                    const el = document.getElementById('myCalendar');
                    if (!el) return;

                    const CAL_HEIGHT = 500; // 月ビューでも外寸を固定

                    // ヘルパー（ゼロ埋め / 時刻フォーマット）
                    const pad2 = n => String(n).padStart(2, '0');
                    const fmtHM = d => `${pad2(d.getHours())}:${pad2(d.getMinutes())}`;

                    const calendar = new FullCalendar.Calendar(el, {
                        themeSystem: 'bootstrap5',
                        initialView: 'dayGridMonth',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth'
                        },
                        timeZone: 'Asia/Manila',
                        height: 500,
                        expandRows: false,
                        fixedWeekCount: true,
                        showNonCurrentDates: true,

                        eventClassNames(arg) {
                            const start = arg.event.start;
                            // 終了が無いときは50分レッスン前提
                            const end = arg.event.end ?? new Date(start.getTime() + 50 * 60000);
                            return (end.getTime() < nowMsInManila()) ? ['is-past'] : [];
                        },

                        eventDidMount(arg) {
                            // まず既存のラッパ装飾は維持
                            arg.el.style.setProperty('background', 'transparent', 'important');
                            arg.el.style.setProperty('background-color', 'transparent', 'important');
                            arg.el.style.setProperty('border', 'none', 'important');
                            arg.el.style.setProperty('box-shadow', 'none', 'important');
                            const dot = arg.el.querySelector('.fc-daygrid-event-dot');
                            if (dot) {
                                dot.style.setProperty('border-color', 'transparent', 'important');
                                dot.style.setProperty('background', 'transparent', 'important');
                            }

                            // 便利ヘルパ：色セット
                            const palette = {
                                blue: {
                                    bg: '#d63384',
                                    border: '1px solid #c12d76'
                                }, // booked
                                yellow: {
                                    bg: '#f59e0b',
                                    border: '1px solid #b45309'
                                }, // open
                                black: {
                                    bg: '#111827',
                                    border: '1px solid #0b1220'
                                }, // canceled by teacher
                                green: {
                                    bg: '#16a34a',
                                    border: '1px solid #166534'
                                }, // report submitted
                                second: {
                                    bg: '#6c757d',
                                    border: '1px solid #5c636a'
                                }, // report not submitted
                            };
                            const applyMain = (col) => {
                                const isTimeGrid = arg.el.classList.contains('fc-timegrid-event');
                                const main = isTimeGrid ? arg.el : (arg.el.querySelector('.fc-event-main') ||
                                    arg.el);
                                main.style.setProperty('background', col.bg, 'important');
                                main.style.setProperty('border', col.border, 'important');
                                main.style.borderRadius = '.5rem';
                                main.style.padding = '.15rem .35rem';
                                main.style.fontWeight = '600';
                                main.style.setProperty('color', '#fff', 'important');
                                (arg.el.querySelectorAll('*') || []).forEach(n => n.style.setProperty('color',
                                    '#fff', 'important'));
                            };

                            // 状態判定
                            const now = nowMsInManila();
                            const start = arg.event.start;
                            const end = arg.event.end ?? new Date(start.getTime() + 50 * 60000);

                            const ext = arg.event.extendedProps || {};
                            const status = String(ext.report_status || '').trim().toLowerCase();
                            const hasReport = ext.has_report === true;

                            const looksOpen =
                                String(ext.state || '').toLowerCase() === 'open' ||
                                String(arg.event.title || '').toLowerCase().includes('open');

                            const isCanceledByTeacher = status === 'canceled by teacher';
                            const isPast = end.getTime() < now;

                            // マッピング
                            // open → yellow
                            // booked → blue（未来で、キャンセルでもレポート提出でもなく、“open” でもない）
                            // canceled by teacher → black
                            // report submitted → green（has_report=true を採用）
                            // report not submitted → secondary（過去なのに has_report=false）
                            if (isCanceledByTeacher) {
                                applyMain(palette.black);
                            } else if (looksOpen) {
                                applyMain(palette.yellow);
                            } else if (hasReport) {
                                applyMain(palette.green);
                            } else if (isPast && !hasReport) {
                                applyMain(palette.second);
                            } else {
                                // デフォルトは booked（未来の通常レッスン）
                                applyMain(palette.blue);
                            }

                            arg.el.style.cursor = 'pointer';
                        },

                        eventMouseEnter(info) {
                            const main = info.el.querySelector('.fc-event-main') || info.el;

                            // 少し持ち上げ＋影を濃く＋わずかに明るく
                            main.style.transform = 'translateY(-1px)';
                            main.style.boxShadow = '0 6px 14px rgba(0,0,0,.16)';
                            main.style.filter = 'brightness(1.05)';

                            // 影が切れないよう浮かせる
                            info.el.style.zIndex = 3;
                        },

                        eventMouseLeave(info) {
                            const main = info.el.querySelector('.fc-event-main') || info.el;

                            // 元に戻す
                            main.style.transform = '';
                            main.style.boxShadow = '';
                            main.style.filter = '';
                            info.el.style.zIndex = '';
                        },

                        // ★ ここを追加：すべてのイベントをブロック表示に
                        eventDisplay: 'block',

                        // ★ 追加：24h表記にして、分も出す
                        eventTimeFormat: {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        },

                        // ★ 追加：終了時刻も一緒に表示（"12:00 - 12:50" になる）
                        displayEventEnd: true,

                        // ★ ここで色を確実に適用（CSSより確実）
                        // eventBackgroundColor: 'rgba(13,110,253,.12)',
                        // eventBorderColor: 'rgba(13,110,253,.35)',
                        // eventTextColor: '#0d6efd',

                        events: @json($fcEvents ?? []),

                        // 時刻のみ表示
                        eventContent(arg) {
                            const div = document.createElement('div');
                            // FCが timeZone を考慮して作る時刻テキスト（例: "12:00 - 12:50"）
                            div.textContent = arg.timeText;
                            div.className = 'small';
                            return {
                                domNodes: [div]
                            };
                        },

                        eventClick(info) {
                            info.jsEvent.preventDefault();

                            const e = info.event;
                            const bookingId = String(e.id || '');
                            const end = e.end ?? new Date(e.start.getTime() + 50 * 60000);

                            const isPast = end.getTime() < nowMsInManila();
                            const isCanceledByTeacher =
                                e.extendedProps?.has_report === true &&
                                String(e.extendedProps?.report_status || '').trim().toLowerCase() ===
                                'canceled by teacher';

                            // 1) 過去 → 履歴モーダル（個別に用意済み）を優先
                            const historyModalEl = document.getElementById(`bookingDetails-${bookingId}`);
                            if (isPast && historyModalEl) {
                                const historyModal = bootstrap.Modal.getOrCreateInstance(historyModalEl);
                                historyModal.show();
                                return;
                            }

                            // 2) 未来 かつ Canceled by teacher → 「Lesson history と同じフォーマット」モーダルのみを開く
                            if (isCanceledByTeacher) {
                                // 念のため、開いているモーダルがあれば閉じる（多重オープン防止）
                                document.querySelectorAll('.modal.show').forEach(m => {
                                    const inst = bootstrap.Modal.getInstance(m);
                                    if (inst) inst.hide();
                                });

                                const likeEl = document.getElementById('calEventDetailsLikeHistory');
                                const likeModal = bootstrap.Modal.getOrCreateInstance(likeEl);

                                const fDate = (d) => calendar.formatDate(d, {
                                    weekday: 'short',
                                    year: 'numeric',
                                    month: 'short',
                                    day: '2-digit'
                                });
                                const fTime = (d) => calendar.formatDate(d, {
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: false
                                });

                                const start = e.start;
                                const eend = e.end ?? new Date(start.getTime() + 50 * 60000);

                                // Booking block
                                document.getElementById('calLikeHistCourse').textContent = e.extendedProps
                                    ?.course_name ?? '-';
                                document.getElementById('calLikeHistTopic').textContent = e.extendedProps
                                    ?.topic_name ?? '-';
                                document.getElementById('calLikeHistTeacher').textContent = e.extendedProps
                                    ?.teacher ?? '-';
                                document.getElementById('calLikeHistWhen').textContent =
                                    `${fDate(start)} ${fTime(start)}–${fTime(eend)}`;
                                document.getElementById('calLikeHistFeedback').textContent = e.extendedProps
                                    ?.report_feedback ?? '—';

                                // Report block
                                const statusText = e.extendedProps?.report_status ?? '—';
                                const statusBadge = document.getElementById('calLikeHistStatusBadge');
                                statusBadge.textContent = statusText;

                                const st = String(statusText).trim().toLowerCase();
                                let badgeClass = 'badge text-bg-secondary';
                                if (st === 'canceled by teacher') badgeClass = 'badge text-bg-danger';
                                else if (st === 'attended' || st === 'done' || st === 'completed') badgeClass =
                                    'badge text-bg-success';
                                else if (st === 'pending' || st === 'todo') badgeClass = 'badge text-bg-warning';
                                else if (st === 'missed' || st === 'absent') badgeClass = 'badge text-bg-danger';
                                statusBadge.className = badgeClass;

                                document.getElementById('calLikeHistNext').textContent = e.extendedProps
                                    ?.report_next ?? '—';
                                likeModal.show();
                                return; // ★ ここで終了（通常モーダルは開かない）
                            }

                            // 3) 上記以外（未来の通常レッスン） → 従来の #calEventModal
                            const footer = document.getElementById('calModalFooter');
                            const cancelTpl = footer.dataset.cancelUrlTemplate;
                            const viewBtn = document.getElementById('calModalViewBtn');
                            const cancelFm = document.getElementById('calModalCancelForm');

                            viewBtn.classList.add('d-none');
                            viewBtn.href = '#';
                            cancelFm.classList.remove('d-none');
                            cancelFm.action = cancelTpl.replace('__ID__', bookingId);

                            const fDate = (d) => calendar.formatDate(d, {
                                weekday: 'short',
                                year: 'numeric',
                                month: 'short',
                                day: '2-digit'
                            });
                            const fTime = (d) => calendar.formatDate(d, {
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false
                            });

                            const start = e.start;
                            const eend = e.end ?? new Date(start.getTime() + 50 * 60000);

                            document.getElementById('calModalTitle').textContent = e.title || 'Lesson';
                            document.getElementById('calModalDate').textContent = fDate(start);
                            document.getElementById('calModalTime').textContent = `${fTime(start)}-${fTime(eend)}`;
                            document.getElementById('calModalCourse').textContent = e.extendedProps?.course_name ??
                                '-';
                            document.getElementById('calModalTopic').textContent = e.extendedProps?.topic_name ??
                                '-';
                            // document.getElementById('calModalTeacher').textContent = e.extendedProps?.teacher ??
                            //     '-';
                            // Teacher セルを書き換え
                            const teacherCell = document.getElementById('calModalTeacher');
                            const teacherName = e.extendedProps?.teacher ?? '-';
                            const teacherId = e.extendedProps?.teacher_id ?? null;
                            const teacherUrlTpl = teacherCell.dataset.urlTemplate || '';

                            if (teacherId && teacherUrlTpl) {
                                teacherCell.innerHTML =
                                    `<a href="${teacherUrlTpl.replace('__ID__', teacherId)}"
            class="text-dark text-decoration-none">
            ${teacherName}
        </a>`;
                            } else {
                                teacherCell.textContent = teacherName;
                            }

                            const badge = document.getElementById('calModalStatus');
                            if (badge) {
                                badge.textContent = '';
                                badge.className = 'badge d-none ms-2';
                            }

                            const modalEl = document.getElementById('calEventModal');
                            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                            modal.show();
                        }
                    });

                    calendar.render();
                });
            </script>
        @endpush

    </section>
    {{-- ===== Lesson history (line-card with Details modal) ===== --}}
    <section class="container py-4">
        <h2 class="h4 mb-3">Lesson history</h2>

        <div class="vstack gap-3">
            @forelse ($history as $b)
                @php
                    $tz = config('app.timezone', 'Asia/Manila');

                    // ① date を 'Y-m-d' の文字列に正規化（casts で Carbon になっている可能性がある）
                    $rawDate = $b->getAttribute('date');
                    $dateStr = $rawDate instanceof \Carbon\Carbon ? $rawDate->format('Y-m-d') : (string) $rawDate;

                    // ② time を 'H:i:s' の文字列に正規化（'H:i' で来たら ':00' を付与）
                    $rawTime = $b->getAttribute('time');
                    if ($rawTime instanceof \Carbon\Carbon) {
                        $timeStr = $rawTime->format('H:i:s');
                    } else {
                        $timeStr = (string) $rawTime;
                        if (preg_match('/^\d{2}:\d{2}$/', $timeStr)) {
                            $timeStr .= ':00';
                        }
                    }

                    // ③ 正規化した文字列を結合してからパース（←ここが重要）
                    $dt = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', "$dateStr $timeStr", $tz);
                    $duration = $b->duration_minutes ?? 50;
                    $end = (clone $dt)->addMinutes($duration);

                    // 以降はそのまま
                    $course = $b->course->title ?? 'Course';
                    $topic = $b->topic->name ?? 'Topic';
                    $teacher = $b->teacher->name ?? 'Teacher';
                    // $iconUrl = $b->course->image_url ?? asset('images/placeholder-course.png');
                    $iconUrl =
                        $b->course && $b->course->image_url
                            ? asset('storage/' . ltrim($b->course->image_url, '/'))
                            : asset('images/placeholder-course.png');
                    // 例: Wed, Oct 29 18:00–18:50
                    $whenStr = $dt->format('D, M j H:i') . '–' . $end->format('H:i');

                    $status = $b->report->status ?? null;
                    $nextTop = $b->report->next_topic ?? '—';
                    $nextTopName = $b->report?->nextTopic?->name ?? '—';
                    $feedback = $b->report->feedback ?? '—';
                    $statusClass = match (strtolower((string) $status)) {
                        'done', 'completed' => 'text-bg-success',
                        'pending', 'todo' => 'text-bg-warning',
                        'missed', 'absent' => 'text-bg-danger',
                        default => 'text-bg-secondary',
                    };
                    // $courseId = $b->course->id ?? null;
                    $teacherId = $b->teacher->id ?? null;
                    $statusRaw = trim((string) ($b->report->status ?? ''));
                    $statusLower = strtolower($statusRaw);

                    if ($statusLower === 'canceled by teacher') {
                        $statusClass = 'badge bg-dark text-white';
                    } elseif ($statusRaw !== '') {
                        $statusClass = 'badge bg-success text-white';
                    } else {
                        $statusClass = '';
                    }

                    $nextTopName = $b->report?->nextTopic?->name ?? '—';
                    $feedback = $b->report->feedback ?? '—';

                    $courseId = $b->course->id ?? null;
                    $teacherIdRow = $b->teacher->id ?? null;
                @endphp

                <div class="card shadow-sm">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex align-items-center gap-3 flex-wrap">

                            {{-- Left: course icon --}}
                            <img src="{{ $iconUrl }}" alt="Course icon" class="rounded-3 border flex-shrink-0"
                                style="width:48px;height:48px;object-fit:cover;">

                            {{-- Middle: title + meta --}}
                            {{-- Middle: title + meta（Up next と同じ構造） --}}
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold text-truncate">
                                    @if ($courseId)
                                        <a href="{{ route('courses.show', ['course' => $courseId]) }}"
                                            class="text-dark text-decoration-none">
                                            {{ $course }}
                                        </a>
                                    @else
                                        {{ $course }}
                                    @endif
                                    <span class="text-body-secondary">/</span>
                                    {{ $topic }}
                                </div>

                                <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                                    <span class="d-inline-flex align-items-center">
                                        <i class="fa-regular fa-calendar me-1"></i>{{ $whenStr }}
                                    </span>

                                    <span>
                                        with Teacher
                                        @if ($teacherId)
                                            <a href="{{ route('teachers.profile', ['user_id' => $teacherId]) }}"
                                                class="text-dark text-decoration-none fw-bold">
                                                {{ $teacher }}
                                            </a>
                                        @else
                                            <span class="text-body fw-bold">{{ $teacher }}</span>
                                        @endif
                                       @if ($statusRaw !== '')
                                            <span class="{{ $statusClass }} ms-2">
                                                {{ $statusRaw }}
                                            </span>
                                        @endif
                                    </span>
                                </div>
                            </div>

                            {{-- Right: Details button (opens modal) --}}
                            <div class="d-flex gap-2 ms-auto">
                                <button type="button" class="btn btn-outline-secondary btn-sm px-3"
                                    data-bs-toggle="modal" data-bs-target="#bookingDetails-{{ $b->id }}">
                                    Details
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Modal (one per item) --}}
                <div class="modal fade" id="bookingDetails-{{ $b->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Lesson details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                {{-- Booking --}}
                                <div class="mb-3">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="fa-regular fa-calendar-check text-primary"></i>
                                        <span class="text-uppercase text-muted small fw-semibold">Booking</span>
                                    </div>

                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item px-0">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-clone"></i><span>Course</span>
                                                </div>
                                                <div class="col-6 fw-semibold text-end text-truncate"
                                                    title="{{ $course }}">
                                                    @if ($courseId)
                                                        <a href="{{ route('courses.show', ['course' => $courseId]) }}"
                                                            class="text-dark text-decoration-none">
                                                            {{ $course }}
                                                        </a>
                                                    @else
                                                        {{ $course }}
                                                    @endif
                                                </div>
                                            </div>
                                        </li>

                                        <li class="list-group-item px-0">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-bookmark"></i><span>Topic</span>
                                                </div>
                                                <div class="col-6 fw-semibold text-end text-truncate"
                                                    title="{{ $topic }}">{{ $topic }}</div>
                                            </div>
                                        </li>

                                        <li class="list-group-item px-0">
                                            <div class="row g-2 align-items-center">
                                                {{-- 左：ラベル --}}
                                                <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-user"></i><span>Teacher</span>
                                                </div>

                                                {{-- 右：値 --}}
                                                <div class="col-6 fw-semibold text-end text-truncate"
                                                    title="{{ $teacher }}">
                                                    @if ($teacherId)
                                                        <a href="{{ route('teachers.profile', ['user_id' => $teacherId]) }}"
                                                            class="text-dark text-decoration-none">
                                                            {{ $teacher }}
                                                        </a>
                                                    @else
                                                        {{ $teacher }}
                                                    @endif
                                                </div>
                                            </div>
                                        </li>

                                        <li class="list-group-item px-0">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-clock"></i><span>Date & time</span>
                                                </div>
                                                <div class="col-6 fw-semibold text-end text-truncate"
                                                    title="{{ $whenStr }}">{{ $whenStr }}</div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>

                                {{-- Report --}}
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="fa-regular fa-clipboard text-primary"></i>
                                        <span class="text-uppercase text-muted small fw-semibold">Report</span>
                                    </div>

                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item px-0">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-flag"></i><span>Status</span>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <span class="badge {{ $statusClass }}">{{ $status ?? '—' }}</span>
                                                </div>
                                            </div>
                                        </li>

                                        <li class="list-group-item px-0">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-lightbulb"></i><span>Next topic</span>
                                                </div>
                                                <div class="col-6 fw-semibold text-end text-truncate"
                                                    title="{{ $nextTopName }}">{{ $nextTopName }}</div>
                                            </div>
                                        </li>
                                        <li class="list-group-item px-0">
                                            <div class="row g-2 align-items-start">
                                                <div class="col-6 text-secondary small d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-comment-dots"></i><span>Comment</span>
                                                </div>
                                                <div class="col-6 fw-semibold text-end text-wrap">
                                                    {{ $feedback }}
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light border"
                                    data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-light border d-flex align-items-center gap-2 mb-0 shadow-sm">
                    <i class="fa-regular fa-circle-info text-secondary"></i>
                    <span class="small">No history yet.</span>
                </div>
            @endforelse
        </div>

        {{-- View more --}}
        <div class="text-center mt-3">
            <a href="{{ route('students.lessonhistory', ['student' => Auth::id()]) }}" class="btn btn-light border">
                View more
            </a>
        </div>
    </section>
@endsection
