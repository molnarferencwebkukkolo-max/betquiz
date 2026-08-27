<?php
namespace Tests\Feature;
use App\Models\User; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class ReferralSystemTest extends TestCase { use RefreshDatabase;
 public function test_inviter_receives_reward_and_username_only_notification():void{
  $inviter=User::factory()->create(['points'=>500,'referral_code'=>'invite123']);
  $this->get(route('referrals.accept','invite123'))->assertOk()->assertSee('Meghívás elfogadása');
  $this->post(route('register'),['username'=>'newfriend','email'=>'friend@example.com','password'=>'Password123!','password_confirmation'=>'Password123!','accept_terms'=>'1','accept_privacy'=>'1'])->assertRedirect(route('verification.notice'));
  $this->assertSame(1500,$inviter->fresh()->points);$n=$inviter->notifications()->firstOrFail();$this->assertStringContainsString('newfriend',$n->data['message']);$this->assertStringNotContainsString('friend@example.com',$n->data['message']);
  $this->assertDatabaseHas('referrals',['inviter_id'=>$inviter->id,'reward_points'=>1000]);
 }
 public function test_points_page_contains_tasks_and_unique_link():void{$u=User::factory()->create();$this->actingAs($u)->get(route('pages.points'))->assertOk()->assertSee('Töltsd ki a profilod')->assertSee('facebook.com/kwizzgo')->assertSee('/meghivo/',false);$this->assertNotNull($u->fresh()->referral_code);}
 public function test_invite_page_has_complete_social_preview_metadata():void{$u=User::factory()->create(['referral_code'=>'socialinvite']);$this->get(route('referrals.accept','socialinvite'))->assertOk()->assertSee('<meta property="og:title" content="Meghívtak a KwizzGo-ra!">',false)->assertSee(asset('images/invite-fb.png'),false)->assertSee('<meta property="og:image:width" content="1200">',false)->assertSee('<meta name="twitter:card" content="summary_large_image">',false);}
}
