<?php
namespace Tests\Feature;
use App\Models\{Notice,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class NoticeArchiveTest extends TestCase {
    use RefreshDatabase;
    public function test_daily_archiving_preserves_drafts_future_notices_and_explicit_future_expiry(): void {
        config(['notices.archive_after_days'=>90]);
        $base=['title'=>'Notice','nepaliTitle'=>'Notice','description'=>'Details','nepaliDescription'=>'Details','status'=>'Published'];
        $old=Notice::create($base+['publish_at'=>now()->subDays(91)]);
        $expired=Notice::create($base+['expires_at'=>now()->subHour()]);
        $future=Notice::create($base+['publish_at'=>now()->addDay()]);
        $valid=Notice::create($base+['publish_at'=>now()->subDays(100),'expires_at'=>now()->addDay()]);
        $draft=Notice::create(array_replace($base,['status'=>'Draft','created_at'=>now()->subYear()]));
        $this->artisan('notices:archive-old --dry-run')->expectsOutput('Eligible: 2 notices.')->assertSuccessful();
        $this->assertSame('Published',$old->fresh()->status);
        $this->artisan('notices:archive-old')->assertSuccessful();
        foreach ([$old,$expired] as $notice) $this->assertSame('Archived',$notice->fresh()->status);
        foreach ([$future,$valid] as $notice) $this->assertSame('Published',$notice->fresh()->status);
        $this->assertSame('Draft',$draft->fresh()->status);
        $this->assertFalse(Notice::visibleToLearners()->whereKey($old->id)->exists());
        $this->actingAs(User::factory()->create(['role'=>'Admin']));
        $this->get(route('allNotice'))->assertOk()->assertViewHas('notices',fn($items)=>!$items->contains('id',$old->id));
        $this->get(route('allNotice',['status'=>'Archived']))->assertOk()->assertViewHas('notices',fn($items)=>$items->contains('id',$old->id));
        $this->patch(route('noticeUnarchive',$expired))->assertRedirect(route('editNotice',$expired));
        $this->assertSame('Draft',$expired->fresh()->status);
        $this->assertNull($expired->fresh()->expires_at);
        $this->patch(route('noticeArchive',$valid))->assertRedirect();
        $this->assertSame('Archived',$valid->fresh()->status);
        $this->actingAs(User::factory()->create(['role'=>'User']))->patch(route('noticeArchive',$future))->assertForbidden();
    }
}
