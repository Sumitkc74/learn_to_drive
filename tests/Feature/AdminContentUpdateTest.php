<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\ExamInformation;
use App\Models\ExamPaper;
use App\Models\TrafficSign;
use App\Models\Tutorial;
use App\Models\User;
use App\Models\VisionTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminContentUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_edit_pages_handle_missing_media_and_replace_only_selected_language(): void
    {
        Storage::fake('public');
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
        foreach ([ExamPaper::class => 'ExamPaper', ExamInformation::class => 'ExamInformation'] as $model => $route) {
            $record = $model::create(['name' => 'English title', 'nepaliName' => 'Nepali title', 'description' => 'Details', 'englishFile' => 'english.pdf', 'nepaliFile' => 'nepali.pdf']);
            $this->get(route('edit'.$route, $record))->assertOk()->assertSee('Added by');
            $record->addMedia(UploadedFile::fake()->createWithContent('english.pdf', '%PDF-1.4 original english'))->toMediaCollection();
            $nepali = $record->addMedia(UploadedFile::fake()->createWithContent('nepali.pdf', '%PDF-1.4 original nepali'))->toMediaCollection();
            $this->post(route('update'.$route, $record), ['name' => 'Updated', 'nepaliName' => 'Updated Nepali', 'description' => 'New details', 'englishFile' => UploadedFile::fake()->createWithContent('replacement.pdf', '%PDF-1.4 replacement')])
                ->assertSessionHasNoErrors()->assertRedirect();
            $record->refresh();
            $this->assertSame('replacement.pdf', $record->englishFile);
            $this->assertSame('Updated Nepali', $record->nepaliName);
            $this->assertCount(2, $record->getMedia());
            $this->assertSame('replacement.pdf', $record->getMedia()->first()->file_name);
            $this->assertSame($nepali->id, $record->getMedia()->get(1)->id);
            $this->assertTrue(is_file($nepali->getPath()));
        }
    }

    public function test_image_edits_enforce_upload_limits_and_retain_creator(): void
    {
        Storage::fake('public');
        AppSetting::updateOrCreate(['key' => 'image_upload_limit_mb'], ['value' => '1']);
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $this->actingAs($admin);
        $cases = [
            [TrafficSign::class, 'TrafficSign', ['name' => 'Stop', 'nepaliSignName' => 'Stop', 'description' => 'Stop here', 'image' => 'old.png']],
            [VisionTest::class, 'VisionTest', ['testNumber' => 12, 'image' => 'old.png']],
            [Tutorial::class, 'Tutorial', ['title' => 'Tutorial', 'description' => 'Details', 'videoLink' => 'https://example.com/video']],
        ];
        foreach ($cases as [$model, $route, $data]) {
            $record = $model::create($data);
            $this->get(route('edit'.$route, $record))->assertOk()->assertSee('Added by');
            unset($data['image']);
            $this->post(route('update'.$route, $record), $data + ['image' => UploadedFile::fake()->image('big.png')->size(1025)])
                ->assertSessionHasErrors('image');
            $this->assertFalse($record->hasMedia());
            $this->post(route('update'.$route, $record), $data + ['image' => UploadedFile::fake()->image('small.png'), 'created_by' => 999999])
                ->assertSessionHasNoErrors();
            $this->assertSame('small.png', $record->fresh()->getFirstMedia()->file_name);
            $this->assertSame($admin->id, $record->fresh()->created_by);
        }
    }

    public function test_missing_content_returns_404_and_invalid_tutorial_url_is_rejected(): void
    {
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
        foreach (['TrafficSign', 'VisionTest', 'Tutorial', 'ExamPaper', 'ExamInformation'] as $route) {
            $this->get(route('edit'.$route, 999999))->assertNotFound();
            $this->delete(route('delete'.$route, 999999))->assertNotFound();
        }
        $tutorial = Tutorial::create(['title' => 'Lesson', 'description' => 'Details', 'videoLink' => 'https://example.com/video']);
        $this->post(route('updateTutorial', $tutorial), ['title' => 'Lesson', 'description' => 'Details', 'videoLink' => 'javascript:alert(1)'])->assertSessionHasErrors('videoLink');
    }
}
