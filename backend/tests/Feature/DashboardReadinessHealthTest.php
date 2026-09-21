<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use App\Services\AdminSystemHealth;
use App\Services\ContentReadiness;
use App\Services\LearningContentCollector;
use App\Services\OfficialContentPreview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardReadinessHealthTest extends TestCase
{
    use RefreshDatabase;

    private function question(array $attributes = []): Question
    {
        return Question::create($attributes + ['question' => 'What should a driver do?', 'option1' => 'Stop', 'option2' => 'Wait', 'option3' => 'Turn', 'option4' => 'Proceed', 'correctOption' => 'A', 'category' => 'General', 'difficulty' => 'Easy', 'status' => 'Draft']);
    }

    private function admin(): User
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $this->actingAs($admin);

        return $admin;
    }

    public function test_readiness_counts_exclude_published_and_deleted_questions_and_do_not_require_every_image(): void
    {
        $this->admin();
        Storage::fake('public');
        $plain = $this->question();
        $sign = $this->question(['question' => 'Identify the sign', 'category' => 'Road Signs', 'explanation' => 'A description']);
        $this->question(['status' => 'Published']);
        $this->question()->delete();
        $counts = app(ContentReadiness::class)->summary();
        $this->assertSame(['drafts' => 2, 'answer' => 0, 'verification' => 2, 'image' => 1, 'explanation' => 1], $counts);
        $this->get(route('contentReadiness', ['issue' => 'image']))->assertOk()->assertViewHas('questions', fn ($items) => $items->count() === 1 && $items->first()->id === $sign->id);
        $sign->addMediaFromString(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a5XcAAAAASUVORK5CYII='))->usingFileName('sign.png')->toMediaCollection('question-images');
        $this->assertSame(0, app(ContentReadiness::class)->summary()['image']);
        $plain->update(['option2' => 'Stop']);
        $this->assertSame(1, app(ContentReadiness::class)->summary()['answer']);
    }

    public function test_verification_records_actor_and_clears_only_when_answer_content_changes(): void
    {
        $admin = $this->admin();
        $question = $this->question();
        $hash = ContentReadiness::answerHash($question);
        $this->post(route('contentReadiness.verify', $question), ['review_hash' => $hash])->assertSessionHasErrors('confirmed');
        $this->post(route('contentReadiness.verify', $question), ['confirmed' => 1, 'review_hash' => $hash, 'answer_verified_by' => 999])->assertRedirect();
        $question->refresh();
        $this->assertSame($admin->id, $question->answer_verified_by);
        $this->assertNotNull($question->answer_verified_at);
        $this->assertSame('Draft', $question->status);
        $question->update(['explanation' => 'Added explanation']);
        $this->assertNotNull($question->fresh()->answer_verified_at);
        $question->update(['option1' => 'Stop safely']);
        $this->assertNull($question->fresh()->answer_verified_at);
        $this->assertNull($question->fresh()->answer_verified_by);
        $this->post(route('contentReadiness.verify', $question), ['confirmed' => 1, 'review_hash' => $hash])->assertStatus(409);
        $this->assertDatabaseHas('audit_logs', ['subject_type' => 'Question', 'subject_id' => $question->id, 'event' => 'updated']);
    }

    public function test_incomplete_and_published_questions_cannot_be_verified(): void
    {
        $this->admin();
        $question = $this->question(['option4' => 'Stop']);
        $this->post(route('contentReadiness.verify', $question), ['confirmed' => 1, 'review_hash' => ContentReadiness::answerHash($question)])->assertSessionHasErrors('answer');
        $question->update(['status' => 'Published']);
        $this->post(route('contentReadiness.verify', $question), ['confirmed' => 1, 'review_hash' => ContentReadiness::answerHash($question)])->assertStatus(409);
    }

    public function test_changing_question_images_invalidates_verification_and_stale_review_forms(): void
    {
        $this->admin();
        Storage::fake('public');
        $question = $this->question();
        $hash = ContentReadiness::answerHash($question);
        $this->post(route('contentReadiness.verify', $question), ['confirmed' => 1, 'review_hash' => $hash])->assertRedirect();
        $question->addMediaFromString(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a5XcAAAAASUVORK5CYII='))->usingFileName('new.png')->toMediaCollection('question-images');
        $this->assertNull($question->fresh()->answer_verified_at);
        $this->post(route('contentReadiness.verify', $question), ['confirmed' => 1, 'review_hash' => $hash])->assertStatus(409);
    }

    public function test_health_reports_unknown_recent_and_stale_heartbeats_without_network_requests(): void
    {
        $this->admin();
        Cache::forget(AdminSystemHealth::HEARTBEAT);
        Http::preventStrayRequests();
        $health = app(AdminSystemHealth::class);
        $this->assertFalse($health->snapshot()['worker_recent']);
        $health->heartbeat();
        $this->assertTrue($health->snapshot()['worker_recent']);
        $this->travel(4)->minutes();
        $this->assertFalse($health->snapshot()['worker_recent']);
        $this->get(route('adminDashboard'))->assertOk()->assertSee('Content Readiness')->assertSee('System Health')->assertSee('Heartbeat is stale')->assertSee('Never checked');
        Http::assertNothingSent();
    }

    public function test_source_checks_record_failure_and_success_without_exposing_exception_text(): void
    {
        $preview = \Mockery::mock(OfficialContentPreview::class);
        $preview->shouldReceive('fetch')->once()->andThrow(new \RuntimeException('Private diagnostic details'));
        try {
            (new LearningContentCollector($preview))->fetch('kalanki');
            $this->fail('Expected source failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Private diagnostic details', $e->getMessage());
        }
        $this->assertDatabaseHas('official_source_checks', ['source_key' => 'kalanki', 'status' => 'Failed']);
        $preview = \Mockery::mock(OfficialContentPreview::class);
        $preview->shouldReceive('fetch')->once()->andReturn(['assets' => [], 'excluded_assets' => []]);
        (new LearningContentCollector($preview))->fetch('kalanki');
        $this->assertDatabaseHas('official_source_checks', ['source_key' => 'kalanki', 'status' => 'Success', 'found' => 0]);
        $this->admin();
        $this->get(route('adminDashboard'))->assertDontSee('Private diagnostic details');
        $this->assertSame(1, DB::table('official_source_checks')->count());
    }

    public function test_readiness_endpoints_require_an_admin(): void
    {
        $question = $this->question();
        $this->get(route('contentReadiness'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'User']));
        $this->get(route('contentReadiness'))->assertForbidden();
        $this->post(route('contentReadiness.verify', $question), ['confirmed' => 1, 'review_hash' => ContentReadiness::answerHash($question)])->assertForbidden();
    }
}
