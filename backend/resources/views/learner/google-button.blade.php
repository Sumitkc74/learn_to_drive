@if(config('services.google.client_id') && config('services.google.client_secret') && config('services.google.redirect'))
<a class="button secondary google-signin" href="{{ route('learn.google') }}">{{ __('Sign in with Google') }}</a>
@else
<button type="button" class="button secondary google-signin" disabled title="Google sign-in is awaiting configuration">{{ __('Sign in with Google') }} <small>{{ __('(coming soon)') }}</small></button>
@endif
