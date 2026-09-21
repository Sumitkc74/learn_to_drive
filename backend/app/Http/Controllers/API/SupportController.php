<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        return $request->user() ? SupportTicket::where('user_id', $request->user()->id)->latest('updated_at')->paginate(20) : abort(401);
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(SupportTicket::TYPES)],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
            'content_type' => ['nullable', 'required_with:content_id', Rule::in(array_keys(\App\Support\ReportableContent::TYPES))],
            'content_id' => ['nullable', 'required_with:content_type', 'integer', 'min:1'],
        ]);
        if (!empty($data['content_type'])) {
            if (!\App\Support\ReportableContent::visible($data['content_type'], (int)$data['content_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['content_id' => 'The selected content is unavailable.']);
            }
        }
        $ticket = SupportTicket::create($data + ['user_id' => $request->user()->id, 'status' => 'New']);
        return response()->json(['data' => $ticket], 201);
    }
    public function report(Request $request,string $type,int $id)
    {
        $record=\App\Support\ReportableContent::visible($type,$id); abort_unless($record,404);
        $data=$request->validate(['message'=>['required','string','max:5000']]);
        $field=\App\Support\ReportableContent::TYPES[$type][2];
        $ticket=SupportTicket::create(['user_id'=>$request->user()->id,'type'=>'Content report','subject'=>mb_substr('Report: '.$record->{$field},0,200),'message'=>$data['message'],'status'=>'New','content_type'=>$type,'content_id'=>$id]);
        return response()->json(['data'=>$ticket],201);
    }
    public function show(Request $request, int $ticket)
    {
        $record = SupportTicket::where('user_id', $request->user()->id)->findOrFail($ticket);
        return response()->json(['data' => $record->load(['replies' => fn ($q) => $q->select('id', 'support_ticket_id', 'author_role', 'message', 'created_at')])]);
    }
    public function reply(Request $request, int $ticket)
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:5000']]);
        DB::transaction(function () use ($request, $ticket, $data) {
            $record = SupportTicket::where('user_id', $request->user()->id)->lockForUpdate()->findOrFail($ticket);
            $record->replies()->create($data + ['user_id' => $request->user()->id, 'author_role' => 'User']);
            $record->forceFill(['status' => 'New', 'updated_at' => now()])->save();
        });
        return response()->json(['message' => 'Reply submitted.']);
    }
}
