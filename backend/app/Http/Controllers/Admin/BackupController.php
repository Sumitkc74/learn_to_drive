<?php
namespace App\Http\Controllers\Admin;
class BackupController extends \App\Http\Controllers\Controller {
    public function index(\App\Services\LocalBackup $backup){$backups=$backup->entries();return view('admin.workflow.backups',compact('backups'));}
    public function create(\App\Services\LocalBackup $backup){
        try {$backup->create();return back()->with('success','Backup created and verified in isolated storage.');}
        catch(\Throwable $e){return back()->withErrors(['backup'=>'Backup failed. Check PHP ZIP, disk space, and file availability; retry during a quiet period.']);}
    }
    public function verify(string $id,\App\Services\LocalBackup $backup){
        try {$backup->verify($id);return back()->with('success','Backup integrity and isolated database restore verified.');}
        catch(\Throwable $e){return back()->withErrors(['backup'=>'Verification failed. Keep the backup and investigate before relying on it.']);}
    }
}
