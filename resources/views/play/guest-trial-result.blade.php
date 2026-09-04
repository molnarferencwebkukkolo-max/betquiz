<!DOCTYPE html>
<html lang="hu"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Próbaeredmény | KwizzGo</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-950 text-white">@include('layouts.navigation')
<main class="mx-auto flex min-h-[80vh] max-w-2xl items-center px-4 py-10"><section class="w-full rounded-3xl border border-amber-400/30 bg-slate-900 p-8 text-center shadow-2xl">
    <p class="font-black uppercase tracking-widest text-amber-300">Próbajáték vége</p><h1 class="mt-3 text-4xl font-black">{{ $correct }}/{{ $total }} helyes válasz</h1>
    <p class="mt-6 text-xl">Ha kérdésenként <strong>{{ $bet }} zsetonos téttel</strong> játszottál volna,</p><p class="mt-3 text-3xl font-black text-amber-300">{{ number_format($winnings, 0, ',', ' ') }} zsetont nyertél volna! 🎉</p>
    <p class="mt-4 text-slate-400">Ez bemutató eredmény, valódi zsetonjóváírás nem történt.</p>
    <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row"><form method="POST" action="{{ route('quizzes.trial.start', $quiz) }}">@csrf<button class="w-full rounded-xl bg-amber-400 px-6 py-3 font-black text-slate-950">Újabb 10 kérdés</button></form><a class="rounded-xl border border-slate-600 px-6 py-3 font-black" href="{{ route('register') }}">Regisztrálok és játszom</a></div>
</section></main></body></html>
