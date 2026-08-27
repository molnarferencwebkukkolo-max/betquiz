<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicQuizSharingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_receives_quiz_specific_social_metadata_without_auth_redirect(): void
    {
        Storage::fake('public');
        // Valódi 1×1 PNG kell ahhoz, hogy a vezérlő képméret-ellenőrzését is lefedjük.
        Storage::disk('public')->put('quiz_covers/social.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        ));
        $quiz = $this->makeQuiz([
            'title' => 'Magyar történelem',
            'seo_title' => 'Magyar történelem kvíz',
            'seo_description' => 'Teszteld a tudásodat a magyar történelemről!',
            'cover_image' => 'quiz_covers/social.png',
        ]);

        $canonical = route('quizzes.share', $quiz);
        $image = asset('storage/quiz_covers/social.png');

        $this->get($canonical)
            ->assertOk()
            ->assertSee('<title>Magyar történelem</title>', false)
            ->assertSee('<link rel="canonical" href="'.$canonical.'">', false)
            ->assertSee('<meta property="og:title" content="Magyar történelem">', false)
            ->assertSee('<meta property="og:description" content="Teszteld a tudásodat a magyar történelemről!">', false)
            ->assertSee('<meta property="og:image" content="'.$image.'">', false)
            ->assertSee('<meta property="og:image:width" content="1">', false)
            ->assertSee('<meta property="og:image:height" content="1">', false)
            ->assertSee('<meta property="og:image:type" content="image/png">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee(route('login'));
    }

    public function test_missing_cover_uses_branded_absolute_fallback(): void
    {
        $quiz = $this->makeQuiz();

        $this->get(route('quizzes.share', $quiz))
            ->assertOk()
            ->assertSee(asset('images/kwizzgo-social-default.svg'))
            ->assertSee('<meta property="og:image:width" content="1200">', false)
            ->assertSee('<meta property="og:image:height" content="630">', false);
    }

    public function test_private_and_unapproved_quizzes_are_not_publicly_visible(): void
    {
        $private = $this->makeQuiz(['is_public' => false]);
        $pending = $this->makeQuiz(['status' => 'pending']);

        $this->get(route('quizzes.share', $private))->assertNotFound();
        $this->get(route('quizzes.share', $pending))->assertNotFound();
    }

    public function test_authenticated_preview_links_to_the_existing_game_setup(): void
    {
        $player = User::factory()->create();
        $quiz = $this->makeQuiz();

        $this->actingAs($player)
            ->get(route('quizzes.share', $quiz))
            ->assertOk()
            ->assertSee(route('quiz.setup', $quiz));
    }

    public function test_legacy_setup_url_serves_social_preview_to_guest_instead_of_login_redirect(): void
    {
        $quiz = $this->makeQuiz([
            'title' => 'Régi megosztott link',
            'seo_description' => 'A régi setup URL is közvetlen előnézetet ad.',
        ]);

        $this->get(route('quiz.setup', $quiz))
            ->assertOk()
            ->assertSee('<meta property="og:title" content="Régi megosztott link">', false)
            ->assertSee('<link rel="canonical" href="'.route('quizzes.share', $quiz).'">', false)
            ->assertDontSee('<title>KwizzGo - Bejelentkezés</title>', false);
    }

    public function test_public_quizzes_are_discoverable_in_the_sitemap(): void
    {
        $public = $this->makeQuiz();
        $private = $this->makeQuiz(['title' => 'Rejtett kvíz', 'is_public' => false]);

        $this->get(route('content.sitemap'))
            ->assertOk()
            ->assertSee(route('quizzes.share', $public))
            ->assertDontSee(route('quizzes.share', $private));
    }

    private function makeQuiz(array $overrides = []): Quiz
    {
        $creator = User::factory()->create();
        $category = Category::create([
            'name' => ['hu' => 'Általános'],
            'slug' => 'altalanos-'.uniqid(),
            'icon' => '❓',
            'is_active' => true,
        ]);

        return Quiz::create(array_merge([
            'creator_id' => $creator->id,
            'category_id' => $category->id,
            'title' => 'Nyilvános tesztkvíz '.uniqid(),
            'description' => 'Rövid, megosztható kvízleírás.',
            'status' => 'approved',
            'is_public' => true,
        ], $overrides));
    }
}
