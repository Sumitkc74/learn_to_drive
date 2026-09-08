<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class QuestionCsvImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = "question,option_a,option_b,option_c,option_d,correct_option,category,difficulty,explanation,status\n";

    public function test_admin_can_import_valid_questions(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $csv = self::HEADER."What does red mean?,Stop,Go,Turn,Park,A,Traffic Rules,Easy,Drivers must stop.,Published\n";
        $file = UploadedFile::fake()->createWithContent('questions.csv', $csv);

        $this->actingAs($admin)->post(route('questionImport.store'), ['question_file' => $file])->assertRedirect(route('allQuestion'));
        $this->assertDatabaseHas('questions', ['question' => 'What does red mean?', 'category' => 'Traffic Rules']);
    }

    public function test_invalid_row_prevents_the_entire_import(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $csv = self::HEADER
            ."Valid question?,One,Two,Three,Four,A,General,Easy,Explanation,Draft\n"
            ."Invalid question?,Same,Same,Three,Four,Z,Unknown,Extreme,Explanation,Hidden\n";
        $file = UploadedFile::fake()->createWithContent('questions.csv', $csv);

        $this->actingAs($admin)->post(route('questionImport.store'), ['question_file' => $file])->assertSessionHasErrors('question_file');
        $this->assertSame(0, Question::count());
    }

    public function test_wrong_headers_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $file = UploadedFile::fake()->createWithContent('questions.csv', "wrong,headers\nvalue,value\n");
        $this->actingAs($admin)->post(route('questionImport.store'), ['question_file' => $file])->assertSessionHasErrors('question_file');
    }

    public function test_admin_can_download_template_and_export(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin)->get(route('questionImport.template'))->assertOk()->assertHeader('content-disposition');
        $this->actingAs($admin)->get(route('questionExport'))->assertOk()->assertHeader('content-disposition');
        if (class_exists(ZipArchive::class)) {
            $this->actingAs($admin)->get(route('questionExport', ['format' => 'xlsx']))
                ->assertOk()->assertDownload('questions-'.now()->format('Y-m-d').'.xlsx');
        }
    }

    public function test_import_page_offers_csv_and_excel_uploads(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)->get(route('questionImport'))
            ->assertOk()
            ->assertSee('Import from CSV / Excel')
            ->assertSee('accept=".csv,.xlsx', false);
    }

    public function test_admin_can_import_an_xlsx_workbook(): void
    {
        if (!class_exists(ZipArchive::class)) $this->markTestSkipped('PHP ZIP extension is not installed.');

        $admin = User::factory()->create(['role' => 'Admin']);
        $path = tempnam(sys_get_temp_dir(), 'questions-xlsx-');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheets><sheet name="Questions" sheetId="1"/></sheets></workbook>');
        $headers = ['question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option', 'category', 'difficulty', 'explanation', 'status'];
        $values = ['Excel question?', 'One', 'Two', 'Three', 'Four', 'A', 'General', 'Easy', 'Imported from Excel.', 'Draft'];
        $rowXml = function (array $row, int $number): string {
            $cells = '';
            foreach ($row as $index => $value) $cells .= '<c r="'.chr(65 + $index).$number.'" t="inlineStr"><is><t>'.htmlspecialchars($value, ENT_XML1).'</t></is></c>';
            return '<row r="'.$number.'">'.$cells.'</row>';
        };
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$rowXml($headers, 1).$rowXml($values, 2).'</sheetData></worksheet>');
        $zip->close();

        $file = new UploadedFile($path, 'questions.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $this->actingAs($admin)->post(route('questionImport.store'), ['question_file' => $file])->assertRedirect(route('allQuestion'));
        $this->assertDatabaseHas('questions', ['question' => 'Excel question?']);
        @unlink($path);
    }
}
