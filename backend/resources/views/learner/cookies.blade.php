@extends('learner.layout')
@section('title',__('Cookie Policy'))
@section('content')
<div class="page-heading"><p class="eyebrow">{{ __('SMALL FILES, PLAIN WORDS') }}</p><h1>{{ __('Cookie Policy') }}</h1><p class="lead">{{ __('Cookies are small files your browser stores when you use Learn to Drive. We only use cookies that are needed for the site to work.') }}</p></div>
<div class="card"><div class="prose">
<h3>{{ __('1. Cookies we set') }}</h3>
<p>{{ __('learn_to_drive_session: keeps you signed in while you use the site. It lasts 2 hours and is renewed as you keep using the site.') }}</p>
<p>{{ __('XSRF-TOKEN: protects our forms against cross-site request forgery. It lasts as long as your session.') }}</p>
<p>{{ __('learner_locale: remembers whether you chose English or Nepali. It lasts 1 year.') }}</p>
<h3>{{ __('2. No trackers, no banner') }}</h3>
<p>{{ __('We do not use analytics or advertising trackers, and we do not share cookie data with advertisers. Because every cookie above is essential for the site to work — signing in, keeping forms safe, and remembering your language — we do not show a cookie consent banner.') }}</p>
<h3>{{ __('3. More about your data') }}</h3>
<p>{{ __('To see what data we collect and how to ask for it to be corrected or deleted, read our') }} <a href="{{ route('learn.privacy') }}">{{ __('Privacy Policy') }}</a>.</p>
<p class="muted">{{ __('Last updated: November 2026.') }}</p>
</div></div>
@endsection
