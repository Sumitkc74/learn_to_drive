<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Support\AdminTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $tickets = AdminTable::paginate(SupportTicket::with('user'), $request, ['subject', 'message'],
            ['id', 'subject', 'status', 'created_at', 'updated_at'],
            ['status' => ['allowed' => SupportTicket::STATUSES], 'type' => ['allowed' => SupportTicket::TYPES]]);
        return view('admin.support.index', compact('tickets'));
    }
    public function show(SupportTicket $ticket)
    {
        $ticket->load('user');
        $replies = $ticket->replies()->paginate(30);
        return view('admin.support.show', compact('ticket', 'replies'));
    }
    public function update(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate(['status' => ['required', Rule::in(SupportTicket::STATUSES)], 'message' => ['nullable', 'string', 'max:5000']]);
        DB::transaction(function () use ($request, $ticket, $data) {
            $record = SupportTicket::lockForUpdate()->findOrFail($ticket->id);
            if (!empty($data['message'])) {
                $record->replies()->create(['message' => $data['message'], 'user_id' => $request->user()->id, 'author_role' => 'Admin']);
            }
            $record->forceFill(['status' => $data['status'], 'updated_at' => now()])->save();
        });
        return back()->with('success', 'Support ticket updated.');
    }
}
