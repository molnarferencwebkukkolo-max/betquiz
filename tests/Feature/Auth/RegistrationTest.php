<?php

namespace Tests\Feature\Auth;

use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\VerifyEmailNotification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_both_legal_acceptances(): void
    {
        $this->post(route('register'), $this->payload([
            'accept_terms' => null,
            'accept_privacy' => null,
        ]))->assertSessionHasErrors(['accept_terms', 'accept_privacy']);

        $this->assertDatabaseMissing('users', ['email' => 'legal@example.com']);
    }

    public function test_registration_records_document_versions_and_request_evidence(): void
    {
        Notification::fake();
        Content::where('slug', 'aszf')->update(['version' => 4, 'status' => 'published']);
        Content::where('slug', 'adatkezeles')->update(['version' => 7, 'status' => 'published']);

        $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_USER_AGENT' => 'KwizzGo registration test',
        ])->post(route('register'), $this->payload())->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'legal@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
        $this->assertDatabaseHas('legal_consents', [
            'user_id' => $user->id,
            'consent_type' => 'terms',
            'content_version' => 4,
            'document_url' => url('/aszf'),
            'ip_address' => '203.0.113.10',
        ]);
        $this->assertDatabaseHas('legal_consents', [
            'user_id' => $user->id,
            'consent_type' => 'privacy',
            'content_version' => 7,
            'document_url' => url('/adatkezeles'),
        ]);
    }

    public function test_registration_page_contains_legal_links_and_cookie_controls(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee(route('content.aszf'), false)
            ->assertSee(route('content.privacy'), false)
            ->assertSee('data-cookie-consent', false)
            ->assertSee('Sütik a KwizzGo oldalán');
    }

    public function test_google_analytics_is_loaded_only_after_optional_cookie_consent(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('data-analytics-id="G-WG40CJBW03"', false)
            ->assertSee("read() === 'all'", false)
            ->assertSee('enableAnalytics()', false)
            ->assertSee('analytics_storage', false)
            ->assertSee('googletagmanager.com/gtag/js?id=', false);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'username' => 'legaluser',
            'email' => 'legal@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'accept_terms' => '1',
            'accept_privacy' => '1',
        ], $overrides);
    }
}
