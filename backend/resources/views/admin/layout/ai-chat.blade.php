<aside class="admin-chat" aria-label="Admin AI assistant">
    <section id="adminChatPanel" class="admin-chat__panel" aria-labelledby="adminChatTitle" hidden>
        <header class="admin-chat__header">
            <strong id="adminChatTitle">AI Assistant</strong>
            <button type="button" id="adminChatClose" aria-label="Close AI assistant">&times;</button>
        </header>
        <div id="adminChatHistory" class="admin-chat__history" role="log" aria-live="polite" aria-relevant="additions" tabindex="0">
            <p class="admin-chat__hint">Ask about support tickets, learner issues, content reviews or admin workflows.</p>
            @foreach(array_slice(session('admin_ai_chat_history', []), -20) as $entry)
                <div class="admin-chat__message"><strong>{{ $entry['role'] === 'user' ? 'You' : 'AI assistant' }}</strong><p>{{ $entry['text'] }}</p>
                    @foreach($entry['sources'] ?? [] as $source)<a href="{{ $source['url'] }}">{{ $source['label'] }}</a><br>@if(isset($source['id']))<button type="button" data-chat-ticket="{{ $source['id'] }}">Draft reply for #{{ $source['id'] }}</button>@endif @endforeach
                </div>
            @endforeach
        </div>
        <form id="adminChatForm" action="{{ route('admin.ai-chat.send') }}" method="post">
            @csrf
            @if(request()->routeIs('support.show'))<input type="hidden" name="ticket_id" value="{{ $ticket->id }}"><p class="admin-chat__hint">Using ticket #{{ $ticket->id }} on this page.</p>@endif
            <label for="adminChatMessage">Message</label>
            <textarea id="adminChatMessage" name="message" rows="2" maxlength="2000" placeholder="Ask the AI assistant..." required></textarea>
            <p class="admin-chat__hint">Questions, database summaries and selected ticket details are sent to Gemini. Read-only: replies and changes are not submitted.</p>
            <p id="adminChatStatus" role="status" aria-live="polite"></p>
            <button id="adminChatSend" type="submit">Send</button>
        </form>
    </section>
    <button type="button" id="adminChatToggle" class="admin-chat__toggle" aria-expanded="false" aria-controls="adminChatPanel"><i class="fas fa-comment-dots" aria-hidden="true"></i> AI Assistant</button>
</aside>
