<?php
namespace Tests\Feature;

use App\Models\{ExamPaper,LearnerPracticeAttempt,Question,TrafficSign,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearnerReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    private function question(string $text): Question
    {
        return Question::create(['question'=>$text,'option1'=>'Stop','option2'=>'Rush','option3'=>'Ignore','option4'=>'Speed','correctOption'=>'A','explanation'=>'Wait until safe.','status'=>'Published','category'=>'General','difficulty'=>'Easy']);
    }

    private function sign(string $name): TrafficSign
    {
        $sign=TrafficSign::create(['name'=>$name,'nepaliSignName'=>$name,'description'=>'Test sign','image'=>'']);
        $sign->addMedia(UploadedFile::fake()->image('sign.png'))->toMediaCollection('default','public');
        return $sign;
    }

    public function test_home_hides_question_bank_stat_and_step_when_no_banks(): void
    {
        $this->assertSame(0,ExamPaper::count());
        $this->get('/')->assertOk()
            ->assertSee('<title>Your next turn starts here · Learn to Drive</title>',false)
            ->assertSee('Your next three steps')
            ->assertDontSee('Question banks')
            ->assertDontSee('Open question banks')
            ->assertDontSee('/learn/library/question-bank')
            ->assertSee('Explore the learning library.');
    }

    public function test_home_shows_question_bank_stat_and_step_when_banks_exist(): void
    {
        ExamPaper::create(['name'=>'Bank','description'=>'Practice bank','language'=>'English']);
        $this->get('/')->assertOk()
            ->assertSee('Question banks')
            ->assertSee('Open question banks')
            ->assertSee('/learn/library/question-bank',false);
    }

    public function test_guest_gets_five_sample_questions_without_login_and_no_attempt_is_created(): void
    {
        foreach(range(1,6) as $i)$this->question("Sample question {$i}?");
        $this->get(route('learn.practice'))->assertOk()
            ->assertSee('Try a few sample questions')
            ->assertSee('Sample question 1?')
            ->assertSee('Sample question 5?')
            ->assertDontSee('Sample question 6?')
            ->assertSee('Check the answer')
            ->assertSee('Correct answer')
            ->assertSee('No sign-in needed and nothing is recorded.');
        $this->assertSame(0,LearnerPracticeAttempt::count());
        $this->assertGuest();
    }

    public function test_guest_practice_page_has_no_sample_section_without_questions(): void
    {
        $this->get(route('learn.practice'))->assertOk()->assertDontSee('Try a few sample questions');
    }

    public function test_footer_sign_in_link_matches_header_destination(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('href="'.route('learn.login').'"',false)
            ->assertDontSee('href="'.route('login').'"',false);
    }

    public function test_practice_uses_british_verb_spelling(): void
    {
        $this->actingAs(User::factory()->create(['is_active'=>true]));
        $this->question('Which action is safe?');
        $this->get(route('learn.practice'))->assertOk()
            ->assertSee('Start practising →')
            ->assertDontSee('Start practice →');
    }

    public function test_flashcards_next_sign_link_is_relative_not_http(): void
    {
        Storage::fake('public');
        $this->sign('Stop sign');$this->sign('Give way');
        $this->get('/learn/sign-flashcards')->assertOk()
            ->assertSee('Next sign')
            ->assertSee('href="/learn/sign-flashcards?page=2"',false)
            ->assertDontSee('http://localhost/learn/sign-flashcards?page=2');
    }

    public function test_viewport_meta_stays_inside_head(): void
    {
        $content=$this->get('/')->assertOk()->getContent();
        $this->assertSame(1,substr_count($content,'<meta name="viewport"'));
        $this->assertTrue(strpos($content,'<meta name="viewport"')<strpos($content,'</head>'));
        $this->assertStringNotContainsString('---',explode('<body',$content)[0]);
    }
}
