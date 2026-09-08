<?php

namespace Tests\Feature;

use App\Models\GovernmentNoticeImport;
use App\Models\Notice;
use App\Models\User;
use App\Services\DotmNoticeImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GovernmentNoticeImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_importer_collects_relevant_official_links_and_ignores_results(): void
    {
        Http::fake(['https://dotm.gov.np/' => Http::response('<html><body><a href="/license-notice/1">सवारी चालक अनुमतिपत्र सम्बन्धी सूचना</a><a href="/procurement">फर्निचर बोलपत्र</a><a href="/results">चालक परीक्षाको नतिजा</a><a href="https://example.com/license">Driving license notice</a></body></html>')]);
        $result = app(DotmNoticeImporter::class)->fetch();

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('government_notice_imports', ['source_url' => 'https://dotm.gov.np/license-notice/1', 'status' => 'Pending']);
        $this->assertDatabaseMissing('government_notice_imports', ['source_url' => 'https://example.com/license']);
    }

    public function test_repeated_fetch_does_not_duplicate_imports(): void
    {
        Http::fake(['https://dotm.gov.np/' => Http::response('<a href="/license">Driving license notice</a>')]);
        $importer = app(DotmNoticeImporter::class);
        $importer->fetch();
        $result = $importer->fetch();
        $this->assertSame(0, $result['created']);
        $this->assertSame(1, GovernmentNoticeImport::count());
    }

    public function test_admin_approval_creates_draft_with_source_attribution(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $import = GovernmentNoticeImport::create(['source_name' => 'Department of Transport Management, Nepal', 'source_url' => 'https://dotm.gov.np/license', 'source_domain' => 'dotm.gov.np', 'title' => 'चालक अनुमति पत्र सूचना', 'content_hash' => hash('sha256', 'source'), 'status' => 'Pending', 'fetched_at' => now()]);

        $this->actingAs($admin)->post(route('governmentNotices.approve', $import->id))->assertRedirect();
        $notice = Notice::firstOrFail();
        $this->assertSame('Draft', $notice->status);
        $this->assertSame($import->source_url, $notice->source_url);
        $this->assertSame('Approved', $import->fresh()->status);
        $this->getJson('/api/notice')->assertJsonMissing(['source_url' => $import->source_url]);
    }

    public function test_non_admin_cannot_access_government_import_review(): void
    {
        $user = User::factory()->create(['role' => 'User']);
        $this->actingAs($user)->get(route('governmentNotices'))->assertForbidden();
    }
}
