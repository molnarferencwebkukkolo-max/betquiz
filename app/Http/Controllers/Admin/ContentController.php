<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Services\ContentHtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeHostadmin();
        $type = in_array($request->input('type'), ['page', 'article'], true) ? $request->input('type') : 'page';
        $contents = Content::query()->with('author')->where('type', $type)->latest('updated_at')->paginate(20)->withQueryString();
        $stats = [
            'pages' => Content::where('type', 'page')->count(),
            'articles' => Content::where('type', 'article')->count(),
            'published' => Content::publiclyVisible()->count(),
            'drafts' => Content::where('status', 'draft')->count(),
        ];

        return view('admin.contents.index', compact('contents', 'type', 'stats'));
    }

    public function create(Request $request)
    {
        $this->authorizeHostadmin();
        $content = new Content(['type' => $request->input('type') === 'article' ? 'article' : 'page']);

        return view('admin.contents.editor', compact('content'));
    }

    public function store(Request $request, ContentHtmlSanitizer $sanitizer)
    {
        $this->authorizeHostadmin();
        $validated = $this->validated($request);
        $validated['author_id'] = auth()->id();
        $content = DB::transaction(function () use ($request, $validated, $sanitizer) {
            $data = $this->prepareData($request, $validated, $sanitizer);
            $content = Content::create($data);
            $this->snapshotIfPublished($content);

            return $content;
        });

        return redirect()->route('admin.contents.edit', $content)->with('success', 'A tartalom elkészült.');
    }

    public function edit(Content $content)
    {
        $this->authorizeHostadmin();
        $content->load('revisions.publisher');

        return view('admin.contents.editor', compact('content'));
    }

    public function update(Request $request, Content $content, ContentHtmlSanitizer $sanitizer)
    {
        $this->authorizeHostadmin();
        $validated = $this->validated($request, $content);
        $oldSocialImage = $content->social_image_path;

        DB::transaction(function () use ($request, $validated, $content, $sanitizer) {
            $data = $this->prepareData($request, $validated, $sanitizer, $content);
            $content->update($data);
            $this->snapshotIfPublished($content);
        });

        if ($request->hasFile('social_image') && $oldSocialImage) {
            Storage::disk('public')->delete($oldSocialImage);
        }

        return back()->with('success', 'A tartalom módosításai elmentve.');
    }

    public function destroy(Content $content)
    {
        $this->authorizeHostadmin();
        abort_if(in_array($content->slug, ['aszf', 'adatkezeles', 'mediaajanlat'], true), 422, 'A rendszer alapoldala archiválható, de nem törölhető.');
        $content->delete();

        return redirect()->route('admin.contents.index', ['type' => $content->type])->with('success', 'A tartalom törölve lett.');
    }

    public function uploadImage(Request $request)
    {
        $this->authorizeHostadmin();
        $validated = $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120']]);
        $path = $validated['image']->store('content/editor', 'public');

        return response()->json(['url' => Storage::disk('public')->url($path)]);
    }

    private function validated(Request $request, ?Content $content = null): array
    {
        $reserved = ['admin', 'login', 'register', 'dashboard', 'quizzes', 'quiz', 'profile', 'notifications', 'points', 'my-quizzes', 'questions'];

        return $request->validate([
            'type' => ['required', Rule::in(['page', 'article'])],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:180', Rule::notIn($reserved), Rule::unique('contents', 'slug')->ignore($content)],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content_json' => ['nullable', 'json'],
            'content_html' => ['nullable', 'string', 'max:2000000'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'archived'])],
            'scheduled_for' => [Rule::requiredIf($request->input('status') === 'scheduled'), 'nullable', 'date'],
            'effective_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'canonical_url' => ['nullable', 'url:http,https', 'max:2048'],
            'sitemap_priority' => ['required', 'numeric', 'min:0', 'max:1'],
            'schema_type' => ['required', Rule::in(['WebPage', 'Article', 'AboutPage', 'ContactPage'])],
            'social_title' => ['nullable', 'string', 'max:255'],
            'social_description' => ['nullable', 'string', 'max:200'],
            'social_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'twitter_card' => ['required', Rule::in(['summary', 'summary_large_image'])],
            'llms_summary' => ['nullable', 'string', 'max:1000'],
            'llms_section' => ['nullable', 'string', 'max:100'],
            'llms_priority' => ['required', 'integer', 'min:0', 'max:1000'],
            'footer_label' => ['nullable', 'string', 'max:100'],
            'footer_group' => ['nullable', 'string', 'max:100'],
            'footer_order' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);
    }

    private function prepareData(Request $request, array $validated, ContentHtmlSanitizer $sanitizer, ?Content $content = null): array
    {
        $validated['content_json'] = filled($validated['content_json'] ?? null) ? json_decode($validated['content_json'], true) : null;
        $validated['content_html'] = $sanitizer->sanitize($validated['content_html'] ?? '');
        $validated['seo_title'] = $validated['seo_title'] ?: $validated['title'].' - KwizzGo';
        $validated['seo_description'] = $validated['seo_description'] ?: Str::limit(strip_tags($validated['excerpt'] ?: $validated['content_html']), 160, '');
        $validated['robots_index'] = $request->boolean('robots_index');
        $validated['robots_follow'] = $request->boolean('robots_follow');
        $validated['sitemap_include'] = $request->boolean('sitemap_include');
        $validated['llms_include'] = $request->boolean('llms_include');
        $validated['markdown_enabled'] = $request->boolean('markdown_enabled');
        $validated['footer_visible'] = $request->boolean('footer_visible');
        $validated['scheduled_for'] = $validated['status'] === 'scheduled' ? $validated['scheduled_for'] : null;
        $validated['published_at'] = $validated['status'] === 'published'
            ? ($content?->published_at ?? now())
            : ($validated['status'] === 'scheduled' ? $validated['scheduled_for'] : $content?->published_at);

        if ($request->hasFile('social_image')) {
            $validated['social_image_path'] = $request->file('social_image')->store('content/social', 'public');
        }

        unset($validated['social_image']);

        return $validated;
    }

    private function snapshotIfPublished(Content $content): void
    {
        if (! in_array($content->status, ['published', 'scheduled'], true)) {
            return;
        }

        $version = $content->version + 1;
        $content->forceFill(['version' => $version])->saveQuietly();
        $content->revisions()->create([
            'published_by' => auth()->id(),
            'version' => $version,
            'snapshot' => Arr::except($content->fresh()->toArray(), ['created_at', 'updated_at', 'deleted_at']),
            'published_at' => now(),
        ]);
    }

    private function authorizeHostadmin(): void
    {
        abort_unless(auth()->user()?->isHostadmin(), 403);
    }
}
