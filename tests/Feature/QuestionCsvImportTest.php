<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class QuestionCsvImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = "question,option_a,option_b,option_c,option_d,correct_option,category,difficulty,explanation,status\n";

    public function test_admin_can_import_valid_questions(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $csv = self::HEADER."What does red mean?,Stop,Go,Turn,Park,A,Traffic Rules,Easy,Drivers must stop.,Published\n";
        $file = UploadedFile::fake()->createWithContent('questions.csv', $csv);

        $this->actingAs($admin)->post(route('questionImport.store'), ['csv_file' => $file])->assertRedirect(route('allQuestion'));
        $this->assertDatabaseHas('questions', ['question' => 'What does red mean?', 'category' => 'Traffic Rules']);
    }

    public function test_invalid_row_prevents_the_entire_import(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $csv = self::HEADER
            ."Valid question?,One,Two,Three,Four,A,General,Easy,Explanation,Draft\n"
            ."Invalid question?,Same,Same,Three,Four,Z,Unknown,Extreme,Explanation,Hidden\n";
        $file = UploadedFile::fake()->createWithContent('questions.csv', $csv);

        $this->actingAs($admin)->post(route('questionImport.store'), ['csv_file' => $file])->assertSessionHasErrors('csv_file');
        $this->assertSame(0, Question::count());
    }

    public function test_wrong_headers_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $file = UploadedFile::fake()->createWithContent('questions.csv', "wrong,headers\nvalue,value\n");
        $this->actingAs($admin)->post(route('questionImport.store'), ['csv_file' => $file])->assertSessionHasErrors('csv_file');
    }

    public function test_admin_can_download_template_and_export(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin)->get(route('questionImport.template'))->assertOk()->assertHeader('content-disposition');
        $this->actingAs($admin)->get(route('questionExport'))->assertOk()->assertHeader('content-disposition');
    }
}
