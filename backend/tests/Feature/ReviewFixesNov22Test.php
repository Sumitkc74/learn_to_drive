<?php

namespace Tests\Feature;

use App\Models\ExamPaper;
use App\Models\Notice;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReviewFixesNov22Test extends TestCase
{
    use RefreshDatabase;

    private function bank(string $name, string $file): ExamPaper
    {
        $paper = ExamPaper::create(['name' => $name, 'description' => 'Practice bank', 'language' => 'English']);
        $paper->addMedia(UploadedFile::fake()->create($file, 10, 'application/pdf'))->toMediaCollection();

        return $paper;
    }

    public function test_question_banks_list_full_collections_first(): void
    {
        $this->bank('General Questions (English)', 'question-bank-english-general.pdf');
        $this->bank('Nepal Driving Licence Exam — Question Bank (English)', 'question-bank-english.pdf');
        $this->bank('नेपाल सवारी चालक अनुमतिपत्र परीक्षा — प्रश्न बैंक (नेपाली)', 'question-bank-nepali.pdf');
        $this->bank('Road Signs Questions (English)', 'question-bank-english-road-signs.pdf');

        $this->get(route('learn.library', 'question-bank'))->assertOk()->assertSeeInOrder([
            'नेपाल सवारी चालक अनुमतिपत्र परीक्षा — प्रश्न बैंक (नेपाली)',
            'Nepal Driving Licence Exam — Question Bank (English)',
            'Road Signs Questions (English)',
            'General Questions (English)',
        ]);
    }

    public function test_guest_hero_links_to_free_samples(): void
    {
        $this->get(route('learn.home'))->assertOk()
            ->assertSee('Try five free questions')
            ->assertSee(route('learn.practice').'#free-samples', false);

        Question::create(['question' => 'Sample?', 'option1' => 'A', 'option2' => 'B', 'option3' => 'C', 'option4' => 'D', 'correctOption' => 'A', 'category' => 'General', 'difficulty' => 'Easy', 'status' => 'Published']);
        $this->get(route('learn.practice'))->assertOk()->assertSee('id="free-samples"', false);
    }

    public function test_notices_list_shows_official_link_when_empty_and_when_notices_exist(): void
    {
        $this->get(route('learn.library', 'notice'))->assertOk()
            ->assertSee('See the latest notices on the Department of Transport Management official website')
            ->assertSee('https://dotm.gov.np/', false);

        Notice::create([
            'title' => 'Licence exam schedule',
            'description' => 'Summary of the official notice.',
            'nepaliTitle' => 'Licence exam schedule',
            'nepaliDescription' => 'Summary of the official notice.',
            'status' => 'Published',
        ]);

        $this->get(route('learn.library', 'notice'))->assertOk()
            ->assertSee('Licence exam schedule')
            ->assertSee('See the latest notices on the Department of Transport Management official website')
            ->assertSee('https://dotm.gov.np/', false);
    }
}
