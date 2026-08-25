<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kérdéshibák kezelése - KwizzGo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
@include('layouts.navigation')
<main class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
    <div class="mb-7">
        <h1 class="text-3xl font-black">Kérdéshibák kezelése</h1>
        <p class="mt-2 text-slate-400">A függőben lévő hibajelzések kérdései a lezárásig nem jelennek meg játék közben.</p>
    </div>

    @if(session('success'))<div class="mb-5 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 font-bold text-emerald-200">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-rose-200">{{ $errors->first() }}</div>@endif

    <div class="space-y-5">
        @forelse($reports as $report)
            @php
                $text = $report->question->question_text;
                $text = is_array($text) ? ($text['hu'] ?? $text['en'] ?? reset($text)) : $text;
            @endphp
            <article class="rounded-3xl border {{ $report->status === 'pending' ? 'border-amber-400/40 bg-slate-900' : 'border-slate-700 bg-slate-900/60' }} p-5 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
                    <div>
                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $report->status === 'pending' ? 'bg-amber-400/15 text-amber-300' : ($report->status === 'accepted' ? 'bg-emerald-400/15 text-emerald-300' : 'bg-rose-400/15 text-rose-300') }}">
                            {{ ['pending'=>'Kivizsgálásra vár','accepted'=>'Valós, javítva','rejected'=>'FAKE jelzés'][$report->status] }}
                        </span>
                        <h2 class="mt-3 text-xl font-black">{{ $text }}</h2>
                        <p class="mt-1 text-sm font-bold text-violet-300">{{ $report->question->quiz->title }}</p>
                    </div>
                    <p class="text-xs text-slate-400">Jelentő: {{ $report->reporter->name }}<br>{{ $report->created_at->diffForHumans() }}</p>
                </div>
                <div class="mt-4 rounded-2xl bg-slate-950/70 p-4"><strong class="text-amber-300">Jelzett hiba</strong><p class="mt-2 whitespace-pre-line text-sm text-slate-300">{{ $report->reason }}</p></div>

                @if($report->status === 'pending')
                    <div class="mt-5 flex flex-col gap-4 lg:flex-row">
                        <a href="{{ route('questions.edit', $report->question) }}" class="self-start rounded-xl bg-violet-600 px-4 py-3 text-sm font-black hover:bg-violet-500">Kérdés szerkesztése</a>
                        <form action="{{ route('question-reports.resolve', $report) }}" method="POST" class="flex flex-1 flex-col gap-3 sm:flex-row">
                            @csrf @method('PATCH')
                            <textarea name="resolution_note" required minlength="10" maxlength="1500" rows="2" class="flex-1 rounded-xl border border-slate-600 bg-slate-950 p-3 text-sm" placeholder="A javítás vagy az elutasítás indoka..."></textarea>
                            <div class="flex flex-col gap-2">
                                <button name="decision" value="accepted" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-black hover:bg-emerald-500">Elfogadva és javítva</button>
                                <button name="decision" value="rejected" class="rounded-xl bg-rose-700 px-4 py-2 text-sm font-black hover:bg-rose-600" onclick="return confirm('Biztosan FAKE jelzésnek minősíted? Ez rontja a játékos hibajelzési arányát.')">Nem valós (FAKE)</button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="mt-4 text-sm text-slate-400"><strong>Lezárás:</strong> {{ $report->resolution_note }} · {{ $report->resolver?->name }}</div>
                @endif
            </article>
        @empty
            <div class="rounded-3xl border border-slate-700 bg-slate-900 p-12 text-center text-slate-400">Nincs kezelendő hibajelzés.</div>
        @endforelse
    </div>
    <div class="mt-8">{{ $reports->links() }}</div>
</main>
</body>
</html>
