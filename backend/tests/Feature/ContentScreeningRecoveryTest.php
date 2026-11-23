<?php
namespace Tests\Feature;
use App\Models\GovernmentNoticeImport;
use App\Jobs\ScreenImportedContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
class ContentScreeningRecoveryTest extends TestCase {
    use RefreshDatabase;
    public function test_stalled_checks_retry_with_cooldown_and_stop_at_limit(): void {
        Queue::fake();
        $record=GovernmentNoticeImport::create(['source_name'=>'Official','source_domain'=>'dotm.gov.np','source_url'=>'https://dotm.gov.np/test','title'=>'Test','content_hash'=>hash('sha256','test'),'status'=>'Pending','fetched_at'=>now()]);
        $record->forceFill(['ai_status'=>'Running','ai_activity_at'=>now()->subMinutes(11)])->save();
        $this->artisan('content:recover-screening')->assertSuccessful();
        $this->assertSame('Queued',$record->fresh()->ai_status);
        $this->assertSame(1,$record->fresh()->ai_retry_count);
        $this->artisan('content:recover-screening')->assertSuccessful();
        Queue::assertPushed(ScreenImportedContent::class,1);
        for ($i=0;$i<3;$i++) {
            $this->travel(11)->minutes();
            $this->artisan('content:recover-screening')->assertSuccessful();
        }
        Queue::assertPushed(ScreenImportedContent::class,3);
        $this->assertSame(3,$record->fresh()->ai_retry_count);
        $this->assertSame('Failed',$record->fresh()->ai_status);
        $this->assertSame('Pending',$record->fresh()->status);
        $record->forceFill(['ai_status'=>'Ready','ai_retry_count'=>0])->save();
        $this->travel(11)->minutes();
        $this->artisan('content:recover-screening')->assertSuccessful();
        Queue::assertPushed(ScreenImportedContent::class,3);
    }
    public function test_deferred_items_resume_with_a_fresh_retry_budget(): void {
        Queue::fake();
        $record=GovernmentNoticeImport::create(['source_name'=>'Official','source_domain'=>'dotm.gov.np','source_url'=>'https://dotm.gov.np/test-deferred','title'=>'Deferred','content_hash'=>hash('sha256','deferred'),'status'=>'Pending','fetched_at'=>now()]);
        $record->forceFill(['ai_status'=>'Deferred','ai_activity_at'=>now('Asia/Kathmandu')->subDays(2),'ai_retry_count'=>3])->save();
        $this->artisan('content:recover-screening')->assertSuccessful();
        $fresh=$record->fresh();
        $this->assertSame('Queued',$fresh->ai_status);
        $this->assertSame(0,$fresh->ai_retry_count);
        Queue::assertPushed(ScreenImportedContent::class,1);
    }
    public function test_bulk_recovery_staggers_screening_jobs(): void {
        Queue::fake();
        $records=[];
        for ($i=0;$i<3;$i++) {
            $record=GovernmentNoticeImport::create(['source_name'=>'Official','source_domain'=>'dotm.gov.np','source_url'=>'https://dotm.gov.np/test-stagger-'.$i,'title'=>'Test '.$i,'content_hash'=>hash('sha256','stagger-'.$i),'status'=>'Pending','fetched_at'=>now()]);
            $record->forceFill(['ai_status'=>'Failed','ai_activity_at'=>now()->subMinutes(11)])->save();
            $records[]=$record;
        }
        $this->artisan('content:recover-screening')->assertSuccessful();
        // The observer also dispatches one undelayed job per create; only
        // the recovery dispatches carry a stagger delay.
        $delays=Queue::pushed(ScreenImportedContent::class)->map(fn ($job) => $job->delay)->filter(fn ($delay) => $delay !== null)->sort()->values()->all();
        $this->assertSame([0,15,30],$delays);
        $this->assertEqualsWithDelta(15,now()->diffInSeconds(\Illuminate\Support\Carbon::parse($records[1]->fresh()->ai_activity_at)),2);
    }
}
