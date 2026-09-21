<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Models\{User,Question,LearnerPracticeAttempt,PremiumChatTurn,SupportTicket};
class LearnerPremiumTest extends TestCase {
    use RefreshDatabase;
    private function question(){return Question::create(['question'=>'A sample study question','option1'=>'One','option2'=>'Two','option3'=>'Three','option4'=>'Four','correctOption'=>'A','explanation'=>'Explanation to study','status'=>'Published','category'=>'General','difficulty'=>'Easy']);}
    private function attempt($question,$answer){
        $this->post(route('learn.practice.start'),['count'=>5]);$attempt=LearnerPracticeAttempt::latest('id')->firstOrFail();
        $this->post(route('learn.practice.attempt',$attempt),['answers'=>[$question->id=>$answer]])->assertRedirect();
    }
    public function test_free_accounts_cannot_access_premium_or_self_upgrade(): void {
        $user=User::factory()->create(['is_active'=>true]);$this->actingAs($user);
        $this->get(route('learn.premium'))->assertOk();
        $this->get(route('learn.premium.chat'))->assertRedirect(route('learn.premium'));
        $this->post(route('learn.premium.message'),['message'=>'hello'])->assertRedirect(route('learn.premium'));
        $this->get(route('learn.premium.modules'))->assertRedirect(route('learn.premium'));
        $this->post(route('learn.practice.start'),['mode'=>'revision','count'=>5])->assertForbidden();
        $this->post(route('learn.premium.request'),['role'=>'PremiumUser'])->assertRedirect();
        $this->post(route('learn.premium.request'))->assertRedirect();
        $this->assertSame(1,SupportTicket::count());$this->assertSame('User',$user->fresh()->role);
    }
    public function test_revision_uses_own_latest_answers_and_excludes_changed_content(): void {
        $user=User::factory()->create(['is_active'=>true,'role'=>'PremiumUser']);$q=$this->question();$this->actingAs($user);$this->attempt($q,'B');
        $this->get(route('learn.premium.modules'))->assertOk()->assertSee('A sample study question')->assertSee('Explanation to study');
        $this->actingAs(User::factory()->create(['is_active'=>true,'role'=>'PremiumUser']))->get(route('learn.premium.modules'))->assertDontSee('A sample study question');
        $this->actingAs($user)->post(route('learn.practice.start'),['mode'=>'revision','count'=>5])->assertRedirect();
        $attempt=LearnerPracticeAttempt::latest('id')->firstOrFail();$this->assertCount(1,$attempt->questions);
        $this->post(route('learn.practice.attempt',$attempt),['answers'=>[$q->id=>'A']]);
        $this->get(route('learn.premium.modules'))->assertDontSee('A sample study question');
        $this->attempt($q,'B');$q->update(['status'=>'Draft']);
        $this->get(route('learn.premium.modules'))->assertDontSee('A sample study question');
    }
    public function test_chat_is_private_bounded_and_has_safe_failure_handling(): void {
        config(['pdf-translation.gemini_key'=>'fake-key','premium.daily_chat_limit'=>2]);
        Http::fake(['*'=>Http::sequence()->push(['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>'A helpful explanation <script>bad()</script>']]]]]])->push([],503)]);
        $user=User::factory()->create(['is_active'=>true,'role'=>'PremiumUser']);$this->actingAs($user);
        $this->post(route('learn.premium.message'),['message'=>'Explain a concept'])->assertRedirect();
        $this->get(route('learn.premium.chat'))->assertOk()->assertSee('A helpful explanation')->assertDontSee('<script>bad()</script>',false);
        Http::assertSent(fn($r)=>!str_contains($r->body(),$user->email) && isset($r['systemInstruction']));
        
        $this->post(route('learn.premium.message'),['message'=>'Try again'])->assertSessionHas('error');
        $this->assertSame(1,PremiumChatTurn::where('status','Failed')->count());
        $this->post(route('learn.premium.message'),['message'=>'Above limit'])->assertSessionHasErrors('message');
        $this->actingAs(User::factory()->create(['is_active'=>true,'role'=>'PremiumUser']))->get(route('learn.premium.chat'))->assertDontSee('Explain a concept');
    }
    public function test_personalized_set_adds_related_questions_and_chat_focus_is_owned(): void {
        $user=User::factory()->create(['is_active'=>true,'role'=>'PremiumUser']);
        $q=$this->question();$this->actingAs($user);$this->attempt($q,'B');
        $related=$q->replicate();$related->question='Another question in the same topic';$related->save();
        $draft=$q->replicate();$draft->question='Unpublished related question';$draft->status='Draft';$draft->save();
        $this->post(route('learn.practice.start'),['mode'=>'personalized','count'=>5])->assertRedirect();
        $attempt=LearnerPracticeAttempt::latest('id')->firstOrFail();
        $this->assertEqualsCanonicalizing([$q->id,$related->id],array_column($attempt->questions,'id'));
        $this->get(route('learn.premium.chat',['question_id'=>$q->id]))->assertOk()->assertSee('YOUR SELECTED QUESTION');
        $this->get(route('learn.premium.chat',['question_id'=>$related->id]))->assertNotFound();
        config(['pdf-translation.gemini_key'=>'test']);
        Http::fake(['*'=>Http::response(['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>'Focused explanation']]]]]])]);
        $this->post(route('learn.premium.message'),['message'=>'Explain this','question_id'=>$q->id])->assertRedirect();
        Http::assertSent(fn($r)=>str_contains($r['systemInstruction']['parts'][0]['text'],$q->question) && !str_contains($r->body(),$draft->question));
        $this->actingAs(User::factory()->create(['is_active'=>true,'role'=>'PremiumUser']))->post(route('learn.premium.message'),['message'=>'Explain this','question_id'=>$q->id])->assertNotFound();
    }
    public function test_floating_chat_is_premium_only_and_json_requests_include_scope_rules(): void {
        $this->get('/learn/practice')->assertDontSee('id="study-chat-toggle"',false);
        $user=User::factory()->create(['is_active'=>true,'role'=>'PremiumUser']);
        $this->actingAs($user)->get('/learn/practice')->assertOk()->assertSee('id="study-chat-toggle"',false);
        $this->get(route('learn.premium.chat'))->assertDontSee('id="study-chat-toggle"',false);
        config(['pdf-translation.gemini_key'=>'fake']);
        Http::fake(['*'=>Http::response(['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>'I can help with this platform and driving-test preparation.']]]]]])]);
        $this->postJson(route('learn.premium.message'),['message'=>'Write an unrelated recipe'])->assertOk()->assertJsonStructure(['answer']);
        Http::assertSent(fn($r)=>str_contains($r['systemInstruction']['parts'][0]['text'],'SCOPE RULE:') && str_contains($r['systemInstruction']['parts'][0]['text'],'PLATFORM FACTS:'));
        $user->update(['role'=>'User']);
        $this->get('/learn/practice')->assertDontSee('id="study-chat-toggle"',false);
        $this->postJson(route('learn.premium.message'),['message'=>'Hello'])->assertRedirect(route('learn.premium'));
    }
}
