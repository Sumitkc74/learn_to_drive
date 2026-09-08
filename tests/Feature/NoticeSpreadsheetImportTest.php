<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class NoticeSpreadsheetImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = "title,description,nepali_title,nepali_description,link,status,publish_at,expires_at\n";

    public function test_admin_can_import_notices_from_csv_with_creator_attribution(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $csv = self::HEADER.'Trial schedule,Check the schedule,ट्रायल तालिका,तालिका हेर्नुहोस्,https://example.com/trial,Draft,2026-09-15 09:00,2026-09-30 23:59'."\n";
        $file = UploadedFile::fake()->createWithContent('notices.csv', $csv);

        $this->actingAs($admin)->post(route('noticeImport.store'), ['notice_file' => $file])
            ->assertRedirect(route('allNotice'));

        $this->assertDatabaseHas('notices', ['title' => 'Trial schedule', 'status' => 'Draft', 'created_by' => $admin->id]);
    }

    public function test_invalid_notice_row_prevents_entire_import(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $csv = self::HEADER
            .'Valid notice,English description,वैध सूचना,नेपाली विवरण,,Draft,,'."\n"
            .'Invalid notice,English description,अवैध सूचना,नेपाली विवरण,not-a-url,Unknown,,'."\n";
        $file = UploadedFile::fake()->createWithContent('notices.csv', $csv);

        $this->actingAs($admin)->post(route('noticeImport.store'), ['notice_file' => $file])
            ->assertSessionHasErrors('notice_file');
        $this->assertSame(0, Notice::count());
    }

    public function test_notice_import_page_offers_template_csv_and_excel_upload(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();

        $this->actingAs($admin)->get(route('noticeImport'))
            ->assertOk()
            ->assertSee('Import from CSV / Excel')
            ->assertSee('Download Notice Template')
            ->assertSee('accept=".csv,.xlsx', false);

        $this->actingAs($admin)->get(route('noticeImport.template'))
            ->assertOk()
            ->assertHeader('content-disposition');
    }
}
