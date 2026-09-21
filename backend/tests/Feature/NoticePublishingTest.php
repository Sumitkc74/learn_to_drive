<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticePublishingTest extends TestCase
{
    use RefreshDatabase;

    private function notice(array $overrides = []): Notice
    {
        return Notice::create(array_merge(['title' => 'Road closure', 'description' => 'A road is closed.', 'nepaliTitle' => 'सडक बन्द', 'nepaliDescription' => 'सडक बन्द छ।', 'status' => 'Published'], $overrides));
    }

    public function test_api_only_returns_currently_active_notices(): void
    {
        $this->notice(['title' => 'Active notice']);
        $this->notice(['title' => 'Draft notice', 'status' => 'Draft']);
        $this->notice(['title' => 'Future notice', 'publish_at' => now()->addDay()]);
        $this->notice(['title' => 'Expired notice', 'expires_at' => now()->subMinute()]);

        $this->getJson('/api/notice')->assertOk()->assertJsonFragment(['title' => 'Active notice'])->assertJsonMissing(['title' => 'Draft notice'])->assertJsonMissing(['title' => 'Future notice'])->assertJsonMissing(['title' => 'Expired notice']);
    }

    public function test_admin_can_schedule_a_notice(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $publishAt = now()->addDay()->format('Y-m-d H:i:s');
        $payload = ['title' => 'Scheduled', 'description' => 'Later', 'nepaliTitle' => 'पछि', 'nepaliDescription' => 'पछि देखाउनुहोस्', 'status' => 'Published', 'publish_at' => $publishAt, 'expires_at' => now()->addDays(2)->format('Y-m-d H:i:s')];

        $this->actingAs($admin)->post(route('insertNotice'), $payload)->assertRedirect(route('allNotice'));
        $this->assertDatabaseHas('notices', ['title' => 'Scheduled', 'status' => 'Published']);
        $this->getJson('/api/notice')->assertJsonMissing(['title' => 'Scheduled']);
    }

    public function test_admin_notice_table_can_filter_by_status(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->notice(['title' => 'Visible draft', 'status' => 'Draft']);
        $this->notice(['title' => 'Hidden published']);
        $this->actingAs($admin)->get(route('allNotice', ['status' => 'Draft']))->assertOk()->assertSee('Visible draft')->assertDontSee('Hidden published');
    }
}
