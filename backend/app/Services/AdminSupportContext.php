<?php

namespace App\Services;

use App\Models\SupportTicket;
use Illuminate\Http\Request;

class AdminSupportContext
{
    public function resolve(Request $request): array
    {
        $message = $request->string('message')->toString();
        $today = preg_match('/\b(today|todays|roday|rodays)\b|आज/iu', str_replace("'", '', $message));
        $explicit = preg_match('/(?:ticket\s*#?\s*|#)(\d+)\b/i', $message, $match) ? (int) $match[1] : null;
        $id = $explicit ?: ($today ? null : ($request->integer('ticket_id') ?: session('admin_ai_ticket')));
        if ($id) {
            $ticket = SupportTicket::find($id);
            if (!$ticket) {
                session()->forget('admin_ai_ticket');
                return ['reply' => 'That ticket is no longer available. Open a support ticket or specify its number.', 'sources' => []];
            }
            session(['admin_ai_ticket' => $ticket->id]);
            $replies = $ticket->replies()->reorder()->orderByDesc('id')->limit(10)->get(['author_role', 'message', 'created_at'])->reverse()->values();
            return [
                'context' => ['ticket' => $ticket->only(['id','subject','type','status','message','created_at','updated_at','content_type','content_id']),
                    'recent_replies' => $replies->toArray(), 'reply_count' => $ticket->replies()->count(),
                    'read_at' => now()->toIso8601String()],
                'sources' => [$this->source($ticket)],
            ];
        }
        if ($today || preg_match('/complaint|ticket|reply|support|उजुरी/iu', $message)) {
            session()->forget('admin_ai_ticket');
            if (!$today) return ['reply' => 'Which support ticket should I use? Open its page or tell me the ticket number, for example #12. You can also ask about today’s complaints.', 'sources' => []];
            $start = now('Asia/Kathmandu')->startOfDay()->utc();
            $query = SupportTicket::where('type', 'Complaint')->where('created_at', '>=', $start)->where('created_at', '<', $start->copy()->addDay());
            $total = (clone $query)->count();
            $tickets = $query->orderByDesc('id')->limit(10)->get();
            if ($total === 1) {
                $request->merge(['ticket_id' => $tickets->first()->id, 'message' => $message]);
                // An explicit selection avoids interpreting "today" again.
                session(['admin_ai_ticket' => $tickets->first()->id]);
                $original = $request->input('message');
                $request->merge(['message' => '#'.$tickets->first()->id]);
                $result = $this->resolve($request);
                $request->merge(['message' => $original]);
                return $result;
            }
            return ['reply' => $total ? "I found {$total} complaints created today (Nepal time). Which one should I use? Select a ticket below. Showing up to 10 newest complaints." : 'There are no complaints created today (Nepal time). Give me a ticket number if you mean an older complaint.',
                'sources' => $tickets->map(fn ($ticket) => $this->source($ticket))->all()];
        }
        return ['sources' => []];
    }

    private function source(SupportTicket $ticket): array
    {
        return ['id' => $ticket->id, 'label' => '#'.$ticket->id.' — '.$ticket->subject.' ('.$ticket->status.')', 'url' => route('support.show', $ticket)];
    }
}
