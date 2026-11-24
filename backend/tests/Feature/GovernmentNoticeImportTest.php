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

    public function test_fetch_uses_configured_certificate_bundle(): void
    {
        config(['official-content.ca_bundle' => '/trusted/ca.pem', 'services.dotm.listing_urls' => []]);
        Http::fake(function ($request, $options) {
            $this->assertSame('/trusted/ca.pem', $options['verify']);
            return Http::response('<html><body>No notices</body></html>');
        });

        $this->assertSame(0, app(DotmNoticeImporter::class)->fetch()['created']);
        Http::assertSentCount(1);
    }

    public function test_fetch_messages_are_rendered_once(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->mock(DotmNoticeImporter::class, function ($mock) {
            $mock->shouldReceive('fetch')->once()->andThrow(new \RuntimeException('Connection failed'));
        });
        $response = $this->from(route('governmentNotices'))->followingRedirects()
            ->post(route('governmentNotices.fetch'))->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'The official website could not be checked. Try again later.'));

        $this->mock(DotmNoticeImporter::class, function ($mock) {
            $mock->shouldReceive('fetch')->once()->andReturn(['created' => 0, 'duplicates' => 2]);
        });
        $response = $this->from(route('governmentNotices'))->followingRedirects()
            ->post(route('governmentNotices.fetch'))->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'Official source checked: 0 new, 2 already known.'));
    }

    public function test_importer_collects_relevant_official_links_and_ignores_results(): void
    {
        config(['services.dotm.listing_urls' => []]);
        Http::fake(['https://dotm.gov.np/' => Http::response('<html><body><a href="/license-notice/1">सवारी चालक अनुमतिपत्र सम्बन्धी सूचना</a><a href="/procurement">फर्निचर बोलपत्र</a><a href="/results">चालक परीक्षाको नतिजा</a><a href="https://example.com/license">Driving license notice</a></body></html>')]);
        $result = app(DotmNoticeImporter::class)->fetch();

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('government_notice_imports', ['source_url' => 'https://dotm.gov.np/license-notice/1', 'status' => 'Pending']);
        $this->assertDatabaseMissing('government_notice_imports', ['source_url' => 'https://example.com/license']);
    }

    public function test_repeated_fetch_does_not_duplicate_imports(): void
    {
        config(['services.dotm.listing_urls' => []]);
        Http::fake(['https://dotm.gov.np/' => fn () => Http::response('<a href="/license">Driving license notice</a>')]);
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

    /**
     * Fake the DoTM site by full URL (query string included — URL-keyed
     * Http fakes match without the query, so they cannot tell ?page=2
     * apart from page 1). Map URL => [body, status]; unmapped URLs get
     * an empty page.
     */
    private function fakeDotmPages(array $pages): void
    {
        Http::fake(function ($request) use ($pages) {
            $uri = (string) $request->toPsrRequest()->getUri();
            [$body, $status] = $pages[$uri] ?? ['<html><body>No notices</body></html>', 200];

            return Http::response($body, $status);
        });
    }

    private function listingConfig(string $listingUrl, int $maxPages = 1): void
    {
        config([
            'services.dotm.listing_urls' => [$listingUrl],
            'services.dotm.listing_max_pages' => $maxPages,
        ]);
    }

    public function test_listing_page_creates_import_for_link_missing_from_homepage(): void
    {
        $this->listingConfig('https://dotm.gov.np/category/details-of-printed-licenses/');
        $this->fakeDotmPages([
            'https://dotm.gov.np/category/details-of-printed-licenses/' => ['<html><body><a href="/content/75/regular-print-license-pokhara/">यातायात व्यवस्था कार्यालय, पोखरा (कास्की) को नियमित तर्फ छपाई भई कार्यालय पठाइएका सवारी चालक अनुमतिपत्र (लाईसेन्स)हरुको विवरण ।</a></body></html>', 200],
        ]);

        $result = app(DotmNoticeImporter::class)->fetch();

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('government_notice_imports', ['source_url' => 'https://dotm.gov.np/content/75/regular-print-license-pokhara/', 'status' => 'Pending']);
    }

    public function test_listing_page_skips_already_imported_link(): void
    {
        $this->listingConfig('https://dotm.gov.np/category/details-of-printed-licenses/');
        $title = 'यातायात व्यवस्था कार्यालय, पोखरा (कास्की) को नियमित तर्फ छपाई भई कार्यालय पठाइएका सवारी चालक अनुमतिपत्र (लाईसेन्स)हरुको विवरण ।';
        $url = 'https://dotm.gov.np/content/75/regular-print-license-pokhara/';
        GovernmentNoticeImport::create(['source_name' => 'Department of Transport Management, Nepal', 'source_url' => $url, 'source_domain' => 'dotm.gov.np', 'title' => $title, 'content_hash' => hash('sha256', $url.'|'.$title), 'status' => 'Pending', 'fetched_at' => now()]);
        $this->fakeDotmPages([
            'https://dotm.gov.np/category/details-of-printed-licenses/' => ['<html><body><a href="/content/75/regular-print-license-pokhara/">'.$title.'</a></body></html>', 200],
        ]);

        $result = app(DotmNoticeImporter::class)->fetch();

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, GovernmentNoticeImport::count());
    }

    public function test_listing_pagination_follows_next_pages(): void
    {
        $this->listingConfig('https://dotm.gov.np/category/details-of-printed-licenses/', 3);
        $this->fakeDotmPages([
            'https://dotm.gov.np/category/details-of-printed-licenses/' => ['<html><body><a href="/content/75/regular-print-license-pokhara/">यातायात व्यवस्था कार्यालय, पोखरा (कास्की) को नियमित तर्फ छपाई भई कार्यालय पठाइएका सवारी चालक अनुमतिपत्र (लाईसेन्स)हरुको विवरण ।</a></body></html>', 200],
            'https://dotm.gov.np/category/details-of-printed-licenses/?page=2' => ['<html><body><a href="/content/82/regular-print-license-kalaiya/">यातायात व्यवस्था कार्यालय, कलैया (बारा)को नियमित तर्फ छपाई भई कार्यालय पठाइएका सवारी चालक अनुमतिपत्र (लाईसेन्स)हरुको विवरण ।</a></body></html>', 200],
            'https://dotm.gov.np/category/details-of-printed-licenses/?page=3' => ['<html><body><a href="/content/75/regular-print-license-pokhara/">यातायात व्यवस्था कार्यालय, पोखरा (कास्की) को नियमित तर्फ छपाई भई कार्यालय पठाइएका सवारी चालक अनुमतिपत्र (लाईसेन्स)हरुको विवरण ।</a></body></html>', 200],
        ]);

        $result = app(DotmNoticeImporter::class)->fetch();

        $this->assertSame(2, $result['created']);
        $this->assertDatabaseHas('government_notice_imports', ['source_url' => 'https://dotm.gov.np/content/75/regular-print-license-pokhara/']);
        $this->assertDatabaseHas('government_notice_imports', ['source_url' => 'https://dotm.gov.np/content/82/regular-print-license-kalaiya/']);
        Http::assertSent(fn ($request) => str_contains((string) $request->toPsrRequest()->getUri(), 'page=3'));
    }

    public function test_listing_pagination_stops_at_404_without_failing_the_run(): void
    {
        $this->listingConfig('https://dotm.gov.np/category/fare-rate/', 3);
        $this->fakeDotmPages([
            'https://dotm.gov.np/category/fare-rate/' => ['<html><body><a href="/content/128/adjusted-fares-for-electric-powered-vehicles/">विद्युतीय शक्तिबाट सञ्चालन हुने सवारी साधनको समायोजित भाडादर सम्बन्धी चालक सूचना</a></body></html>', 200],
            'https://dotm.gov.np/category/fare-rate/?page=2' => ['', 404],
        ]);

        $result = app(DotmNoticeImporter::class)->fetch();

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('government_notice_imports', ['source_url' => 'https://dotm.gov.np/content/128/adjusted-fares-for-electric-powered-vehicles/']);
        Http::assertNotSent(fn ($request) => str_contains((string) $request->toPsrRequest()->getUri(), 'page=3'));
    }

    public function test_failed_listing_page_is_reported_and_other_sources_still_run(): void
    {
        $this->listingConfig('https://dotm.gov.np/category/fare-rate/', 1);
        $this->fakeDotmPages([
            'https://dotm.gov.np/' => ['<html><body><a href="/content/134/regarding-the-postponement-of-the-pre-published-notice-/">पुर्व-प्रकाशित सूचना स्थगन गरिएको सम्बन्धमा। चालक अनुमतिपत्र सूचना</a></body></html>', 200],
            'https://dotm.gov.np/category/fare-rate/' => ['', 500],
        ]);

        try {
            app(DotmNoticeImporter::class)->fetch();
            $this->fail('A failed listing page was silently skipped.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('https://dotm.gov.np/category/fare-rate/', $exception->getMessage());
        }

        $this->assertDatabaseHas('government_notice_imports', ['source_url' => 'https://dotm.gov.np/content/134/regarding-the-postponement-of-the-pre-published-notice-/']);
        $this->assertDatabaseHas('content_source_runs', ['source' => 'government-notices', 'status' => 'Failed']);
    }

    public function test_listing_fetches_use_configured_certificate_bundle(): void
    {
        config(['official-content.ca_bundle' => '/trusted/ca.pem']);
        $this->listingConfig('https://dotm.gov.np/category/fare-rate/', 1);
        Http::fake(function ($request, $options) {
            $this->assertSame('/trusted/ca.pem', $options['verify']);

            return Http::response('<html><body>No notices</body></html>');
        });

        $this->assertSame(0, app(DotmNoticeImporter::class)->fetch()['created']);
        Http::assertSentCount(2);
    }
}
