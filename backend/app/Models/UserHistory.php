<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'attempted_questions',
        'optionA',
        'optionB',
        'optionC',
        'optionD',
        'correct_options',
        'selected_options',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCorrectCountAttribute(): int
    {
        $correct = $this->asArray($this->correct_options);
        $selected = $this->asArray($this->selected_options);

        return count(array_filter($correct, fn ($answer, $index) => isset($selected[$index]) && $selected[$index] === $answer, ARRAY_FILTER_USE_BOTH));
    }

    public function getQuestionCountAttribute(): int
    {
        return max(count($this->asArray($this->attempted_questions)), count($this->asArray($this->correct_options)));
    }

    public function getScorePercentageAttribute(): int
    {
        return $this->question_count === 0 ? 0 : (int) round(($this->correct_count / $this->question_count) * 100);
    }

    private function asArray(?string $value): array
    {
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }
}
