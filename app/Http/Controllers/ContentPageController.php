<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Quiz;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ContentPageController extends Controller
{
    public function show(Content $content)
    {
        abort_unless($content->type === 'page' && $content->isPubliclyVisible(), 404);

        return view('content.show', compact('content'));
    }

    public function articles()
    {
        $articles = Content::query()->publiclyVisible()->where('type', 'article')->latest('published_at')->paginate(12);

        return view('content.articles', compact('articles'));
    }

    public function article(Content $content)
    {
        abort_unless($content->type === 'article' && $content->isPubliclyVisible(), 404);
        $content->load(['recommendedQuizzes' => fn ($query) => $query
            ->where('status', 'approved')
            ->where('is_public', true)
            ->withCount('questions')]);

        return view('content.show', compact('content'));
    }

    public function short(string $slug)
    {
        $content = Content::query()->publiclyVisible()->where('type', 'page')->where('slug', $slug)->firstOrFail();

        return view('content.show', compact('content'));
    }

    public function markdown(Content $content): Response
    {
        $expectedType = request()->routeIs('articles.markdown') ? 'article' : 'page';
        abort_unless($content->type === $expectedType && $content->isPubliclyVisible() && $content->markdown_enabled, 404);
        $plain = html_entity_decode(strip_tags(str_replace(
            ['</h2>', '</h3>', '</p>', '</li>', '<br>', '<br/>', '<br />'],
            ["\n\n", "\n\n", "\n\n", "\n", "\n", "\n", "\n"],
            $content->content_html
        )));
        $markdown = '# '.$content->title."\n\n";
        if ($content->excerpt) {
            $markdown .= '> '.str_replace("\n", ' ', $content->excerpt)."\n\n";
        }
        $markdown .= trim(preg_replace("/\n{3,}/", "\n\n", $plain))."\n";

        return response($markdown, 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    public function llms(): Response
    {
        $contents = Content::query()->publiclyVisible()->where('llms_include', true)->orderBy('llms_section')->orderBy('llms_priority')->get();
        $output = "# KwizzGo\n\n> Magyar közösségi kvízplatform, játékokkal és felhasználói tartalmakkal.\n\n";
        foreach ($contents->groupBy('llms_section') as $section => $items) {
            $output .= '## '.($section ?: 'Tartalmak')."\n\n";
            foreach ($items as $content) {
                $description = $content->llms_summary ?: $content->effectiveSeoDescription();
                $target = $content->markdown_enabled ? $content->markdownUrl() : $content->publicUrl();
                $output .= '- ['.$content->title.']('.$target.'): '.Str::squish($description)."\n";
            }
            $output .= "\n";
        }

        return response($output, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $contents = Content::query()
            ->publiclyVisible()
            ->where('sitemap_include', true)
            ->orderBy('type')
            ->orderBy('slug')
            ->get(['id', 'type', 'slug', 'updated_at', 'sitemap_priority']);

        // Csak ténylegesen publikus, nem törölt kvíz kerülhet keresőrobot elé.
        $quizzes = Quiz::query()
            ->where('status', 'approved')
            ->where('is_public', true)
            ->orderBy('slug')
            ->get(['id', 'slug', 'updated_at']);

        $baseUrl = rtrim((string) config('app.url'), '/');
        if (app()->environment('production')) {
            // A publikus KwizzGo domain kizárólag HTTPS-en indexelendő akkor is,
            // ha a shared-hosting proxy belső kérése HTTP-ként érkezik Laravelhez.
            $baseUrl = preg_replace('#^http://#i', 'https://', $baseUrl);
        }
        $latestChange = $contents->pluck('updated_at')->merge($quizzes->pluck('updated_at'))->filter()->max() ?? now();
        $entries = [
            [$baseUrl.'/', $latestChange, '1.0', 'daily'],
            [$baseUrl.'/quizzes', $latestChange, '0.8', 'daily'],
            [$baseUrl.'/cikkek', $latestChange, '0.6', 'weekly'],
        ];

        foreach ($contents as $content) {
            $path = $content->type === 'article'
                ? '/cikk/'.$content->slug
                : (in_array($content->slug, ['aszf', 'adatkezeles', 'mediaajanlat'], true)
                    ? '/'.$content->slug
                    : '/oldal/'.$content->slug);
            $priority = number_format(max(0, min(1, (float) $content->sitemap_priority)), 1, '.', '');
            $entries[] = [$baseUrl.$path, $content->updated_at, $priority, 'monthly'];
        }

        foreach ($quizzes as $quiz) {
            $entries[] = [$baseUrl.'/kviz/'.$quiz->slug, $quiz->updated_at, '0.7', 'weekly'];
        }

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($entries as [$location, $lastModified, $priority, $changeFrequency]) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.$this->escapeXml($location)."</loc>\n";
            $xml .= '    <lastmod>'.Carbon::parse($lastModified)->utc()->toAtomString()."</lastmod>\n";
            $xml .= '    <changefreq>'.$changeFrequency."</changefreq>\n";
            $xml .= '    <priority>'.$priority."</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= "</urlset>\n";

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=900',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
