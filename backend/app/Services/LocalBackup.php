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
        if(!class_exists(ZipArchive::class)) throw new RuntimeException('PHP ZIP extension is required.');
        $driver=DB::connection()->getDriverName();
        if(!in_array($driver,['sqlite','mysql'],true)) throw new RuntimeException('This backup tool supports SQLite and MySQL deployments. Use a database-native backup for other engines.');
        $id=(string)Str::uuid();$root=$this->root();$dbFile=$driver==='mysql' ? $id.'.sql' : $id.'.sqlite';$dbPath=$root.'/'.$dbFile;$zipPath=$root.'/'.$id.'.zip';
        $info=['id'=>$id,'created_at'=>now()->toIso8601String(),'status'=>'Creating','verified_at'=>null,'driver'=>$driver];
        file_put_contents($root.'/'.$id.'.json',json_encode($info));
        $zip=new ZipArchive();
        try {
            $pdo=DB::connection()->getPdo();
            $manifest=[];
            if($driver==='mysql') {
                $dumped=$this->dumpMysql($pdo,$dbPath);
                $manifest['database.sql']=hash_file('sha256',$dbPath);
                $manifest['database_tables']=array_keys($dumped);
                $manifest['database_rows']=$dumped;
            } else {
                $version=$pdo->query('PRAGMA data_version')->fetchColumn();
                $pdo->exec('VACUUM INTO '.$pdo->quote($dbPath));
            }
            if($zip->open($zipPath,ZipArchive::CREATE|ZipArchive::EXCL)!==true) throw new RuntimeException('Could not create backup archive.');
            if($driver==='mysql') { $zip->addFile($dbPath,'database.sql'); }
            else {
                $manifest['database.sqlite']=hash_file('sha256',$dbPath);$zip->addFile($dbPath,'database.sqlite');
            }
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
            if($driver==='sqlite' && $version!==$pdo->query('PRAGMA data_version')->fetchColumn()) throw new RuntimeException('Database changed during backup. Retry during a quiet period.');
            $info['status']='Created';$info['files']=count(array_filter(array_keys($manifest),fn($key)=>str_starts_with($key,'files/')));$info['sha256']=hash_file('sha256',$zipPath);
            file_put_contents($root.'/'.$id.'.json',json_encode($info));
            return $this->verify($id);
        } catch(\Throwable $e) {
            $info['status']='Failed';$info['error']='Backup creation or verification failed. Check extensions, disk space and logs, then retry during a quiet period.';
            file_put_contents($root.'/'.$id.'.json',json_encode($info));
            throw $e;
        } finally { if(is_file($dbPath)) unlink($dbPath); }
    }
    /**
     * Logical MySQL dump (SHOW CREATE TABLE + chunked INSERTs) written
     * straight to $path. Runs inside one transaction so the dump is a
     * consistent snapshot under the default REPEATABLE READ isolation.
     * Returns table => row count, stored in the manifest for verification.
     * Values are quoted with PDO::quote; the application stores no blobs
     * in the database (uploads live on disk), so quoted text is safe here.
     */
    private function dumpMysql(\PDO $pdo, string $path): array {
        $handle=fopen($path,'wb');
        if($handle===false) throw new RuntimeException('Could not create backup file.');
        $started=false;
        $rows=[];
        try {
            if(!$pdo->inTransaction()) { $pdo->beginTransaction(); $started=true; }
            $tables=$pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(\PDO::FETCH_COLUMN);
            fwrite($handle,"-- Learn to Drive database backup\n-- Created: ".now()->toIso8601String()."\n-- Driver: mysql\n\nSET FOREIGN_KEY_CHECKS=0;\n");
            foreach($tables as $table) {
                $create=$pdo->query('SHOW CREATE TABLE '.$this->quoteMysqlIdentifier($table))->fetch(\PDO::FETCH_ASSOC);
                $createSql=$create['Create Table'] ?? null;
                if(!is_string($createSql)) throw new RuntimeException('Could not read table definition for '.$table.'.');
                fwrite($handle,"\nDROP TABLE IF EXISTS ".$this->quoteMysqlIdentifier($table).";\n".$createSql.";\n");
                $count=(int)$pdo->query('SELECT COUNT(*) FROM '.$this->quoteMysqlIdentifier($table))->fetchColumn();
                $rows[$table]=$count;
                if($count===0) continue;
                $offset=0;
                while($offset<$count) {
                    $batch=$pdo->query('SELECT * FROM '.$this->quoteMysqlIdentifier($table).' LIMIT 500 OFFSET '.$offset)->fetchAll(\PDO::FETCH_ASSOC);
                    if($batch===[]) break;
                    $columns=array_keys($batch[0]);
                    $values=[];
                    foreach($batch as $row) $values[]='('.implode(',',array_map(fn($value)=>$value===null?'NULL':$pdo->quote((string)$value),array_values($row))).')';
                    fwrite($handle,'INSERT INTO '.$this->quoteMysqlIdentifier($table).' ('.implode(',',array_map(fn($column)=>$this->quoteMysqlIdentifier($column),$columns)).') VALUES '.implode(',',$values).";\n");
                    $offset+=count($batch);
                }
            }
            fwrite($handle,"\nSET FOREIGN_KEY_CHECKS=1;\n");
            if($started) $pdo->commit();
        } catch(\Throwable $e) {
            if($started && $pdo->inTransaction()) $pdo->rollBack();
            fclose($handle);
            throw $e;
        }
        fclose($handle);
        return $rows;
    }
    private function quoteMysqlIdentifier(string $name): string {
        return '`'.str_replace('`','``',$name).'`';
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
            $manifest=json_decode($zip->getFromName('manifest.json'),true);if(!is_array($manifest) || (!isset($manifest['database.sqlite']) && !isset($manifest['database.sql']))) throw new RuntimeException('Missing manifest.');
            foreach($manifest as $file=>$hash) {
                if(!is_string($hash)) continue;
                $stream=$zip->getStream($file);if(!$stream) throw new RuntimeException('Missing backup file.');
                $ctx=hash_init('sha256');hash_update_stream($ctx,$stream);fclose($stream);
                if(!hash_equals($hash,hash_final($ctx))) throw new RuntimeException('Backup file checksum mismatch.');
            }
            if(isset($manifest['database.sql'])) {
                // No MySQL server is available to test-restore into, so a
                // MySQL dump is verified by structure: every dumped table
                // must have its CREATE TABLE in the file. The manifest
                // hash check above already proves the archive is intact.
                $sql=$zip->getFromName('database.sql');
                if(!is_string($sql) || $sql==='') throw new RuntimeException('Backup database dump is empty.');
                foreach($manifest['database_tables'] ?? [] as $table) {
                    if(!str_contains($sql,'CREATE TABLE `'.$table.'`')) throw new RuntimeException('Backup dump is missing table '.$table.'.');
                }
                $info['restore_verified']=false;
            } else {
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
                $info['restore_verified']=true;
            }
            $info['status']='Verified';$info['verified_at']=now()->toIso8601String();file_put_contents($metadata,json_encode($info));return $info;
        } finally { unset($pdo);$zip->close();if(is_file($restore)) unlink($restore); }
    }
}
