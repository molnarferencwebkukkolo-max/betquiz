@php
    $cName = is_array($quiz->category->name ?? null)
        ? ($quiz->category->name['hu'] ?? reset($quiz->category->name))
        : ($quiz->category->name ?? 'Általános');
@endphp

<div class="quiz-card" style="height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
    <div>
        <div class="quiz-card-cover" style="height: 8rem;">
            @if(!empty($quiz->cover_image))
                <img src="{{ asset('storage/' . $quiz->cover_image) }}" alt="{{ $quiz->title }}" style="width: 100%; height: 100%; object-fit: cover;">
            @endif
            <span class="badge-category-float">
                {{ $cName }}
            </span>
        </div>

        <div class="quiz-card-body">
            <h4 class="quiz-card-title" style="font-size: 1rem;">{{ $quiz->title }}</h4>
            <p class="quiz-card-desc">{{ Str::limit($quiz->description ?? 'Nincs leírás.', 80) }}</p>
        </div>
    </div>

    <div class="quiz-card-footer" style="display: flex; align-items: center; justify-content: space-between; padding-top: 0.75rem;">
        <span style="font-size: 0.75rem; color: #9ca3af; font-weight: 700;">❓ {{ $quiz->questions_count ?? 0 }} kérdés</span>

        <a href="{{ route('quiz.setup', $quiz->id) }}" class="btn-primary-purple" style="padding: 0.5rem 1rem; font-size: 0.75rem; text-decoration: none;">
            🎮 Játék
        </a>
    </div>
</div>
