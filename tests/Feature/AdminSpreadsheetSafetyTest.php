<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Support\SpreadsheetDownload;
use App\Support\SpreadsheetReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class AdminSpreadsheetSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_summary_counts_duplicates_and_invalid_rows_without_partial_writes(): void
    {
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail());
        $header = "question,option_a,option_b,option_c,option_d,correct_option,category,difficulty,explanation,status\n";
        $valid = "New question,A,B,C,D,A,General,Easy,,Draft\n";
        $file = UploadedFile::fake()->createWithContent('questions.csv', $header.$valid.$valid."Broken,A,B,C,D,Z,General,Easy,,Draft\n\n");
        $this->post(route('questionImport.store'), ['question_file' => $file])
            ->assertSessionHasErrors('question_file')
            ->assertSessionHas('import_summary', ['added' => 0, 'skipped' => 2, 'duplicate' => 1, 'invalid' => 1]);
        $this->assertDatabaseCount('questions', 0);
        $file = UploadedFile::fake()->createWithContent('questions.csv', $header.$valid.$valid);
        $this->post(route('questionImport.store'), ['question_file' => $file])
            ->assertSessionHas('import_summary', ['added' => 1, 'skipped' => 0, 'duplicate' => 1, 'invalid' => 0]);
        $this->get(route('allQuestion'))->assertSee('Import summary:')->assertSee('Added: 1');
    }

    public function test_notice_blank_status_defaults_to_draft_and_retains_source(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $csv = "title,description,nepali_title,nepali_description,link,status,publish_at,expires_at\nNotice,Details,Title,Description,https://dotm.gov.np/example,,,\n";
        $this->actingAs($admin)->post(route('noticeImport.store'), ['notice_file' => UploadedFile::fake()->createWithContent('notices.csv', $csv)])
            ->assertSessionHasNoErrors()->assertSessionHas('import_summary.added', 1);
        $this->assertDatabaseHas('notices', ['status' => 'Draft', 'link' => 'https://dotm.gov.np/example', 'created_by' => $admin->id]);
    }

    public function test_import_uses_saved_document_size_limit(): void
    {
        AppSetting::updateOrCreate(['key' => 'document_upload_limit_mb'], ['value' => '1']);
        $this->actingAs(User::where('is_seed_admin', true)->firstOrFail())
            ->post(route('noticeImport.store'), ['notice_file' => UploadedFile::fake()->create('notices.csv', 1025, 'text/csv')])
            ->assertSessionHasErrors('notice_file');
    }

    public function test_csv_download_neutralizes_formulas(): void
    {
        $response = SpreadsheetDownload::make('safe', ['text'], [['=1+1'], ["\t=2+2"], ['ordinary']], 'csv');
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();
        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringContainsString("'\t=2+2", $csv);
        $this->assertStringContainsString('ordinary', $csv);
    }

    public function test_xlsx_round_trip_preserves_unicode_and_blank_cells_as_text(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('PHP ZIP extension required.');
        }
        $response = SpreadsheetDownload::make('roundtrip', ['title', 'optional'], [['नेपाली', ''], ['=1+1', 'text']], 'xlsx');
        $path = $response->getFile()->getPathname();
        try {
            $this->assertSame([['title', 'optional'], ['नेपाली', ''], ['=1+1', 'text']], SpreadsheetReader::read($path, 'xlsx'));
        } finally {
            unlink($path);
        }
    }

    public function test_xlsx_rejects_formulas_entities_and_unbounded_cell_references(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('PHP ZIP extension required.');
        }
        foreach (['<row><c r="A1"><f>1+1</f><v>2</v></c></row>', '<row><c r="ZZZZZZZZ1"><v>2</v></c></row>', '<!DOCTYPE foo><row/>'] as $xml) {
            $path = tempnam(sys_get_temp_dir(), 'unsafe-xlsx-');
            $zip = new ZipArchive;
            $zip->open($path, ZipArchive::OVERWRITE);
            $zip->addFromString('xl/worksheets/sheet1.xml', '<worksheet><sheetData>'.$xml.'</sheetData></worksheet>');
            $zip->close();
            try {
                SpreadsheetReader::read($path, 'xlsx');
                $this->fail('Unsafe workbook was accepted.');
            } catch (\RuntimeException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            } finally {
                unlink($path);
            }
        }
    }
}
