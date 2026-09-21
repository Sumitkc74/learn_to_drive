<?php
namespace App\Services;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;
class LocalBackup {
    public function root(): string { $root=storage_path('app/private/backups'); if(!is_dir($root)) mkdir($root,0700,true); return $root; }
    public function entries(): array {
        $items=[]; foreach(glob($this->root().'/*.json') as $path) $items[]=json_decode(file_get_contents($path),true);
        usort($items,fn($a,$b)=>strcmp($b['created_at'],$a['created_at'])); return $items;
    }
    public function create(): array {
        if(DB::connection()->getDriverName()!=='sqlite') throw new RuntimeException('This backup tool supports the current SQLite deployment. Use a database-native backup for other engines.');
        if(!class_exists(ZipArchive::class)) throw new RuntimeException('PHP ZIP extension is required.');
        $id=(string)Str::uuid();$root=$this->root();$dbPath=$root.'/'.$id.'.sqlite';$zipPath=$root.'/'.$id.'.zip';
        $info=['id'=>$id,'created_at'=>now()->toIso8601String(),'status'=>'Creating','verified_at'=>null];
        file_put_contents($root.'/'.$id.'.json',json_encode($info));
        $zip=new ZipArchive();
        try {
            $pdo=DB::connection()->getPdo();$version=$pdo->query('PRAGMA data_version')->fetchColumn();
            $pdo->exec('VACUUM INTO '.$pdo->quote($dbPath));
            if($zip->open($zipPath,ZipArchive::CREATE|ZipArchive::EXCL)!==true) throw new RuntimeException('Could not create backup archive.');
            $manifest=['database.sqlite'=>hash_file('sha256',$dbPath)];$zip->addFile($dbPath,'database.sqlite');
            foreach(['public','protected-media','learning-content'] as $diskName) {
                $disk=Storage::disk($diskName);
                foreach($disk->allFiles() as $file) {
                    if($diskName==='learning-content' && (str_starts_with($file,'pdf-previews/') || str_starts_with($file,'translation-builds/'))) continue;
                    $name='files/'.$diskName.'/'.$file;
                    $manifest[$name]=hash_file('sha256',$disk->path($file));
                    if(!$zip->addFile($disk->path($file),$name)) throw new RuntimeException('Could not add an upload.');
                }
            }
            $zip->addFromString('manifest.json',json_encode($manifest));
            if(!$zip->close()) throw new RuntimeException('Could not finish backup archive.');
            if($version!==$pdo->query('PRAGMA data_version')->fetchColumn()) throw new RuntimeException('Database changed during backup. Retry during a quiet period.');
            $info['status']='Created';$info['files']=count($manifest)-1;$info['sha256']=hash_file('sha256',$zipPath);
            file_put_contents($root.'/'.$id.'.json',json_encode($info));
            return $this->verify($id);
        } catch(\Throwable $e) {
            $info['status']='Failed';$info['error']='Backup creation or verification failed. Check extensions, disk space and logs, then retry during a quiet period.';
            file_put_contents($root.'/'.$id.'.json',json_encode($info));
            throw $e;
        } finally { if(is_file($dbPath)) unlink($dbPath); }
    }
    public function verify(string $id): array {
        try { return $this->verifyArchive($id); }
        catch (\Throwable $e) {
            if (Str::isUuid($id)) {
                $path=$this->root().'/'.$id.'.json';
                if (is_file($path)) {
                    $info=json_decode(file_get_contents($path),true);
                    $info['status']='Verification failed';
                    file_put_contents($path,json_encode($info));
                }
            }
            throw $e;
        }
    }
    private function verifyArchive(string $id): array {
        if(!Str::isUuid($id)) throw new RuntimeException('Invalid backup ID.');
        $root=$this->root();$metadata=$root.'/'.$id.'.json';
        if(!is_file($metadata)) throw new RuntimeException('Backup not found.');
        $info=json_decode(file_get_contents($metadata),true);$path=$root.'/'.$id.'.zip';
        if(!is_file($path) || !isset($info['sha256']) || !hash_equals($info['sha256'],hash_file('sha256',$path))) throw new RuntimeException('Backup checksum mismatch.');
        $zip=new ZipArchive();if($zip->open($path)!==true) throw new RuntimeException('Cannot open backup.');
        $restore=$root.'/verify-'.Str::uuid().'.sqlite';
        try {
            $manifest=json_decode($zip->getFromName('manifest.json'),true);if(!is_array($manifest) || !isset($manifest['database.sqlite'])) throw new RuntimeException('Missing manifest.');
            foreach($manifest as $file=>$hash) {
                $stream=$zip->getStream($file);if(!$stream) throw new RuntimeException('Missing backup file.');
                $ctx=hash_init('sha256');hash_update_stream($ctx,$stream);fclose($stream);
                if(!hash_equals($hash,hash_final($ctx))) throw new RuntimeException('Backup file checksum mismatch.');
            }
            $stream=$zip->getStream('database.sqlite');$output=fopen($restore,'wb');stream_copy_to_stream($stream,$output);fclose($stream);fclose($output);
            $pdo=new \PDO('sqlite:'.$restore);
            if($pdo->query('PRAGMA integrity_check')->fetchColumn()!=='ok') throw new RuntimeException('Restored database integrity check failed.');
            foreach($pdo->query('SELECT id, disk, file_name FROM media') as $media) {
                if(in_array($media['disk'],['public','protected-media']) && !isset($manifest['files/'.$media['disk'].'/'.$media['id'].'/'.$media['file_name']])) throw new RuntimeException('Backup is missing referenced media.');
            }
            foreach($pdo->query('SELECT file_path FROM learning_content_imports WHERE file_path IS NOT NULL') as $record) {
                if(!isset($manifest['files/learning-content/'.$record['file_path']])) throw new RuntimeException('Backup is missing a review file.');
            }
            $pdo=null;
            $info['status']='Verified';$info['verified_at']=now()->toIso8601String();file_put_contents($metadata,json_encode($info));return $info;
        } finally { unset($pdo);$zip->close();if(is_file($restore)) unlink($restore); }
    }
}
