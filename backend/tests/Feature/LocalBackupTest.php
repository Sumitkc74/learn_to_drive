<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class LocalBackupTest extends TestCase {
    use DatabaseMigrations;
    public function test_backup_round_trip_verifies_files_and_detects_tampering(): void {
        if(!class_exists(\ZipArchive::class)) $this->markTestSkipped('ZIP extension required.');
        foreach(['public','protected-media','learning-content'] as $disk) Storage::fake($disk);
        Storage::disk('public')->put('example.txt','Test bytes');
        $backup=new class extends \App\Services\LocalBackup {
            private ?string $folder=null;
            public function root(): string {
                if(!$this->folder){$this->folder=storage_path('framework/testing/backup-'.\Illuminate\Support\Str::uuid());mkdir($this->folder,0700,true);}return $this->folder;
            }
        };
        $info=$backup->create();$this->assertSame('Verified',$info['status']);
        $this->assertSame('Verified',$backup->verify($info['id'])['status']);
        file_put_contents($backup->root().'/'.$info['id'].'.zip','damaged');
        $this->expectException(\RuntimeException::class);$this->expectExceptionMessage('checksum mismatch');$backup->verify($info['id']);
    }
}
