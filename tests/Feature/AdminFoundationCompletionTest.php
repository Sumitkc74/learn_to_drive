<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Question;
use App\Models\User;
use App\Models\UserHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFoundationCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counters_are_not_links_and_activity_is_last(): void
    {
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
        $question = Question::create(['question' => str_repeat('LongSubject', 45), 'option1' => 'A', 'option2' => 'B', 'option3' => 'C', 'option4' => 'D', 'correctOption' => 'A']);
        $response = $this->get(route('adminDashboard'))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(8, $xpath->query('//div[@class="ltd-stat-card"]')->length);
        $this->assertSame(0, $xpath->query('//div[@class="ltd-stat-card"]//a | //a[.//div[@class="ltd-stat-card"]]')->length);
        $this->assertSame(1, $xpath->query('//main/div[last()][contains(@class,"ltd-activity")]')->length);
        $response->assertSee($question->question);
    }

    public function test_analytics_uses_saved_passing_score_and_distinct_months_at_month_end(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 31));
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        AppSetting::updateOrCreate(['key' => 'exam_passing_score'], ['value' => '80']);
        UserHistory::create(['user_id' => $admin->id, 'attempted_questions' => '[1,2,3]', 'optionA' => '[]', 'optionB' => '[]', 'optionC' => '[]', 'optionD' => '[]', 'correct_options' => '["A","A","A"]', 'selected_options' => '["A","A","B"]']);
        $this->actingAs($admin)->get(route('adminAnalytics'))->assertOk()
            ->assertViewHas('performance', fn ($data) => $data['average'] === 67 && $data['pass_rate'] === 0)
            ->assertViewHas('signupLabels', fn ($labels) => $labels->all() === ['Oct 2025', 'Nov 2025', 'Dec 2025', 'Jan 2026', 'Feb 2026', 'Mar 2026']);
    }

    public function test_creator_is_derived_from_admin_and_survives_creator_deletion(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);
        $question = new Question(['question' => 'Creator check', 'option1' => 'A', 'option2' => 'B', 'option3' => 'C', 'option4' => 'D', 'correctOption' => 'A']);
        $question->created_by = 999999;
        $question->save();
        $this->assertSame($admin->id, $question->created_by);
        $this->get(route('previewQuestion', $question))->assertOk()->assertSee('Added by')->assertSee($admin->name);
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
        $admin->delete();
        $this->get(route('previewQuestion', $question))->assertOk()->assertSee('Former admin');
    }

    public function test_settings_ignore_other_cards_and_resend_setting_is_enforced(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $admin->forceFill(['phone_verified_at' => null])->save();
        $this->actingAs($admin)->patch(route('appSettings.update', 'verification'), ['otp_expiry_minutes' => 5, 'otp_resend_seconds' => 120, 'exam_passing_score' => 99])
            ->assertSessionHasNoErrors();
        $this->assertSame(60, AppSetting::read('exam_passing_score'));
        $this->post(route('profile.phone.verification.send'))->assertRedirect();
        $this->assertTrue($admin->fresh()->phone_verification_expires_at->between(now()->addMinutes(4), now()->addMinutes(6)));
        $this->travel(61)->seconds();
        $this->post(route('profile.phone.verification.send'))->assertStatus(429);
        $this->travel(60)->seconds();
        $this->post(route('profile.phone.verification.send'))->assertRedirect();
        $this->patch(route('appSettings.update', 'verification'), ['otp_expiry_minutes' => 5, 'otp_resend_seconds' => 0])->assertSessionHasErrors('otp_resend_seconds');
    }
}
