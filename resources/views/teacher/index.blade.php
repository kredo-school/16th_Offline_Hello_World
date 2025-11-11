@extends('layouts.app')

@section('title', 'Weekly Calendar')

@section('content')
    <section class="container py-3">
        {{-- CSRF（fetch用） --}}
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @if (!$hasMeetingUrl)
            <div class="alert alert-danger py-2 px-3 mb-2">
                To open a slot, set your meeting URL in <strong><a href="{{ route('teachers.profile', Auth::id()) }}"
                        class="text-decoration-none" style="color: inherit;">Profile</a></strong>.
            </div>
        @endif

        <!-- Toolbar row: Left = Legend, Right = Bulk delete -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <!-- Legend (left/top) -->
            <div id="tcLegend" class="d-flex flex-wrap align-items-center gap-2">
                <span class="legend-item"><span class="legend-dot" style="--c:#0d6efd"></span> Open</span>
                <span class="legend-item"><span class="legend-dot" style="--c:#d63384"></span> Scheduled</span>
                <span class="legend-item"><span class="legend-dot" style="--c:#111827"></span> Teacher-canceled</span>
                <span class="legend-item"><span class="legend-dot" style="--c:#16a34a"></span> Report submitted</span>
                <span class="legend-item"><span class="legend-dot" style="--c:#6c757d"></span> Report pending</span>
            </div>

            <!-- Bulk delete (right/top) -->
            <button id="btn-open-bulk-range" type="button" class="btn btn-outline-danger">
                Bulk delete
            </button>
        </div>

        {{-- Bulk delete modal --}}
        <div class="modal fade" id="bulkRangeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form id="bulk-range-form" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bulk delete Open slots (by range)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="bd-date">Date</label>
                            <input type="date" class="form-control" id="bd-date" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold" for="bd-from">From (hour)</label>
                                <select class="form-select" id="bd-from" required></select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold" for="bd-to">To (hour)</label>
                                <select class="form-select" id="bd-to" required></select>
                            </div>
                        </div>

                        <div class="form-text mt-2">
                            Only <strong>Open</strong> slots (student_id is <code>NULL</code>) will be deleted. Booked slots
                            are excluded.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Report modal --}}
        <div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-md">
                <form id="report-form" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Report</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="rpt-booking-id">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="small text-muted">Student</div>
                                <div class="fw-semibold">
                                    <a id="rpt-student-link" href="javascript:void(0)"
                                        class="link-dark text-decoration-none disabled-link">—</a><br>
                                    {{-- ★ 追加: View history リンク（初期は非表示） --}}
                                    <a id="rpt-student-history-link" href="#"
                                        class="small text-decoration-none d-none" rel="noopener">
                                        View history
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted">Date</div>
                                <div id="rpt-date" class="fw-semibold">—</div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted">Time</div>
                                <div id="rpt-time" class="fw-semibold">—</div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="small text-muted">Course</div>
                                <div class="fw-semibold">
                                    <a id="rpt-course-link" href="javascript:void(0)"
                                        class="link-dark text-decoration-none disabled-link">—</a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-muted">Topic</div>
                                <div id="rpt-topic" class="fw-semibold">—</div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label for="rpt-status-select" class="form-label fw-semibold">Status</label>
                            <select id="rpt-status-select" class="form-select" required>
                                <option value="">—</option>
                                {{-- <option value="scheduled">scheduled</option> --}}
                                <option value="Attended">Attended</option>
                                <option value="Absent">Absent</option>
                                {{-- <option value="canceled by student">canceled by student</option> --}}
                                <option value="Canceled by teacher">Canceled by teacher</option> {{-- ★ 要望の選択肢 --}}
                                <option value="Others">Others</option> {{-- ★ 要望の選択肢 --}}
                            </select>
                        </div>

                        {{-- ★ Next topic（候補は id 昇順のリスト）。保存は「テキスト」で reports.next_topic に入れます --}}
                        <div class="mt-3" id="rpt-next-topic-wrap">
                            <label for="rpt-next-topic" class="form-label fw-semibold">Next topic</label>
                            <select id="rpt-next-topic" class="form-select" required></select>

                            <!-- 既存の補足テキスト（通常時に表示） -->
                            <div id="rpt-next-topic-hint" class="form-text">
                                Default = current topic. Options are ordered by id asc.
                            </div>

                            <!-- キャンセル時（Canceled by teacher）だけ表示するメッセージ -->
                            <div id="rpt-next-topic-msg" class="small text-muted d-none">
                                Next topic is not required when the lesson is <strong>Canceled by teacher</strong>.
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-semibold" for="rpt-feedback">Comment</label>
                            <textarea id="rpt-feedback" class="form-control" rows="4"
                                placeholder="Write feedback or the cancellation reason here..." required></textarea>
                            {{-- <div class="form-text">When teacher cancels, put the reason here.</div> --}}
                        </div>

                    </div>

                    <div class="modal-footer gap-2">
                        {{-- Enter classroom ボタンを追加 --}}
                        <a id="btn-enter-classroom" href="#" class="btn btn-primary" target="_blank"
                            rel="noopener">
                            Enter classroom
                        </a>
                        <button id="btn-save-report" type="button" class="btn btn-success">Save report</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        {{-- <button id="btn-submit-cancel" type="button" class="btn btn-danger">Cancel booking</button> --}}
                    </div>
                </form>
            </div>
        </div>

        {{-- カレンダー --}}
        <div id="teacherWeekCal" class="rounded-3 border shadow-sm" data-feed-url="{{ route('teachers.calendar.show') }}"
            data-store-url="{{ route('teachers.bookings.store') }}"
            data-destroy-url="{{ route('teachers.bookings.destroy', ['id' => '__ID__']) }}"
            data-bulkdel-url="{{ route('teachers.bookings.bulkDelete') }}"
            data-cancel-url="{{ route('teachers.bookings.cancel', ['id' => '__ID__']) }}"
            data-report-url="{{ route('teachers.reports.show', ['booking' => '__ID__']) }}"
            data-report-update-url="{{ route('teachers.reports.update', ['booking' => '__ID__']) }}"
            data-student-url="{{ route('students.profile.show', ['user' => '__ID__']) }}"
            data-course-url="{{ route('courses.show', ['course' => '__ID__']) }}"
            data-has-meeting-url="{{ $hasMeetingUrl ? 1 : 0 }}" style="min-height: 500px;"
            data-student-history-url="{{ route('students.lessonhistory', ['student' => '__ID__']) }}"
            data-meeting-url="{{ $hasMeetingUrl ? Auth::user()->meeting_url : '' }}" style="min-height: 500px;"></div>
    </section>
@endsection

@push('styles')
    {{-- ① FullCalendar の公式CSSを必ず先に --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #teacherWeekCal .fc a {
            color: inherit !important;
            text-decoration: none !important;
        }

        #teacherWeekCal .fc-col-header-cell-cushion,
        #teacherWeekCal .fc-timegrid-axis-cushion,
        #teacherWeekCal .fc-daygrid-day-number {
            color: #000 !important;
        }

        #teacherWeekCal .fc .fc-toolbar-title {
            color: #000;
            font-weight: 600;
        }

        #teacherWeekCal .fc .fc-button {
            color: #212529;
            background: #f8f9fa;
            border-color: rgba(0, 0, 0, .12);
        }

        #teacherWeekCal .fc .fc-button:hover {
            background: #e9ecef;
            border-color: rgba(0, 0, 0, .18);
        }

        #teacherWeekCal .fc .fc-scrollgrid {
            border-radius: .75rem;
            overflow: hidden;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .05);
        }

        #teacherWeekCal .fc-theme-standard td,
        #teacherWeekCal .fc-theme-standard th {
            border-color: rgba(0, 0, 0, .08);
        }

        #teacherWeekCal .fc-timegrid-slot-label {
            color: #6c757d;
        }

        /* 1時間コマを強制的に大きくする */
        #teacherWeekCal .fc-timegrid-slot,
        #teacherWeekCal .fc-timegrid-slot-lane {
            height: 2.5rem !important;
            /* お好みで 5.5〜8rem */
            min-height: 2.5rem !important;
            line-height: 2.5rem !important;
            /* 目盛りの縦位置がズレる時の保険 */
        }

        /* スクロールが抑え込まれていないかの保険 */
        #teacherWeekCal .fc-scroller {
            overflow-y: auto !important;
        }

        #tcLegend .legend-item {
            font-size: .85rem;
            color: #000;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            white-space: nowrap;
        }

        #tcLegend .legend-dot {
            width: .8rem;
            height: .8rem;
            border-radius: 999px;
            background: var(--c, #6c757d);
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .18);
            display: inline-block;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('teacherWeekCal');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const hasMeetingUrl = (el.dataset.hasMeetingUrl === '1');
            const meetingUrl = el.dataset.meetingUrl || '';
            const feed = el.dataset.feedUrl;
            const store = el.dataset.storeUrl;
            const delTpl = el.dataset.destroyUrl; // .../__ID__
            const bulk = el.dataset.bulkdelUrl;
            const cancelTpl = el.dataset.cancelUrl; // .../__ID__/cancel
            const enterBtn = document.getElementById('btn-enter-classroom');
            const studentUrlTpl = el.dataset.studentUrl || '';
            const courseUrlTpl = el.dataset.courseUrl || '';
            const studentHistoryUrlTpl = el.dataset.studentHistoryUrl || '';
            if (enterBtn) {
                if (meetingUrl) {
                    enterBtn.href = meetingUrl;
                    enterBtn.classList.remove('d-none');
                } else {
                    // URL 未設定なら押せないようにする（任意）
                    enterBtn.href = '#';
                    enterBtn.classList.add('disabled');
                    enterBtn.classList.add('d-none'); // 完全に隠したい場合
                }
            }

            const ONE_HOUR_MS = 60 * 60 * 1000;
            const nowLocal = () => new Date();

            const fmtYMD = d =>
                `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
            const fmtHms = d =>
                `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}:00`;

            const hoursBetween = (start, end) => {
                const out = [];
                const cur = new Date(start.getTime());
                cur.setMinutes(0, 0, 0);
                while (cur < end) {
                    out.push(fmtHms(cur));
                    cur.setHours(cur.getHours() + 1);
                }
                return out;
            };

            // ステータスの全候補（値と表示ラベル）
            const ALL_STATUS_OPTIONS = [{
                    value: 'Attended',
                    label: 'Attended'
                },
                {
                    value: 'Absent',
                    label: 'Absent'
                },
                {
                    value: 'Canceled by teacher',
                    label: 'Canceled by teacher'
                },
                {
                    value: 'Others',
                    label: 'Others'
                },
            ];

            const PAST_STATUS_OPTIONS = ALL_STATUS_OPTIONS.filter(
                o => o.value !== 'Canceled by teacher'
            );

            function setStatusOptions(selectEl, options) {
                // 先頭のプレースホルダ
                selectEl.innerHTML = '<option value="">—</option>';
                for (const opt of options) {
                    const o = document.createElement('option');
                    o.value = opt.value;
                    o.textContent = opt.label;
                    selectEl.appendChild(o);
                }
            }

            const calendar = new FullCalendar.Calendar(el, {
                themeSystem: 'bootstrap5',
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'timeGridWeek,timeGridDay,dayGridMonth'
                },

                // ▼ スクロールさせるために高さ“固定”
                height: 680, // ← 全体の高さ。好みで 600〜800
                // contentHeight: 750,      // ← 使わない（どちらか一方にする）
                expandRows: false, // ← これが true だと伸びてしまいスクロールしにくい

                // ▼ 時間帯・表示
                allDaySlot: false,
                nowIndicator: true,
                slotMinTime: '06:00:00',
                slotMaxTime: '24:00:00',
                slotDuration: '01:00:00',
                snapDuration: '01:00:00',
                slotLabelInterval: {
                    hours: 1
                },

                // ▼ 初期スクロール位置（開いた瞬間の位置）
                scrollTime: '06:00:00',

                selectable: true,
                selectMirror: true,
                selectOverlap: false,
                views: {
                    timeGridWeek: {
                        eventTimeFormat: {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        }
                    },
                    timeGridDay: {
                        eventTimeFormat: {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        }
                    },
                    dayGridMonth: {
                        eventTimeFormat: {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        },
                        displayEventEnd: true
                    }
                },

                // 過去開始は選べない
                // ✅ ここを置き換え：Month では選択自体を許可しない
                selectAllow(span) {
                    const isMonth = calendar.view?.type === 'dayGridMonth';
                    if (isMonth) return false; // ← 月表示では新規作成不可
                    if (!hasMeetingUrl) return false;
                    return span.start.getTime() >= nowLocal().getTime();
                },

                // 複数枠作成
                select(info) {

                    // ★追加：URL未設定なら何もしない（メッセージ表示して解除）
                    if (!hasMeetingUrl) {
                        alert('To open a slot, set your meeting URL in Profile.');
                        calendar.unselect();
                        return;
                    }
                    const times = hoursBetween(info.start, info.end);
                    if (!times.length) return calendar.unselect();

                    fetch(store, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                date: fmtYMD(info.start),
                                times,
                                duration_minutes: 50
                            })
                        }).then(() => calendar.refetchEvents())
                        .finally(() => calendar.unselect());
                },

                // クリックで削除/取消
                eventClick(arg) {
                    const id = arg.event.id;
                    const ep = arg.event.extendedProps || {};
                    const isBooked = !!ep.student_id;
                    const hasReport = ep.has_report === true;

                    // 将来 Others など、APIが返せない時のための即時プレフィル用
                    const fallback = {
                        student: ep.student_name || null,
                        course: ep.course_title || null,
                        topic: ep.topic_name || null,
                        start: arg.event.start || null,
                        end: arg.event.end || null,
                    };

                    // ▼ 過去の「Canceled by teacher」はモーダルを開かない
                    const ONE_HOUR_MS = 60 * 60 * 1000;
                    const start = arg.event.start;
                    const end = arg.event.end ?? new Date(start.getTime() + ONE_HOUR_MS);
                    const isPast = end.getTime() < Date.now();

                    const statusRaw = ep.report_status ?? '';
                    const status = typeof statusRaw === 'string' ? statusRaw.trim().toLowerCase() : '';

                    if (hasReport && status === 'canceled by teacher' && isPast) {
                        return; // 何もしない（モーダルを開かない）
                    }

                    if (!isBooked && !hasReport && isPast) {
                        return;
                    }

                    if (hasReport || isBooked) {
                        // ← ここがポイント：fallback を渡す
                        openReportModal(id, fallback);
                    } else {
                        const url = delTpl.replace('__ID__', id);
                        if (!confirm('Delete this open slot?')) return;
                        fetch(url, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': csrf,
                                    'Accept': 'application/json'
                                }
                            })
                            .then(() => calendar.refetchEvents());
                    }
                },

                eventSources: [{
                    url: feed,
                    method: 'GET'
                }],

                eventDidMount(info) {
                    const ONE_HOUR_MS = 60 * 60 * 1000;
                    const start = info.event.start;
                    const end = info.event.end ?? new Date(start.getTime() + ONE_HOUR_MS);
                    const isPast = end.getTime() < Date.now();
                    const isBooked = !!info.event.extendedProps?.student_id;

                    const hasReport = info.event.extendedProps?.has_report === true;
                    const statusRaw = info.event.extendedProps?.report_status ?? '';
                    const status = typeof statusRaw === 'string' ? statusRaw.trim().toLowerCase() : '';

                    // FCの既定装飾をリセット
                    const wrap = info.el;
                    const main = info.el.querySelector('.fc-event-main');
                    if (main) {
                        main.style.setProperty('background', 'transparent', 'important');
                        main.style.setProperty('border', 'none', 'important');
                        main.style.setProperty('color', 'inherit', 'important');
                        main.style.padding = '0';
                    }

                    // ==== ここがポイント：色マップ ====
                    // open → 青(#0d6efd)
                    // booked → ピンク(#d63384)
                    // canceled by teacher → 黒(#111827)
                    // report submitted → 緑(#16a34a)
                    // report not submitted → 灰(#6c757d)
                    const palette = {
                        open: {
                            bg: '#0d6efd',
                            brd: '1px solid rgba(13,110,253,.90)'
                        }, // 青
                        booked: {
                            bg: '#d63384',
                            brd: '1px solid #c12d76'
                        }, // ピンク
                        cancel: {
                            bg: '#111827',
                            brd: '1px solid #0b1220'
                        }, // 黒
                        done: {
                            bg: '#16a34a',
                            brd: '1px solid #166534'
                        }, // 緑
                        second: {
                            bg: '#6c757d',
                            brd: '1px solid #5c636a'
                        }, // 灰
                    };

                    // 優先度:
                    // 1) hasReport && status === 'canceled by teacher' → 黒
                    // 2) hasReport → 緑
                    // 3) isPast && !hasReport && isBooked → 灰（報告未提出）
                    // 4) isBooked（未来 & 未提出） → ピンク
                    // 5) それ以外（Open） → 青
                    let col;
                    if (hasReport && status === 'canceled by teacher') {
                        col = palette.cancel;
                    } else if (hasReport) {
                        col = palette.done;
                    } else if (isPast && isBooked) {
                        col = palette.second;
                    } else if (isBooked) {
                        col = palette.booked;
                    } else {
                        col = palette.open;
                    }

                    // 適用
                    wrap.style.setProperty('background', col.bg, 'important');
                    wrap.style.setProperty('border', col.brd, 'important');
                    wrap.style.borderRadius = '.5rem';
                    wrap.style.padding = '.12rem .32rem';
                    wrap.style.fontWeight = '600';
                    wrap.style.setProperty('color', '#fff', 'important');
                    wrap.style.cursor = 'pointer';
                },

                eventContent(arg) {
                    const timeTxt = (arg.timeText || '').replace(/\s*-\s*/g, '–');
                    const wrap = document.createElement('div');
                    wrap.style.display = 'flex';
                    wrap.style.alignItems = 'center';
                    wrap.style.gap = '6px';
                    wrap.style.lineHeight = '1.1';

                    const time = document.createElement('span');
                    time.textContent = timeTxt;
                    time.style.fontWeight = '700';
                    time.style.fontSize = '.85rem';

                    const label = document.createElement('span');
                    const isBooked = !!arg.event.extendedProps?.student_id;
                    const reportStatus = arg.event.extendedProps?.report_status ?? null;
                    const title = arg.event.title || ''; // 学生名などが入っている想定

                    // ★ ここを変更：reportがあれば "Booked" は出さない
                    let labelText;
                    if (reportStatus) {
                        // 学生名があれば「学生名 · status」、なければ「status」のみ
                        labelText = toTitleCase(reportStatus);
                    } else {
                        // reportなし：学生名があればそれ、無ければ Booked/Open
                        labelText = (isBooked ? 'Scheduled' : 'Open');
                    }

                    label.textContent = labelText;
                    label.style.fontWeight = '600';
                    label.style.fontSize = '.85rem';

                    wrap.appendChild(time);
                    wrap.appendChild(label);
                    return {
                        domNodes: [wrap]
                    };
                }
            });
            calendar.render();

            // ==== Bulk delete (by range) ====
            const bulkUrl = el.dataset.bulkdelUrl;

            // Bootstrap Modal（CDNでBootstrap使っている前提）
            const bulkModalEl = document.getElementById('bulkRangeModal');
            const bulkModal = bulkModalEl ? new bootstrap.Modal(bulkModalEl) : null;
            const btnOpenBulk = document.getElementById('btn-open-bulk-range');
            const bulkForm = document.getElementById('bulk-range-form');

            btnOpenBulk?.addEventListener('click', () => {
                // 今日を初期値に
                const d = new Date();
                const ymd =
                    `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
                document.getElementById('bd-date').value = ymd;
                bulkModal?.show();
            });

            bulkForm?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const date = document.getElementById('bd-date').value;
                const from = document.getElementById('bd-from').value;
                const to = document.getElementById('bd-to').value;
                if (!date || !from || !to) return;

                if (!confirm(`Delete OPEN slots on ${date} from ${from} to ${to}?`)) return;

                try {
                    const res = await fetch(bulkUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            date,
                            from,
                            to
                        })
                    });

                    if (!res.ok) {
                        const j = await res.json().catch(() => ({}));
                        console.warn('Bulk delete error', j);
                        alert('Deletion failed. Please check your inputs.');
                        return;
                    }

                    bulkModal?.hide();
                    calendar.refetchEvents();
                } catch (err) {
                    console.error(err);
                    alert('A network error occurred.');
                }
            });
            (function setupBulkDeleteControls() {
                // モーダルのIDは環境に合わせて変更（例: bulkDeleteModal）
                const modalEl = document.getElementById('bulkDeleteModal');
                const dateEl = document.getElementById('bd-date');
                const fromEl = document.getElementById('bd-from');
                const toEl = document.getElementById('bd-to');

                if (!dateEl || !fromEl || !toEl) return;

                const pad = n => String(n).padStart(2, '0');
                const todayYMD = () => {
                    const n = new Date();
                    return `${n.getFullYear()}-${pad(n.getMonth()+1)}-${pad(n.getDate())}`;
                };
                const nextWholeHour = () => {
                    const n = new Date();
                    return (n.getMinutes() > 0 || n.getSeconds() > 0) ? n.getHours() + 1 : n.getHours();
                };

                const makeOptions = (select, startH, endH, selectedH = null) => {
                    select.innerHTML = '';
                    for (let h = startH; h <= endH; h++) {
                        const val = `${pad(h)}:00`;
                        const opt = document.createElement('option');
                        opt.value = val;
                        opt.textContent = val;
                        if (selectedH !== null && h === selectedH) opt.selected = true;
                        select.appendChild(opt);
                    }
                };

                function refresh() {
                    // 今日なら From の最小は「次の丸めた時」かつ 06:00 以上
                    const isToday = dateEl.value === todayYMD();
                    const minFromHour = Math.max(6, Math.min(nextWholeHour(), 24));

                    // From: 06..24（ただし今日なら min 未満を disabled）
                    makeOptions(fromEl, 6, 24);
                    if (isToday) {
                        [...fromEl.options].forEach(o => {
                            const h = parseInt(o.value.slice(0, 2), 10);
                            o.disabled = h < minFromHour;
                        });
                        // デフォルトを許容最小に合わせる
                        const defH = Math.min(Math.max(minFromHour, 6), 24);
                        fromEl.value = `${pad(defH)}:00`;
                    } else {
                        // 今日でなければ 06:00 を初期値
                        fromEl.value = '06:00';
                    }

                    // To: (From+1)..24
                    const fromH = parseInt(fromEl.value.slice(0, 2), 10) || 6;
                    const toStart = Math.min(fromH + 1, 24);
                    makeOptions(toEl, toStart, 24, toStart);
                }

                // From が変わったら To の下限を更新
                fromEl.addEventListener('change', () => {
                    const fromH = parseInt(fromEl.value.slice(0, 2), 10) || 6;
                    const toStart = Math.min(fromH + 1, 24);
                    makeOptions(toEl, toStart, 24, toStart);
                });

                // 日付が変わったら最小From再計算
                dateEl.addEventListener('change', refresh);

                // モーダルが開いたら初期化（Bootstrap 5）
                if (modalEl) {
                    modalEl.addEventListener('shown.bs.modal', () => {
                        if (!dateEl.value) dateEl.value = todayYMD();
                        refresh();
                    });
                } else {
                    // モーダルが無い(固定フォーム)環境でも初期化できるように
                    if (!dateEl.value) dateEl.value = todayYMD();
                    refresh();
                }
            })();

            // ==== Report modal (inside DOMContentLoaded) ====
            const reportTpl = el.dataset.reportUrl;
            const reportUpdateTpl = el.dataset.reportUpdateUrl;
            const reportModalEl = document.getElementById('reportModal');
            const reportModal = reportModalEl ? new bootstrap.Modal(reportModalEl) : null;

            // ステータス変更で Next topic を制御
            document.getElementById('rpt-status-select')?.addEventListener('change', (e) => {
                const val = String(e.target.value || '').trim().toLowerCase();
                setNextTopicDisabled(val === 'canceled by teacher');
            });

            async function openReportModal(bookingId) {
                if (!reportTpl || !reportModal) return;

                const url = reportTpl.replace('__ID__', bookingId);
                try {
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error('Failed to fetch report');
                    const j = await res.json();

                    // 基本情報
                    document.getElementById('rpt-booking-id').value = j.booking?.id ?? bookingId;
                    // document.getElementById('rpt-student').textContent = j.student?.name ?? '—';
                    document.getElementById('rpt-date').textContent = j.booking?.date ?? '—';
                    document.getElementById('rpt-time').textContent =
                        (j.booking?.start && j.booking?.end) ? `${j.booking.start} – ${j.booking.end}` : '—';
                    // document.getElementById('rpt-course').textContent = j.course?.title ?? '—';
                    document.getElementById('rpt-topic').textContent = j.topic?.name ?? '—';

                    // 未来判定
                    const startStr = (j.booking?.start ?? '00:00'); // "HH:MM"
                    const startDT = new Date(`${j.booking?.date}T${startStr}:00`);
                    const isFuture = isFinite(startDT) && startDT.getTime() > Date.now();

                    const statusSelect = document.getElementById('rpt-status-select');

                    if (isFuture) {
                        // 未来枠：Canceled by teacher のみ
                        setStatusOptions(
                            statusSelect,
                            ALL_STATUS_OPTIONS.filter(o => o.value === 'Canceled by teacher')
                        );
                        statusSelect.value = 'Canceled by teacher';
                    } else {
                        // ★ 過去枠：Canceled by teacher を選択肢から除外
                        setStatusOptions(statusSelect, PAST_STATUS_OPTIONS);

                        // 既存レポートの値があれば反映、無ければ空（—）
                        const wanted = j.report?.status ?? '';
                        // “Canceled by teacher” だった場合は選択肢に存在しないので空にする
                        statusSelect.value = [...statusSelect.options].some(o => o.value === wanted) ? wanted :
                            '';
                    }

                    // ここで Next topic の有効/無効を反映
                    {
                        const val = String(statusSelect.value || '').trim().toLowerCase();
                        setNextTopicDisabled(val === 'canceled by teacher'); // 過去はそもそも false になる
                    }

                    {
                        const val = String(statusSelect.value || '').trim().toLowerCase();
                        setNextTopicDisabled(val === 'canceled by teacher');
                    }

                    // ステータス / フィードバック
                    // const wanted = j.report?.status ?? '';
                    // statusSelect.value = [...statusSelect.options].some(o => o.value === wanted) ? wanted : '';
                    document.getElementById('rpt-feedback').value = j.report?.feedback ?? '';

                    // 次回トピック選択肢
                    const preferredId = j.preferred_topic_id ?? null;
                    fillNextTopicSelect(j.topics ?? [], preferredId);

                    // 学生・コース
                    const student = j.student || null;
                    const course = j.course || null;

                    // Studentリンク
                    const sLink = document.getElementById('rpt-student-link');
                    const sHistory = document.getElementById('rpt-student-history-link');

                    if (sLink) {
                        if (student && student.name) {
                            sLink.textContent = student.name;

                            // プロフィールへのリンク（任意で有効化）
                            if (studentUrlTpl && student.id) {
                                sLink.href = studentUrlTpl.replace('__ID__', student.id);
                                sLink.classList.remove('disabled-link');
                            } else {
                                sLink.href = 'javascript:void(0)';
                                sLink.classList.add('disabled-link');
                            }

                            // ★ View history の設定
                            if (sHistory) {
                                if (studentHistoryUrlTpl && student.id) {
                                    sHistory.href = studentHistoryUrlTpl.replace('__ID__', student.id);
                                    sHistory.classList.remove('d-none');
                                } else {
                                    sHistory.href = '#';
                                    sHistory.classList.add('d-none');
                                }
                            }
                        } else {
                            sLink.textContent = '—';
                            sLink.href = 'javascript:void(0)';
                            sLink.classList.add('disabled-link');

                            if (sHistory) {
                                sHistory.href = '#';
                                sHistory.classList.add('d-none');
                            }
                        }
                    }

                    // Courseリンク
                    const cLink = document.getElementById('rpt-course-link');
                    if (cLink) {
                        if (course && course.title) {
                            cLink.textContent = course.title;

                            if (courseUrlTpl && course.id) {
                                cLink.href = courseUrlTpl.replace('__ID__', course.id);
                                cLink.classList.remove('disabled-link');
                            } else {
                                cLink.href = 'javascript:void(0)';
                                cLink.classList.add('disabled-link');
                            }
                        } else {
                            cLink.textContent = '—';
                            cLink.href = 'javascript:void(0)';
                            cLink.classList.add('disabled-link');
                        }
                    }

                } catch (e) {
                    console.error(e);
                    alert('Failed to fetch report.');
                    return;
                } finally {
                    reportModal.show();
                }
            }

            function fillNextTopicSelect(options, preferredId) {
                const sel = document.getElementById('rpt-next-topic');
                sel.innerHTML = '';

                // 先頭のプレースホルダ（必須化に効く）
                const ph = document.createElement('option');
                ph.value = '';
                ph.textContent = '— Select next topic —';
                ph.disabled = true;
                ph.selected = true;
                ph.hidden = true;
                sel.appendChild(ph);

                for (const t of options) {
                    const opt = document.createElement('option');
                    opt.value = String(t.id); // 値はID
                    opt.textContent = t.name ?? '';
                    sel.appendChild(opt);
                }

                // ★ 必ずどれかが選ばれるようにする
                if (preferredId != null && options.some(o => String(o.id) === String(preferredId))) {
                    sel.value = String(preferredId); // 優先候補
                } else if (options.length > 0) {
                    sel.value = String(options[0].id); // 先頭候補にフォールバック
                }

            }

            const reportForm = document.getElementById('report-form');

            document.getElementById('btn-save-report')?.addEventListener('click', async () => {
                if (!reportForm.checkValidity()) {
                    reportForm.reportValidity();
                    return;
                }

                const bookingId = document.getElementById('rpt-booking-id').value;
                const status = document.getElementById('rpt-status-select').value;
                const nextSel = document.getElementById('rpt-next-topic');
                const isNextDisabled = nextSel?.disabled === true;
                const feedback = document.getElementById('rpt-feedback').value.trim();

                if (!reportUpdateTpl || !bookingId) return;
                const url = reportUpdateTpl.replace('__ID__', bookingId);

                // ← ここで payload を条件付きで組む
                const payload = {
                    status,
                    feedback
                };
                if (!isNextDisabled) {
                    const nextTopicRaw = nextSel.value;
                    if (!/^\d+$/.test(nextTopicRaw)) {
                        alert('Please select a next topic.');
                        nextSel.focus();
                        return;
                    }
                    payload.next_topic = parseInt(nextTopicRaw, 10);
                }

                try {
                    const res = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!res.ok) {
                        let msg = 'Failed to save the report.';
                        try {
                            const text = await res.text();
                            try {
                                msg = JSON.parse(text).message || JSON.parse(text).error || text;
                            } catch {
                                msg = text || msg;
                            }
                        } catch {}
                        alert(msg);
                        return;
                    }

                    reportModal.hide();
                    calendar.refetchEvents();
                } catch (e) {
                    console.error(e);
                    alert('A network error occurred.');
                }
            });

            // Cancel booking（キャンセル理由→学生通知＋report に反映はサーバ側で実装）
            document.getElementById('btn-submit-cancel')?.addEventListener('click', async () => {
                const bookingId = document.getElementById('rpt-booking-id').value;
                const reason = document.getElementById('rpt-cancel-reason').value.trim();
                if (!bookingId) return;
                if (!reason) {
                    alert('Please enter a reason.');
                    return;
                }

                const url = cancelTpl.replace('__ID__', bookingId);
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            reason
                        })
                    });
                    if (!res.ok) {
                        const j = await res.json().catch(() => ({}));
                        console.warn('cancel error', j);
                        alert('Cancel failed.');
                        return;
                    }
                    reportModal?.hide();
                    calendar.refetchEvents();
                } catch (e) {
                    console.error(e);
                    alert('Network error.');
                }
            });

            // Title Case（先頭語は必ず大文字。a, an, the, by などは中語なら小文字に）
            const toTitleCase = (str) => {
                if (!str) return '';
                const SMALL = new Set(['a', 'an', 'the', 'and', 'or', 'but', 'for', 'nor', 'as', 'at', 'by',
                    'for', 'from', 'in', 'into', 'near', 'of', 'on', 'onto', 'to', 'vs', 'via'
                ]);
                const words = String(str).toLowerCase().split(/\s+/);
                return words.map((w, i) => {
                    if (i === 0) return w.charAt(0).toUpperCase() + w.slice(1); // 先頭語は必ず大文字
                    return SMALL.has(w) ? w : (w.charAt(0).toUpperCase() + w.slice(1));
                }).join(' ');
            };

            function setNextTopicDisabled(disabled) {
                const sel = document.getElementById('rpt-next-topic');
                const hint = document.getElementById('rpt-next-topic-hint');
                const msg = document.getElementById('rpt-next-topic-msg');
                if (!sel) return;

                // 入力制御
                sel.disabled = !!disabled;

                // required の出し入れ（無効時は必須を外す）
                if (disabled) {
                    sel.dataset.wasRequired = sel.hasAttribute('required') ? '1' : '0';
                    sel.removeAttribute('required');
                } else {
                    if (sel.dataset.wasRequired !== '0') sel.setAttribute('required', '');
                }

                // 見た目：セレクト/通常ヒントは隠す、メッセージは出す
                sel.classList.toggle('d-none', !!disabled);
                hint?.classList.toggle('d-none', !!disabled);
                msg?.classList.toggle('d-none', !disabled);
            }
        });
    </script>
@endpush
