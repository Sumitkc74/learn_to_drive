@foreach(['signin'=>false,'signup'=>true] as $dialogId=>$register)
<dialog class="auth-dialog" id="auth-{{ $dialogId }}" aria-labelledby="title-{{ $dialogId }}">
<button type="button" class="dialog-close" data-auth-close aria-label="{{ __('Close authentication dialog') }}">@include('learner.icon',['icon'=>'close'])</button>
<p class="eyebrow">{{ __('YOUR LEARNING JOURNEY') }}</p><h2 id="title-{{ $dialogId }}">{{ __($register?'Create your free account':'Welcome back') }}</h2>
@include('learner.google-button')
<p class="auth-divider">{{ __('Use your email to sign in or create an account.') }}</p>
@include('learner.auth-form')
@if($register)<p>{{ __('Already registered?') }} <a href="{{ route('learn.login') }}" data-auth-open="signin">{{ __('Sign in') }}</a></p>@else<p><a href="{{ route('learn.password.request') }}">{{ __('Forgot password?') }}</a></p><p>{{ __('New here?') }} <a href="{{ route('learn.register') }}" data-auth-open="signup">{{ __('Create a free account') }}</a></p>@endif
</dialog>
@endforeach
