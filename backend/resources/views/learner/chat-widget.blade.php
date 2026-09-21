<button type="button" id="study-chat-toggle" class="study-chat-toggle" aria-controls="study-chat-panel" aria-expanded="false">{{ __('Study chat') }} <span aria-hidden="true">✦</span></button>
<section id="study-chat-panel" class="study-chat-panel" aria-labelledby="study-chat-title" hidden>
<header><div><strong id="study-chat-title">{{ __('Driving study assistant') }}</strong><small>{{ __('Gemini · Premium') }}</small></div><button type="button" id="study-chat-close" aria-label="{{ __('Close study chat') }}">@include('learner.icon',['icon'=>'close'])</button></header>
<p class="widget-intro">{{ __('Ask about this platform, driving or test preparation. AI can make mistakes. Messages and recent study context are sent to Gemini; avoid private information.') }}</p>
<div id="study-chat-messages" class="study-chat-messages" role="log" aria-live="polite" aria-relevant="additions"><p class="widget-reply">{{ __('How can I help with your learning today?') }}</p></div>
<p id="study-chat-status" role="status" class="widget-status"></p>
<form id="study-chat-form" action="{{ route('learn.premium.message') }}" method="post"
    data-thinking="{{ __('Thinking…') }}"
    data-session-error="{{ __('Please refresh and sign in with a Premium account to continue.') }}"
    data-send-error="{{ __('Unable to send. Try again later.') }}"
    data-connection-error="{{ __('Connection failed. Please try again.') }}">@csrf<label for="study-chat-input">{{ __('Your question') }}</label><textarea id="study-chat-input" name="message" rows="2" maxlength="2000" required placeholder="{{ __('Ask about driving or the app…') }}"></textarea><div><a href="{{ route('learn.premium.chat') }}">{{ __('Full chat & history') }}</a><button class="button small" type="submit">{{ __('Send') }}</button></div></form>
</section>
<script src="{{ asset('js/learner-chat.js') }}" defer></script>
