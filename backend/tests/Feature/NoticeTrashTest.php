<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticeTrashTest extends TestCase
{
    use RefreshDatabase;

    private function notice(): Notice
    {
        return Notice::create(['title' => 'Recoverable notice', 'description' => 'Details', 'nepaliTitle' => 'सूचना', 'nepaliDescription' => 'विवरण', 'status' => 'Published']);
    }

    public function test_delete_moves_notice_to_trash_and_hides_it_from_api(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $notice = $this->notice();
        $this->actingAs($admin)->delete(route('deleteNotice', $notice->id))->assertRedirect();
        $this->assertSoftDeleted($notice);
        $this->getJson('/api/notice')->assertJsonMissing(['title' => 'Recoverable notice']);
        $this->actingAs($admin)->get(route('noticeTrash'))->assertOk()->assertSee('Recoverable notice');
    }

    public function test_admin_can_restore_notice(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $notice = $this->notice();
        $notice->delete();
        $this->actingAs($admin)->patch(route('restoreNotice', $notice->id))->assertRedirect(route('noticeTrash'));
        $this->assertDatabaseHas('notices', ['id' => $notice->id, 'deleted_at' => null]);
    }

    public function test_admin_can_permanently_delete_trashed_notice(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $notice = $this->notice();
        $notice->delete();
        $this->actingAs($admin)->delete(route('forceDeleteNotice', $notice->id))->assertRedirect(route('noticeTrash'));
        $this->assertDatabaseMissing('notices', ['id' => $notice->id]);
    }
}
