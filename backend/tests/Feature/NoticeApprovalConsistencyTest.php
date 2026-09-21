<?php
namespace Tests\Feature;
use App\Models\{GovernmentNoticeImport,Notice,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class NoticeApprovalConsistencyTest extends TestCase {
    use RefreshDatabase;
    public function test_repeated_approval_creates_only_one_draft(): void {
        $this->actingAs(User::factory()->create(['role'=>'Admin']));
        $record=GovernmentNoticeImport::create(['source_name'=>'Official','source_domain'=>'example.org','source_url'=>'https://example.org/notice','title'=>'Notice','content_hash'=>hash('sha256','notice'),'status'=>'Pending','fetched_at'=>now()]);
        $this->post(route('governmentNotices.approve',$record))->assertRedirect();
        $this->post(route('governmentNotices.approve',$record))->assertNotFound();
        $this->assertSame(1,Notice::where('government_notice_import_id',$record->id)->count());
        $this->assertSame('Draft',Notice::where('government_notice_import_id',$record->id)->firstOrFail()->status);
    }
}
