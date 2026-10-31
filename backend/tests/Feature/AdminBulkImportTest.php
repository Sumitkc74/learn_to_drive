<?php

namespace Tests\Feature;

use App\Models\{User, Tutorial, TrafficSign, VisionTest, ExamPaper, ExamInformation, DrivingSchool, SchoolMembership};
use App\Support\{AdminImportCatalog, SpreadsheetDownload};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminBulkImportTest extends TestCase
{
    use RefreshDatabase;

    private function file(string $type, array $rows): UploadedFile
    {
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, AdminImportCatalog::TYPES[$type][2]);
        foreach ($rows as $row) fputcsv($stream, $row);
        rewind($stream); $csv = stream_get_contents($stream); fclose($stream);
        return UploadedFile::fake()->createWithContent('data.csv', $csv);
    }

    private function admin(): User
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $this->actingAs($admin);
        return $admin;
    }

    public function test_users_import_with_no_plaintext_password_or_role_escalation_and_duplicates_are_skipped(): void
    {
        $this->admin();
        $row = ['CSV Learner', 'csv@example.com', '9812345678', 'User'];
        $this->post(route('bulkImport.store', 'users'), ['file' => $this->file('users', [$row, $row])])
            ->assertSessionHasNoErrors()->assertSessionHas('import_summary.added', 1)->assertSessionHas('import_summary.duplicate', 1);
        $user = User::where('email', 'csv@example.com')->firstOrFail();
        $this->assertNotEmpty(password_get_info($user->password)['algo']);
        $this->assertNull($user->email_verified_at);
        $this->post(route('bulkImport.store', 'users'), ['file' => $this->file('users', [['Forbidden', 'no@example.com', '9812345678', 'Admin']])])->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('users', ['email' => 'no@example.com']);
    }

    public function test_invalid_row_prevents_partial_writes(): void
    {
        $this->admin();
        $this->post(route('bulkImport.store', 'users'), ['file' => $this->file('users', [
            ['Good', 'good@example.com', '9812345678', 'User'], ['Bad', 'invalid', '123', 'User']])])
            ->assertSessionHasErrors('file')->assertSessionHas('import_summary.added', 0);
        $this->assertDatabaseMissing('users', ['email' => 'good@example.com']);
    }

    public function test_images_are_attached_to_tutorials_signs_and_vision_tests(): void
    {
        $this->admin();
        $cases = [
            ['tutorials', ['Safe driving', 'Practice safely', 'https://example.com/video', 'photo.png'], Tutorial::class],
            ['traffic-signs', ['Stop', 'रोक्नुहोस्', 'Stop here', 'photo.png'], TrafficSign::class],
            ['vision-tests', ['789', 'photo.png'], VisionTest::class],
        ];
        foreach ($cases as [$type, $row, $class]) {
            $this->post(route('bulkImport.store', $type), ['file' => $this->file($type, [$row]), 'attachments' => [UploadedFile::fake()->image('photo.png')]])
                ->assertSessionHasNoErrors()->assertSessionHas('import_summary.added', 1);
            $this->assertNotNull($class::latest('id')->firstOrFail()->getFirstMedia());
        }
    }

    public function test_missing_media_and_remote_paths_are_rejected(): void
    {
        $this->admin();
        $this->post(route('bulkImport.store', 'tutorials'), ['file' => $this->file('tutorials', [['Missing photo', 'Details', 'https://example.com/video', 'https://internal/photo.png']])])->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('tutorials', ['title' => 'Missing photo']);
    }

    public function test_documents_import_as_individual_language_files(): void
    {
        $this->admin();
        foreach (['question-banks' => ExamPaper::class, 'exam-information' => ExamInformation::class] as $type => $class) {
            $pdf = UploadedFile::fake()->createWithContent('bank.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
            $this->post(route('bulkImport.store', $type), ['file' => $this->file($type, [['English guide', 'Driving test guide', 'English', 'bank.pdf']]), 'attachments' => [$pdf]])->assertSessionHasNoErrors();
            $record = $class::latest('id')->firstOrFail();
            $this->assertSame('English', $record->language);
            $this->assertSame('English', $record->pdfMedia()->getCustomProperty('language'));
        }
    }

    public function test_schools_start_pending_and_memberships_preserve_existing_roles(): void
    {
        $this->admin();
        $owner = User::factory()->create(['is_active' => true]);
        $this->post(route('bulkImport.store', 'schools'), ['file' => $this->file('schools', [['Imported School', 'Kathmandu', '', '', $owner->email, '27.7', '85.3']])])->assertSessionHasNoErrors();
        $school = DrivingSchool::where('name', 'Imported School')->firstOrFail();
        $this->assertSame('Pending', $school->status);
        $school->update(['status' => 'Approved']);
        $this->post(route('bulkImport.store', 'school-memberships'), ['file' => $this->file('school-memberships', [[$school->id, $owner->email, 'Student']])])->assertSessionHasNoErrors()->assertSessionHas('import_summary.duplicate', 1);
        $this->assertSame('Owner', SchoolMembership::where('user_id', $owner->id)->first()->role);
    }

    public function test_templates_and_xlsx_import_work_and_nonadmins_are_blocked(): void
    {
        $this->admin();
        foreach (array_keys(AdminImportCatalog::TYPES) as $type) {
            $this->get(route('bulkImport', ['type' => $type]))->assertOk();
            $this->get(route('bulkImport.template', ['type' => $type]))->assertOk();
        }
        $this->get(route('bulkImport', ['type' => 'payments']))->assertNotFound();
        if (class_exists(\ZipArchive::class)) {
            $download = SpreadsheetDownload::make('users', AdminImportCatalog::TYPES['users'][2], [['Excel User', 'excel@example.com', '9812345678', 'User']], 'xlsx');
            $path = $download->getFile()->getPathname();
            try {
                $file = new UploadedFile($path, 'users.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
                $this->post(route('bulkImport.store', 'users'), ['file' => $file])->assertSessionHasNoErrors();
                $this->assertDatabaseHas('users', ['email' => 'excel@example.com']);
            } finally { if (is_file($path)) unlink($path); }
        }
        $this->actingAs(User::factory()->create(['is_active' => true]))->get(route('bulkImport'))->assertForbidden();
        $this->post(route('bulkImport.store', 'users'), [])->assertForbidden();
    }
}
