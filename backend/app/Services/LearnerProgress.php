<?php

namespace App\Services;

use App\Models\LearnerPracticeAttempt;
use Carbon\CarbonImmutable;

class LearnerProgress
{
    public function forUser(?int $userId): array
    {
        $today = CarbonImmutable::now('Asia/Kathmandu')->startOfDay();
        $days = [];
        $sessions = $correct = $answered = 0;
        if ($userId) {
            foreach (LearnerPracticeAttempt::where('user_id', $userId)->whereNotNull('completed_at')
                ->select(['completed_at', 'questions', 'score'])->cursor() as $attempt) {
                $days[$attempt->completed_at->timezone('Asia/Kathmandu')->toDateString()] = true;
                $sessions++;
                $correct += $attempt->score;
                $answered += count($attempt->questions);
            }
        }
        $done = isset($days[$today->toDateString()]);
        $day = $done ? $today : $today->subDay();
        $streak = 0;
        while (isset($days[$day->toDateString()])) {
            $streak++;
            $day = $day->subDay();
        }
        return [
            'todayDone' => $done, 'streak' => $streak, 'sessions' => $sessions,
            'answered' => $answered, 'accuracy' => $answered ? (int) round(100 * $correct / $answered) : null,
            'week' => collect(range(6, 0))->map(fn ($ago) => [
                'label' => $today->subDays($ago)->format('D'),
                'date' => $today->subDays($ago)->format('j M'),
                'done' => isset($days[$today->subDays($ago)->toDateString()]),
            ])->all(),
        ];
    }
}
