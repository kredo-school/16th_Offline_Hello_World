@extends('layouts.app')

@push('styles')
<style>
.lesson-status-icon {
    cursor: pointer;
    font-size: 1rem;
}

.lesson-text-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    min-height: 300px;
    white-space: pre-wrap;
    transition: opacity 0.3s ease;
}

/* 左プレビューサイドバー */
.col-md-2.preview-sidebar {
    overflow-y: auto;
    max-height: 90vh;
    padding: 5px;
}

/* 小さなサムネイル - ページ番号を重ねるためにposition: relativeを追加 */
.preview-page {
    width: 60px;
    height: 40px;
    margin-bottom: 4px;
    cursor: pointer;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    overflow: hidden;
    transition: transform 0.2s, border-color 0.2s;
    position: relative;
}

.preview-page-number {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: bold;
    color: white;
    background-color: rgba(0, 0, 0, 0.4);
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.2s;
}

.preview-page:hover .preview-page-number,
.preview-page[data-has-image="false"] .preview-page-number {
    opacity: 1;
}

.preview-page img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border-radius: 4px;
}

.preview-page.active {
    border-color: #0d6efd;
    transform: scale(1.1);
}
  

@media (max-width: 767.98px) {
    .col-md-2, .col-md-7, .col-md-3 {
        flex: 0 0 100%;
        max-width: 100%;
        margin-bottom: 15px;
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        {{-- 戻るボタン --}}
            <div class="mb-3">
                <a href="{{ route('selflearning.show', $course->id) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                </a>
            </div>

        {{-- 左：プレビュー (コース全体の通しページ番号を表示) --}}
        <div class="col-md-2 border-end p-2 preview-sidebar">
            @php $globalPageNumber = 0; @endphp
            @foreach($course->topics as $topic)
                @foreach($topic->lessons as $lesson)
                    @for($p = 1; $p <= $lesson->pages; $p++)
                        @php
                            $globalPageNumber++;
                            $hasImage = $lesson->images && isset($lesson->images[$p-1]);
                        @endphp
                        <div class="preview-page 
                             {{ $lesson->id == $currentLesson->id && $p == $currentIndex+1 ? 'active' : '' }}"
                             data-lesson-id="{{ $lesson->id }}"
                             data-page="{{ $p }}"
                             data-has-image="{{ $hasImage ? 'true' : 'false' }}">
                            @if($hasImage)
                                <img src="{{ $lesson->images[$p-1] }}" class="img-fluid">
                            @endif
                            <div class="preview-page-number">{{ $globalPageNumber }}</div>
                        </div>
                    @endfor
                @endforeach
            @endforeach
        </div>

        {{-- 中央：テキスト・大きな画像 --}}
        <div class="col-md-7 p-3">
            {{-- ページ送りボタン（上） - クラスに page-nav-btn を追加 --}}
            <div class="d-flex justify-content-between mb-3">
                <button id="prevPageBtn" type="button" class="btn page-nav-btn" disabled>
                    <i class="fas fa-chevron-left"></i> 前のページ
                </button>
                <button id="nextPageBtn" type="button" class="btn page-nav-btn">
                    次のページ <i class="fas fa-chevron-right"></i>
                </button>

            </div>

            <div id="lessonContainer">
                @foreach($course->topics as $topic)
                    @foreach($topic->lessons as $lesson)
                        @for($p = 1; $p <= $lesson->pages; $p++)
                            <div class="lesson-item mb-4 p-3 rounded shadow"
                                 data-lesson-id="{{ $lesson->id }}"
                                 data-page="{{ $p }}"
                                 style="display:none; opacity:0; transition: opacity 0.3s;">
                                @if($lesson->images && isset($lesson->images[$p-1]))
                                    <img src="{{ $lesson->images[$p-1] }}" class="img-fluid rounded mb-2">
                                @elseif($lesson->content)
                                    <div class="lesson-text-content">{!! nl2br(e($lesson->content)) !!}</div>
                                @else
                                    <div class="bg-light text-center p-5 rounded">No content</div>
                                @endif
                                <div class="text-muted mt-1">{{ $course->title }} &lt; {{ $lesson->title }}</div>
                                <div class="text-end text-muted small">
                                    Page <span class="current-page-num">{{ $p }}</span> / <span class="total-pages-num">{{ $lesson->pages }}</span>
                                </div>
                            </div>
                        @endfor
                    @endforeach
                @endforeach
            </div>
        </div>

        {{-- 右：TOC --}}
        <div class="col-md-3 border-start bg-white p-3" style="min-height:100vh; overflow-y:auto;">
            <h5 class="fw-bold mb-3">Table of contents</h5>
            <div class="accordion" id="textAccordion">
                @foreach($course->topics as $sIndex => $topic)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingText{{ $sIndex }}">
                            <button class="accordion-button {{ $sIndex>0?'collapsed':'' }}" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapseText{{ $sIndex }}">
                                {{ $loop->iteration }}. {{ $topic->title }}
                                <span class="ms-auto small text-muted">
                                    {{ $topic->lessons->count() }} lessons ・ {{ $topic->lessons->sum('pages') }} pages
                                </span>
                            </button>
                        </h2>
                        <div id="collapseText{{ $sIndex }}"
                             class="accordion-collapse collapse {{ $sIndex==0?'show':'' }}"
                             data-bs-parent="#textAccordion">
                            <div class="accordion-body">
                                <ul class="list-unstyled">
                                    @foreach($topic->lessons as $lesson)
                                    <li class="mb-2 d-flex align-items-center">
                                        <i class="lesson-status-icon me-2 {{ auth()->user()->completedLessons->contains($lesson->id)?'fa-solid fa-check-circle text-success':'fa-regular fa-circle text-muted' }}" 
                                           data-lesson-id="{{ $lesson->id }}"></i>
                                        <a href="javascript:void(0);" class="flex-grow-1 lesson-link text-decoration-none {{ $lesson->id==$currentLesson->id?'fw-bold text-primary':'text-dark' }}" data-lesson-id="{{ $lesson->id }}">
                                            {{ $lesson->title }}
                                        </a>
                                        <span class="ms-auto small text-muted">{{ $lesson->pages }} pages</span>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function(){
    const lessons = Array.from(document.querySelectorAll("#lessonContainer .lesson-item"));
    const previews = document.querySelectorAll(".preview-page");
    const statusIcons = document.querySelectorAll(".lesson-status-icon");
    const prevBtn = document.getElementById("prevPageBtn");
    const nextBtn = document.getElementById("nextPageBtn");
    const sidebar = document.querySelector('.preview-sidebar'); 

    let currentLessonId = parseInt("{{ $currentLesson->id }}");
    let currentPage = parseInt({{ $currentIndex+1 }});
    const totalPages = {};
    const lessonIds = Array.from(new Set(lessons.map(l => parseInt(l.dataset.lessonId))));

    lessons.forEach(l => {
        const lid = parseInt(l.dataset.lessonId);
        if (!totalPages[lid]) totalPages[lid] = parseInt(l.querySelector(".total-pages-num").textContent);
    });

    function updatePaginationButtons() {
        const lessonPages = totalPages[currentLessonId];
        const currentLessonIndex = lessonIds.indexOf(currentLessonId);
        prevBtn.disabled = currentPage === 1 && currentLessonIndex === 0;
        nextBtn.disabled = currentPage === lessonPages && currentLessonIndex === lessonIds.length - 1;
    }

    function showLesson(lessonId, page = 1){
        lessons.forEach(l=>{
            if(parseInt(l.dataset.lessonId) === parseInt(lessonId) && parseInt(l.dataset.page) === parseInt(page)){
                l.style.display='block';
                setTimeout(()=>l.style.opacity=1,50);
            } else {
                l.style.opacity=0;
                setTimeout(()=>l.style.display='none',300);
            }
        });

        document.querySelectorAll('.lesson-link').forEach(l=>l.classList.remove('fw-bold','text-primary'));
        const activeLink = document.querySelector(`.lesson-link[data-lesson-id="${lessonId}"]`);
        if(activeLink) activeLink.classList.add('fw-bold','text-primary');

        previews.forEach(p=>p.classList.remove('active'));
        const activePreview = Array.from(previews).find(p=>parseInt(p.dataset.lessonId)===parseInt(lessonId) && parseInt(p.dataset.page)===parseInt(page));
        if(activePreview) activePreview.classList.add('active');

        currentLessonId = parseInt(lessonId);
        currentPage = parseInt(page);

        updatePaginationButtons();

        // ←サイドバー内だけスクロール
        if(activePreview) {
            const sidebarRect = sidebar.getBoundingClientRect();
            const previewRect = activePreview.getBoundingClientRect();
            const offset = previewRect.top - sidebarRect.top - (sidebar.clientHeight / 2) + (previewRect.height / 2);
            sidebar.scrollBy({top: offset, behavior: 'smooth'});
        }
    }

    function goToNextPage() {
        const lessonPages = totalPages[currentLessonId];
        let newLessonId = currentLessonId;
        let newPage = currentPage + 1;

        if (newPage > lessonPages) {
            const idx = lessonIds.indexOf(currentLessonId);
            if (idx < lessonIds.length - 1) {
                newLessonId = lessonIds[idx + 1];
                newPage = 1;
            } else return;
        }
        showLesson(newLessonId, newPage);
    }

    function goToPrevPage() {
        let newLessonId = currentLessonId;
        let newPage = currentPage - 1;

        if (newPage < 1) {
            const idx = lessonIds.indexOf(currentLessonId);
            if (idx > 0) {
                newLessonId = lessonIds[idx - 1];
                newPage = totalPages[newLessonId];
            } else return;
        }
        showLesson(newLessonId, newPage);
    }

    showLesson(currentLessonId, currentPage);

    previews.forEach(p=>{
        p.addEventListener("click", function(){
            showLesson(parseInt(this.dataset.lessonId), parseInt(this.dataset.page));
        });
    });

    document.querySelectorAll('.lesson-link').forEach(link=>{
        link.addEventListener("click", function(){
            showLesson(parseInt(this.dataset.lessonId),1);
        });
    });

    prevBtn.addEventListener("click", goToPrevPage);
    nextBtn.addEventListener("click", goToNextPage);

    document.addEventListener("keydown", function(e) {
        if (e.key === "ArrowRight") goToNextPage();
        else if (e.key === "ArrowLeft") goToPrevPage();
    });

    statusIcons.forEach(icon=>{
        icon.addEventListener("click", function(){
            const lid = parseInt(this.dataset.lessonId);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute("content");

            fetch(`/selflearning/{{ $course->id }}/lesson/${lid}/toggle`, {
                method:"POST",
                headers:{
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept":"application/json"
                }
            })
            .then(res=>res.json())
            .then(data=>{
                if(data.status==="checked") this.className="lesson-status-icon fa-solid fa-check-circle text-success me-2";
                else this.className="lesson-status-icon fa-regular fa-circle text-muted me-2";
            });
        });
    });
});

</script>
@endpush
@endsection