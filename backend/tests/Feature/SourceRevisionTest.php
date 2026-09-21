<?php
namespace Tests\Feature;
use App\Models\LearningContentImport;
use App\Services\{LearningContentDownload,SourceRevisionCheck};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Queue,Storage};
use Tests\TestCase;
class SourceRevisionTest extends TestCase {
    use RefreshDatabase;
    public function test_changed_file_creates_one_pending_revision_and_preserves_original(): void {
        Storage::fake('learning-content'); Queue::fake(); config(['content-screening.enabled'=>true]);
        $original=LearningContentImport::create(['source_key'=>'kalanki','source_name'=>'Official','source_url'=>'https://example.org','asset_url'=>'https://example.org/bank.pdf','url_hash'=>hash('sha256','revision-source'),'title'=>'Bank','kind'=>'question-bank-document','fetched_at'=>now()]);
        Storage::disk('learning-content')->put('old.pdf','original');
        $original->forceFill(['status'=>'Approved','file_path'=>'old.pdf','file_hash'=>hash('sha256','original')])->save();
        Storage::disk('learning-content')->put('new.pdf','changed');
        $download=$this->mock(LearningContentDownload::class);
        $download->shouldReceive('fetch')->once()->andReturn(['file_path'=>'new.pdf','file_hash'=>hash('sha256','changed'),'file_size'=>7,'mime_type'=>'application/pdf','downloaded_at'=>now()]);
        app(SourceRevisionCheck::class)->check($original);
        $revision=LearningContentImport::where('parent_resource_id',$original->id)->firstOrFail();
        $this->assertSame('Pending',$revision->status);$this->assertSame('Queued',$revision->ai_status);
        $this->assertSame('Approved',$original->fresh()->status);$this->assertSame('old.pdf',$original->fresh()->file_path);
        $this->assertSame('Needs review',$original->fresh()->source_check_status);
        Storage::disk('learning-content')->assertExists('old.pdf');Storage::disk('learning-content')->assertExists('new.pdf');
        Storage::disk('learning-content')->put('repeat.pdf','changed');
        $this->mock(LearningContentDownload::class)->shouldReceive('fetch')->once()->andReturn(['file_path'=>'repeat.pdf','file_hash'=>hash('sha256','changed')]);
        app(SourceRevisionCheck::class)->check($original->fresh());
        $this->assertSame(1,LearningContentImport::where('parent_resource_id',$original->id)->count());
        Storage::disk('learning-content')->assertMissing('repeat.pdf');
        $revision->forceFill(['status'=>'Approved'])->save();
        Storage::disk('learning-content')->put('same.pdf','changed');
        $this->mock(LearningContentDownload::class)->shouldReceive('fetch')->once()->andReturn(['file_path'=>'same.pdf','file_hash'=>hash('sha256','changed')]);
        app(SourceRevisionCheck::class)->check($original->fresh());
        $this->assertSame('Unchanged',$original->fresh()->source_check_status);
    }
}
