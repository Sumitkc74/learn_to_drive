<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserHistoryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_only_receive_their_own_history(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        UserHistory::create($this->historyData($user->id));
        UserHistory::create($this->historyData($otherUser->id));
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/userHistory')->assertOk();

        $response->assertJsonCount(1, 'data.userHistories')
            ->assertJsonPath('data.userHistories.0.user_id', $user->id);
    }

    public function test_history_is_recorded_for_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = $this->historyData($otherUser->id);
        $this->postJson('/api/userHistory', $payload)->assertOk();

        $this->assertDatabaseHas('user_histories', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('user_histories', ['user_id' => $otherUser->id]);
    }

    private function historyData(int $userId): array
    {
        return [
            'user_id' => $userId,
            'attempted_questions' => '[1]',
            'optionA' => '["A"]',
            'optionB' => '["B"]',
            'optionC' => '["C"]',
            'optionD' => '["D"]',
            'correct_options' => '["A"]',
            'selected_options' => '["A"]',
        ];
    }
}
