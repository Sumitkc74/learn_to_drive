@extends('learner.layout')
@section('title',__('Privacy Policy'))
@section('content')
<div class="page-heading"><p class="eyebrow">{{ __('YOUR DATA, IN PLAIN WORDS') }}</p><h1>{{ __('Privacy Policy') }}</h1><p class="lead">{{ __('This policy explains what data Learn to Drive collects, why we collect it, who else sees it, and how to ask for it to be corrected or deleted.') }}</p></div>
<div class="card"><div class="prose">
<h3>{{ __('1. What we collect') }}</h3>
<p>{{ __('Account details: your name, email address, phone number, and password. Your password is stored scrambled so it cannot be read back. If you upload a profile photo, we keep that too.') }}</p>
<p>{{ __('Learning activity: your practice attempts and scores, mock exam results, saved bookmarks, and the notices you have read.') }}</p>
<p>{{ __('Bookings: your lesson bookings, waitlist entries, and private session requests.') }}</p>
<p>{{ __('Support messages and reports you send us through the Help & feedback page.') }}</p>
<h3>{{ __('2. Why we collect it') }}</h3>
<p>{{ __('We use your account details to create and run your account and to sign you in. We use your learning activity to show your progress and to build revision from the topics you got wrong. We use your bookings to arrange lessons with driving schools. We use your support messages to answer you. We use your photo for your profile and, for schools, for their public page.') }}</p>
<h3>{{ __('3. Who else sees your data') }}</h3>
<p>{{ __('Google Sign-In, only if you use it: Google shares your name and verified email address with us so we can create your account.') }}</p>
<p>{{ __('Cloudflare R2: profile and school photos are stored there.') }}</p>
<p>{{ __('Gemini, Google’s AI service: your study-chat questions, and content we send for screening and translation.') }}</p>
<p>{{ __('YouTube: tutorial videos play through YouTube’s privacy-enhanced embed.') }}</p>
<p>{{ __('Email: verification, password-recovery, and other account emails are sent through our email service.') }}</p>
<p>{{ __('Khalti and eSewa: only if you pay for Premium once online checkout opens. No payment details are collected now.') }}</p>
<h3>{{ __('4. Minimum age') }}</h3>
<p>{{ __('You must be at least 16 years old to create an account. In Nepal, the minimum licence age is 16 for Category A (motorcycle) and Category K (scooter or moped), 18 for Category B (car, jeep, or van), and 21 for heavy vehicles.') }}</p>
<h3>{{ __('5. Asking for your data to be corrected or deleted') }}</h3>
<p>{{ __('If you want your data corrected or deleted, open a support ticket and tell us what to change or delete.') }} <a href="{{ route('learn.support') }}">{{ __('Open a support ticket') }}</a></p>
<h3>{{ __('6. Cookies') }}</h3>
<p>{{ __('We use a small number of cookies that are needed for the site to work, such as keeping you signed in and remembering your language. See our') }} <a href="{{ route('learn.cookies') }}">{{ __('Cookie Policy') }}</a>.</p>
<h3>{{ __('7. Changes to this policy') }}</h3>
<p>{{ __('We may update this policy when the service changes. The current version always applies; the date of the last update is shown below.') }}</p>
<p class="muted">{{ __('Last updated: November 2026.') }}</p>
</div></div>
@endsection
