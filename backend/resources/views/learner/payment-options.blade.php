@php($gateways=collect(['khalti','esewa'])->filter(fn($g)=>app(\App\Services\PremiumGateway::class)->ready($g)))
<section class="card premium-upgrade"><h2>{{ __($premium?'Extend Premium':'Upgrade to Premium') }}</h2>
@if($gateways->isNotEmpty())
<p><strong>{{ __('NPR') }} {{ number_format(config('payments.amount_paisa')/100,2) }}</strong> {{ __('for') }} {{ config('payments.days') }} {{ __('days. One-time payment; no automatic renewal.') }}</p>
@unless(config('payments.live'))<p class="alert">{{ __('Sandbox checkout — test payments only.') }}</p>@endunless
@auth<div class="actions">@foreach($gateways as $gateway)<form method="post" action="{{ route('learn.payments.checkout') }}">@csrf<input type="hidden" name="gateway" value="{{ $gateway }}"><button class="button">{{ __('Pay with') }} {{ $gateway==='esewa'?'eSewa':'Khalti' }}</button></form>@endforeach</div>@else<a class="button" href="{{ route('learn.login') }}">{{ __('Sign in to upgrade') }}</a>@endauth
@else<p>{{ __('Online checkout is not available yet. Contact the admin team about Premium access.') }}</p>@unless($premium)@auth<form action="{{ route('learn.premium.request') }}" method="post">@csrf<button class="button">{{ __('Request Premium upgrade') }}</button></form>@else<a class="button" href="{{ route('learn.login') }}">{{ __('Sign in to request Premium') }}</a>@endauth
@endunless
@endif
@auth<p><a href="{{ route('learn.payments') }}">{{ __('Payment history & status →') }}</a></p>@if(auth()->user()->premium_until?->isFuture())<p>{{ __('Paid access ends') }} {{ auth()->user()->premium_until->timezone('Asia/Kathmandu')->locale(app()->getLocale())->translatedFormat('d M Y, H:i') }} {{ __('Nepal time.') }}</p>@endif
@endauth
</section>
