<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\Question;
use App\Models\TrafficSign;
use App\Models\Tutorial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResourceDescriptionColumnsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The admin forms validate description fields at max:1000 (and the
     * notice bulk import at max:5000) while the columns were VARCHAR(255),
     * so on live MySQL every over-long save threw "Data too long" and the
     * site showed a 500. A plain "create a row with a 300-char description"
     * test would pass on SQLite even before the fix — SQLite does not
     * enforce VARCHAR length — so the column-type assertion below is the
     * real guard; the create tests only prove the widened columns hold
     * validation-max values end to end.
     */
    public function test_description_columns_are_text(): void
    {
        $this->assertSame('text', Schema::getColumnType('tutorials', 'description'));
        $this->assertSame('text', Schema::getColumnType('exam_information', 'description'));
        $this->assertSame('text', Schema::getColumnType('exam_papers', 'description'));
        $this->assertSame('text', Schema::getColumnType('traffic_signs', 'description'));
        $this->assertSame('text', Schema::getColumnType('notices', 'description'));
        $this->assertSame('text', Schema::getColumnType('notices', 'nepaliDescription'));
    }

    public function test_long_descriptions_and_urls_save_at_validation_max(): void
    {
        $tutorial = Tutorial::create([
            'title' => 'Long description tutorial',
            'description' => str_repeat('d', 1000),
            'videoLink' => 'https://example.com/'.str_repeat('v', 2028),
        ]);
        $this->assertSame(1000, strlen($tutorial->fresh()->description));
        $this->assertSame(2048, strlen($tutorial->fresh()->videoLink));

        $sign = TrafficSign::create([
            'name' => 'Long description sign',
            'nepaliSignName' => 'चिन्ह',
            'description' => str_repeat('s', 1000),
            'image' => 'sign.png',
        ]);
        $this->assertSame(1000, strlen($sign->fresh()->description));

        $notice = Notice::create([
            'title' => 'Long description notice',
            'description' => str_repeat('n', 5000),
            'nepaliTitle' => 'सूचना',
            'nepaliDescription' => str_repeat('प', 5000),
            'link' => 'https://example.com/'.str_repeat('l', 2028),
            'status' => 'Draft',
        ]);
        $this->assertSame(5000, strlen($notice->fresh()->description));
        $this->assertSame(5000, mb_strlen($notice->fresh()->nepaliDescription));
        $this->assertSame(2048, strlen($notice->fresh()->link));

        $question = Question::create([
            'question' => str_repeat('q', 500),
            'option1' => 'One',
            'option2' => 'Two',
            'option3' => 'Three',
            'option4' => 'Four',
            'correctOption' => 'A',
            'category' => 'General',
            'difficulty' => 'Easy',
            'status' => 'Published',
        ]);
        $this->assertSame(500, strlen($question->fresh()->question));
    }
}
