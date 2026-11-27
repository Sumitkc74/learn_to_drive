<?php
namespace App\Http\Controllers\Admin;
class BackupController extends \App\Http\Controllers\Controller {
    public function index(\App\Services\LocalBackup $backup){$backups=$backup->entries();return view('admin.workflow.backups',compact('backups'));}
    public function create(\App\Services\LocalBackup $backup){
        try {$info=$backup->create();return back()->with('success',($info['restore_verified'] ?? false) ? 'Backup created and verified in isolated storage.' : 'Backup created and verified.');}
        catch(\Throwable $e){return back()->withErrors(['backup'=>'Backup failed. Check PHP ZIP, disk space, and file availability; retry during a quiet period.']);}
    }
    public function download(string $id, \App\Services\LocalBackup $backup){
        $path=$backup->downloadPath($id);
        if($path===null) abort(404);
        return response()->download($path,'learn-to-drive-backup-'.$id.'.zip');
    }
    public function verify(string $id,\App\Services\LocalBackup $backup){
        try {$info=$backup->verify($id);return back()->with('success',($info['restore_verified'] ?? false) ? 'Backup integrity and isolated database restore verified.' : 'Backup integrity verified (database dump checked, not test-restored).');}
        catch(\Throwable $e){return back()->withErrors(['backup'=>'Verification failed. Keep the backup and investigate before relying on it.']);}
    }
}
