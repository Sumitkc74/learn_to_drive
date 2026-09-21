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
}
