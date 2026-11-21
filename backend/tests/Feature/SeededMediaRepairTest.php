<?php

namespace Tests\Feature;

use App\Models\ExamPaper;
use App\Models\VisionTest;
use Database\Seeders\RealContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Boot repair for admin-added media: Render's free disk is wiped on
 * every deploy, which orphans the question-bank PDFs and vision-test
 * plates. RealContentSeeder::repairSeededMedia() re-attaches them from
 * database/seeders/data/ on every boot. These tests simulate the wipe
 * (delete the files, keep the rows) and prove the repair restores them
 * without duplicating media records.
 */
class SeededMediaRepairTest extends TestCase
{
    use RefreshDatabase;

    public function test_repair_restores_wiped_question_bank_pdfs_and_preserves_language(): void
    {
        Storage::fake('public');

        $english = ExamPaper::create([
            'name' => 'Nepal Driving Licence Exam — Question Bank (English)',
            'description' => 'Test bank.',
            'language' => 'English',
        ]);
        $english->addMedia(database_path('seeders/data/question-banks/question-bank-english.pdf'))
            ->preservingOriginal()
            ->withCustomProperties(['language' => 'English'])
            ->toMediaCollection();

        $nepali = ExamPaper::create([
            'name' => 'नेपाल सवारी चालक अनुमतिपत्र परीक्षा — प्रश्न बैंक (नेपाली)',
            'description' => 'Test bank.',
            'language' => 'Nepali',
        ]);
        $nepali->addMedia(database_path('seeders/data/question-banks/question-bank-nepali.pdf'))
            ->preservingOriginal()
            ->withCustomProperties(['language' => 'Nepali'])
            ->toMediaCollection();

        // Simulate the deploy wipe: delete the files, keep the rows.
        foreach ([$english, $nepali] as $paper) {
            $media = $paper->fresh()->getFirstMedia();
            Storage::disk($media->disk)->delete($media->getPathRelativeToRoot());
            $this->assertFalse(Storage::disk($media->disk)->exists($media->getPathRelativeToRoot()));
        }

        $this->seed(RealContentSeeder::class);

        foreach ([$english, $nepali] as $paper) {
            $media = $paper->fresh()->getFirstMedia();
            $this->assertNotNull($media, 'Wiped bank PDF was not re-attached.');
            $this->assertSame(1, $paper->fresh()->getMedia()->count(), 'Repair must not duplicate media records.');
            $this->assertTrue(Storage::disk($media->disk)->exists($media->getPathRelativeToRoot()), 'Re-attached bank PDF file is missing from disk.');
            $this->assertSame($paper->language, $media->getCustomProperty('language'), 'Repair must preserve the language custom property.');
        }

        // A second boot must not duplicate anything.
        $this->seed(RealContentSeeder::class);
        $this->assertSame(1, $english->fresh()->getMedia()->count());
        $this->assertSame(1, $nepali->fresh()->getMedia()->count());
    }

    public function test_repair_restores_wiped_vision_test_plates(): void
    {
        Storage::fake('public');

        foreach ([6, 74] as $testNumber) {
            $test = VisionTest::create(['testNumber' => $testNumber, 'image' => 'plate.png']);
            $test->addMedia(database_path('seeders/data/vision-plates/vision-plate-'.$testNumber.'.png'))
                ->preservingOriginal()
                ->toMediaCollection();
            $media = $test->fresh()->getFirstMedia();
            Storage::disk($media->disk)->delete($media->getPathRelativeToRoot());
            $this->assertFalse(Storage::disk($media->disk)->exists($media->getPathRelativeToRoot()));
        }

        $this->seed(RealContentSeeder::class);

        foreach ([6, 74] as $testNumber) {
            $test = VisionTest::where('testNumber', $testNumber)->firstOrFail();
            $media = $test->getFirstMedia();
            $this->assertNotNull($media, "Wiped plate for test $testNumber was not re-attached.");
            $this->assertSame(1, $test->getMedia()->count(), 'Repair must not duplicate media records.');
            $this->assertTrue(Storage::disk($media->disk)->exists($media->getPathRelativeToRoot()), 'Re-attached plate file is missing from disk.');
        }

        $this->seed(RealContentSeeder::class);
        $this->assertSame(1, VisionTest::where('testNumber', 6)->firstOrFail()->getMedia()->count());
    }

    public function test_repair_never_creates_rows_for_missing_banks_or_tests(): void
    {
        // Repair only touches rows that still exist: with no ExamPaper
        // or VisionTest rows at all, seeding must not create any.
        $this->seed(RealContentSeeder::class);

        $this->assertSame(0, ExamPaper::count());
        $this->assertSame(0, VisionTest::count());
    }
}
