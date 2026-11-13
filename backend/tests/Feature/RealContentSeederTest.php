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

        // The bank is bilingual: 44 English questions plus their 44 Nepali
        // translations, one-to-one per category, all Published, plus 15
        // traffic-sign questions (official sign diagram attached as media)
        // in each language.
        $this->assertSame(118, $first['questions']);
        $this->assertSame(59, Question::where('language', 'en')->count());
        $this->assertSame(59, Question::where('language', 'ne')->count());
        $withDiagram = Question::where('category', 'Road Signs')->get()
            ->filter(fn ($question) => $question->getMedia('question-images')->isNotEmpty());
        $this->assertSame(30, $withDiagram->count(), 'Every traffic-sign question must carry its sign diagram as media.');
        foreach (['General', 'Road Signs', 'Traffic Rules', 'Road Safety', 'Vehicle Knowledge'] as $category) {
            $this->assertSame(
                Question::where('language', 'en')->where('category', $category)->count(),
                Question::where('language', 'ne')->where('category', $category)->count(),
                "Category $category must have the same number of English and Nepali questions."
            );
        }
        foreach (Question::where('language', 'ne')->pluck('question') as $text) {
            $this->assertMatchesRegularExpression('/\p{Devanagari}/u', $text, 'Every Nepali question must be written in Devanagari script.');
        }
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
