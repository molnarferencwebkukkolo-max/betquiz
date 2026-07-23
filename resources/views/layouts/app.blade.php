<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'BetQuiz') }}</title>

    <!-- 🟢 Az egyedi stíluslapod behívása a központi keretben: -->
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
