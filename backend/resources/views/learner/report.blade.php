@extends('learner.layout')
@section('title',__('Report a problem'))
@section('content')
<div class="auth-panel card"><p class="eyebrow">{{ __('HELP US IMPROVE') }}</p><h1>{{ __('Report a problem') }}</h1><p>{{ __($label) }}</p><form method="post" class="stack">@csrf<label>{{ __('What needs checking?') }}<textarea name="message" required maxlength="5000" rows="6" placeholder="Tell us what seems incorrect or isn’t working.">{{ old('message') }}</textarea></label><button class="button">{{ __('Send report →') }}</button></form><p class="muted">{{ __('This report includes a reference to the content, so the admin team can investigate.') }}</p></div>
@endsection
