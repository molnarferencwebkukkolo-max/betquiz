<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_required_pages_are_created_as_drafts(): void
    {
        $this->assertDatabaseHas('contents', ['slug' => 'aszf', 'status' => 'draft', 'type' => 'page']);
        $this->assertDatabaseHas('contents', ['slug' => 'adatkezeles', 'status' => 'draft']);
        $this->assertDatabaseHas('contents', ['slug' => 'mediaajanlat', 'footer_visible' => true]);
        $this->get(route('content.aszf'))->assertNotFound();

        Content::where('slug', 'aszf')->update(['status' => 'published', 'content_html' => '<p>Hatályos feltételek.</p>']);
        $this->get(route('content.aszf'))->assertOk()->assertSee('Hatályos feltételek.');
    }

    public function test_only_hostadmin_can_manage_contents(): void
    {
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        $useradmin = User::factory()->create(['role' => 'useradmin']);

        $this->actingAs($hostadmin)->get(route('admin.contents.index'))->assertOk()->assertSee('Tartalomkezelő');
        $this->actingAs($useradmin)->get(route('admin.contents.index'))->assertForbidden();
        $this->actingAs($useradmin)->post(route('admin.contents.store'), $this->payload())->assertForbidden();
    }

    public function test_hostadmin_can_publish_sanitized_page_with_revision_and_metadata(): void
    {
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        $payload = $this->payload([
            'content_html' => '<h2>Biztonságos cím</h2><p onclick="alert(1)">Szöveg</p><script>alert(1)</script><a href="javascript:alert(1)">rossz</a>',
            'status' => 'published', 'footer_visible' => '1', 'llms_include' => '1',
        ]);

        $this->actingAs($hostadmin)->post(route('admin.contents.store'), $payload)
            ->assertRedirect()->assertSessionHasNoErrors();

        $content = Content::where('slug', 'teszt-oldal')->firstOrFail();
        $this->assertSame(1, $content->version);
        $this->assertCount(1, $content->revisions);
        $this->assertStringNotContainsString('<script', $content->content_html);
        $this->assertStringNotContainsString('onclick', $content->content_html);
        $this->assertStringNotContainsString('javascript:', $content->content_html);

        $this->get($content->publicUrl())->assertOk()->assertSee('Biztonságos cím')->assertSee('og:title', false);
    }

    public function test_draft_is_hidden_and_scheduled_page_appears_when_due(): void
    {
        $draft = Content::create($this->modelData(['slug' => 'rejtett', 'status' => 'draft']));
        $scheduled = Content::create($this->modelData(['slug' => 'idozitett', 'status' => 'scheduled', 'scheduled_for' => now()->subMinute()]));

        $this->get(route('content.show', $draft->slug))->assertNotFound();
        $this->get(route('content.show', $scheduled->slug))->assertOk();
    }

    public function test_llms_markdown_sitemap_and_footer_use_only_published_content(): void
    {
        $content = Content::create($this->modelData([
            'slug' => 'ai-oldal', 'title' => 'AI oldal', 'status' => 'published',
            'llms_include' => true, 'llms_summary' => 'Hiteles összefoglaló.',
            'markdown_enabled' => true, 'sitemap_include' => true, 'footer_visible' => true,
        ]));

        $this->get(route('content.llms'))->assertOk()->assertSee('AI oldal')->assertSee('Hiteles összefoglaló.');
        $this->get(route('content.sitemap'))->assertOk()->assertSee($content->publicUrl());
        $this->get(route('content.markdown', $content->slug))->assertOk()->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')->assertSee('# AI oldal');
        $this->get($content->publicUrl())->assertSee('AI oldal');
    }

    public function test_hostadmin_can_upload_editor_image(): void
    {
        Storage::fake('public');
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        $this->actingAs($hostadmin)->post(route('admin.contents.upload-image'), [
            'image' => UploadedFile::fake()->image('editor.webp', 800, 500),
        ])->assertOk()->assertJsonStructure(['url']);
        $this->assertCount(1, Storage::disk('public')->allFiles('content/editor'));
    }

    public function test_published_articles_have_listing_and_dedicated_route(): void
    {
        $article = Content::create($this->modelData([
            'type' => 'article', 'slug' => 'elso-cikk', 'title' => 'Első KwizzGo cikk',
            'status' => 'published', 'published_at' => now(),
        ]));

        $this->get(route('articles.index'))->assertOk()->assertSee('Első KwizzGo cikk');
        $this->get($article->publicUrl())->assertOk()->assertSee('KwizzGo cikk');
        $this->get(route('content.show', $article->slug))->assertNotFound();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'page', 'title' => 'Teszt oldal', 'slug' => 'teszt-oldal', 'excerpt' => 'Rövid leírás.',
            'content_json' => json_encode(['type' => 'doc', 'content' => []]), 'content_html' => '<p>Tartalom</p>',
            'status' => 'draft', 'seo_title' => 'Teszt SEO', 'seo_description' => 'Teszt meta leírás.',
            'sitemap_priority' => '0.5', 'schema_type' => 'WebPage', 'twitter_card' => 'summary_large_image',
            'llms_section' => 'Információk', 'llms_priority' => 50, 'footer_group' => 'Információk', 'footer_order' => 100,
            'robots_index' => '1', 'robots_follow' => '1', 'sitemap_include' => '1', 'markdown_enabled' => '1',
        ], $overrides);
    }

    private function modelData(array $overrides = []): array
    {
        return array_merge([
            'type' => 'page', 'title' => 'Teszt', 'slug' => 'teszt', 'content_html' => '<h2>Fejezet</h2><p>Szöveg.</p>',
            'status' => 'draft', 'robots_index' => true, 'robots_follow' => true, 'sitemap_include' => true,
            'sitemap_priority' => .5, 'schema_type' => 'WebPage', 'twitter_card' => 'summary_large_image',
            'llms_include' => false, 'llms_section' => 'Információk', 'llms_priority' => 50,
            'markdown_enabled' => true, 'footer_visible' => false, 'footer_group' => 'Információk', 'footer_order' => 100,
        ], $overrides);
    }
}
