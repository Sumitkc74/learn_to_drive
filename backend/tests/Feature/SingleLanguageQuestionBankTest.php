<?php
namespace Tests\Feature;
use App\Models\{ExamPaper, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Storage, DB, Schema};
use Tests\TestCase;
class SingleLanguageQuestionBankTest extends TestCase {
    use RefreshDatabase;
    public function test_individual_files_language_filter_and_replacement(): void {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role'=>'Admin']));
        $details=['name'=>'Bank','description'=>'Practice'];
        $this->post(route('insertExamPaper'),$details)->assertSessionHasErrors(['language','pdf']);
        foreach (['Nepali','English'] as $language) {
            $this->post(route('insertExamPaper'),array_replace($details, $language === 'Nepali' ? ['name'=>'नेपाली प्रश्न संग्रह', 'description'=>'नेपाली प्रश्न संग्रह'] : [])+['language'=>$language,'pdf'=>UploadedFile::fake()->createWithContent($language.'.pdf',"%PDF-1.4\n$language\n%%EOF")])->assertSessionHasNoErrors()->assertRedirect();
        }
        $this->assertDatabaseCount('exam_papers',2);
        $nepali=ExamPaper::where('language','Nepali')->firstOrFail();
        $english=ExamPaper::where('language','English')->firstOrFail();
        $details=['name'=>'नेपाली प्रश्न संग्रह', 'description'=>'नेपाली प्रश्न संग्रह'];
        $original=$nepali->pdfMedia()->id;
        $englishHash=hash_file('sha256',$english->pdfMedia()->getPath());
        $this->post(route('updateExamPaper',$nepali),$details+['language'=>'Nepali'])->assertSessionHasNoErrors();
        $this->assertSame($original,$nepali->fresh()->pdfMedia()->id);
        $this->post(route('updateExamPaper',$nepali),$details+['language'=>'Nepali','pdf'=>UploadedFile::fake()->createWithContent('new.pdf',"%PDF-1.4\nReplacement\n%%EOF")])->assertSessionHasNoErrors();
        $this->assertNotSame($original,$nepali->fresh()->pdfMedia()->id);
        $this->assertCount(1,$nepali->fresh()->getMedia());
        $this->assertSame($englishHash,hash_file('sha256',$english->fresh()->pdfMedia()->getPath()));
        $this->get(route('allExamPaper',['language'=>'Nepali']))->assertOk()->assertViewHas('examPapers',fn($rows)=>$rows->count()===1 && $rows->first()->language==='Nepali')->assertDontSee('English File')->assertDontSee('Nepali File');
        $this->get(route('addExamPaper'))->assertOk()->assertSee('name="pdf"',false)->assertSee('name="language"',false)->assertDontSee('name="nepaliName"',false);
        $this->get(route('editExamPaper',$nepali))->assertOk()->assertSee('Open current PDF');
        $this->getJson('/api/examPaper')->assertOk()->assertJsonPath('data.examPapers.0.language','Nepali')->assertJsonStructure(['data'=>['examPapers'=>[['pdf_url','language']]]]);
        $this->assertFalse(Schema::hasColumn('exam_papers','englishFile'));
    }
    public function test_migration_splits_paired_banks_without_changing_pdf_paths(): void {
        Storage::fake('public');
        $migration=require database_path('migrations/2026_09_14_000001_individual_question_bank_files.php');
        $titleMigration=require database_path('migrations/2026_09_14_000002_single_question_bank_title.php');
        $titleMigration->down();
        $migration->down();
        $id=DB::table('exam_papers')->insertGetId(['name'=>'Legacy','nepaliName'=>'Legacy','description'=>'Practice','englishFile'=>'en.pdf','nepaliFile'=>'ne.pdf']);
        $paper=ExamPaper::findOrFail($id);
        $files=[];
        foreach (['English','Nepali'] as $language) {
            $media=$paper->addMedia(UploadedFile::fake()->createWithContent($language.'.pdf',"%PDF-1.4\n$language\n%%EOF"))->withCustomProperties(['language'=>$language])->toMediaCollection();
            $files[$media->id]=[$media->getPath(),hash_file('sha256',$media->getPath())];
        }
        $migration->up();
        $titleMigration->up();
        $this->assertDatabaseCount('exam_papers',2);
        foreach (ExamPaper::all() as $bank) {
            $this->assertCount(1,$bank->getMedia());
            $media=$bank->pdfMedia();
            $this->assertSame($files[$media->id][0],$media->getPath());
            $this->assertSame($files[$media->id][1],hash_file('sha256',$media->getPath()));
            $this->assertSame($bank->language,$media->getCustomProperty('language'));
        }
    }
}
