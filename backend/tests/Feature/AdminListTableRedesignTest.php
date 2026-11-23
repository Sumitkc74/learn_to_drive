<?php

namespace Tests\Feature;

use App\Models\ExamInformation;
use App\Models\ExamPaper;
use App\Models\Notice;
use App\Models\Question;
use App\Models\TrafficSign;
use App\Models\Tutorial;
use App\Models\User;
use App\Models\VisionTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminListTableRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
    }

    private function seedRows(): void
    {
        Question::create([
            'question' => 'Redesigned question row should stay readable?',
            'option1' => 'Alpha', 'option2' => 'Beta', 'option3' => 'Gamma', 'option4' => 'Delta',
            'correctOption' => 'B', 'category' => 'Traffic Rules', 'difficulty' => 'Hard', 'status' => 'Published',
        ]);
        ExamPaper::create(['name' => 'Redesigned bank row', 'description' => 'A long bank description that the redesign clamps to two lines.', 'language' => 'English']);
        ExamInformation::create(['name' => 'Redesigned exam info row', 'description' => 'A long exam info description that the redesign clamps to two lines.', 'language' => 'Nepali']);
        Tutorial::create(['title' => 'Redesigned tutorial row', 'description' => 'A long tutorial description that the redesign clamps to two lines.', 'videoLink' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
        Notice::create(['title' => 'Redesigned notice row', 'description' => 'A notice body.', 'nepaliTitle' => 'पुन: डिजाइन गरिएको सूचना', 'nepaliDescription' => 'सूचनाको विवरण।', 'status' => 'Draft']);
        TrafficSign::create(['name' => 'Redesigned sign row', 'nepaliSignName' => 'पुन: डिजाइन गरिएको चिन्ह', 'description' => 'A long sign description that the redesign clamps to two lines.', 'image' => 'sign.jpg']);
        VisionTest::create(['testNumber' => '42', 'image' => 'plate.jpg']);
    }

    #[DataProvider('listPages')]
    public function test_list_page_renders_redesigned_table(string $route, string $rowText, string $actionText): void
    {
        $this->admin();
        $this->seedRows();

        $this->get(route($route))
            ->assertOk()
            ->assertSee('ltd-list-table', false)
            ->assertSee('ltd-actions', false)
            ->assertSee('bulk-delete-form', false)
            ->assertSee('bulk-delete-select-all', false)
            ->assertSee('bulk-delete-row', false)
            ->assertSee('Delete selected')
            ->assertSee($rowText)
            ->assertSee($actionText);
    }

    public static function listPages(): array
    {
        return [
            'questions' => ['allQuestion', 'Redesigned question row should stay readable?', 'Preview'],
            'question banks' => ['allExamPaper', 'Redesigned bank row', 'Edit'],
            'exam information' => ['allExamInformation', 'Redesigned exam info row', 'Edit'],
            'tutorials' => ['allTutorial', 'Redesigned tutorial row', 'Watch'],
            'notices' => ['allNotice', 'Redesigned notice row', 'Archive'],
            'traffic signs' => ['allTrafficSign', 'Redesigned sign row', 'Edit'],
            'vision tests' => ['allVisionTest', '42', 'Edit'],
        ];
    }

    public function test_questions_list_shows_labels_and_correct_option(): void
    {
        $this->admin();
        $this->seedRows();

        $this->get(route('allQuestion'))
            ->assertOk()
            ->assertSee('Labels')
            ->assertSee('Traffic Rules')
            ->assertSee('Hard')
            ->assertSee('Correct option: B');
    }

    public function test_notices_list_stacks_titles_and_dates(): void
    {
        $this->admin();
        $this->seedRows();

        $this->get(route('allNotice'))
            ->assertOk()
            ->assertSee('पुन: डिजाइन गरिएको सूचना')
            ->assertSee('Dates')
            ->assertSee('Published:')
            ->assertSee('Expires:');
    }
}
