<!DOCTYPE html>
<html lang="hu"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>{{ $quiz->title }} próba | KwizzGo</title>@vite(['resources/css/app.css','resources/js/app.js'])<link rel="stylesheet" href="{{ asset('css/app-custom.css') }}"></head>
<body class="min-h-screen bg-slate-950 text-white">@include('layouts.navigation')
<main class="mx-auto max-w-3xl px-4 py-10"><section class="rounded-3xl border border-amber-400/30 bg-slate-900 p-6 shadow-2xl sm:p-9">
    <div class="flex items-center justify-between gap-4"><p class="font-black text-amber-300">Vendég próba · {{ $number }}/{{ $total }}</p><p class="text-sm text-slate-400">Képzeletbeli tét: 50 zseton</p></div>
    <h1 class="mt-3 text-xl font-black text-slate-300">{{ $quiz->title }}</h1>
    @if($question->image_path)<img class="mt-6 max-h-80 w-full rounded-2xl object-contain" src="{{ asset('storage/'.$question->image_path) }}" alt="">@endif
    <h2 class="mt-6 text-2xl font-black">{{ is_array($question->question_text) ? ($question->question_text['hu'] ?? reset($question->question_text)) : $question->question_text }}</h2>
    <form method="POST" action="{{ route('quizzes.trial.answer', $quiz) }}" class="mt-7 grid gap-3">@csrf
        @foreach($options as $option)<label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-slate-700 bg-slate-800 p-4 hover:border-amber-400"><input type="radio" name="option_id" value="{{ $option->id }}" required>@if($option->image_path)<img class="h-16 w-20 rounded-lg object-cover" src="{{ asset('storage/'.$option->image_path) }}" alt="">@endif<span class="font-bold">{{ $option->translated_text }}</span></label>@endforeach
        <button class="mt-3 rounded-xl bg-amber-400 px-6 py-3 font-black text-slate-950">Válasz elküldése</button>
    </form>
    <div class="mt-8 border-t border-slate-700 pt-6 text-center">
        <p class="text-lg text-slate-300">Regisztrálj be és használd ki a segítségeket, gyűjtsd a zsetonokat és teljesebb lesz az élmény!</p>
        <a href="{{ route('register') }}" class="mt-5 block rounded-xl bg-amber-400 px-8 py-4 text-xl font-black text-slate-950 hover:bg-amber-300">Regisztrálok!</a>
    </div>
</section></main></body></html>
