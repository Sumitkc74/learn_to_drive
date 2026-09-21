<?php
namespace Tests\Feature;
use App\Models\{ExamPaper, LearningContentImport, PdfTranslation, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
class AttachReviewedBankTest extends TestCase {
    use RefreshDatabase;
    public function test_reviewed_translation_creates_an_independent_bank(): void {
        Storage::fake('public'); Storage::fake('learning-content');
        $admin=User::factory()->create(['role'=>'Admin']);
        $this->actingAs($admin);
        $original="%PDF-1.4\nEnglish original\n%%EOF";
        $translated="%PDF-1.4\nTranslated\n%%EOF";
        $paper=ExamPaper::create(['name'=>'Bank','nepaliName'=>'बैंक','description'=>'Practice','language'=>'English']);
        $paper->replacePdf(UploadedFile::fake()->createWithContent('en.pdf',$original));
        $originalId=$paper->pdfMedia()->id;
        Storage::disk('learning-content')->put('ne.pdf',$translated);
        $source=LearningContentImport::create(['source_key'=>'reviewed-translation','source_name'=>'Reviewed translation','source_url'=>'https://example.org','asset_url'=>'https://example.org/ne.pdf','url_hash'=>hash('sha256','ne'),'kind'=>'question-bank-document','title'=>'Nepali','fetched_at'=>now()]);
        $source->forceFill(['language'=>'Nepali','file_path'=>'ne.pdf','file_hash'=>hash('sha256',$translated),'status'=>'Pending','downloaded_at'=>now(),'file_size'=>strlen($translated)])->save();
        $data=['name'=>'नेपाली प्रश्न संग्रह','nepaliName'=>'Translated title','description'=>'नेपाली प्रश्न संग्रह','file_hash'=>$source->file_hash,'confirmed'=>1];
        $url=route('learningContent.storeExamPaper',$source);
        $this->post($url,$data)->assertStatus(409);
        $source->forceFill(['status'=>'Approved'])->save();
        $this->post($url,array_replace($data,['file_hash'=>str_repeat('b',64)]))->assertStatus(409);
        $this->get(route('learningContent.show',$source))->assertOk()->assertSee('Add to Question Banks')->assertDontSee('Attach to an Existing');
        $this->post($url,$data)->assertRedirect(route('allExamPaper'));
        $paper->refresh();
        $this->assertSame($originalId,$paper->pdfMedia()->id);
        $this->assertSame(hash('sha256',$translated),hash_file('sha256',ExamPaper::where('language','Nepali')->firstOrFail()->pdfMedia()->getPath()));
        Storage::disk('learning-content')->assertExists('ne.pdf');
        $this->post($url,$data)->assertSessionHasErrors('file_hash');
        $this->assertDatabaseCount('exam_papers',2);
        $this->actingAs(User::factory()->create(['role'=>'User']))->post($url,$data)->assertForbidden();
    }
}
