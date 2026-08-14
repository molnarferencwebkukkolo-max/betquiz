<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $content->exists ? 'Tartalom szerkesztése' : 'Új tartalom' }} - KwizzGo</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}"><script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="content-admin-page min-h-screen pb-12">
@include('layouts.navigation')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div><a href="{{ route('admin.contents.index', ['type'=>$content->type]) }}" class="text-sm font-bold text-purple-300">← Tartalomkezelő</a><h1 class="mt-2 text-3xl font-black">{{ $content->exists ? $content->title : 'Új tartalom' }}</h1></div>
        @if($content->exists && $content->isPubliclyVisible())<a href="{{ $content->publicUrl() }}" target="_blank" class="content-secondary-button">Publikus előnézet ↗</a>@endif
    </div>
    @if(session('success'))<div class="content-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="content-error"><strong>A mentés nem sikerült:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" enctype="multipart/form-data" data-content-editor-form
          action="{{ $content->exists ? route('admin.contents.update', $content) : route('admin.contents.store') }}">
        @csrf @if($content->exists) @method('PUT') @endif
        <div class="content-editor-layout">
            <section class="content-editor-main space-y-5">
                <div class="content-panel grid gap-4 md:grid-cols-2">
                    <label><span>Típus</span><select name="type"><option value="page" @selected(old('type',$content->type)==='page')>Oldal</option><option value="article" @selected(old('type',$content->type)==='article')>Cikk</option></select></label>
                    <label><span>Slug</span><input name="slug" required maxlength="180" value="{{ old('slug',$content->slug) }}" placeholder="oldal-url"></label>
                    <label class="md:col-span-2"><span>Cím</span><input name="title" required maxlength="255" value="{{ old('title',$content->title) }}"></label>
                    <label class="md:col-span-2"><span>Rövid kivonat</span><textarea name="excerpt" rows="3" maxlength="1000">{{ old('excerpt',$content->excerpt) }}</textarea></label>
                </div>

                <div class="content-panel">
                    <div class="content-editor-toolbar" data-editor-toolbar>
                        @foreach([['bold','B'],['italic','I'],['underline','U'],['strike','S'],['h2','H2'],['h3','H3'],['bulletList','• Lista'],['orderedList','1. Lista'],['blockquote','Idézet'],['link','Link'],['alignLeft','Bal'],['alignCenter','Közép'],['alignRight','Jobb'],['table','Táblázat'],['undo','↶'],['redo','↷']] as [$command,$label])
                            <button type="button" data-editor-command="{{ $command }}">{{ $label }}</button>
                        @endforeach
                        <button type="button" data-editor-image>Kép</button><input type="file" data-editor-image-input accept="image/jpeg,image/png,image/webp,image/gif" hidden>
                    </div>
                    <div class="content-tiptap-editor" data-content-editor data-upload-url="{{ route('admin.contents.upload-image') }}"></div>
                    <input type="hidden" name="content_json" data-content-json>
                    <input type="hidden" name="content_html" data-content-html>
                    <script type="application/json" data-initial-content>{!! json_encode(old('content_json') ? json_decode(old('content_json'), true) : $content->content_json, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
                    @if(!$content->content_json && $content->content_html)<template data-initial-html>{!! $content->content_html !!}</template>@endif
                </div>

                @include('admin.contents.partials.metadata', ['content'=>$content])
            </section>

            <aside class="content-editor-sidebar space-y-5">
                <div class="content-panel">
                    <h2>Publikálás</h2>
                    <label><span>Állapot</span><select name="status"><option value="draft" @selected(old('status',$content->status)==='draft')>Piszkozat</option><option value="scheduled" @selected(old('status',$content->status)==='scheduled')>Időzített</option><option value="published" @selected(old('status',$content->status)==='published')>Publikált</option><option value="archived" @selected(old('status',$content->status)==='archived')>Archivált</option></select></label>
                    <label><span>Időzített publikálás</span><input type="datetime-local" name="scheduled_for" value="{{ old('scheduled_for',$content->scheduled_for?->format('Y-m-d\TH:i')) }}"></label>
                    <label><span>Hatálybalépés</span><input type="datetime-local" name="effective_at" value="{{ old('effective_at',$content->effective_at?->format('Y-m-d\TH:i')) }}"></label>
                    <button class="content-primary-button w-full">Mentés</button>
                </div>
                @if($content->exists)
                    <div class="content-panel"><h2>Verziótörténet</h2><p class="text-sm text-slate-400">Jelenlegi verzió: <strong>v{{ $content->version }}</strong></p><div class="content-revisions">@forelse($content->revisions->take(8) as $revision)<div><strong>v{{ $revision->version }}</strong><span>{{ $revision->published_at->format('Y. m. d. H:i') }}</span><small>{{ $revision->publisher?->username ?? 'Rendszer' }}</small></div>@empty<p>Még nincs publikált változat.</p>@endforelse</div></div>
                    @unless(in_array($content->slug,['aszf','adatkezeles','mediaajanlat'],true))<div class="content-panel"><button type="submit" form="delete-content-form" class="content-danger-button w-full" onclick="return confirm('Biztosan törlöd ezt a tartalmat?')">Törlés</button></div>@endunless
                @endif
            </aside>
        </div>
    </form>
    @if($content->exists && !in_array($content->slug,['aszf','adatkezeles','mediaajanlat'],true))<form id="delete-content-form" method="POST" action="{{ route('admin.contents.destroy',$content) }}">@csrf @method('DELETE')</form>@endif
</main>
</body></html>
