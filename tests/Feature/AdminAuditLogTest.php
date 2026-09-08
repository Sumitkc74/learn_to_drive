<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_question_changes_are_recorded_without_sensitive_values(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);
        $question = Question::create([
            'question' => 'Audit this question', 'option1' => 'A', 'option2' => 'B',
            'option3' => 'C', 'option4' => 'D', 'correctOption' => 'A',
            'category' => 'General', 'difficulty' => 'Medium', 'status' => 'Draft',
        ]);
        $question->update(['status' => 'Published']);

        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'event' => 'created', 'subject_id' => $question->id]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'event' => 'updated', 'subject_id' => $question->id]);
    }

    public function test_passwords_are_never_stored_in_the_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);
        User::factory()->create(['password' => 'secret-hash']);

        $serialized = AuditLog::latest('id')->firstOrFail()->toJson();
        $this->assertStringNotContainsString('secret-hash', $serialized);
        $this->assertStringNotContainsString('password', $serialized);
    }

    public function test_admin_can_view_and_filter_the_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        AuditLog::create(['actor_id' => $admin->id, 'event' => 'updated', 'subject_type' => 'Question', 'subject_id' => 10, 'subject_label' => 'Test question']);

        $this->actingAs($admin)->get(route('auditLogs', ['event' => 'updated', 'subject_type' => 'Question']))
            ->assertOk()->assertSee('Test question')->assertSee('Administrative Activity');
    }
}
