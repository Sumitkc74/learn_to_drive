<?php

namespace Tests\Feature;

use App\Models\ExamPaper;
use App\Models\LearningContentImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewedExamPaperTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_reviewed_unchanged_pdfs_can_be_added_and_duplicates_are_blocked(): void
    {
        Storage::fake('learning-content');
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'Admin']);
        $sources = [];
        foreach (['English', 'Nepali'] as $language) {
            $bytes = "%PDF-1.4\n% Question bank $language\n%%EOF";
            $path = $language.'.pdf';
            Storage::disk('learning-content')->put($path, $bytes);
            $source = LearningContentImport::create(['source_key' => 'kalanki', 'source_name' => 'Official source',
                'source_url' => 'https://example.com/banks', 'asset_url' => 'https://example.com/'.$path,
                'url_hash' => hash('sha256', $path), 'kind' => 'question-bank-document', 'title' => $language.' bank', 'fetched_at' => now()]);
            $source->forceFill(['file_path' => $path, 'file_hash' => hash('sha256', $bytes), 'file_size' => strlen($bytes),
                'downloaded_at' => now(), 'language' => $language, 'licence_category' => 'B', 'status' => 'Pending'])->save();
            $sources[] = $source;
        }
        [$english, $nepali] = $sources;
        $url = route('learningContent.storeExamPaper', $english);
        $data = ['name' => 'Question bank', 'nepaliName' => 'प्रश्न संग्रह', 'description' => 'Practice question bank',
            'file_hash' => $english->file_hash, 'confirmed' => 1];
        $this->actingAs(User::factory()->create(['role' => 'User']))->post($url, $data)->assertForbidden();
        $this->actingAs($admin)->post($url, $data)->assertStatus(409);
        $this->get(route('learningContent.show', $english))->assertOk()->assertDontSee('id="paper-name"', false);
        foreach ($sources as $source) $source->forceFill(['status' => 'Approved', 'reviewed_by' => $admin->id, 'reviewed_at' => now()])->save();
        $this->get(route('learningContent.show', $english))->assertOk()->assertSee('id="paper-name"', false);
        $this->post($url, array_replace($data, ['confirmed' => 0]))->assertSessionHasErrors('confirmed');
        $this->post($url, array_replace($data, ['file_hash' => str_repeat('0', 64)]))->assertStatus(409);
        $this->assertSame(0, ExamPaper::count());
        $this->post($url, $data)->assertRedirect(route('allExamPaper'));
        $paper = ExamPaper::firstOrFail();
        $this->assertCount(1, $paper->getMedia());
        foreach ($paper->getMedia() as $index => $media) {
            $this->assertSame($sources[$index]->file_hash, hash_file('sha256', $media->getPath()));
            $this->assertSame($sources[$index]->id, $media->getCustomProperty('source_resource_id'));
            Storage::disk('learning-content')->assertExists($sources[$index]->file_path);
        }
        $this->post($url, $data)->assertSessionHasErrors('file_hash');
        $this->assertSame(1, ExamPaper::count());
    }
}
