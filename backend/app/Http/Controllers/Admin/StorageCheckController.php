<?php
namespace App\Http\Controllers\Admin;
class StorageCheckController extends \App\Http\Controllers\Controller {
    public function index() {
        $path=storage_path('app/private/storage-check.json');
        $report=is_file($path) ? json_decode(file_get_contents($path),true) : null;
        return view('admin.media.storage-check',compact('report'));
    }
    public function scan(\App\Services\MediaStorageAudit $audit) {
        $report=$audit->scan();
        file_put_contents(storage_path('app/private/storage-check.json'),json_encode($report,JSON_PRETTY_PRINT));
        return redirect()->route('storageCheck')->with('success','Storage checked. No files were changed or deleted.');
    }
}
