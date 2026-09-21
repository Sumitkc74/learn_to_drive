<?php
namespace Tests\Feature;
use App\Models\{Question,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
class ProtectedMediaTest extends TestCase {
    use RefreshDatabase;
    public function test_question_media_requires_admin_until_published_and_is_revoked_on_archive(): void {
        Storage::fake('protected-media'); Storage::fake('public');
        $question=Question::create(['question'=>'Test?','option1'=>'A','option2'=>'B','option3'=>'C','option4'=>'D','correctOption'=>'A','category'=>'B','difficulty'=>'Easy','status'=>'Draft']);
        $media=$question->addMedia(UploadedFile::fake()->createWithContent('test.png','test-image'))->toMediaCollection('question-images');
        $this->assertSame('protected-media',$media->disk);
        Storage::disk('public')->assertMissing($media->getPathRelativeToRoot());
        $this->get(route('media.review',$media))->assertRedirect(route('login'));
        $this->get(route('media.published',$media))->assertNotFound();
        $this->actingAs(User::factory()->create(['role'=>'User']))->get(route('media.review',$media))->assertForbidden();
        $this->actingAs(User::factory()->create(['role'=>'Admin']))->get(route('media.review',$media))->assertOk();
        $question->update(['status'=>'Published']);
        $this->get(route('media.published',$media))->assertOk()->assertHeader('Cache-Control','no-store, private');
        $question->update(['status'=>'Archived']);
        $this->get(route('media.published',$media))->assertNotFound();
    }
    public function test_audit_reports_missing_and_unused_files_without_deleting_them(): void {
        foreach (['public','protected-media','learning-content'] as $disk) Storage::fake($disk);
        Storage::disk('public')->put('unused.pdf','unused');
        $record=\App\Models\LearningContentImport::create(['source_key'=>'test','source_name'=>'Test','source_url'=>'https://example.org','asset_url'=>'https://example.org/missing.pdf','url_hash'=>hash('sha256','missing'),'title'=>'Missing','kind'=>'question-bank-document','fetched_at'=>now()]);
        $record->forceFill(['file_path'=>'missing.pdf'])->save();
        $report=app(\App\Services\MediaStorageAudit::class)->scan();
        $this->assertContains('unused.pdf',array_column($report['issues'],'path'));
        $this->assertContains('Missing file',array_column($report['issues'],'type'));
        Storage::disk('public')->assertExists('unused.pdf');
    }
    public function test_existing_public_image_moves_without_changing_id_or_bytes(): void {
        Storage::fake('public'); Storage::fake('protected-media');
        $question=Question::create(['question'=>'Test?','option1'=>'A','option2'=>'B','option3'=>'C','option4'=>'D','correctOption'=>'A','category'=>'B','difficulty'=>'Easy','status'=>'Draft']);
        $media=$question->addMedia(UploadedFile::fake()->createWithContent('legacy.png','original bytes'))->toMediaCollection('question-images','public');
        $path=$media->getPathRelativeToRoot();
        $this->artisan('media:protect-questions')->assertSuccessful();
        $this->assertSame('protected-media',$media->fresh()->disk);
        $this->assertSame('original bytes',Storage::disk('protected-media')->get($path));
        Storage::disk('public')->assertMissing($path);
    }
}
