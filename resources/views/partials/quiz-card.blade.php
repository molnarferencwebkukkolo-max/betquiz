@php
    // A kategória neve lehet fordított tömb vagy sima string, mindkét formátumot kezeljük.
    $categoryName = 'Általános';
    if (isset($quiz->category)) {
        if (is_array($quiz->category->name)) {
            $categoryName = $quiz->category->name['hu'] ?? reset($quiz->category->name);
        } elseif (is_string($quiz->category->name)) {
            $categoryName = $quiz->category->name;
        }
    }
@endphp

<div class="quiz-card" style="height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
    <div>
        <div class="quiz-card-cover" style="height: 8rem; position: relative;">
            @if(!empty($quiz->cover_image))
                <img src="{{ asset('storage/' . $quiz->cover_image) }}" alt="{{ $quiz->title }}" style="width: 100%; height: 100%; object-fit: cover;">
            @else
                <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #1f2937, #111827);"></div>
            @endif

            <span class="badge-category-float">
                {{ $categoryName }}
            </span>

            <div style="position: absolute; bottom: 8px; left: 8px; display: flex; gap: 4px; flex-wrap: wrap;">
                <span style="background: #4f46e5; color: white; padding: 2px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800;">
                    Lvl {{ $quiz->level }}
                </span>
                <span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800;">
                    {{ $quiz->badge }}
                </span>
            </div>
        </div>

        <div class="quiz-card-body" style="padding: 0.75rem;">
            <h4 class="quiz-card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">{{ $quiz->title }}</h4>
            <p class="quiz-card-desc" style="font-size: 0.85rem; color: #9ca3af;">
                {{ Str::limit($quiz->description ?? 'Nincs leírás.', 80) }}
            </p>
            @if($quiz->relationLoaded('tags') && $quiz->tags->isNotEmpty())
                <div style="display: flex; gap: 0.35rem; flex-wrap: wrap; margin-top: 0.5rem;">
                    @foreach($quiz->tags->take(3) as $tag)
                        <span style="font-size: 0.68rem; font-weight: 800; color: #4f46e5; background: #eef2ff; border-radius: 9999px; padding: 0.2rem 0.45rem;">{{ $tag->name }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="quiz-card-footer" style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem;">
        <div style="font-size: 0.75rem; color: #9ca3af; font-weight: 700; display: flex; flex-direction: column; gap: 0.1rem;">
            <span>{{ $quiz->questions_count ?? 0 }} kérdés</span>
            <span>{{ number_format($quiz->totalAnswersCount(), 0, ',', ' ') }} válasz / {{ number_format($quiz->correctAnswersCount(), 0, ',', ' ') }} helyes</span>
        </div>

        @auth
            <a href="{{ route('quiz.setup', $quiz) }}" class="btn-primary-purple" style="padding: 0.5rem 1rem; font-size: 0.75rem; text-decoration: none;">
                Játék
            </a>
        @else
            <button type="button" class="btn-primary-purple" onclick="openGuestAuthPrompt()" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                Játék
            </button>
        @endauth
    </div>
</div>
