<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User,Question,Notice,ExamPaper,SupportTicket,LearnerPracticeAttempt,UserHistory};
class LearnerWebTest extends TestCase {
    use RefreshDatabase;
    private function question($status='Published') {
        return Question::create(['question'=>'Which action is safe?','option1'=>'Stop','option2'=>'Rush','option3'=>'Ignore','option4'=>'Speed','correctOption'=>'A','explanation'=>'Unique explanation hidden until completion','status'=>$status,'category'=>'General','difficulty'=>'Easy']);
    }
    public function test_public_library_pages_and_notice_visibility() {
        foreach(['traffic-sign','question-bank','exam-information','tutorial','vision-test','notice'] as $type)$this->get('/learn/library/'.$type)->assertOk();
        foreach(['Draft','Archived','Published'] as $status){$notice=Notice::create(['title'=>$status.' notice','description'=>'Details','nepaliTitle'=>'Notice','nepaliDescription'=>'Details','link'=>'https://example.test','status'=>$status]);$response=$this->get('/learn/library/notice/'.$notice->id);$status==='Published'?$response->assertOk():$response->assertNotFound();}
        $scheduled=Notice::create(['title'=>'Future','description'=>'Not yet','nepaliTitle'=>'Future','nepaliDescription'=>'Not yet','link'=>'https://example.test','status'=>'Published','publish_at'=>now()->addDay()]);
        $this->get('/learn/library/notice/'.$scheduled->id)->assertNotFound();
        $this->get('/learn/practice')->assertOk();
        $this->get('/learn/login')->assertOk();$this->get('/learn/register')->assertOk();
        $this->get('/learn/account')->assertRedirect(route('learn.login'));
    }
    public function test_registration_cannot_choose_admin_role_and_login_works() {
        $this->post('/learn/register',['name'=>'Learner','email'=>'learner@example.test','phoneNumber'=>'9812345678','password'=>'safe-password','password_confirmation'=>'safe-password','role'=>'Admin'])->assertRedirect(route('learn.account'));
        $user=User::where('email','learner@example.test')->firstOrFail();$this->assertSame('User',$user->role);
        $this->post('/learn/logout')->assertRedirect('/');
        $this->post('/learn/login',['email'=>$user->email,'password'=>'safe-password'])->assertRedirect(route('learn.account'));
        $this->get('/learn/account')->assertOk();
    }
    public function test_practice_grades_on_server_hides_answers_and_is_idempotent() {
        $user=User::factory()->create(['is_active'=>true]);$q=$this->question();$this->question('Draft');
        $this->actingAs($user)->post('/learn/practice/start',['count'=>5])->assertRedirect();
        $attempt=LearnerPracticeAttempt::firstOrFail();$this->assertCount(1,$attempt->questions);
        $this->get('/learn/practice/'.$attempt->id)->assertOk()->assertDontSee('Unique explanation hidden until completion')->assertDontSee('Correct answer');
        $this->post('/learn/practice/'.$attempt->id,['answers'=>[$q->id=>'B'],'score'=>100])->assertRedirect();
        $this->assertSame(0,$attempt->fresh()->score);
        $this->get('/learn/practice/'.$attempt->id)->assertOk()->assertSee('Unique explanation hidden until completion')->assertSee('Correct answer');
        $this->post('/learn/practice/'.$attempt->id,['answers'=>[$q->id=>'A']])->assertRedirect();
        $this->assertSame(0,$attempt->fresh()->score);$this->assertSame(1,UserHistory::count());
        $this->actingAs(User::factory()->create(['is_active'=>true]))->get('/learn/practice/'.$attempt->id)->assertNotFound();
    }
    public function test_changed_and_expired_questions_cannot_be_graded() {
        $this->actingAs(User::factory()->create(['is_active'=>true]));$q=$this->question();
        $this->post('/learn/practice/start',['count'=>5]);$attempt=LearnerPracticeAttempt::firstOrFail();
        $q->update(['status'=>'Draft']);$this->post('/learn/practice/'.$attempt->id,['answers'=>[$q->id=>'A']])->assertStatus(409);
        $q->update(['status'=>'Published']);$attempt->update(['expires_at'=>now()->subMinute()]);
        $this->get('/learn/practice/'.$attempt->id)->assertStatus(410);$this->assertSame(0,UserHistory::count());
    }
    public function test_reports_reach_admin_tickets_and_conversations_are_private() {
        $user=User::factory()->create(['is_active'=>true]);$q=$this->question();$draft=$this->question('Draft');$this->actingAs($user);
        $this->get('/learn/report/question/'.$q->id)->assertOk();
        $this->post('/learn/report/question/'.$draft->id,['message'=>'Private'])->assertNotFound();
        $this->post('/learn/report/question/'.$q->id,['message'=>'Please check wording'])->assertRedirect();
        $ticket=SupportTicket::firstOrFail();$this->assertSame($q->id,$ticket->content_id);$this->assertSame('Content report',$ticket->type);
        $this->get('/learn/support')->assertOk()->assertSee('Report:');
        $this->post('/learn/support/'.$ticket->id,['message'=>'More details'])->assertRedirect();
        $this->get('/learn/support/'.$ticket->id)->assertOk()->assertSee('More details');
        $this->actingAs(User::factory()->create(['is_active'=>true]))->get('/learn/support/'.$ticket->id)->assertNotFound();
        $this->post('/learn/support/'.$ticket->id,['message'=>'Not mine'])->assertNotFound();
    }
}

