<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\GovernmentNoticeImport;
use App\Models\User;
use App\Models\UserHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_user_question_and_learning_analytics(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $learner = User::factory()->create(['role' => 'PremiumUser', 'email_verified_at' => now(), 'phone_verified_at' => now()]);
        $question = Question::create(['question' => 'Frequently missed', 'option1' => 'A', 'option2' => 'B', 'option3' => 'C', 'option4' => 'D', 'correctOption' => 'A', 'category' => 'General', 'difficulty' => 'Medium', 'status' => 'Published']);
        UserHistory::create(['user_id' => $learner->id, 'attempted_questions' => json_encode([$question->id]), 'optionA' => '[]', 'optionB' => '[]', 'optionC' => '[]', 'optionD' => '[]', 'correct_options' => json_encode(['A']), 'selected_options' => json_encode(['B'])]);
        GovernmentNoticeImport::create(['source_name' => 'Department of Transport Management, Nepal', 'source_url' => 'https://dotm.gov.np/notice', 'source_domain' => 'dotm.gov.np', 'title' => 'Driving notice', 'content_hash' => hash('sha256', 'dashboard-notice'), 'status' => 'Pending', 'fetched_at' => now()]);

        $this->actingAs($admin)->get(route('adminDashboard'))->assertOk()->assertSee('Learning Health')->assertSee('Frequently Missed Questions')->assertSee('Frequently missed')->assertSee('Premium users')->assertSee('Exam attempts')->assertSee('Government Notices')->assertSee('1 pending');
    }
}
