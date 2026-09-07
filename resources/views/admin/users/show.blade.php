<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $user->username }} teljes adatlapja | KwizzGo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/content-editor.js'])
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
</head>
<body class="admin-user-profile min-h-screen bg-slate-100 text-slate-900">
@include('layouts.navigation')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <a href="{{ route('admin.users.index') }}" class="font-bold text-indigo-700">← Vissza a felhasznalokhoz</a>
    <div class="mt-4 rounded-3xl bg-slate-950 p-6 text-white shadow-xl sm:p-8">
        <p class="text-sm font-black uppercase tracking-widest text-amber-300">Hostadmin teljes adatlap</p>
        <h1 class="mt-2 break-words text-3xl font-black sm:text-4xl">{{ $user->username ?: $user->name }}</h1>
        <p class="mt-2 break-all text-slate-300">{{ $user->email }}</p>
    </div>

    @if(session('success'))
        <div class="mt-5 rounded-2xl border border-emerald-300 bg-emerald-50 p-4 font-bold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mt-5 rounded-2xl border border-red-300 bg-red-50 p-4 font-bold text-red-800">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mt-5 rounded-2xl border border-red-300 bg-red-50 p-4 text-red-800">
            <ul class="list-disc space-y-1 pl-5 font-bold">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl bg-white p-6 shadow">
            <h2 class="text-2xl font-black">Pontmódosítás</h2>
            <p class="mt-2 text-sm text-slate-600">Jelenlegi egyenleg: <strong>{{ number_format($user->points ?? 0, 0, ',', ' ') }} PT</strong></p>
            <form method="POST" action="{{ route('admin.users.points.award', $user) }}" class="mt-5 space-y-4">@csrf
                <label class="block font-bold">Pontok száma<input name="amount" type="number" min="-1000000" max="1000000" required value="{{ old('amount') }}" class="mt-1 w-full rounded-xl border-slate-300"></label>
                <p class="text-sm text-slate-600">Pozitív szám jóváír, negatív szám levon. Az egyenleg nem lehet negatív.</p>
                <label class="block font-bold">Módosítás oka<textarea name="reason" rows="3" maxlength="500" required class="mt-1 w-full rounded-xl border-slate-300">{{ old('reason') }}</textarea></label>
                <button type="submit" class="rounded-xl bg-amber-400 px-6 py-3 font-black text-slate-950" onclick="return confirm('Biztosan módosítod a megadott pontokat?')">Pontok módosítása</button>
            </form>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow">
            <h2 class="text-2xl font-black">Kézi pontmódosítások</h2>
            <div class="mt-4 space-y-3">@forelse($user->manualPointAwards as $award)<div class="border-t pt-3"><div class="flex flex-wrap justify-between gap-2"><strong class="{{ $award->amount > 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $award->amount > 0 ? '+' : '−' }}{{ number_format(abs($award->amount), 0, ',', ' ') }} PT</strong><span class="text-sm text-slate-600">{{ $award->created_at?->format('Y. m. d. H:i') }}</span></div><div class="mt-1 text-sm">{{ $award->reason }}</div><div class="mt-1 text-sm text-slate-600">Módosította: {{ $award->awardedBy?->username ?? $award->awardedBy?->name ?? 'Ismeretlen' }}</div></div>@empty<p class="text-slate-500">Még nincs kézi pontmódosítás.</p>@endforelse</div>
        </div>
    </section>

    @if(\Illuminate\Support\Facades\Route::has('admin.users.email'))
    <section class="mt-6 rounded-3xl bg-white p-6 shadow">
        <h2 class="text-2xl font-black">E-mail küldése a felhasználónak</h2>
        <p class="mt-2 text-sm text-slate-600">A levél közvetlenül a következő címre megy: <strong>{{ $user->email }}</strong></p>
        <form method="POST" action="{{ route('admin.users.email', $user) }}" class="mt-5 space-y-4">
            @csrf
            <div>
                <label for="email-subject" class="mb-2 block text-sm font-black">Tárgy</label>
                <input id="email-subject" name="subject" value="{{ old('subject') }}" maxlength="200" required
                       class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="email-body" class="mb-2 block text-sm font-black">Üzenet</label>
                <textarea id="email-body" name="body" rows="9" maxlength="10000" required
                          class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500">{{ old('body') }}</textarea>
            </div>
            <button type="submit" class="rounded-xl bg-indigo-700 px-6 py-3 font-black text-white hover:bg-indigo-800"
                    onclick="return confirm('Biztosan elküldöd ezt az e-mailt a felhasználónak?')">
                E-mail elküldése
            </button>
        </form>
    </section>
    @endif

    <section class="mt-6 rounded-3xl bg-white p-6 shadow">
        <h2 class="text-2xl font-black">E-mail műveletek</h2>
        <p class="mt-2 text-sm text-slate-600">Címzett: <strong>{{ $user->email }}</strong></p>
        <div class="mt-5 grid gap-4 lg:grid-cols-3">
            <form method="POST" action="{{ route('admin.users.email.verification', $user) }}" class="rounded-2xl border border-amber-200 bg-amber-50 p-5">@csrf
                <h3 class="font-black">1. Hitelesítő e-mail</h3><p class="mt-2 text-sm text-slate-600">Új, személyre szabott hitelesítő link küldése.</p>
                <button class="mt-5 w-full rounded-xl bg-amber-400 px-4 py-3 font-black text-slate-950">Hitelesítő e-mail újraküldése</button>
            </form>
            <details class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
                <summary class="cursor-pointer list-none font-black">2. Előre megírt levél küldése</summary>
                <form method="POST" action="{{ route('admin.users.email.campaign', $user) }}" class="mt-4">@csrf
                    <label class="block text-sm font-bold">Időzített levél
                        <select name="email_template_id" required class="mt-2 w-full rounded-xl border-slate-300">
                            <option value="">Válassz sablont…</option>@foreach($campaigns as $campaign)<option value="{{ $campaign->id }}">{{ $campaign->name }}{{ $campaign->is_active ? '' : ' (piszkozat)' }}</option>@endforeach
                        </select>
                    </label>
                    <button class="mt-4 w-full rounded-xl bg-indigo-700 px-4 py-3 font-black text-white">Kiválasztott levél küldése</button>
                </form>
            </details>
            <div class="rounded-2xl border border-purple-200 bg-purple-50 p-5">
                <h3 class="font-black">3. Egyedi e-mail készítése</h3><p class="mt-2 text-sm text-slate-600">Teljes, formázható levélszerkesztő használata.</p>
                <a href="#custom-email-editor" onclick="document.getElementById('custom-email-editor').open=true" class="mt-5 block w-full rounded-xl bg-purple-700 px-4 py-3 text-center font-black text-white">Egyedi e-mail írása</a>
            </div>
        </div>

        <details @if(old('subject') !== null) open @endif id="custom-email-editor" class="mt-5 rounded-2xl border border-purple-200 p-5">
            <summary class="cursor-pointer text-lg font-black text-purple-800">Egyedi e-mail szerkesztő megnyitása</summary>
            <form method="POST" enctype="multipart/form-data" data-content-editor-form action="{{ route('admin.users.email.custom', $user) }}" class="mt-5 space-y-5">@csrf
                <div class="grid gap-4 md:grid-cols-2"><label class="font-bold">Levél tárgya<input name="subject" required maxlength="255" value="{{ old('subject') }}" class="mt-1 w-full rounded-xl border-slate-300"></label><label class="font-bold">Főcím<input name="heading" required maxlength="255" value="{{ old('heading') }}" class="mt-1 w-full rounded-xl border-slate-300"></label><label class="font-bold md:col-span-2">Fejléckép<input type="file" name="header_image" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full"></label></div>
                <div class="content-editor-toolbar" data-editor-toolbar>@foreach([['bold','B'],['italic','I'],['underline','U'],['strike','S'],['h2','H2'],['h3','H3'],['bulletList','• Lista'],['orderedList','1. Lista'],['blockquote','Idézet'],['link','Link'],['alignLeft','Bal'],['alignCenter','Közép'],['alignRight','Jobb'],['table','Táblázat'],['undo','↶'],['redo','↷']] as [$command,$label])<button type="button" data-editor-command="{{ $command }}">{{ $label }}</button>@endforeach<button type="button" data-editor-image>Kép</button><input type="file" data-editor-image-input accept="image/jpeg,image/png,image/webp,image/gif" hidden></div>
                <div class="content-tiptap-editor" data-content-editor data-upload-url="{{ route('admin.email-templates.upload-image') }}"></div><input type="hidden" name="content_json" data-content-json><input type="hidden" name="content_html" data-content-html><script type="application/json" data-initial-content>{!! json_encode(old('content_json') ? json_decode(old('content_json'), true) : null, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
                <div class="grid gap-5 md:grid-cols-2"><label class="font-bold">Ajánlott kvízek<select name="recommended_quiz_ids[]" multiple size="6" class="mt-1 w-full rounded-xl border-slate-300">@foreach($recommendedQuizzes as $quiz)<option value="{{ $quiz->id }}">{{ $quiz->title }}</option>@endforeach</select></label><label class="font-bold">Ajánlott tartalmak<select name="recommended_content_ids[]" multiple size="6" class="mt-1 w-full rounded-xl border-slate-300">@foreach($recommendedContents as $content)<option value="{{ $content->id }}">{{ $content->title }}</option>@endforeach</select></label></div>
                <label class="flex items-center gap-3 font-bold"><input type="checkbox" name="include_progress" value="1"> „Így állsz” blokk beillesztése</label>
                <div class="grid gap-4 md:grid-cols-2"><label class="font-bold">Fő gomb felirata<input name="button_text" maxlength="100" class="mt-1 w-full rounded-xl border-slate-300"></label><label class="font-bold">Záró szöveg<textarea name="footer" rows="3" class="mt-1 w-full rounded-xl border-slate-300"></textarea></label></div>
                <button type="submit" class="content-primary-button w-full" onclick="return confirm('Biztosan elküldöd az egyedi e-mailt?')">Egyedi e-mail elküldése</button>
            </form>
        </details>
    </section>

    @php
        $fields = [
            'Felhasznaloi azonosito' => $user->id,
            'Nev / legacy nev' => $user->name,
            'Szerepkor' => $user->role,
            'Pontok' => number_format($user->points ?? 0, 0, ',', ' ').' PT',
            'Fiok aktiv' => $user->is_active ? 'Igen' : 'Nem',
            'Bannolt' => $user->is_banned ? 'Igen' : 'Nem',
            'Reklammentes' => $user->is_ad_free ? 'Igen' : 'Nem',
            'E-mail hitelesitve' => $user->email_verified_at?->format('Y. m. d. H:i') ?? 'Nem',
            'Google-fiok kapcsolva' => $user->google_id ? 'Igen' : 'Nem',
            'Szuletesi datum' => $user->birth_date?->format('Y. m. d.') ?? 'Nincs megadva',
            'Nem' => $user->gender ?: 'Nincs megadva',
            'Orszag' => $user->country ?: 'Nincs megadva',
            'Megye' => $user->county ?: 'Nincs megadva',
            'Kedvenc kategoria' => $user->favoriteCategory?->name ?? 'Nincs megadva',
            'Kapcsolati allapot' => $user->relationship_status ?: 'Nincs megadva',
            'Gyermekek szama' => $user->children_count ?? 'Nincs megadva',
            'Profiljutalom ideje' => $user->profile_details_rewarded_at?->format('Y. m. d. H:i') ?? 'Meg nem kapta meg',
            'Regisztracio' => $user->created_at?->format('Y. m. d. H:i'),
            'Utolso adatmodositas' => $user->updated_at?->format('Y. m. d. H:i'),
            'Meghivokod' => $user->referral_code ?: 'Nincs',
        ];
    @endphp
    <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($fields as $label => $value)
            @php
                // Egyes legacy adminfiokokban regi, osszetett profilertek is maradhatott.
                // Ezeket biztonsagosan olvashato szovegge alakitjuk ahelyett, hogy
                // a Blade tombot probalna HTML-karaktermentesiteni es 500-ra futna.
                $displayValue = is_array($value)
                    ? collect($value)->flatten()->filter(fn ($item) => is_scalar($item))->implode(', ')
                    : (is_object($value) && ! method_exists($value, '__toString')
                        ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : $value);
            @endphp
            <div class="rounded-2xl bg-white p-5 shadow"><div class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $label }}</div><div class="mt-2 break-words font-bold">{{ $displayValue }}</div></div>
        @endforeach
    </section>

    <section class="mt-6 rounded-3xl bg-white p-6 shadow">
        <h2 class="text-2xl font-black">Aktivitas es eredmenyek</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>Letrehozott kvizek: <strong>{{ $user->created_quizzes_count }}</strong></div><div>Valaszok: <strong>{{ $activity['answers'] }}</strong></div><div>Helyes valaszok: <strong>{{ $activity['correct_answers'] }}</strong></div><div>Kerdesjelentesek: <strong>{{ $user->question_reports_count }}</strong></div><div>Ertesitesek: <strong>{{ $activity['notifications'] }}</strong></div><div>Olvasatlan: <strong>{{ $activity['unread_notifications'] }}</strong></div><div>Meghivottak: <strong>{{ $user->invited_referrals_count }}</strong></div><div>Meghivasi pont: <strong>{{ number_format($activity['referral_points'], 0, ',', ' ') }} PT</strong></div>
        </div>
    </section>

    <section class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl bg-white p-6 shadow"><h2 class="text-xl font-black">Jogi elfogadasok</h2>@forelse($user->legalConsents as $consent)<div class="mt-4 border-t pt-4"><strong>{{ $consent->consent_type }}</strong><div class="text-sm text-slate-600">Verzio: {{ $consent->content_version }} · {{ $consent->accepted_at?->format('Y. m. d. H:i') }}</div><div class="break-all text-sm">{{ $consent->document_url }}</div><div class="text-sm">IP: {{ $consent->ip_address ?: 'nem rogzitett' }}</div></div>@empty<p class="mt-3 text-slate-500">Nincs rogzitett elfogadas.</p>@endforelse</div>
        <div class="rounded-3xl bg-white p-6 shadow"><h2 class="text-xl font-black">Meghivasok</h2><p class="mt-3">Meghivo: <strong>{{ $user->receivedReferral?->inviter?->username ?? 'Nem meghivassal regisztralt' }}</strong></p>@forelse($user->invitedReferrals as $referral)<div class="mt-3 border-t pt-3"><strong>{{ $referral->invitedUser?->username ?? 'Torolt felhasznalo' }}</strong> · {{ number_format($referral->reward_points, 0, ',', ' ') }} PT · {{ $referral->rewarded_at?->format('Y. m. d. H:i') }}</div>@empty<p class="mt-3 text-slate-500">Meg nincs meghivott felhasznalo.</p>@endforelse</div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="rounded-3xl bg-white p-6 shadow">
            <h2 class="text-xl font-black">E-mail történet</h2>
            <p class="mt-2 text-sm text-slate-600">A hostadmin által innen indított levelek és a kampányküldések.</p>
            <div class="mt-4 space-y-3">
                @forelse($user->emailLogs as $emailLog)
                    <div class="border-t pt-3">
                        <div class="flex flex-wrap items-center justify-between gap-2"><strong>{{ $emailLog->subject }}</strong><span class="rounded-full bg-purple-100 px-2 py-1 text-xs font-black text-purple-800">{{ match($emailLog->type) {'verification' => 'Hitelesítés', 'campaign' => 'Kampány', 'custom' => 'Egyedi', default => $emailLog->type} }}</span></div>
                        <div class="mt-1 text-sm text-slate-600">{{ $emailLog->sent_at?->format('Y. m. d. H:i') }} · Küldte: {{ $emailLog->sender?->username ?? $emailLog->sender?->name ?? 'Rendszer' }}</div>
                    </div>
                @empty
                    <p class="text-slate-500">Még nincs innen indított e-mail.</p>
                @endforelse
                @foreach($campaignDeliveries as $delivery)
                    <div class="border-t pt-3">
                        <div class="flex flex-wrap items-center justify-between gap-2"><strong>{{ $delivery->template?->subject ?? 'Kampánylevél' }}</strong><span class="rounded-full bg-indigo-100 px-2 py-1 text-xs font-black text-indigo-800">Automatikus</span></div>
                        <div class="mt-1 text-sm text-slate-600">{{ $delivery->sent_at?->format('Y. m. d. H:i') }} · {{ $delivery->template?->name ?? 'Korábbi kampány' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow">
            <h2 class="text-xl font-black">Bejelentkezési történet</h2>
            <p class="mt-2 text-sm text-slate-600">Csak sikeres belépések; a jelszó és a munkamenet adatai nem kerülnek naplózásra.</p>
            <div class="mt-4 space-y-3">
                @forelse($user->loginActivities as $loginActivity)
                    <div class="border-t pt-3">
                        <div class="flex flex-wrap items-center justify-between gap-2"><strong>{{ match($loginActivity->method) {'password' => 'E-mail és jelszó', 'google' => 'Google', 'emergency' => 'Vészhelyzeti hostadmin', default => $loginActivity->method} }}</strong><span class="text-sm text-slate-600">{{ $loginActivity->logged_in_at?->format('Y. m. d. H:i') }}</span></div>
                        <div class="mt-1 break-all text-sm text-slate-600">IP: {{ $loginActivity->ip_address ?? 'Nincs rögzítve' }} · {{ $loginActivity->user_agent ?: 'Ismeretlen böngésző' }}</div>
                    </div>
                @empty
                    <p class="text-slate-500">A belépések a mostani frissítéstől kezdve jelennek meg itt.</p>
                @endforelse
            </div>
        </div>
    </section>
</main>
</body>
</html>
