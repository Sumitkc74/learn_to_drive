<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GovernmentNoticeImport;
use App\Models\Notice;
use App\Services\DotmNoticeImporter;
use App\Support\AdminTable;
use Illuminate\Http\Request;

class GovernmentNoticeImportController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->query->has('sort')) $request->query->set('sort', 'fetched_at');
        $imports = AdminTable::paginate(GovernmentNoticeImport::with('reviewer'), $request, ['title', 'source_name', 'source_url'], ['id', 'title', 'status', 'fetched_at', 'reviewed_at'], ['status' => ['allowed' => ['Pending', 'Approved', 'Rejected']]]);
        return view('admin.crud.notices.government-imports', compact('imports'));
    }

    public function fetch(DotmNoticeImporter $importer)
    {
        try {
            $result = $importer->fetch();
            return back()->with('success', "Official source checked: {$result['created']} new, {$result['duplicates']} already known.");
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['import' => 'The official website could not be checked. Try again later.']);
        }
    }

    public function approve($id)
    {
        $import = GovernmentNoticeImport::where('status', 'Pending')->findOrFail($id);
        $notice = Notice::create(['title' => $import->title, 'description' => 'Official notice imported for review. Add an English summary before publishing.', 'nepaliTitle' => $import->title, 'nepaliDescription' => $import->title, 'link' => $import->source_url, 'status' => 'Draft', 'source_name' => $import->source_name, 'source_url' => $import->source_url, 'government_notice_import_id' => $import->id]);
        $import->update(['status' => 'Approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        return redirect()->route('editNotice', $notice->id)->with('success', 'Imported as a draft. Review and translate it before publishing.');
    }

    public function reject($id)
    {
        GovernmentNoticeImport::where('status', 'Pending')->findOrFail($id)->update(['status' => 'Rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        return back()->with('success', 'Official notice suggestion rejected.');
    }
}
