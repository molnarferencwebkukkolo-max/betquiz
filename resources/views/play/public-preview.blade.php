<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $quiz->title }}</title>
    <meta name="description" content="{{ $quiz->effective_seo_description }}">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="KwizzGo">
    <meta property="og:locale" content="hu_HU">
    <meta property="og:title" content="{{ $quiz->title }}">
    <meta property="og:description" content="{{ $quiz->effective_seo_description }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $socialImage['url'] }}">
    <meta property="og:image:secure_url" content="{{ $socialImage['url'] }}">
    @if($socialImage['width'])<meta property="og:image:width" content="{{ $socialImage['width'] }}">@endif
    @if($socialImage['height'])<meta property="og:image:height" content="{{ $socialImage['height'] }}">@endif
    @if($socialImage['type'])<meta property="og:image:type" content="{{ $socialImage['type'] }}">@endif
    <meta property="og:image:alt" content="{{ $quiz->title }} kvízborító">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $quiz->title }}">
    <meta name="twitter:description" content="{{ $quiz->effective_seo_description }}">
    <meta name="twitter:image" content="{{ $socialImage['url'] }}">
    <meta name="twitter:image:alt" content="{{ $quiz->title }} kvízborító">

    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Quiz',
        'name' => $quiz->title,
        'description' => $quiz->effective_seo_description,
        'url' => $canonicalUrl,
        'image' => $socialImage['url'],
        'numberOfQuestions' => $quiz->active_questions_count,
        'author' => ['@type' => 'Person', 'name' => $quiz->creator->username ?? $quiz->creator->name ?? 'KwizzGo közösség'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="public-quiz-page">
@include('layouts.navigation')
<main class="public-quiz-shell">
    <article class="public-quiz-card">
        <div class="public-quiz-cover {{ $quiz->cover_image ? 'has-image' : '' }}" @if($quiz->cover_image) style="background-image:url('{{ $socialImage['url'] }}')" @endif>
            <span>{{ $quiz->category->icon ?? '✦' }} {{ $quiz->category->translated_name ?? $quiz->category->name ?? 'Általános' }}</span>
            @unless($quiz->cover_image)<strong>Kwizz<span>Go</span></strong>@endunless
        </div>
        <div class="public-quiz-copy">
            <p class="public-quiz-eyebrow">Megosztott KwizzGo kvíz</p>
            <h1>{{ $quiz->title }}</h1>
            <p class="public-quiz-description">{{ $quiz->description }}</p>
            @if($quiz->tags->isNotEmpty())
                <div class="public-quiz-tags">@foreach($quiz->tags as $tag)<span>#{{ $tag->name }}</span>@endforeach</div>
            @endif
            <dl class="public-quiz-stats">
                <div><dt>Kérdések</dt><dd>{{ $quiz->active_questions_count }}</dd></div>
                <div><dt>Kitöltések</dt><dd>{{ number_format($quiz->totalAnswersCount(), 0, ',', ' ') }}</dd></div>
                <div><dt>Készítette</dt><dd>{{ $quiz->creator->username ?? $quiz->creator->name ?? 'Ismeretlen' }}</dd></div>
            </dl>
            @auth
                <a class="public-quiz-cta" href="{{ route('quiz.setup', $quiz) }}">Játék indítása</a>
            @else
                <a class="public-quiz-cta" href="{{ route('login') }}">Belépés és játék</a>
                <p class="public-quiz-register">Még nincs fiókod? <a href="{{ route('register') }}">Regisztrálj ingyen!</a></p>
            @endauth
        </div>
    </article>
</main>
<x-site-footer />
</body>
</html>
