<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAppSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_valid_application_settings(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $payload = ['exam_duration_minutes' => 45, 'exam_passing_score' => 70, 'exam_question_count' => 25, 'otp_expiry_minutes' => 8, 'image_upload_limit_mb' => 3, 'document_upload_limit_mb' => 15, 'maintenance_notice' => 'Brief maintenance tonight.'];
        $this->actingAs($admin)->patch(route('appSettings.update'), $payload)->assertRedirect();
        $this->assertSame(70, AppSetting::read('exam_passing_score'));
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'subject_type' => 'AppSetting', 'subject_label' => 'exam_passing_score']);
    }

    public function test_invalid_exam_settings_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin)->patch(route('appSettings.update'), ['exam_duration_minutes' => 0, 'exam_passing_score' => 150])->assertSessionHasErrors(['exam_duration_minutes', 'exam_passing_score']);
    }

    public function test_configuration_api_exposes_only_learner_safe_settings(): void
    {
        $this->getJson('/api/configuration')->assertOk()->assertJsonPath('data.exam_passing_score', 60)->assertJsonMissingPath('data.otp_expiry_minutes')->assertJsonMissingPath('data.image_upload_limit_mb');
    }
}
