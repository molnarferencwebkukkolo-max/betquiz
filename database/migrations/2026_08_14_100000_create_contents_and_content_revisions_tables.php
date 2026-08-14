<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20)->default('page')->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->json('content_json')->nullable();
            $table->longText('content_html')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->timestamp('effective_at')->nullable();
            $table->unsignedInteger('version')->default(0);

            $table->string('seo_title')->nullable();
            $table->string('seo_description', 160)->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);
            $table->boolean('sitemap_include')->default(true);
            $table->decimal('sitemap_priority', 2, 1)->default(0.5);
            $table->string('schema_type', 30)->default('WebPage');

            $table->string('social_title')->nullable();
            $table->string('social_description', 200)->nullable();
            $table->string('social_image_path')->nullable();
            $table->string('twitter_card', 30)->default('summary_large_image');

            $table->boolean('llms_include')->default(false)->index();
            $table->text('llms_summary')->nullable();
            $table->string('llms_section')->default('Információk');
            $table->unsignedSmallInteger('llms_priority')->default(50);
            $table->boolean('markdown_enabled')->default(true);

            $table->boolean('footer_visible')->default(false)->index();
            $table->string('footer_label')->nullable();
            $table->string('footer_group')->default('Információk');
            $table->unsignedSmallInteger('footer_order')->default(100);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->timestamp('published_at');
            $table->timestamps();
            $table->unique(['content_id', 'version']);
        });

        $now = now();
        DB::table('contents')->insert([
            $this->starterPage('Általános Szerződési Feltételek', 'aszf', 10, true, $now),
            $this->starterPage('Adatkezelési szabályzat', 'adatkezeles', 20, true, $now),
            $this->starterPage('Médiaajánlat', 'mediaajanlat', 30, true, $now),
        ]);
    }

    private function starterPage(string $title, string $slug, int $order, bool $llmsInclude, $now): array
    {
        return [
            'type' => 'page', 'title' => $title, 'slug' => $slug,
            'excerpt' => null, 'content_json' => null, 'content_html' => null,
            'status' => 'draft', 'version' => 0,
            'seo_title' => $title.' - KwizzGo', 'seo_description' => null,
            'robots_index' => true, 'robots_follow' => true,
            'sitemap_include' => true, 'sitemap_priority' => 0.5, 'schema_type' => 'WebPage',
            'twitter_card' => 'summary_large_image',
            'llms_include' => $llmsInclude, 'llms_section' => 'Információk',
            'llms_priority' => $order, 'markdown_enabled' => true,
            'footer_visible' => true, 'footer_label' => $title,
            'footer_group' => 'Információk', 'footer_order' => $order,
            'created_at' => $now, 'updated_at' => $now,
        ];
    }

    public function down(): void
    {
        Schema::dropIfExists('content_revisions');
        Schema::dropIfExists('contents');
    }
};
