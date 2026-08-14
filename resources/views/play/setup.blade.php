@extends('layouts.game')

@section('title', $quiz->effective_seo_title . ' - KwizzGo')
@section('meta_description', $quiz->effective_seo_description)
@section('body_class', 'kwizzgo-setup-page')
@section('page_wrapper_class', 'kwizzgo-setup-wrapper')

@section('content')
<main class="quiz-setup-shell" x-data="{
    mode: 'normal',
    userPoints: {{ auth()->user()->points ?? 0 }},
    bet: 50,
    time: 60,
    difficulty: 'mixed',
    questionCount: {{ $remainingQuestionsCount >= 30 ? 30 : ($remainingQuestionsCount >= 20 ? 20 : 10) }}
}">
    <a href="{{ route('quizzes.index') }}" class="setup-back-link">← Vissza a kvízekhez</a>

    @if(session('success'))<div class="setup-alert success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="setup-alert error">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="setup-alert error">{{ $errors->first() }}</div>@endif

    <div class="quiz-setup-layout">
        <aside class="setup-quiz-card">
            <div class="setup-cover {{ $quiz->cover_image ? 'has-image' : '' }}" @if($quiz->cover_image) style="background-image:url('{{ asset('storage/'.$quiz->cover_image) }}')" @endif>
                <div class="setup-cover-shade"></div>
                <span class="setup-category">{{ $quiz->category->icon }} {{ $quiz->category->translated_name ?? 'Általános' }}</span>
                @unless($quiz->cover_image)<span class="setup-cover-mark">?</span>@endunless
            </div>
            <div class="setup-quiz-copy">
                <span class="setup-eyebrow">KwizzGo kvíz</span>
                <h1>{{ $quiz->title }}</h1>
                <p>{{ $quiz->description }}</p>
                @if($quiz->tags->isNotEmpty())
                    <div class="setup-tags">@foreach($quiz->tags as $tag)<span>#{{ $tag->name }}</span>@endforeach</div>
                @endif
                <div class="setup-author"><div>{{ mb_strtoupper(mb_substr($quiz->creator->username ?? $quiz->creator->name ?? '?', 0, 1)) }}</div><p>Készítette<strong>{{ $quiz->creator->username ?? $quiz->creator->name ?? 'Ismeretlen' }}</strong></p></div>
                <div class="setup-quiz-stats">
                    <article><span>▤</span><p>Összes kérdés<strong>{{ $totalQuestionsCount }} db</strong></p></article>
                    <article><span>✓</span><p>Még kitölthető<strong>{{ $remainingQuestionsCount }} db</strong></p></article>
                    <article><span>◎</span><p>Teljesítve<strong>{{ $answeredCount }} db</strong></p></article>
                </div>
                <div class="setup-social-actions">
                    @if(!$isDisliked)<form action="{{ route('quiz.toggle-favorite', $quiz) }}" method="POST">@csrf<button class="{{ $isFavorite ? 'active favorite' : '' }}">♥ {{ $isFavorite ? 'Kedvenc' : 'Kedvencekhez' }}</button></form>@endif
                    @if(!$isFavorite)<form action="{{ route('quiz.toggle-dislike', $quiz) }}" method="POST">@csrf<button class="{{ $isDisliked ? 'active' : '' }}">⌄ {{ $isDisliked ? 'Visszavonás' : 'Nem tetszik' }}</button></form>@endif
                </div>
                @if($answeredCount > 0)
                    <form action="{{ route('quiz.reset-answers', $quiz) }}" method="POST" class="setup-reset-form" onsubmit="return confirm('Biztosan feléleszted a kvízt? Ez {{ $resetCost }} PT-be kerül.')">@csrf<button>⚡ Kvíz felélesztése <strong>{{ $resetCost }} PT</strong><small>{{ $answeredCount }} kérdés × {{ $resetCostPerQuestion }} PT</small></button></form>
                @endif
            </div>
        </aside>

        <section class="setup-config-panel">
            <header><span class="setup-eyebrow">Játék előkészítése</span><h2>Állítsd össze a játékodat</h2><p>Válaszd ki a játékmódot, a tétet és a neked megfelelő kihívást.</p></header>
            <form action="{{ route('quiz.start_play', $quiz) }}" method="POST">@csrf
                <div class="setup-section">
                    <div class="setup-section-title"><span>1</span><div><strong>Játékmód</strong><small>Hogyan szeretnél játszani?</small></div></div>
                    <div class="setup-choice-grid modes">
                        <button type="button" @click="mode='normal'; bet=50" :class="{'selected':mode==='normal'}"><i>🎯</i><strong>Normál mód</strong><small>Fix szorzók, minden kör után kiszállhatsz.</small><b x-show="mode==='normal'">✓</b></button>
                        <button type="button" @click="mode='odds'; bet=10" :class="{'selected':mode==='odds'}" @disabled($remainingQuestionsCount < 10)><i>🎲</i><strong>Odds mód</strong><small>Egymásra épülő nyeremény, nagyobb kockázat.</small><b x-show="mode==='odds'">✓</b></button>
                    </div>
                    @if($remainingQuestionsCount < 10)<p class="setup-warning">Az Odds módhoz legalább 10 kitöltetlen kérdés szükséges.</p>@endif
                    <input type="hidden" name="game_mode" :value="mode">
                </div>

                <div class="setup-section">
                    <div class="setup-section-title"><span>2</span><div><strong>Tét</strong><small>Egyenleged: {{ number_format(auth()->user()->points, 0, ',', ' ') }} PT</small></div><em x-text="bet + ' PT'"></em></div>
                    <input class="setup-number-input" type="number" name="bet_points" x-model.number="bet" :min="mode==='normal'?50:10" :max="mode==='normal'?userPoints:100">
                    <input class="setup-range" type="range" x-model.number="bet" :min="mode==='normal'?50:10" :max="mode==='normal'?Math.max(50,userPoints):100" step="10">
                    <div class="setup-range-labels"><span x-text="mode==='normal'?'50 PT':'10 PT'"></span><span x-text="mode==='normal'?userPoints+' PT':'100 PT'"></span></div>
                </div>

                <div class="setup-section" x-show="mode==='odds'" x-cloak>
                    <div class="setup-section-title"><span>3</span><div><strong>Kérdések száma</strong><small>{{ $remainingQuestionsCount }} megválaszolható kérdés</small></div></div>
                    <div class="setup-choice-grid triples"><template x-for="count in [10,20,30]"><button type="button" @click="if({{ $remainingQuestionsCount }}>=count)questionCount=count" :disabled="{{ $remainingQuestionsCount }}<count" :class="{'selected':questionCount===count}"><strong x-text="count"></strong><small>kérdés</small></button></template></div>
                    <input type="hidden" name="question_count" :value="questionCount">
                </div>

                <div class="setup-section">
                    <div class="setup-section-title"><span x-text="mode==='odds'?4:3"></span><div><strong>Válaszadási idő</strong><small>Kérdésenként rendelkezésre álló idő</small></div></div>
                    <div class="setup-choice-grid triples">
                        <button type="button" @click="time=60" :class="{'selected':time===60}"><strong>60 mp</strong><small>1,0× szorzó</small></button>
                        <button type="button" @click="time=30" :class="{'selected':time===30}"><strong>30 mp</strong><small>1,5× szorzó</small></button>
                        <button type="button" @click="time=10" :class="{'selected':time===10}"><strong>10 mp</strong><small>2,0× szorzó</small></button>
                    </div>
                    <input type="hidden" name="time_limit" :value="time">
                </div>

                <div class="setup-section">
                    <div class="setup-section-title"><span x-text="mode==='odds'?5:4"></span><div><strong>Nehézségi szint</strong><small>Milyen kérdésekből válogassunk?</small></div></div>
                    <div class="setup-difficulty-grid">
                        @foreach(['mixed'=>['◈','Vegyes','Minden szint'],'easy'=>['●','Könnyű','1,2× szorzó'],'medium'=>['●','Közepes','1,5× szorzó'],'hard'=>['●','Nehéz','2,0× szorzó']] as $value=>$item)
                            <label :class="{'selected':difficulty==='{{ $value }}'}"><input type="radio" name="difficulty" value="{{ $value }}" x-model="difficulty"><i>{{ $item[0] }}</i><strong>{{ $item[1] }}</strong><small>{{ $item[2] }}</small></label>
                        @endforeach
                    </div>
                </div>

                <div class="setup-launch-row"><a href="{{ route('quizzes.index') }}">Mégse</a><button type="submit">Játék indítása <span>→</span></button></div>
            </form>
        </section>
    </div>
    <x-ad-slot position="content_horizontal" />
</main>
@endsection
