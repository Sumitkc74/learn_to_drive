<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admins_cannot_access_the_admin_chatbot(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => 'User']);

        $this->actingAs($user)->get(route('admin.ai-chat'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.ai-chat.send'), ['message' => 'Can I access this?'])->assertForbidden();
    }

    public function test_admin_can_access_the_ai_chatbot_and_post_messages(): void
    {
        config(['pdf-translation.gemini_key' => 'admin-key', 'premium.chat_model' => 'gemini-test']);
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'finishReason' => 'STOP',
                    'content' => ['parts' => [['text' => 'Review the pending support queue first.']]],
                ]],
            ]),
        ]);

        $admin = User::factory()->create(['is_active' => true, 'role' => 'Admin']);

        $this->actingAs($admin)
            ->get(route('admin.ai-chat'))
            ->assertRedirect(route('adminDashboard', ['chat' => 'open']));

        $this->get(route('adminDashboard'))->assertOk()->assertSee('adminChatPanel')->assertSee('adminChatToggle');

        $this->actingAs($admin)
            ->postJson(route('admin.ai-chat.send'), ['message' => 'Explain moderation workflows'])
            ->assertOk()->assertJsonPath('history.1.text', 'Review the pending support queue first.');

        Http::assertSent(fn ($request) =>
            str_contains($request['systemInstruction']['parts'][0]['text'], 'admin')
            && str_contains($request['systemInstruction']['parts'][0]['text'], 'support')
        );
    }

    public function test_chat_failure_keeps_history_and_returns_inline_error(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'role' => 'Admin']);
        $this->mock(\App\Services\LearnerChat::class)->shouldReceive('answer')->once()->andThrow(new \RuntimeException('private provider error'));
        $this->actingAs($admin)->postJson(route('admin.ai-chat.send'), ['message' => 'Explain moderation workflows'])
            ->assertStatus(503)->assertJsonPath('message', 'The admin AI assistant is not available right now.')
            ->assertDontSee('private provider error')->assertSessionMissing('admin_ai_chat_history');
        $this->postJson(route('admin.ai-chat.send'), ['message' => ''])->assertUnprocessable();
    }

    public function test_today_is_disambiguated_and_selected_ticket_is_refreshed_for_followups(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-10 10:00:00', 'Asia/Kathmandu'));
        $admin = User::factory()->create(['is_active'=>true,'role'=>'Admin']);
        $learner = User::factory()->create();
        $ticket = \App\Models\SupportTicket::create(['user_id'=>$learner->id,'type'=>'Complaint','subject'=>'Lesson problem','message'=>'The instructor was late.','status'=>'New']);
        $other = \App\Models\SupportTicket::create(['user_id'=>$learner->id,'type'=>'Complaint','subject'=>'Another problem','message'=>'UNSELECTED_PRIVATE_TEXT','status'=>'New']);
        $this->actingAs($admin)->postJson(route('admin.ai-chat.send'), ['message'=>"How do I reply to today's complaint?"])
            ->assertOk()->assertJsonCount(2,'history.1.sources');
        $this->mock(\App\Services\LearnerChat::class)->shouldReceive('answer')->twice()->withArgs(function($message,$history,$questions,$prompt) use ($learner) {
            return str_contains($prompt,'The instructor was late.') && str_contains($prompt,'We are checking the schedule.')
                && !str_contains($prompt,'UNSELECTED_PRIVATE_TEXT') && !str_contains($prompt,$learner->email)
                && str_contains($prompt,'never claim it was sent');
        })->andReturn('Suggested reply: We will review your lesson details.');
        $ticket->replies()->create(['user_id'=>$admin->id,'author_role'=>'Admin','message'=>'We are checking the schedule.']);
        $this->postJson(route('admin.ai-chat.send'), ['message'=>'Draft a reply to #'.$ticket->id])->assertOk();
        $this->postJson(route('admin.ai-chat.send'), ['message'=>'Make it shorter'])->assertOk();
        $this->assertDatabaseCount('support_replies',1);
        $this->assertSame('New',$ticket->fresh()->status);
        $this->get(route('support.show',$ticket))->assertOk()->assertSee('Using ticket #'.$ticket->id);
    }

    public function test_one_today_complaint_uses_nepal_date_and_page_context_is_supported(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-10 00:15:00','Asia/Kathmandu'));
        $admin=User::factory()->create(['is_active'=>true,'role'=>'Admin']);
        $ticket=\App\Models\SupportTicket::create(['user_id'=>$admin->id,'type'=>'Complaint','subject'=>'Current complaint','message'=>'Need lesson help','status'=>'New']);
        $this->mock(\App\Services\LearnerChat::class)->shouldReceive('answer')->twice()->withArgs(fn($m,$h,$q,$p)=>str_contains($p,'Need lesson help'))->andReturn('Draft only.');
        $this->actingAs($admin)->postJson(route('admin.ai-chat.send'), ['message'=>"Reply to today's complaint"])->assertOk()->assertJsonPath('history.1.sources.0.id',$ticket->id);
        $this->postJson(route('admin.ai-chat.send'), ['message'=>'How do I answer this?', 'ticket_id'=>$ticket->id])->assertOk();
        $ticket->delete();
        $this->postJson(route('admin.ai-chat.send'), ['message'=>'Make it shorter'])->assertOk()->assertSessionMissing('admin_ai_ticket');
    }
}
