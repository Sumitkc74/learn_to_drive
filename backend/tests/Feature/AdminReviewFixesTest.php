<?php

namespace Tests\Feature;

use App\Jobs\ScreenImportedContent;
use App\Models\AuditLog;
use App\Models\DrivingLesson;
use App\Models\DrivingSchool;
use App\Models\GovernmentNoticeImport;
use App\Models\LearningContentImport;
use App\Models\LessonBooking;
use App\Models\LessonVehicle;
use App\Models\SchoolLessonPayment;
use App\Models\User;
use App\Services\ContentAiScreening;
use App\Services\DotmNoticeImporter;
use App\Services\LearningContentDownload;
use App\Services\LocalBackup;
use App\Services\OfficialContentPreview;
use App\Services\RedirectFollowingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class AdminReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('is_seed_admin', true)->firstOrFail();
    }

    private function notice(array $overrides = []): GovernmentNoticeImport
    {
        $screening = [];
        foreach (['ai_status', 'ai_retry_count', 'ai_activity_at', 'ai_report'] as $key) {
            if (array_key_exists($key, $overrides)) {
                $screening[$key] = $overrides[$key];
                unset($overrides[$key]);
            }
        }
        $record = GovernmentNoticeImport::create(array_merge([
            'source_name' => 'Official', 'source_domain' => 'example.org',
            'source_url' => 'https://example.org/notice-'.Str::random(8), 'title' => 'Notice',
            'content_hash' => hash('sha256', 'notice-'.Str::random(8)),
            'status' => 'Pending', 'fetched_at' => now(),
        ], $overrides));
        if ($screening !== []) {
            $record->forceFill($screening)->save();
        }

        return $record;
    }

    public function test_dashboard_renders_with_live_like_data(): void
    {
        $admin = $this->admin();
        $learner = User::factory()->create(['role' => 'User']);
        $school = DrivingSchool::create(['name' => 'Declined School', 'address' => 'Kathmandu', 'status' => 'Pending']);
        $school->forceFill(['rates_declined_at' => now(), 'rates_declined_reason' => 'Too high', 'rates_declined_by' => $admin->id])->save();
        $teacher = User::factory()->create(['role' => 'User']);
        $car = LessonVehicle::create(['driving_school_id' => $school->id, 'instructor_id' => $teacher->id, 'name' => 'Car', 'registration' => 'TEST-1', 'transmission' => 'Manual']);
        $lesson = DrivingLesson::create(['lesson_vehicle_id' => $car->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'price_npr' => 1500, 'meeting_point' => 'School']);
        $booking = LessonBooking::create(['driving_lesson_id' => $lesson->id, 'learner_id' => $learner->id, 'active_lesson_id' => $lesson->id]);
        SchoolLessonPayment::forceCreate(['id' => (string) Str::uuid(), 'lesson_booking_id' => $booking->id, 'amount_paisa' => 150000, 'gateway' => 'khalti', 'live' => false, 'status' => 'Paid', 'paid_at' => now()]);
        $this->notice(['ai_status' => 'Failed']);
        $this->notice(['ai_status' => 'Flagged']);

        $this->actingAs($admin)->get(route('adminDashboard'))
            ->assertOk()
            ->assertSee('Declined rate proposals: 1')
            ->assertSee('Driving schools')
            ->assertSee('Premium users')
            ->assertSee('Failed AI checks: 1');
    }

    public function test_dashboard_degrades_when_a_table_is_missing(): void
    {
        // A missing column cannot be reproduced on the sqlite test DB:
        // SQLite's double-quoted-string fallback turns a quoted missing
        // column into a string literal instead of an error, so the query
        // returns rows instead of throwing (live MySQL uses backticks and
        // would throw). A missing table does throw, so it proves the same
        // resilience guard: one failing panel degrades instead of 500ing.
        Schema::drop('lesson_bookings');

        $this->actingAs($this->admin())->get(route('adminDashboard'))
            ->assertOk()
            ->assertSee('Total users')
            ->assertSee('<div class="ltd-stat-card__value">—</div><div class="ltd-stat-card__label">Lesson bookings</div>', false);
    }

    public function test_backups_page_loads_and_backup_creation_verifies_on_sqlite(): void
    {
        $this->actingAs($this->admin())->get(route('backups'))->assertOk();
        // VACUUM INTO (used for the sqlite snapshot) cannot run inside a
        // transaction, and RefreshDatabase wraps every test in one, so end
        // it before creating the backup. The in-memory test DB is discarded
        // with the test, so nothing leaks to other tests.
        DB::connection()->commit();
        $this->actingAs($this->admin())->post(route('backups.create'))->assertRedirect();
        $verified = collect(app(LocalBackup::class)->entries())->firstWhere('status', 'Verified');
        $this->assertNotNull($verified, 'Expected a verified backup entry.');
        $this->assertTrue($verified['restore_verified'] ?? false);
        $this->actingAs($this->admin())->get(route('backups'))->assertOk();
    }

    public function test_mysql_style_backup_verifies_by_structure(): void
    {
        $backup = app(LocalBackup::class);
        $root = $backup->root();
        $id = (string) Str::uuid();
        $sql = "-- dump\nCREATE TABLE `users` (`id` integer);\nCREATE TABLE `media` (`id` integer);\n";
        $zipPath = $root.'/'.$id.'.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::EXCL);
        $zip->addFromString('database.sql', $sql);
        $manifest = ['database.sql' => hash('sha256', $sql), 'database_tables' => ['users', 'media'], 'database_rows' => ['users' => 0, 'media' => 0]];
        $zip->addFromString('manifest.json', json_encode($manifest));
        $zip->close();
        file_put_contents($root.'/'.$id.'.json', json_encode(['id' => $id, 'created_at' => now()->toIso8601String(), 'status' => 'Created', 'verified_at' => null, 'driver' => 'mysql', 'sha256' => hash_file('sha256', $zipPath)]));

        $info = $backup->verify($id);
        $this->assertSame('Verified', $info['status']);
        $this->assertFalse($info['restore_verified']);

        $id2 = (string) Str::uuid();
        $incomplete = "-- dump\nCREATE TABLE `users` (`id` integer);\n";
        $zipPath2 = $root.'/'.$id2.'.zip';
        $zip2 = new ZipArchive();
        $zip2->open($zipPath2, ZipArchive::CREATE | ZipArchive::EXCL);
        $zip2->addFromString('database.sql', $incomplete);
        $manifest2 = ['database.sql' => hash('sha256', $incomplete), 'database_tables' => ['users', 'media'], 'database_rows' => ['users' => 0, 'media' => 0]];
        $zip2->addFromString('manifest.json', json_encode($manifest2));
        $zip2->close();
        file_put_contents($root.'/'.$id2.'.json', json_encode(['id' => $id2, 'created_at' => now()->toIso8601String(), 'status' => 'Created', 'verified_at' => null, 'driver' => 'mysql', 'sha256' => hash_file('sha256', $zipPath2)]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('missing table media');
        $backup->verify($id2);
    }

    public function test_storage_check_scan_completes_and_writes_report(): void
    {
        $path = storage_path('app/private/storage-check.json');
        if (is_file($path)) {
            unlink($path);
        }
        $this->actingAs($this->admin())->post(route('storageCheck.scan'))->assertRedirect();
        $this->assertFileExists($path);
        $report = json_decode(file_get_contents($path), true);
        $this->assertArrayHasKey('issues', $report);
        $this->assertArrayHasKey('checked_at', $report);
    }

    public function test_boot_creates_the_storage_directories_the_app_writes_to(): void
    {
        foreach (['storage/app/public', 'storage/app/private/media', 'storage/app/private/learning-content', 'storage/app/private/backups'] as $dir) {
            $this->assertStringContainsString($dir, file_get_contents(base_path('docker/entrypoint.sh')));
            $this->assertStringContainsString($dir, file_get_contents(base_path('Dockerfile')));
        }
        $backups = storage_path('app/private/backups');
        File::deleteDirectory($backups);
        $this->assertSame($backups, app(LocalBackup::class)->root());
        $this->assertDirectoryExists($backups);
    }

    public function test_redirects_are_followed_only_to_allowlisted_hosts(): void
    {
        Http::fake(function ($request) {
            if ($request->url() === 'https://www.dotm.gov.np/start') {
                return Http::response('', 301, ['Location' => 'https://dotm.gov.np/final']);
            }

            return Http::response('ok', 200);
        });
        $allowed = fn ($url) => in_array(strtolower((string) parse_url($url, PHP_URL_HOST)), ['dotm.gov.np', 'www.dotm.gov.np'], true);
        // allow_redirects must stay false per hop, or Guzzle follows the
        // redirect internally and the helper never sees the 301.
        $fetch = fn ($url) => Http::withOptions(['allow_redirects' => false])->get($url);
        $response = app(RedirectFollowingRequest::class)->get('https://www.dotm.gov.np/start', $allowed, $fetch);
        $this->assertSame(200, $response->status());
        $this->assertSame('ok', $response->body());
        Http::assertSentCount(2);
    }

    public function test_redirect_to_an_unlisted_host_is_refused(): void
    {
        Http::fake(fn ($request) => $request->url() === 'https://www.dotm.gov.np/start'
            ? Http::response('', 301, ['Location' => 'https://evil.example/final'])
            : Http::response('ok', 200));
        $allowed = fn ($url) => in_array(strtolower((string) parse_url($url, PHP_URL_HOST)), ['dotm.gov.np', 'www.dotm.gov.np'], true);
        $fetch = fn ($url) => Http::withOptions(['allow_redirects' => false])->get($url);
        try {
            app(RedirectFollowingRequest::class)->get('https://www.dotm.gov.np/start', $allowed, $fetch);
            $this->fail('Redirect to an unlisted host was followed.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('not on the official allowlist', $e->getMessage());
        }
        Http::assertSentCount(1);
    }

    public function test_redirect_chains_stop_after_five_hops(): void
    {
        Http::fake(fn () => Http::response('', 301, ['Location' => 'https://dotm.gov.np/next']));
        $fetch = fn ($url) => Http::withOptions(['allow_redirects' => false])->get($url);
        try {
            app(RedirectFollowingRequest::class)->get('https://dotm.gov.np/start', fn () => true, $fetch);
            $this->fail('Redirect chain was not capped.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Too many redirects', $e->getMessage());
        }
        Http::assertSentCount(6);
    }

    public function test_official_preview_follows_a_redirect_on_an_approved_host(): void
    {
        Http::fake(function ($request) {
            if ($request->url() === 'https://www.dotm.gov.np/page') {
                return Http::response('', 301, ['Location' => 'https://dotm.gov.np/page']);
            }

            return Http::response('<html><body><a href="/bank.pdf">Bank</a></body></html>', 200, ['Content-Type' => 'text/html']);
        });
        $result = app(OfficialContentPreview::class)->fetch(['url' => 'https://www.dotm.gov.np/page', 'hosts' => ['www.dotm.gov.np', 'dotm.gov.np'], 'kind' => 'question-bank-document', 'name' => 'DoTM']);
        $this->assertCount(1, $result['assets']);
        $this->assertSame('https://www.dotm.gov.np/bank.pdf', $result['assets'][0]['asset_url']);
    }

    public function test_learning_download_follows_a_redirect_on_an_approved_host(): void
    {
        Storage::fake('learning-content');
        Http::fake(function ($request) {
            if ($request->url() === 'https://www.dotm.gov.np/bank.pdf') {
                return Http::response('', 301, ['Location' => 'https://dotm.gov.np/bank.pdf']);
            }

            return Http::response("%PDF-1.4\n%%EOF", 200, ['Content-Type' => 'application/pdf']);
        });
        $resource = LearningContentImport::create(['source_key' => 'dotm-b', 'source_name' => 'DoTM', 'source_url' => 'https://www.dotm.gov.np/page', 'asset_url' => 'https://www.dotm.gov.np/bank.pdf', 'url_hash' => hash('sha256', 'dotm-b-bank'), 'kind' => 'question-bank-document', 'title' => 'Bank', 'fetched_at' => now()]);
        $file = app(LearningContentDownload::class)->fetch($resource);
        Storage::disk('learning-content')->assertExists($file['file_path']);
        $this->assertSame('application/pdf', $file['mime_type']);
    }

    public function test_dotm_importer_follows_a_redirect_on_an_approved_host(): void
    {
        Http::fake(function ($request) {
            if ($request->url() === 'https://dotm.gov.np/') {
                return Http::response('', 301, ['Location' => 'https://www.dotm.gov.np/']);
            }

            return Http::response('<html><body><a href="https://dotm.gov.np/notice-1">Driving license notice</a></body></html>', 200, ['Content-Type' => 'text/html']);
        });
        $result = app(DotmNoticeImporter::class)->fetch();
        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('government_notice_imports', ['source_url' => 'https://dotm.gov.np/notice-1']);
    }

    public function test_government_notice_counts_agree_across_both_pages(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->notice(['ai_status' => 'Failed']);
        }
        for ($i = 0; $i < 31; $i++) {
            $this->notice(['ai_status' => 'Flagged']);
        }
        $this->notice(['ai_status' => 'Ready']);
        $this->notice(['ai_status' => 'Failed', 'status' => 'Approved']);

        $this->actingAs($this->admin())->get(route('governmentNotices'))
            ->assertOk()
            ->assertSee('AI screening (awaiting decision): 6 Failed · 31 Flagged · 1 Ready · 0 Deferred · 0 in progress · 0 not screened (38 awaiting decision)');
        $this->actingAs($this->admin())->get(route('reviewOperations', ['type' => 'government']))
            ->assertOk()
            ->assertSee('AI screening (awaiting decision): 6 Failed · 31 Flagged · 1 Ready · 0 Deferred · 0 in progress · 0 not screened (38 awaiting decision)');
    }

    public function test_school_status_filter_is_case_insensitive(): void
    {
        DrivingSchool::create(['name' => 'Approved School', 'address' => 'Kathmandu', 'status' => 'Approved']);
        DrivingSchool::create(['name' => 'Pending School', 'address' => 'Lalitpur', 'status' => 'Pending']);

        $this->actingAs($this->admin())->get(route('admin.schools.index', ['status' => 'approved']))
            ->assertOk()->assertSee('Approved School')->assertDontSee('Pending School');
        $this->actingAs($this->admin())->get(route('admin.schools.index', ['status' => 'PENDING']))
            ->assertOk()->assertSee('Pending School')->assertDontSee('Approved School');
    }

    public function test_school_payments_shows_an_empty_state(): void
    {
        $this->actingAs($this->admin())->get(route('admin.school-payments'))
            ->assertOk()->assertSee('No school lesson payments yet.');
    }

    public function test_one_screening_writes_one_audit_entry(): void
    {
        $record = $this->notice();
        app(ContentAiScreening::class)->screen($record);
        $this->assertSame('Failed', $record->fresh()->ai_status);
        $this->assertSame(1, AuditLog::where('subject_type', 'GovernmentNoticeImport')->where('subject_id', $record->id)->where('event', 'updated')->count());
    }

    public function test_exhausted_retries_are_not_auto_recovered_but_manual_retry_works(): void
    {
        Queue::fake();
        $exhausted = $this->notice(['ai_status' => 'Failed', 'ai_retry_count' => 3, 'ai_activity_at' => now()->subHour()]);
        $retryable = $this->notice(['ai_status' => 'Failed', 'ai_retry_count' => 2, 'ai_activity_at' => now()->subHour()]);

        $this->artisan('content:recover-screening')->assertExitCode(0);

        $this->assertSame('Failed', $exhausted->fresh()->ai_status);
        $this->assertSame(3, $exhausted->fresh()->ai_retry_count);
        $this->assertSame('Queued', $retryable->fresh()->ai_status);
        $this->assertSame(3, $retryable->fresh()->ai_retry_count);

        $this->actingAs($this->admin())->post(route('contentScreening.retry', ['kind' => 'notice', 'id' => $exhausted->id]))
            ->assertRedirect();
        $this->assertSame('Queued', $exhausted->fresh()->ai_status);
        $this->assertSame(0, $exhausted->fresh()->ai_retry_count);
        Queue::assertPushed(ScreenImportedContent::class, fn ($job) => $job->kind === 'notice' && $job->id === $exhausted->id);
    }
}
