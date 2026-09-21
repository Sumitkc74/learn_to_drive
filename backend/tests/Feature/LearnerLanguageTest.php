<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\TrafficSign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_switch_language_and_return_to_the_current_page(): void
    {
        $this->get('/')->assertOk()->assertSee('lang="en"', false)->assertSee('नेपाली');
        $this->post(route('learn.language'), ['locale'=>'ne','return_to'=>'/learn/practice'])
            ->assertRedirect('/learn/practice')->assertSessionHas('learner_locale','ne')->assertCookie('learner_locale','ne');
        $this->get(route('learn.practice'))->assertOk()->assertSee('lang="ne"', false)
            ->assertSee('अभ्यास')->assertSee('अभ्यास सत्र तयार गर्नुहोस्');
        $this->post(route('learn.language'), ['locale'=>'en','return_to'=>'/learn/practice']);
        $this->get(route('learn.practice'))->assertSee('Set up your session')->assertSee('lang="en"',false);
    }

    public function test_cookie_remembers_language_and_admin_stays_english(): void
    {
        $this->withCookie('learner_locale','ne')->get(route('learn.login'))->assertOk()->assertSee('पासवर्ड');
        $this->actingAs(User::factory()->create(['role'=>'Admin']))->withSession(['learner_locale'=>'ne']);
        $this->get(route('adminDashboard'))->assertOk();
        $this->assertSame('en',app()->getLocale());
    }

    public function test_language_validation_and_redirects_are_restricted(): void
    {
        $this->post(route('learn.language'), ['locale'=>'fr'])->assertSessionHasErrors('locale');
        foreach (['https://example.org','//example.org','/admin','/\\example.org'] as $target) {
            $this->post(route('learn.language'),['locale'=>'ne','return_to'=>$target])->assertRedirect('/');
        }
    }

    public function test_nepali_forms_keep_original_values_and_localize_validation(): void
    {
        $this->withSession(['learner_locale'=>'ne']);
        $this->get(route('learn.library','question-bank'))->assertOk()
            ->assertSee('value="English"',false)->assertSee('अंग्रेजी')->assertSee('सामग्रीको भाषा');
        $this->postJson(route('learn.login'),['email'=>'invalid','password'=>'example'])
            ->assertStatus(422)->assertJsonPath('errors.email.0','इमेल मान्य इमेल ठेगाना हुनुपर्छ।');
        $this->actingAs(User::factory()->create(['is_active'=>true]));
        $this->get(route('learn.support'))->assertSee('value="Complaint"',false)->assertSee('गुनासो');
        $this->get(route('learn.settings'))->assertOk()->assertSee('खाता सेटिङहरू');
    }

    public function test_nepali_resource_title_uses_saved_translation(): void
    {
        $sign=TrafficSign::create(['name'=>'Stop','nepaliSignName'=>'रोक्नुहोस्','description'=>'Original description','image'=>'']);
        $this->withSession(['learner_locale'=>'ne'])->get(route('learn.detail',['traffic-sign',$sign->id]))
            ->assertOk()->assertSee('रोक्नुहोस्')->assertSee('Original description');
        $this->assertSame('Stop',$sign->fresh()->name);
    }

    public function test_language_switch_during_practice_preserves_answers_and_scoring(): void
    {
        $this->actingAs(User::factory()->create(['is_active'=>true]));
        $question=\App\Models\Question::create(['question'=>'Which action is safe?', 'option1'=>'Stop', 'option2'=>'Rush', 'option3'=>'Ignore', 'option4'=>'Speed', 'correctOption'=>'A', 'explanation'=>'Wait until safe.', 'status'=>'Published', 'category'=>'General', 'difficulty'=>'Easy']);
        $this->post(route('learn.practice.start'),['count'=>5])->assertRedirect();
        $attempt=\App\Models\LearnerPracticeAttempt::firstOrFail();
        $snapshot=$attempt->questions;
        $this->post(route('learn.language'),['locale'=>'ne','return_to'=>'/learn/practice/'.$attempt->id]);
        $this->get(route('learn.practice.attempt',$attempt))->assertOk()->assertSee('मेरा उत्तर जाँच्नुहोस्')->assertSee('Which action is safe?')->assertDontSee('Wait until safe.');
        $this->post(route('learn.practice.attempt',$attempt),['answers'=>[$question->id=>'A']])->assertRedirect();
        $this->assertSame($snapshot,$attempt->fresh()->questions);
        $this->assertSame(1,$attempt->fresh()->score);
        $this->get(route('learn.practice.attempt',$attempt))->assertOk()->assertSee('सही उत्तर')->assertSee('Wait until safe.');
    }

    public function test_nepali_premium_pages_and_chat_use_selected_language(): void
    {
        $this->withSession(['learner_locale'=>'ne']);
        foreach (['/','/learn/library','/learn/register','/learn/forgot-password','/learn/premium','/learn/sign-flashcards'] as $page) {
            $this->get($page)->assertOk()->assertSee('lang="ne"',false)->assertDontSee('My learning');
        }
        $this->actingAs(User::factory()->create(['is_active'=>true,'role'=>'PremiumUser']));
        foreach (['/learn/account','/learn/premium','/learn/premium/revision','/learn/premium/chat','/learn/premium/payments'] as $page) {
            $this->get($page)->assertOk()->assertSee('मेरो सिकाइ');
        }
        $this->get('/learn/account')->assertSee('data-thinking="सोच्दै छ…"',false);
        config(['pdf-translation.gemini_key'=>'test-key','premium.chat_model'=>'gemini-test']);
        \Illuminate\Support\Facades\Http::fake(['*'=>\Illuminate\Support\Facades\Http::response(['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>'सुरक्षित अभ्यास गर्नुहोस्।']]]]]])]);
        $this->postJson(route('learn.premium.message'),['message'=>'Help me study road signs'])->assertOk()->assertJsonPath('answer','सुरक्षित अभ्यास गर्नुहोस्।');
        \Illuminate\Support\Facades\Http::assertSent(fn($request)=>str_contains($request['systemInstruction']['parts'][0]['text'],'The learner selected Nepali'));
    }
}
