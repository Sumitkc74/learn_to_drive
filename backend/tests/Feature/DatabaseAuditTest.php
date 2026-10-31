<?php

namespace Tests\Feature;

use App\Models\{Tutorial, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Artisan, DB};
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DatabaseAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_audit_reports_candidates_without_disclosing_values_or_changing_data(): void
    {
        foreach (['Private title', ' private title '] as $title) Tutorial::create(['title' => $title, 'description' => 'Different material', 'videoLink' => 'https://example.com']);
        $this->assertSame(0, Artisan::call('database:audit-duplicates', ['--json' => true]));
        $output = Artisan::output();
        $report = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        $tutorial = collect($report['tables'])->firstWhere('table', 'tutorials');
        $this->assertSame(1, $tutorial['candidate_groups']);
        $this->assertSame(1, $tutorial['extra_rows']);
        $this->assertStringNotContainsString('Private title', $output);
        $this->assertSame(2, Tutorial::count());
    }

    public function test_import_duplicate_lookups_are_batched_and_trim_existing_names(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        Tutorial::create(['title' => ' Reused title ', 'description' => 'Keep this content', 'videoLink' => 'https://example.com']);
        $count = 0;
        DB::listen(function ($query) use (&$count) {
            if (str_contains($query->sql, 'LOWER(TRIM(') && str_contains($query->sql, 'tutorials')) $count++;
        });
        $csv = "title,description,videoLink,attachment\nReused title,Details,https://example.com,image.png\nNew title,Details,https://example.com,image.png\n";
        $this->actingAs($admin)->post(route('bulkImport.store', 'tutorials'), [
            'file' => UploadedFile::fake()->createWithContent('tutorials.csv', $csv),
            'attachments' => [UploadedFile::fake()->image('image.png')],
        ])->assertSessionHasNoErrors()->assertSessionHas('import_summary.duplicate', 1)->assertSessionHas('import_summary.added', 1);
        $this->assertSame(1, $count);
        $this->assertSame(2, Tutorial::count());
    }
}
