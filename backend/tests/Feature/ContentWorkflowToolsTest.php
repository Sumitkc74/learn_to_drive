<?php
namespace Tests\Feature;
use App\Models\{User,Notice,LearningContentImport,ExamPaper};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Http};
use Tests\TestCase;
class ContentWorkflowToolsTest extends TestCase {
    use RefreshDatabase;
    public function test_versions_restore_as_draft_and_reject_stale_edits(): void {
        $this->actingAs(User::factory()->create(['role'=>'Admin']));
        $record=Notice::create(['title'=>'First','description'=>'First details','nepaliTitle'=>'Title','nepaliDescription'=>'Details','status'=>'Published']);
        $version=DB::table('content_versions')->where('content_type','notices')->where('content_id',$record->id)->first();
        $record->update(['title'=>'Changed']);
        $this->get(route('contentVersions',['notices',$record->id]))->assertOk()->assertSee('First')->assertSee('Changed');
        $url=route('contentVersions.restore',['notices',$record->id,$version->id]);
        $this->post($url,['confirmed'=>1,'current_hash'=>str_repeat('a',64)])->assertStatus(409);
        $this->post($url,['confirmed'=>1,'current_hash'=>\App\Support\VersionedContent::hash($record->fresh())])->assertRedirect();
        $this->assertSame('First',$record->fresh()->title);$this->assertSame('Draft',$record->fresh()->status);
        $this->assertSame(3,DB::table('content_versions')->where('content_type','notices')->where('content_id',$record->id)->count());
        $this->actingAs(User::factory()->create(['role'=>'User']))->post($url,['confirmed'=>1,'current_hash'=>\App\Support\VersionedContent::hash($record->fresh())])->assertForbidden();
    }
    public function test_assignments_prevent_conflicting_bulk_reviews(): void {
        $admin=User::factory()->create(['role'=>'Admin']);$other=User::factory()->create(['role'=>'Admin']);$this->actingAs($admin);
        $item=LearningContentImport::create(['source_key'=>'sample','source_name'=>'Sample','source_url'=>'https://example.org','asset_url'=>'https://example.org/a.pdf','url_hash'=>hash('sha256','assignment'),'title'=>'Sample','kind'=>'question-bank-document','fetched_at'=>now()]);
        $data=['type'=>'learning','ids'=>[$item->id],'confirmed'=>1];
        $this->get(route('reviewOperations'))->assertOk();
        $this->post(route('reviewOperations.update'),$data+['action'=>'claim'])->assertRedirect();
        $this->assertSame($admin->id,$item->fresh()->assigned_to);
        $this->actingAs($other)->post(route('reviewOperations.update'),$data+['action'=>'reject','reason'=>'Duplicate'])->assertStatus(409);
        $this->assertSame('Pending',$item->fresh()->status);
        $this->actingAs($admin)->post(route('reviewOperations.update'),$data+['action'=>'reject','reason'=>'Duplicate'])->assertRedirect();
        $this->assertSame('Rejected',$item->fresh()->status);
    }
    public function test_source_pause_prevents_fetch_and_settings_validate_time(): void {
        $this->actingAs(User::factory()->create(['role'=>'Admin']));Http::fake();
        $this->get(route('scrapingSources'))->assertOk();
        $this->post(route('scrapingSources.update','kalanki'),['enabled'=>0,'daily_time'=>'25:99'])->assertSessionHasErrors('daily_time');
        $this->post(route('scrapingSources.update','kalanki'),['enabled'=>0,'daily_time'=>'03:15'])->assertSessionHasNoErrors();
        $this->assertSame(0,app(\App\Services\LearningContentCollector::class)->fetch('kalanki')['added']);Http::assertNothingSent();
        $this->assertFalse(app(\App\Services\ScrapingSourceSettings::class)->enabled('kalanki'));
    }
    public function test_pdf_reports_link_to_support_and_drafts_cannot_be_reported(): void {
        \Laravel\Sanctum\Sanctum::actingAs(User::factory()->create(['role'=>'User']));
        $bank=ExamPaper::create(['name'=>'Bank','description'=>'Practice','language'=>'English']);
        $this->postJson('/api/content/question-bank/'.$bank->id.'/report',['message'=>'PDF is blurry'])->assertCreated()->assertJsonPath('data.content_id',$bank->id)->assertJsonPath('data.type','Content report');
        $notice=Notice::create(['title'=>'Draft','description'=>'Details','nepaliTitle'=>'Title','nepaliDescription'=>'Details','status'=>'Draft']);
        $this->postJson('/api/content/notice/'.$notice->id.'/report',['message'=>'Hidden'])->assertNotFound();
    }
}
