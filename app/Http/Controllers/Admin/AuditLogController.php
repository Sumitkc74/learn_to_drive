<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\AdminTable;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AdminTable::paginate(
            AuditLog::with('actor'),
            $request,
            ['event', 'subject_type', 'subject_label', 'ip_address'],
            ['id', 'event', 'subject_type', 'subject_label', 'created_at'],
            [
                'event' => ['allowed' => ['created', 'updated', 'deleted', 'restored', 'permanently_deleted']],
                'subject_type' => ['allowed' => ['User', 'Question', 'Notice', 'AppSetting']],
            ]
        );

        return view('admin.audit-logs.index', compact('logs'));
    }
}
