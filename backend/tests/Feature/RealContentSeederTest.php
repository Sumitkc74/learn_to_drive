<?php

namespace Tests\Feature;

use App\Models\ExamInformation;
use App\Models\Question;
use App\Models\Tutorial;
use Database\Seeders\RealContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_idempotent_and_publishes_valid_questions(): void
    {
        $this->seed(RealContentSeeder::class);
        $first = $this->counts();

        $this->assertGreaterThanOrEqual(40, $first['questions']);
        $this->assertGreaterThanOrEqual(8, $first['tutorials']);
        $this->assertGreaterThanOrEqual(3, $first['exam_information']);

        $this->seed(RealContentSeeder::class);
        $this->assertSame($first, $this->counts(), 'Seeding twice must not duplicate rows.');

        $this->assertSame(0, Question::where('status', '!=', 'Published')->count());
        $this->assertSame(0, Question::whereNotIn('correctOption', ['A', 'B', 'C', 'D'])->count());
        $this->assertSame(0, Question::whereNotIn('category', ['General', 'Road Signs', 'Traffic Rules', 'Road Safety', 'Vehicle Knowledge'])->count());
        $this->assertSame(0, Question::whereNull('explanation')->orWhere('explanation', '')->count());
    }

    /** @return array{questions: int, tutorials: int, exam_information: int} */
    private function counts(): array
    {
        return [
            'questions' => Question::count(),
            'tutorials' => Tutorial::count(),
            'exam_information' => ExamInformation::count(),
        ];
    }
}
