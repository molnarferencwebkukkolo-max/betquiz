<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - KwizzGo</title>
    <!-- Központi Stíluslap -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body>

@include('layouts.navigation')

<div class="coming-soon-container">
    <div class="card-coming-soon">
        <div class="coming-soon-icon">🚀</div>
        <h1 class="coming-soon-title">{{ $title }}</h1>
        <p class="coming-soon-subtitle">{{ $subtitle }}</p>

        <span class="badge-coming-soon">
            Fejlesztés alatt (Coming Soon)
        </span>

        <div class="mt-8">
            <a href="{{ route('dashboard') }}" class="btn-back-home">
                Vissza a Játékhoz
            </a>
        </div>
    </div>
</div>

</body>
</html>
