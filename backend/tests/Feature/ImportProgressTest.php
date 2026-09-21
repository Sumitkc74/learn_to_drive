<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\LearningContentImport;
use App\Models\PdfTranslation;
use App\Models\PdfExtractionRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ImportProgressTest extends TestCase {
    use RefreshDatabase;
    public function test_progress_combines_jobs_and_filters_them(): void {
        $admin = User::factory()->create(['role'=>'Admin']);
        $source = LearningContentImport::create(['source_key'=>'kalanki','source_name'=>'Source','source_url'=>'https://example.org','asset_url'=>'https://example.org/a.pdf','url_hash'=>str_repeat('a',64),'kind'=>'question-bank-document','title'=>'Sample bank','fetched_at'=>now()]);
        PdfTranslation::create(['learning_content_import_id'=>$source->id,'source_hash'=>str_repeat('b',64),'source_language'=>'en','target_language'=>'ne','created_by'=>$admin->id,'status'=>'Failed','error'=>'Check API quota.','next_page'=>2,'total_pages'=>4]);
        PdfExtractionRun::create(['learning_content_import_id'=>$source->id,'source_hash'=>str_repeat('b',64),'mode'=>'text','from_page'=>3,'to_page'=>5,'next_page'=>6,'requested_by'=>$admin->id,'status'=>'Completed']);
        $this->actingAs($admin)->get(route('importProgress'))->assertOk()->assertSee('Sample bank')->assertSee('Check API quota.')->assertSee('Retry Unfinished Page')->assertViewHas('runs',fn($runs)=>$runs->total()===2);
        $this->get(route('importProgress',['type'=>'extraction']))->assertOk()->assertDontSee('Check API quota.')->assertViewHas('runs',fn($runs)=>$runs->total()===1);
        $this->get(route('importProgress',['status'=>'Failed']))->assertOk()->assertViewHas('runs',fn($runs)=>$runs->total()===1);
        $this->actingAs(User::factory()->create(['role'=>'User']))->get(route('importProgress'))->assertForbidden();
    }
}
