@extends('learner.layout')
@section('title',__('School reports'))
@section('content')
<h1>{{ __('School reports') }} · {{ $school->name }}</h1>
<p>{{ __('Exam dates, class dates and lesson dates determine activity totals. Payment totals use invoice creation dates and current status.') }}</p>
<form method="get" class="card stack"><label>{{ __('From') }}<input name="from" type="date" value="{{ $from }}" required></label><label>{{ __('To') }}<input name="to" type="date" value="{{ $to }}" required></label><button>{{ __('Apply') }}</button></form>
<a class="button" href="{{ route('learn.school.report',[$school->id,'from'=>$from,'to'=>$to,'format'=>'csv']) }}">{{ __('Export CSV') }}</a>
<div class="grid">@foreach($metrics as $label=>$value)<section class="card"><h2>{{ __($label) }}</h2><p>{{ $value }}</p></section>@endforeach</div>
@endsection
