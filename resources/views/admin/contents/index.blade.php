<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tartalomkezelő - KwizzGo</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}"><script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="content-admin-page min-h-screen pb-12">
@include('layouts.navigation')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <header class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div><span class="content-admin-kicker">KwizzGo CMS</span><h1 class="mt-2 text-3xl font-black">Tartalomkezelő</h1><p class="mt-2 text-sm text-slate-400">Oldalak, cikkek, publikálás és keresőmegjelenés egy helyen.</p></div>
        <a href="{{ route('admin.contents.create', ['type' => $type]) }}" class="content-primary-button">Új {{ $type === 'page' ? 'oldal' : 'cikk' }}</a>
    </header>
    @if(session('success'))<div class="content-success">{{ session('success') }}</div>@endif
    <section class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach(['Oldalak'=>$stats['pages'],'Cikkek'=>$stats['articles'],'Publikált'=>$stats['published'],'Piszkozatok'=>$stats['drafts']] as $label=>$value)
            <div class="content-stat"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
        @endforeach
    </section>
    <nav class="content-tabs mb-5">
        <a href="{{ route('admin.contents.index', ['type'=>'page']) }}" class="{{ $type === 'page' ? 'active' : '' }}">Oldalak</a>
        <a href="{{ route('admin.contents.index', ['type'=>'article']) }}" class="{{ $type === 'article' ? 'active' : '' }}">Cikkek</a>
    </nav>
    <section class="content-list-card overflow-x-auto">
        <table class="min-w-full">
            <thead><tr><th>Cím</th><th>Állapot</th><th>Verzió</th><th>Menü / LLM</th><th>Módosítva</th><th></th></tr></thead>
            <tbody>
            @forelse($contents as $item)
                <tr>
                    <td><strong>{{ $item->title }}</strong><small>/{{ $item->slug }}</small></td>
                    <td><span class="content-status content-status--{{ $item->status }}">{{ ['draft'=>'Piszkozat','scheduled'=>'Időzített','published'=>'Publikált','archived'=>'Archivált'][$item->status] }}</span></td>
                    <td>v{{ $item->version }}</td>
                    <td><span>{{ $item->footer_visible ? 'Lábléc' : '—' }}</span> · <span>{{ $item->llms_include ? 'LLM' : '—' }}</span></td>
                    <td>{{ $item->updated_at->format('Y. m. d. H:i') }}</td>
                    <td class="text-right"><a href="{{ route('admin.contents.edit', $item) }}">Szerkesztés →</a></td>
                </tr>
            @empty<tr><td colspan="6" class="py-12 text-center text-slate-500">Nincs még ilyen tartalom.</td></tr>@endforelse
            </tbody>
        </table>
    </section>
    <div class="mt-5">{{ $contents->links() }}</div>
</main>
</body></html>
