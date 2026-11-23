<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
class SourceLastCheckedTest extends TestCase {
    use RefreshDatabase;
    public function test_government_notices_page_shows_latest_check_not_oldest(): void {
        DB::table('content_source_runs')->insert(['source'=>'government-notices','status'=>'Success','added'=>0,'known'=>37,'started_at'=>now()->subDay(),'finished_at'=>now()->subDay()]);
        DB::table('content_source_runs')->insert(['source'=>'government-notices','status'=>'Failed','added'=>0,'known'=>0,'error'=>'Connection refused','started_at'=>now(),'finished_at'=>now()]);
        $this->actingAs(User::factory()->create(['role'=>'Admin']))->get(route('governmentNotices'))->assertOk()
            ->assertSee('Government notices — last checked:')
            ->assertSee('Failed')
            ->assertDontSee(', 0 new');
    }
    public function test_learning_content_page_shows_last_check_per_source(): void {
        DB::table('content_source_runs')->insert(['source'=>'dotm-b','status'=>'Success','added'=>2,'known'=>1,'started_at'=>now(),'finished_at'=>now()]);
        DB::table('content_source_runs')->insert(['source'=>'nei-vision','status'=>'Failed','added'=>0,'known'=>0,'error'=>'Connection refused','started_at'=>now(),'finished_at'=>now()]);
        $this->actingAs(User::factory()->create(['role'=>'Admin']))->get(route('learningContent'))->assertOk()
            ->assertSee('DoTM: Category B question bank 2082-83 — last checked:')
            ->assertSee(', 2 new')
            ->assertSee('National Eye Institute: color vision plate — last checked:')
            ->assertSee('Failed');
    }
    public function test_pages_show_not_checked_yet_without_runs(): void {
        $admin=User::factory()->create(['role'=>'Admin']);
        $this->actingAs($admin)->get(route('governmentNotices'))->assertOk()->assertSee('not checked yet');
        $this->actingAs($admin)->get(route('learningContent'))->assertOk()->assertSee('not checked yet');
    }
}
