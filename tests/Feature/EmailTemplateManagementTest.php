<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\HtmlString;
use Tests\TestCase;

class EmailTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_hostadmin_can_manage_both_templates_on_one_page(): void
    {
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        $useradmin = User::factory()->create(['role' => 'useradmin']);
        $this->actingAs($hostadmin)->get(route('admin.email-templates.index'))->assertOk()
            ->assertSee('E-mail-cim hitelesitese')->assertSee('Welcome level');
        $this->actingAs($useradmin)->get(route('admin.email-templates.index'))->assertForbidden();
    }

    public function test_hostadmin_can_edit_template_and_verification_cannot_be_disabled(): void
    {
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        $template = EmailTemplate::content(EmailTemplate::VERIFICATION);
        $this->actingAs($hostadmin)->patch(route('admin.email-templates.update', $template), [
            'subject' => 'Egyedi {{username}}', 'heading' => 'Udvozlet', 'body' => 'Hitelesitsd a cimed.',
            'button_text' => 'Hitelesites', 'footer' => 'KwizzGo',
        ])->assertRedirect();
        $this->assertDatabaseHas('email_templates', ['key' => EmailTemplate::VERIFICATION, 'subject' => 'Egyedi {{username}}', 'is_active' => true]);
    }

    public function test_verified_event_sends_welcome_email_and_test_send_uses_selected_template(): void
    {
        Notification::fake();
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        $user = User::factory()->create();
        event(new Verified($user));
        Notification::assertSentTo($user, WelcomeNotification::class);

        $template = EmailTemplate::content(EmailTemplate::VERIFICATION);
        $this->actingAs($hostadmin)->post(route('admin.email-templates.test', $template))->assertRedirect();
        Notification::assertSentTo($hostadmin, VerifyEmailNotification::class);
    }

    public function test_already_verified_google_style_registration_gets_welcome_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['google_id' => 'google-test-id']);
        event(new Registered($user));
        Notification::assertSentTo($user, WelcomeNotification::class);
    }

    public function test_template_preview_renders_safe_markdown_links(): void
    {
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        EmailTemplate::content(EmailTemplate::WELCOME)->update([
            'body' => '[Kvizkatalogus](https://kwizzgo.com/quizzes)',
        ]);

        $this->actingAs($hostadmin)->get(route('admin.email-templates.index'))
            ->assertOk()
            ->assertSee('<a href="https://kwizzgo.com/quizzes">Kvizkatalogus</a>', false);
    }

    public function test_template_preview_preserves_editor_line_breaks(): void
    {
        $hostadmin = User::factory()->create(['role' => 'hostadmin']);
        EmailTemplate::content(EmailTemplate::WELCOME)->update(['body' => "Elso sor\nMasodik sor"]);

        $this->actingAs($hostadmin)->get(route('admin.email-templates.index'))
            ->assertOk()->assertSee("Elso sor<br>\nMasodik sor", false);
    }

    public function test_two_enters_are_preserved_as_a_visible_empty_mail_line(): void
    {
        $user = User::factory()->create();
        $template = EmailTemplate::content(EmailTemplate::WELCOME);
        $template->update(['body' => "Elso sor\n\nHarmadik sor"]);

        $mail = (new WelcomeNotification())->toMail($user);
        $this->assertSame('Elso sor', $mail->introLines[0]);
        $this->assertInstanceOf(HtmlString::class, $mail->introLines[1]);
        $this->assertSame('&nbsp;', $mail->introLines[1]->toHtml());
        $this->assertSame('Harmadik sor', $mail->introLines[2]);
    }
}
