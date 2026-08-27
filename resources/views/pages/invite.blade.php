<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meghívtak a KwizzGo-ra – Regisztrálj és játssz!</title>
    <meta name="description" content="Csatlakozz a KwizzGo közösségéhez, készíts és játssz kvízeket, versenyezz, és indulj 1 000 PT kezdőtőkével!">
    <meta name="robots" content="noindex,follow">
    <link rel="canonical" href="{{ $inviteUrl }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="KwizzGo">
    <meta property="og:locale" content="hu_HU">
    <meta property="og:title" content="Meghívtak a KwizzGo-ra!">
    <meta property="og:description" content="Fogadd el a meghívást, regisztrálj ingyen, és kezdd a játékot 1 000 PT kezdőtőkével!">
    <meta property="og:url" content="{{ $inviteUrl }}">
    <meta property="og:image" content="{{ $socialImage }}">
    <meta property="og:image:secure_url" content="{{ $socialImage }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="KwizzGo meghívó – Tanulj, játssz és versenyezz">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Meghívtak a KwizzGo-ra!">
    <meta name="twitter:description" content="Regisztrálj ingyen, és kezdd a játékot 1 000 PT kezdőtőkével!">
    <meta name="twitter:image" content="{{ $socialImage }}">
    <meta name="twitter:image:alt" content="KwizzGo meghívó">

    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}?v={{ filemtime(public_path('css/app-custom.css')) }}">
</head>
<body class="invite-landing-page">
@include('layouts.navigation')
<main class="invite-landing-shell">
    <section class="invite-landing-card">
        <span class="invite-landing-eyebrow">Személyes meghívó</span>
        <h1>Meghívtak a <em>KwizzGo</em> világába!</h1>
        <p>Készíts saját kvízeket, próbáld ki magad izgalmas témákban, gyűjts pontokat és versenyezz a közösséggel.</p>
        <div class="invite-landing-bonus"><strong>1 000 PT</strong><span>kezdőtőke regisztráció után</span></div>
        <a href="{{ route('register') }}" class="invite-landing-cta">Meghívás elfogadása →</a>
        <small>A meghívó már rögzítve van; a regisztráció után automatikusan érvényesítjük.</small>
    </section>
</main>
</body>
</html>
