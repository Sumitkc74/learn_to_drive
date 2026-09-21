<?php
namespace Tests\Feature;
use App\Models\{User, VisionTest, ExamPaper};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
class MediaLibraryTest extends TestCase {
    use RefreshDatabase;
    public function test_library_filters_files_and_reports_missing_storage(): void {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role'=>'Admin']));
        $vision=VisionTest::create(['testNumber'=>123,'image'=>'plate.png']);
        $image=$vision->addMedia(UploadedFile::fake()->image('plate.png'))->toMediaCollection();
        $bank=ExamPaper::create(['name'=>'Test bank','nepaliName'=>'बैंक','description'=>'Test','language'=>'English']);
        $bank->replacePdf(UploadedFile::fake()->createWithContent('bank.pdf',"%PDF-1.4\n%%EOF"),'English');
        $this->get(route('mediaLibrary'))->assertOk()->assertSee('plate.png')->assertSee('bank.pdf')->assertSee('Edit Content');
        $this->get(route('mediaLibrary',['type'=>'vision']))->assertOk()->assertSee('plate.png')->assertDontSee('bank.pdf');
        $this->get(route('mediaLibrary',['format'=>'pdf']))->assertOk()->assertSee('bank.pdf')->assertDontSee('plate.png');
        Storage::disk('public')->delete($image->getPathRelativeToRoot());
        $this->get(route('mediaLibrary',['search'=>'plate']))->assertOk()->assertSee('File missing from storage')->assertDontSee('bank.pdf');
        $this->actingAs(User::factory()->create(['role'=>'User']))->get(route('mediaLibrary'))->assertForbidden();
    }
}
