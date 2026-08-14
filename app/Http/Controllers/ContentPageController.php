<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

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
        $contents = Content::query()->publiclyVisible()->where('sitemap_include', true)->get();

        return response()->view('content.sitemap', compact('contents'), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
