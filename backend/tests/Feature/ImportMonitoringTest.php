<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Services\{ContentAiBudget,ContentSourceHistory};
use App\Models\User;
use Tests\TestCase;
class ImportMonitoringTest extends TestCase {
    use RefreshDatabase;
    public function test_history_records_success_and_safe_failure_and_admin_access(): void {
        $history=app(ContentSourceHistory::class);
        $history->run('sample',fn()=>['added'=>2,'known'=>3]);
        try {$history->run('sample',fn()=>throw new \RuntimeException('secret-provider-detail'));} catch (\RuntimeException $e) {}
        $this->assertDatabaseHas('content_source_runs',['status'=>'Success','added'=>2,'known'=>3]);
        $this->assertDatabaseHas('content_source_runs',['status'=>'Failed']);
        $this->actingAs(User::factory()->create(['role'=>'Admin']))->get(route('importHistory'))->assertOk()->assertSee('sample')->assertDontSee('secret-provider-detail');
        $this->actingAs(User::factory()->create(['role'=>'User']))->get(route('importHistory'))->assertForbidden();
    }
    public function test_daily_limit_does_not_allow_extra_requests_and_resets_next_day(): void {
        config(['content-screening.daily_limit'=>2]);
        $budget=app(ContentAiBudget::class);
        $this->assertTrue($budget->reserve()); $this->assertTrue($budget->reserve());
        $this->assertFalse($budget->reserve());
        $this->assertSame(2,DB::table('content_ai_usage')->where('day',$budget->day())->value('requests'));
        $this->travel(1)->days();
        $this->assertTrue($budget->reserve());
    }
    public function test_identical_bytes_are_flagged_without_calling_gemini(): void {
        \Illuminate\Support\Facades\Storage::fake('learning-content');
        \Illuminate\Support\Facades\Http::fake();
        config(['pdf-translation.gemini_key'=>'fake','pdf-translation.gemini_model'=>'gemini-test']);
        $bytes="%PDF-1.4\nSame file\n%%EOF";
        \Illuminate\Support\Facades\Storage::disk('learning-content')->put('duplicate.pdf',$bytes);
        foreach ([1,2] as $id) {
            $record=\App\Models\LearningContentImport::create(['source_key'=>'sample','source_name'=>'Sample','source_url'=>'https://example.org','asset_url'=>'https://example.org/'.$id.'.pdf','url_hash'=>hash('sha256',(string)$id),'title'=>'Sample','kind'=>'question-bank-document','fetched_at'=>now()]);
            $record->forceFill(['file_path'=>'duplicate.pdf','file_hash'=>hash('sha256',$bytes)])->save();
        }
        app(\App\Services\ContentAiScreening::class)->screen($record);
        $this->assertSame('Flagged',$record->fresh()->ai_status);
        $this->assertStringContainsString('Exact duplicate',$record->fresh()->ai_report);
        $this->assertSame('Pending',$record->fresh()->status);
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }
}
