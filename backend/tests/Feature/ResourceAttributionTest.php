<?php

namespace Tests\Feature;

use App\Models\ExamPaper;
use App\Models\TrafficSign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceAttributionTest extends TestCase
{
    use RefreshDatabase;

    private const COMMONS_URL = 'https://commons.wikimedia.org/wiki/Category:Diagrams_of_road_signs_of_Nepal';
    private const SIGN_MANUAL = 'Department of Roads Traffic Signs Manual';
    private const QUESTION_NOT_OFFICIAL = 'they are not the official question bank';

    public function test_sign_source_on_traffic_sign_library_list(): void
    {
        $this->get('/learn/library/traffic-sign')->assertOk()
            ->assertSee('Wikimedia Commons')
            ->assertSee(self::SIGN_MANUAL)
            ->assertSee(self::COMMONS_URL);
    }

    public function test_sign_source_on_traffic_sign_detail_page(): void
    {
        $sign = TrafficSign::create(['name' => 'Stop and give way', 'nepaliSignName' => 'रोक्नुहोस् र बाटो दिनुहोस्', 'description' => 'Come to a complete stop.', 'image' => 'stop.svg']);

        $this->get('/learn/library/traffic-sign/'.$sign->id)->assertOk()
            ->assertSee('Wikimedia Commons')
            ->assertSee(self::SIGN_MANUAL)
            ->assertSee(self::COMMONS_URL);
    }

    public function test_sign_source_on_flashcards_page(): void
    {
        Storage::fake('public');
        $sign = TrafficSign::create(['name' => 'Stop sign', 'nepaliSignName' => 'Stop', 'description' => 'Come to a stop', 'image' => '']);
        $sign->addMedia(UploadedFile::fake()->image('sign.png'))->toMediaCollection('default', 'public');

        $this->get('/learn/sign-flashcards')->assertOk()
            ->assertSee('Wikimedia Commons')
            ->assertSee(self::SIGN_MANUAL)
            ->assertSee(self::COMMONS_URL);
    }

    public function test_question_source_on_practice_start_page(): void
    {
        $this->get('/learn/practice')->assertOk()->assertSee(self::QUESTION_NOT_OFFICIAL);
    }

    public function test_question_source_on_question_bank_list_and_detail(): void
    {
        $paper = ExamPaper::create(['name' => 'Sample bank', 'description' => 'A set of practice questions.', 'language' => 'English']);

        $this->get('/learn/library/question-bank')->assertOk()->assertSee(self::QUESTION_NOT_OFFICIAL);
        $this->get('/learn/library/question-bank/'.$paper->id)->assertOk()->assertSee(self::QUESTION_NOT_OFFICIAL);
    }

    public function test_attribution_lines_are_translated_in_nepali(): void
    {
        $this->withSession(['learner_locale' => 'ne'])->get('/learn/library/traffic-sign')->assertOk()
            ->assertSee('सङ्केतका चित्रहरू:')
            ->assertSee('सार्वजनिक डोमेन');
        $this->withSession(['learner_locale' => 'ne'])->get('/learn/practice')->assertOk()
            ->assertSee('यी आधिकारिक प्रश्न बैंक होइनन्');
    }
}
