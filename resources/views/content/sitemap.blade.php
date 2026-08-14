{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<url><loc>{{ url('/') }}</loc><lastmod>{{ now()->toAtomString() }}</lastmod><priority>1.0</priority></url>
<url><loc>{{ route('quizzes.index') }}</loc><lastmod>{{ now()->toAtomString() }}</lastmod><priority>0.8</priority></url>
<url><loc>{{ route('articles.index') }}</loc><lastmod>{{ now()->toAtomString() }}</lastmod><priority>0.6</priority></url>
@foreach($contents as $content)<url><loc>{{ $content->publicUrl() }}</loc><lastmod>{{ $content->updated_at->toAtomString() }}</lastmod><priority>{{ number_format($content->sitemap_priority, 1, '.', '') }}</priority></url>@endforeach
</urlset>
