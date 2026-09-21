<?php
namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_ownership_admin_reply_and_reopening(): void
    {
        $user = User::factory()->create(['role' => 'User']);
        $other = User::factory()->create(['role' => 'User']);
        $admin = User::factory()->create(['role' => 'Admin']);
        $data = ['type' => 'Complaint', 'subject' => 'Unable to load content', 'message' => '<script>alert(1)</script>', 'user_id' => $other->id, 'status' => 'Resolved'];
        $this->getJson('/api/support-tickets')->assertUnauthorized();
        $this->actingAs($user)->postJson('/api/support-tickets', $data)->assertCreated()->assertJsonPath('data.status', 'New');
        $ticket = SupportTicket::firstOrFail();
        $this->assertEquals($user->id, $ticket->user_id);
        $this->get(route('support.index'))->assertForbidden();
        $this->actingAs($other)->getJson('/api/support-tickets/'.$ticket->id)->assertNotFound();
        $this->postJson('/api/support-tickets/'.$ticket->id.'/replies', ['message' => 'intrusion'])->assertNotFound();
        $this->getJson('/api/support-tickets')->assertJsonCount(0, 'data');
        $this->actingAs($admin)->get(route('support.index'))->assertOk()->assertSee($ticket->subject);
        $this->get(route('support.show', $ticket))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        $this->post(route('support.update', $ticket), ['status' => 'Resolved', 'message' => 'Please try again.'])->assertRedirect();
        $this->actingAs($user)->getJson('/api/support-tickets/'.$ticket->id)->assertOk()
            ->assertJsonPath('data.replies.0.message', 'Please try again.')
            ->assertJsonPath('data.replies.0.author_role', 'Admin');
        $this->postJson('/api/support-tickets/'.$ticket->id.'/replies', ['message' => 'Still happening'])->assertOk();
        $this->assertSame('New', $ticket->fresh()->status);
        $this->assertSame(2, $ticket->replies()->count());
    }

    public function test_submission_validates_content_and_is_rate_limited(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'User']));
        $this->postJson('/api/support-tickets', ['type' => 'invalid'])->assertUnprocessable();
        $data = ['type' => 'Content report', 'subject' => 'Wrong image', 'message' => 'Please check'];
        $this->postJson('/api/support-tickets', $data + ['content_type' => 'vision-test', 'content_id' => 999999])->assertUnprocessable();
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/support-tickets', $data)->assertCreated();
        }
        $this->postJson('/api/support-tickets', $data)->assertStatus(429);
    }
}
