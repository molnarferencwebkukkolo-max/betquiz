<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $user->username }} teljes adatlapja | KwizzGo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
@include('layouts.navigation')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <a href="{{ route('admin.users.index') }}" class="font-bold text-indigo-700">← Vissza a felhasznalokhoz</a>
    <div class="mt-4 rounded-3xl bg-slate-950 p-6 text-white shadow-xl sm:p-8">
        <p class="text-sm font-black uppercase tracking-widest text-amber-300">Hostadmin teljes adatlap</p>
        <h1 class="mt-2 break-words text-3xl font-black sm:text-4xl">{{ $user->username ?: $user->name }}</h1>
        <p class="mt-2 break-all text-slate-300">{{ $user->email }}</p>
    </div>

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
</main>
</body>
</html>
