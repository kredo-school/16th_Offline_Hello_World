<div>
                {{-- コース画像 --}}
            <div class="mb-3">
                <img src="{{ $course->display_image }}"
                    class="course-header-image rounded"
                    alt="{{ $course->title }}">
            </div>


            {{-- コースタイトル & アクション --}}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="fw-bold mb-0">{{ $course->title }}</h3>

               {{-- 管理者のみ Unenroll ボタンを表示 --}}
                @if(Auth::user() && Auth::user()->role_id === 1)
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#unenrollModal-{{ $course->id }}">
                        Unenroll
                    </button>
                @endif
            </div>

                {{-- 管理者用 Unenroll モーダル --}}
                    @if(Auth::user() && Auth::user()->role_id === 1)
                        <div class="modal fade" id="unenrollModal-{{ $course->id }}" tabindex="-1" aria-labelledby="unenrollModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="unenrollModalLabel">Confirm Unenroll</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure you want to unenroll from <strong>{{ $course->title }}</strong>?
                                    </div>
                                    <div class="modal-footer">
                                        <form action="{{ route('courses.unenroll', $course->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">Yes, Unenroll</button>
                                        </form>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    </div>
                        {{-- コース進捗 --}}
                        <p class="text-muted">Overall Progress: {{ $coursePercent }}%</p>
                        <div class="progress mb-4" style="height:8px;">
                            <div class="progress-bar bg-info" style="width: {{ $coursePercent }}%;"></div>
                        </div>


    {{-- セクションごとのアコーディオン --}}
    <div class="accordion mt-3" id="courseAccordion">
        @foreach($course->topics as $topicIndex => $topic)
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading{{ $topic->id }}">
                    <button class="accordion-button collapsed" type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#collapse{{ $topic->id }}" 
                            aria-expanded="false" 
                            aria-controls="collapse{{ $topic->id }}">
                        {{ $loop->iteration }}. {{ $topic->title }}
                        <span class="ms-2 text-muted small">
                            {{ $topicProgress[$topic->id]['completed'] ?? 0 }}/{{ $topicProgress[$topic->id]['total'] ?? 0 }}
                        </span>
                    </button>
                </h2>
                <div id="collapse{{ $topic->id }}" class="accordion-collapse collapse" 
                     aria-labelledby="heading{{ $topic->id }}" 
                     data-bs-parent="#courseAccordion">
                    <div class="accordion-body">
                        <ul class="list-unstyled mb-0">
                            @foreach($topic->lessons as $lesson)
                                <li class="d-flex align-items-center mb-2">
                                    <form method="POST" 
                                          action="{{ route('lessons.toggle', [$course->id, $lesson->id]) }}"
                                          class="lesson-toggle-form me-2">
                                        @csrf
                                        <input type="checkbox" class="form-check-input lesson-checkbox"
                                               onchange="this.form.submit()"
                                               {{ in_array($lesson->id, $completedLessonIds ?? []) ? 'checked' : '' }}  disabled>
                                    </form>
                                    <span>{{ $lesson->title }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>