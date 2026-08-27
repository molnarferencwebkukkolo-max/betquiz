<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-mail-cim hitelesitese | KwizzGo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white">
<main class="mx-auto flex min-h-screen max-w-xl items-center px-5 py-12">
    <section class="w-full rounded-3xl border border-amber-400/30 bg-slate-900 p-7 shadow-2xl sm:p-10">
        <div class="mb-5 text-4xl">✉️</div>
        <h1 class="text-3xl font-black text-amber-300">Erositsd meg az e-mail-cimed!</h1>
        <p class="mt-4 leading-7 text-slate-300">Elkuldtuk a hitelesito linket a regisztraciokor megadott cimre. Kattints a levelben levo gombra, es maris hasznalhatod a KwizzGo minden funkciojat.</p>
        @if (session('status') === 'verification-link-sent')
            <div class="mt-5 rounded-xl border border-emerald-400/40 bg-emerald-500/10 p-4 font-bold text-emerald-200">Uj hitelesito linket kuldtunk.</div>
        @endif
        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
            <form method="POST" action="{{ route('verification.send') }}">@csrf
                <button class="w-full rounded-xl bg-amber-400 px-5 py-3 font-black text-slate-950">Link ujrakuldese</button>
            </form>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="w-full rounded-xl border border-slate-600 px-5 py-3 font-bold text-slate-200">Kijelentkezes</button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
