<?php
namespace Tests\Feature;

use App\Models\{User,Question,LearnerPracticeAttempt,LearnerBookmark,Notice};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerStudyToolsTest extends TestCase
{
    use RefreshDatabase;

    private function question(string $text='Which action is safe?'): Question
    {
        return Question::create(['question'=>$text,'option1'=>'Stop','option2'=>'Rush','option3'=>'Ignore','option4'=>'Speed','correctOption'=>'A','explanation'=>'Wait until safe.','status'=>'Published','category'=>'General','difficulty'=>'Easy']);
    }
    public function test_practice_filters_by_language_and_never_silently_falls_back(): void
    {
        $this->actingAs(User::factory()->create(['is_active'=>true]));
        $english=$this->question(); $nepali=$this->question('कुन काम सुरक्षित हुन्छ?');
        $this->assertSame('ne',$nepali->language);
        $this->post(route('learn.practice.start'),['count'=>5,'language'=>'ne'])->assertRedirect();
        $attempt=LearnerPracticeAttempt::firstOrFail();
        $this->assertSame([$nepali->id],array_column($attempt->questions,'id'));
        $nepali->update(['status'=>'Draft']);
        $this->post(route('learn.practice.start'),['count'=>5,'language'=>'ne'])->assertSessionHasErrors('practice');
        $this->assertSame(1,LearnerPracticeAttempt::count());
        $this->assertSame('en',$english->language);
    }
    public function test_drafts_are_private_ungraded_and_can_resume_without_exposing_solutions(): void
    {
        $user=User::factory()->create(['is_active'=>true]);$q=$this->question();
        $this->actingAs($user)->post(route('learn.practice.start'),['count'=>5]);
        $attempt=LearnerPracticeAttempt::firstOrFail();
        $this->patchJson(route('learn.practice.draft',$attempt),['answers'=>[$q->id=>'B']])->assertOk()->assertExactJson(['message'=>'Answers saved.']);
        $this->assertNull($attempt->fresh()->completed_at);
        $this->get(route('learn.practice.attempt',$attempt))->assertSee('value="B" required checked',false)->assertDontSee('Wait until safe.');
        $this->get(route('learn.account'))->assertSee('Resume your practice');
        $this->patchJson(route('learn.practice.draft',$attempt),['answers'=>[999999=>'A']])->assertStatus(422);
        $this->actingAs(User::factory()->create(['is_active'=>true]))->patchJson(route('learn.practice.draft',$attempt),['answers'=>[$q->id=>'A']])->assertNotFound();
        $this->actingAs($user)->post(route('learn.practice.attempt',$attempt),['answers'=>[$q->id=>'A']])->assertRedirect();
        $this->patchJson(route('learn.practice.draft',$attempt),['answers'=>[$q->id=>'B']])->assertStatus(409);
        $this->assertSame(1,$attempt->fresh()->score);
    }
    public function test_expired_and_changed_drafts_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['is_active'=>true]));$q=$this->question();
        $this->post(route('learn.practice.start'),['count'=>5]);$attempt=LearnerPracticeAttempt::firstOrFail();
        $q->update(['question'=>'Updated text']);
        $this->get(route('learn.account'))->assertDontSee('Resume your practice');
        $this->patchJson(route('learn.practice.draft',$attempt),['answers'=>[$q->id=>'A']])->assertStatus(409);
        $attempt->update(['expires_at'=>now()->subMinute()]);
        $this->patchJson(route('learn.practice.draft',$attempt),['answers'=>[$q->id=>'A']])->assertStatus(410);
    }
    public function test_bookmarks_are_idempotent_owned_and_respect_resource_visibility(): void
    {
        $user=User::factory()->create(['is_active'=>true]);$this->actingAs($user);
        $notice=Notice::create(['title'=>'Public notice','description'=>'Private after archive','nepaliTitle'=>'सूचना','nepaliDescription'=>'विवरण','link'=>'https://example.test','status'=>'Published']);
        $url=route('learn.saved.store',['notice',$notice]);
        $this->post($url)->assertRedirect();$this->post($url)->assertRedirect();
        $this->assertSame(1,LearnerBookmark::count());$bookmark=LearnerBookmark::firstOrFail();
        $this->get(route('learn.saved'))->assertSee('Public notice');
        $this->actingAs(User::factory()->create(['is_active'=>true]))->delete(route('learn.saved.destroy',$bookmark))->assertNotFound();
        $this->get(route('learn.saved'))->assertDontSee('Public notice');
        $notice->update(['status'=>'Archived']);$this->actingAs($user);
        $this->post($url)->assertNotFound();
        $this->get(route('learn.saved'))->assertDontSee('Public notice')->assertSee('This saved resource is no longer available.');
        $this->delete(route('learn.saved.destroy',$bookmark))->assertRedirect();
        $this->assertSame(0,LearnerBookmark::count());
    }
    public function test_recent_mistakes_recommend_a_topic_without_granting_premium(): void
    {
        $user=User::factory()->create(['is_active'=>true]);$q=$this->question();$this->actingAs($user);
        $this->post(route('learn.practice.start'),['count'=>5]);$attempt=LearnerPracticeAttempt::firstOrFail();
        $this->post(route('learn.practice.attempt',$attempt),['answers'=>[$q->id=>'B']]);
        $this->get(route('learn.account'))->assertSee('Practise your difficult topic')->assertSee('category=General');
        $this->post(route('learn.practice.start'),['count'=>5,'mode'=>'personalized'])->assertForbidden();
    }

    public function test_personalized_practice_respects_selected_language(): void
    {
        $this->actingAs(User::factory()->create(['is_active'=>true,'role'=>'PremiumUser']));
        $english=$this->question();$nepali=$this->question('कुन काम सुरक्षित हुन्छ?');
        foreach(['en'=>$english,'ne'=>$nepali] as $language=>$question){
            $this->post(route('learn.practice.start'),['count'=>5,'language'=>$language])->assertRedirect();
            $attempt=LearnerPracticeAttempt::latest('id')->firstOrFail();
            $this->assertSame([$question->id],array_column($attempt->questions,'id'));
            $this->post(route('learn.practice.attempt',$attempt),['answers'=>[$question->id=>'B']])->assertRedirect();
        }
        $this->post(route('learn.practice.start'),['count'=>5,'mode'=>'personalized','language'=>'ne'])->assertRedirect();
        $this->assertSame([$nepali->id],array_column(LearnerPracticeAttempt::latest('id')->firstOrFail()->questions,'id'));
    }
}
