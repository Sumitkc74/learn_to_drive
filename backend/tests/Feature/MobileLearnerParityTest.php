<?php
namespace Tests\Feature;

use App\Models\{User,Question,LearnerPracticeAttempt,LearnerBookmark,PremiumChatTurn};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache,Hash};
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileLearnerParityTest extends TestCase
{
    use RefreshDatabase;
    public function test_browser_handoff_preserves_practice_and_language_without_sharing_other_users_attempts(): void
    {
        $user=User::factory()->create(['is_active'=>true]);
        $token=$user->createToken('phone')->plainTextToken;
        $q=$this->question();
        $id=$this->withToken($token)->postJson('/api/learner/practice',['count'=>5,'language'=>'en'])->assertOk()->json('data.id');
        $this->patchJson('/api/learner/practice/'.$id,['answers'=>[$q->id=>'B']])->assertOk();
        $url=$this->postJson('/api/mobile/handoff',['destination'=>'practice.attempt','attempt_id'=>$id,'language'=>'ne','practice_language'=>'en'])->assertOk()->json('data.url');
        $this->post($url)->assertRedirect(route('learn.practice.attempt',$id))->assertSessionHas('learner_locale','ne')->assertSessionHas('practice_language','en');
        $this->post($url)->assertStatus(410);
        $this->assertSame('B',LearnerPracticeAttempt::findOrFail($id)->answers[$q->id]);
        $other=User::factory()->create(['is_active'=>true]);
        \Laravel\Sanctum\Sanctum::actingAs($other);
        $this->postJson('/api/mobile/handoff',['destination'=>'practice.attempt','attempt_id'=>$id])->assertNotFound();
    }
    private function question(): Question
    {
        return Question::create(['question'=>'Which action is safe?','option1'=>'Stop','option2'=>'Rush','option3'=>'Ignore','option4'=>'Speed','correctOption'=>'A','explanation'=>'Wait until safe.','status'=>'Published','category'=>'General','difficulty'=>'Easy']);
    }
    public function test_mobile_revision_and_attempt_status_match_web_content(): void
    {
        $user=User::factory()->create(['is_active'=>true,'role'=>'PremiumUser']);
        Sanctum::actingAs($user);
        $q=$this->question();
        \Illuminate\Support\Facades\Storage::fake('protected-media');
        $q->addMedia(\Illuminate\Http\UploadedFile::fake()->image('sign.png'))->toMediaCollection('question-images');
        $this->assertNotNull($q->image_url);
        $start=$this->postJson('/api/learner/practice',['count'=>5])->assertOk();
        $start->assertJsonPath('data.questions.0.image_url',$q->image_url)->assertJsonMissingPath('data.questions.0.correct');
        $id=$start->json('data.id');
        $this->getJson('/api/learner/account')->assertOk()->assertJsonPath('data.next_step.action','resume')->assertJsonPath('data.next_step.attempt_id',$id);
        $this->postJson('/api/learner/practice/'.$id,['answers'=>[$q->id=>'B']])->assertOk();
        $this->getJson('/api/learner/revision')->assertOk()->assertJsonPath('data.0.correctOption','A')->assertJsonPath('data.0.option1','Stop')->assertJsonStructure(['data'=>[['image_url','category']]]);
        $this->getJson('/api/learner/catalog')->assertOk()->assertJsonPath('data.premium.active',true);
        $this->postJson('/api/learner/practice',['count'=>5])->assertOk();
        $this->travel(3)->hours();
        $this->getJson('/api/learner/account')->assertOk()->assertJsonPath('data.attempts.0.status','expired')->assertJsonPath('data.attempts.1.status','completed');
    }
    public function test_mobile_practice_resumes_on_web_and_updates_shared_progress(): void
    {
        $user=User::factory()->create(['is_active'=>true]);Sanctum::actingAs($user);$q=$this->question();
        $start=$this->postJson('/api/learner/practice',['count'=>5,'language'=>'en'])->assertOk();
        $id=$start->json('data.id');
        $start->assertJsonMissingPath('data.questions.0.correct')->assertJsonMissingPath('data.questions.0.explanation');
        $this->patchJson('/api/learner/practice/'.$id,['answers'=>[$q->id=>'A']])->assertOk();
        $this->actingAs($user)->get('/learn/practice/'.$id)->assertOk()->assertSee('Which action is safe?');
        $this->postJson('/api/learner/practice/'.$id,['answers'=>[$q->id=>'A']])->assertOk()->assertJsonPath('data.score',1)->assertJsonPath('data.questions.0.correct','A');
        $this->getJson('/api/learner/account')->assertOk()->assertJsonPath('data.progress.sessions',1)->assertJsonPath('data.progress.accuracy',100);
        $this->assertDatabaseCount('user_histories',1);
        $this->postJson('/api/learner/practice/'.$id,['answers'=>[$q->id=>'A']])->assertOk();
        $this->assertDatabaseCount('user_histories',1);
    }
    public function test_mobile_cannot_read_other_users_practice_or_premium_data(): void
    {
        $owner=User::factory()->create(['is_active'=>true]);$other=User::factory()->create(['is_active'=>true]);
        Sanctum::actingAs($owner);$this->question();
        $id=$this->postJson('/api/learner/practice',['count'=>5])->json('data.id');
        Sanctum::actingAs($other);
        $this->getJson('/api/learner/practice/'.$id)->assertNotFound();
        $this->patchJson('/api/learner/practice/'.$id,['answers'=>[]])->assertNotFound();
        $this->getJson('/api/learner/chat')->assertForbidden();
        $this->postJson('/api/learner/chat',['message'=>'Help'])->assertForbidden();
        $this->getJson('/api/learner/revision')->assertForbidden();
    }
    public function test_browser_login_requires_approval_and_is_single_use(): void
    {
        $flow=$this->postJson('/api/mobile/login/start')->assertOk()->json('data');
        $this->postJson('/api/mobile/login/exchange',['id'=>$flow['id'],'verifier'=>$flow['verifier']])->assertStatus(409);
        $user=User::factory()->create(['is_active'=>true]);$this->actingAs($user);
        $this->get($flow['url'])->assertOk()->assertSee($flow['code']);
        $this->post('/learn/mobile/connect/'.$flow['id'])->assertRedirect();
        $this->postJson('/api/mobile/login/exchange',['id'=>$flow['id'],'verifier'=>str_repeat('a',64)])->assertForbidden();
        $response=$this->postJson('/api/mobile/login/exchange',['id'=>$flow['id'],'verifier'=>$flow['verifier']])->assertOk();
        $response->assertJsonPath('user.id',$user->id)->assertJsonStructure(['token'=>['access_token','expires_at']]);
        $this->postJson('/api/mobile/login/exchange',['id'=>$flow['id'],'verifier'=>$flow['verifier']])->assertForbidden();
    }
    public function test_native_mfa_setup_requires_password_and_login_requires_fresh_code(): void
    {
        $user=User::factory()->create(['is_active'=>true]);$token=$user->createToken('phone')->plainTextToken;
        $this->withToken($token)->postJson('/api/learner/mfa/setup',['current_password'=>'wrong'])->assertUnprocessable();
        $secret=$this->postJson('/api/learner/mfa/setup',['current_password'=>'password'])->assertOk()->json('data.secret');
        $code=(new \PragmaRX\Google2FA\Google2FA)->getCurrentOtp($secret);
        $codes=$this->postJson('/api/learner/mfa/enable',['current_password'=>'password','code'=>$code])->assertOk()->json('data.recovery_codes');
        $this->assertNotSame($secret,$user->fresh()->getRawOriginal('mfa_secret'));
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization','')->postJson('/api/auth/login',['email'=>$user->email,'password'=>'password'])->assertForbidden()->assertJsonPath('mfa_required',true);
        $this->postJson('/api/auth/login',['email'=>$user->email,'password'=>'password','mfa_code'=>$codes[0]])->assertOk();
        $this->postJson('/api/auth/login',['email'=>$user->email,'password'=>'password','mfa_code'=>$codes[0]])->assertForbidden();
    }
    public function test_email_change_requires_password_and_resets_verification(): void
    {
        $user=User::factory()->create(['email_verified_at'=>now(),'is_active'=>true]);Sanctum::actingAs($user);
        $this->patchJson('/api/learner/settings/email',['email'=>'new@example.test'])->assertUnprocessable();
        $this->patchJson('/api/learner/settings/email',['email'=>'new@example.test','current_password'=>'password'])->assertOk();
        $this->assertNull($user->fresh()->email_verified_at);
        $this->getJson('/api/learner/account')->assertJsonMissingPath('data.user.password')->assertJsonMissingPath('data.user.mfa_secret');
    }
    public function test_revoked_token_cannot_be_used_for_browser_handoff(): void
    {
        $user=User::factory()->create(['is_active'=>true]);$token=$user->createToken('phone')->plainTextToken;
        $url=$this->withToken($token)->postJson('/api/mobile/handoff',['destination'=>'premium'])->assertOk()->json('data.url');
        $user->tokens()->delete();
        $this->post($url)->assertForbidden();
    }
    public function test_suspended_account_cannot_use_new_mobile_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_active'=>false]));
        $this->getJson('/api/learner/account')->assertForbidden();
    }
    public function test_timed_mock_expires_and_grades_only_saved_answers_once(): void
    {
        $user=User::factory()->create(['is_active'=>true]);Sanctum::actingAs($user);$q=$this->question();
        $id=$this->postJson('/api/learner/practice',['count'=>25,'mode'=>'mock'])->assertOk()->json('data.id');
        $attempt=LearnerPracticeAttempt::findOrFail($id);
        $this->assertSame(30,(int)$attempt->created_at->diffInMinutes($attempt->expires_at));
        $this->patchJson('/api/learner/practice/'.$id,['answers'=>[$q->id=>'A']])->assertOk();
        $this->travel(31)->minutes();
        $this->patchJson('/api/learner/practice/'.$id,['answers'=>[$q->id=>'B']])->assertStatus(410);
        $this->getJson('/api/learner/practice/'.$id)->assertOk()->assertJsonPath('data.score',1);
        $this->getJson('/api/learner/practice/'.$id)->assertOk();
        $this->assertDatabaseCount('user_histories',1);
    }
    public function test_mobile_saved_resources_are_visible_on_web_and_hide_archived_notices(): void
    {
        $user=User::factory()->create(['is_active'=>true]);Sanctum::actingAs($user);
        $notice=\App\Models\Notice::create(['title'=>'Shared notice','description'=>'A description','nepaliTitle'=>'Notice','nepaliDescription'=>'Description','link'=>'https://dotm.gov.np/','status'=>'Published']);
        $id=$this->postJson('/api/learner/saved/notice/'.$notice->id)->assertOk()->json('data.bookmark_id');
        $this->postJson('/api/learner/saved/notice/'.$notice->id)->assertOk();
        $this->assertDatabaseCount('learner_bookmarks',1);
        $this->getJson('/api/learner/library/notice/'.$notice->id)->assertOk()->assertJsonPath('data.bookmark_id',$id);
        $this->actingAs($user)->get('/learn/saved')->assertOk()->assertSee('Shared notice');
        $notice->update(['status'=>'Archived']);
        $this->getJson('/api/learner/saved')->assertOk()->assertJsonPath('data.0.resource',null);
        $this->getJson('/api/learner/library/notice/'.$notice->id)->assertNotFound();
        Sanctum::actingAs(User::factory()->create(['is_active'=>true]));
        $this->deleteJson('/api/learner/saved/'.$id)->assertNotFound();
    }
    public function test_premium_chat_uses_the_shared_service_and_private_history(): void
    {
        $user=User::factory()->create(['is_active'=>true,'role'=>'PremiumUser']);Sanctum::actingAs($user);
        config(['pdf-translation.gemini_key'=>'test']);
        $this->mock(\App\Services\LearnerChat::class,fn($mock)=>$mock->shouldReceive('answer')->once()->andReturn('Check both sides before crossing.'));
        $this->postJson('/api/learner/chat',['message'=>'How should I cross?'])->assertOk()->assertJsonPath('answer','Check both sides before crossing.');
        $this->getJson('/api/learner/chat')->assertJsonCount(1,'data');
        Sanctum::actingAs(User::factory()->create(['is_active'=>true,'role'=>'PremiumUser']));
        $this->getJson('/api/learner/chat')->assertJsonCount(0,'data');
    }

}

