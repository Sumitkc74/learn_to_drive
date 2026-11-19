@extends('learner.layout')
@section('title',__('Terms and Conditions'))
@section('content')
<div class="page-heading"><p class="eyebrow">{{ __('THE FINE PRINT, IN PLAIN WORDS') }}</p><h1>{{ __('Terms and Conditions') }}</h1><p class="lead">{{ __('These terms explain how Learn to Drive works and what we expect from each other. By creating an account, you agree to them.') }}</p></div>
<div class="card"><div class="prose">
<h3>{{ __('1. What this service is') }}</h3>
<p>{{ __('Learn to Drive is a study app for Nepal’s driving licence exam. It offers a learning library, practice questions, mock exams, driving-school discovery and lesson booking, and an optional Premium plan with a study chatbot and revision modules.') }}</p>
<h3>{{ __('2. A study aid, not official material') }}</h3>
<p>{{ __('Our questions, signs and explanations are study aids. They are not official Department of Transport Management material. Exam rules, fees and requirements change; always check the current official notices before you apply or sit an exam.') }}</p>
<h3>{{ __('3. Your account') }}</h3>
<p>{{ __('Give accurate details when you register, keep your password safe, and do not share your account. One account per person. You are responsible for what happens under your account.') }}</p>
<h3>{{ __('4. Premium') }}</h3>
<p>{{ __('Premium is a one-time payment, made through the checkout options shown on the Premium page, that unlocks the study chatbot and revision modules. There is no automatic renewal. If something goes wrong with a payment, contact support and we will look into a refund.') }}</p>
<h3>{{ __('5. School lessons') }}</h3>
<p>{{ __('Lesson times, prices and availability are set by the driving school, not by us. Booking a lesson is an arrangement between you and the school; we cannot guarantee a time slot or a vehicle.') }}</p>
<h3>{{ __('6. Acceptable use') }}</h3>
<p>{{ __('Use the app for learning. Do not abuse the service, copy or resell its content, misuse the AI chatbot, or interfere with other learners or schools.') }}</p>
<h3>{{ __('7. Liability') }}</h3>
<p>{{ __('The service is provided as-is for study purposes. We do our best to keep it accurate and available, but we are not liable for exam results, lesson changes, or losses from using the app.') }}</p>
<h3>{{ __('8. Changes to these terms') }}</h3>
<p>{{ __('We may update these terms when the service changes. The current version always applies; the date of the last update is shown below.') }}</p>
<p class="muted">{{ __('Last updated: November 2026.') }}</p>
</div></div>
@endsection
