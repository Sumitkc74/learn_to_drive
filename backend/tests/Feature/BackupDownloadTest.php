<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LocalBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class BackupDownloadTest extends TestCase
{
    use RefreshDatabase;

    /** @var string[] */
    private array $createdIds = [];

    protected function tearDown(): void
    {
        $backup = app(LocalBackup::class);
        foreach ($this->createdIds as $id) {
            foreach ([$backup->root().'/'.$id.'.zip', $backup->root().'/'.$id.'.json'] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
        parent::tearDown();
    }

    private function fabricateBackup(string $status = 'Verified'): string
    {
        $id = (string) Str::uuid();
        $this->createdIds[] = $id;
        $backup = app(LocalBackup::class);
        $zipPath = $backup->root().'/'.$id.'.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::EXCL);
        $zip->addFromString('manifest.json', json_encode(['database.sql' => 'x']));
        $zip->close();
        file_put_contents($backup->root().'/'.$id.'.json', json_encode([
            'id' => $id,
            'created_at' => now()->toIso8601String(),
            'status' => $status,
            'verified_at' => now()->toIso8601String(),
            'driver' => 'sqlite',
            'sha256' => hash_file('sha256', $zipPath),
        ]));

        return $id;
    }

    public function test_admin_can_download_a_verified_backup(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $id = $this->fabricateBackup('Verified');

        $this->actingAs($admin)
            ->get(route('backups.download', $id))
            ->assertOk()
            ->assertDownload('learn-to-drive-backup-'.$id.'.zip');
    }

    public function test_guest_is_redirected_and_non_admin_is_forbidden(): void
    {
        $id = $this->fabricateBackup('Verified');

        $this->get(route('backups.download', $id))->assertRedirect('/login');

        $user = User::factory()->create(['role' => 'User']);
        $this->actingAs($user)->get(route('backups.download', $id))->assertForbidden();
    }

    public function test_unknown_or_tampered_backup_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)
            ->get(route('backups.download', (string) Str::uuid()))
            ->assertNotFound();

        $this->actingAs($admin)->get(route('backups.download', 'not-a-uuid'))->assertNotFound();

        $id = $this->fabricateBackup('Verified');
        $backup = app(LocalBackup::class);
        file_put_contents(
            $backup->root().'/'.$id.'.json',
            json_encode(['id' => $id, 'status' => 'Verified', 'sha256' => str_repeat('0', 64)])
        );
        $this->actingAs($admin)->get(route('backups.download', $id))->assertNotFound();

        $failedId = $this->fabricateBackup('Failed');
        $this->actingAs($admin)->get(route('backups.download', $failedId))->assertNotFound();
    }
}
