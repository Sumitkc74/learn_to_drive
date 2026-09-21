<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Question;
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

    public function test_legacy_history_write_is_retired(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $q = Question::create(['question'=>'Stop?', 'option1'=>'Yes', 'option2'=>'No', 'option3'=>'Wait', 'option4'=>'Go', 'correctOption'=>'A', 'status'=>'Published', 'category'=>'General', 'difficulty'=>'Easy']);
        $payload = $this->historyData($otherUser->id) + ['answers'=>[['question_id'=>$q->id,'selected_option'=>'B']]];
        $this->postJson('/api/userHistory', $payload)->assertStatus(410)->assertJsonMissingPath('data.score');
        $this->assertDatabaseCount('user_histories',0);
    }

    public function test_history_rejects_invalid_and_oversized_submissions(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/userHistory', ['answers'=>[['question_id'=>999, 'selected_option'=>'A']]])->assertStatus(410);
        $this->postJson('/api/userHistory', ['answers'=>array_fill(0,101,['question_id'=>1,'selected_option'=>'Z'])])->assertStatus(410);
        $this->postJson('/api/userHistory', $this->historyData(1))->assertStatus(410);
        $this->assertDatabaseCount('user_histories', 0);
    }

    public function test_history_is_paginated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        for ($i=0; $i<21; $i++) UserHistory::create($this->historyData($user->id));
        $this->getJson('/api/userHistory')->assertJsonCount(20,'data.userHistories')->assertJsonPath('data.pagination.last_page',2);
        $this->getJson('/api/userHistory?page=2')->assertJsonCount(1,'data.userHistories');
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
