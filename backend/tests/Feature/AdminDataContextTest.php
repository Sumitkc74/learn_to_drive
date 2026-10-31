<?php

namespace Tests\Feature;

use App\Models\{User, Notice, LearningContentImport};
use App\Services\{AdminDataContext, LearnerChat};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminDataContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_ranking_counts_students_not_staff_and_reports_ties(): void
    {
        $schools=[];
        foreach (['First'=>'Approved','Second'=>'Approved','Pending'=>'Pending','Empty'=>'Approved'] as $name=>$status) {
            $schools[$name]=\App\Models\DrivingSchool::create(compact('name','status'));
        }
        $students=User::factory()->count(3)->create(['is_active'=>true]);
        $students[0]->update(['is_active'=>false]);
        foreach (['First'=>2,'Second'=>2,'Pending'=>3] as $name=>$count) {
            foreach ($students->take($count) as $student) $schools[$name]->memberships()->create(['user_id'=>$student->id,'role'=>'Student']);
        }
        $schools['First']->memberships()->create(['user_id'=>$students[2]->id,'role'=>'Instructor']);
        $context=app(AdminDataContext::class)->resolve(Request::create('/','POST',['message'=>'which driving school has most students']));
        $rank=$context['context']['school_student_ranking'];
        $this->assertSame(2,$rank['maximum_student_count']);
        $this->assertSame(2,$rank['schools_tied_at_maximum']);
        $this->assertSame(['First','Second','Empty'],array_column($rank['top_schools'],'name'));
        $this->assertSame([2,2,0],array_column($rank['top_schools'],'student_count'));
        $this->assertSame(1,$rank['top_schools'][0]['active_student_count']);
        $admin=User::factory()->create(['role'=>'Admin','is_active'=>true]);
        $this->mock(LearnerChat::class)->shouldReceive('answer')->once()->withArgs(fn($m,$h,$q,$p)=>str_contains($p,'"school_student_ranking"') && str_contains($p,'"schools_tied_at_maximum":2'))->andReturn('First and Second tie with 2 students each.');
        $this->actingAs($admin)->postJson(route('admin.ai-chat.send'),['message'=>'which driving school has most students'])->assertOk()->assertJsonPath('history.1.text','First and Second tie with 2 students each.');
    }

    public function test_school_ranking_handles_empty_database_and_zero_enrollments(): void
    {
        $request=Request::create('/','POST',['message'=>'which school has most students']);
        $rank=app(AdminDataContext::class)->resolve($request)['context']['school_student_ranking'];
        $this->assertNull($rank['maximum_student_count']);
        $this->assertSame([],$rank['top_schools']);
        \App\Models\DrivingSchool::create(['name'=>'Empty school','status'=>'Approved']);
        $rank=app(AdminDataContext::class)->resolve($request)['context']['school_student_ranking'];
        $this->assertSame(0,$rank['maximum_student_count']);
        $this->assertSame(0,$rank['top_schools'][0]['student_count']);
    }

    public function test_counts_use_nepal_day_and_actual_access_rules_without_personal_data(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-10 00:15:00', 'Asia/Kathmandu'));
        $baseline = User::count();
        $dayStart = now('Asia/Kathmandu')->startOfDay()->utc();
        $baselineToday = User::where('created_at','>=',$dayStart)->where('created_at','<',$dayStart->copy()->addDay())->count();
        $admin = User::factory()->create(['role'=>'Admin','is_active'=>true]);
        User::factory()->create(['role'=>'PremiumUser','is_active'=>true,'premium_until'=>now()->subDay()]);
        User::factory()->create(['role'=>'User','is_active'=>true,'premium_until'=>now()->addDay()]);
        User::factory()->create(['role'=>'PremiumUser','is_active'=>false]);
        User::factory()->create(['role'=>'User','is_active'=>true,'premium_until'=>now()->subDay(),'created_at'=>now()->subHour()]);
        session(['admin_ai_ticket'=>999]);
        $snapshot = app(AdminDataContext::class)->resolve(Request::create('/', 'POST', ['message'=>'How many users added today and how many premium users?']));
        $this->assertSame($baseline+5,$snapshot['context']['users']['total']);
        $this->assertSame($baselineToday+4,$snapshot['context']['users']['added_today']);
        $this->assertSame(1,$snapshot['context']['users']['added_yesterday']);
        $this->assertSame(2,$snapshot['context']['users']['premium_learners_with_access']);
        $this->assertFalse(session()->has('admin_ai_ticket'));
        $this->assertStringNotContainsString($admin->email,json_encode($snapshot));
        $this->mock(LearnerChat::class)->shouldReceive('answer')->once()->withArgs(fn($m,$h,$q,$p)=>str_contains($p,'"added_today":'.($baselineToday+4)) && str_contains($p,'"premium_learners_with_access":2'))->andReturn('2 learners have Premium access.');
        $this->actingAs($admin)->postJson(route('admin.ai-chat.send'),['message'=>'How many premium users?'])->assertOk()->assertJsonPath('history.1.sources.0.label','Users');
    }

    public function test_notices_and_link_checks_report_evidence_without_fetching_urls(): void
    {
        Http::preventStrayRequests();
        $notice=Notice::create(['title'=>'Past notice','description'=>'Details','nepaliTitle'=>'Notice','nepaliDescription'=>'Details','link'=>'https://example.test/notice','status'=>'Published','expires_at'=>now()->subDay(),'source_url'=>'https://example.test/notice']);
        $resource=new LearningContentImport(['source_key'=>'test','source_name'=>'Example','source_url'=>'https://example.test/notice','asset_url'=>'https://example.test/file.pdf','url_hash'=>hash('sha256','test'),'kind'=>'exam-paper','title'=>'Test file']);
        $resource->forceFill(['fetched_at'=>now(),'source_check_status'=>'Check failed','source_checked_at'=>now()->subDays(3)])->save();
        $context=app(AdminDataContext::class)->resolve(Request::create('/','POST',['message'=>'Is notice #'.$notice->id.' correct and is https://example.test/file.pdf working?']))['context'];
        $this->assertSame(0,$context['notices']['visible_to_learners']);
        $this->assertSame(1,$context['notices']['expired_but_published']);
        $this->assertSame($notice->id,$context['selected_notice']['id']);
        $this->assertFalse($context['requested_link']['live_check_performed']);
        $this->assertSame('Check failed',$context['requested_link']['matching_learning_resources'][0]['source_check_status']);
        $this->assertStringContainsString('do not prove factual correctness',$context['verification_limits']);
        Http::assertNothingSent();
    }
}
