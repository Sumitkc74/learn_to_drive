@extends('learner.layout')
@section('title',__($attempt->completed_at?'Your results':'Practice session'))
@section('content')
<div class="page-heading"><p class="eyebrow">{{ __($attempt->completed_at?'SESSION COMPLETE':'TAKE YOUR TIME') }}</p><h1>{{ __($attempt->completed_at?'Here’s how you did.':'Let’s put it into practice.') }}</h1>@if($attempt->completed_at)<p class="lead">{{ __('You answered :score of :total correctly.', ['score'=>$attempt->score,'total'=>count($attempt->questions)]) }}</p><a class="button" href="{{ route('learn.practice') }}">{{ __('Practise again →') }}</a>@else<p>{{ __(':count questions · Answer every question before submitting.', ['count'=>count($attempt->questions)]) }}</p>@endif</div>
<form method="post" class="stack" @unless($attempt->completed_at) data-draft-url="{{ route('learn.practice.draft',$attempt) }}" data-saving="{{ __('Saving answers...') }}" data-saved="{{ __('Answers saved.') }}" data-save-error="{{ __('Could not save. Use Save and continue later to retry.') }}" @endunless>@csrf
@foreach($attempt->questions as $q)<fieldset class="card question"><legend>{{ __('Question') }} {{ $loop->iteration }}</legend><h2>{{ $q['text'] }}</h2>@if($questions[$q['id']]->image_url)<img class="content-image" src="{{ $questions[$q['id']]->image_url }}" alt="{{ __('Image for question :number', ['number'=>$loop->iteration]) }}">@endif
@foreach($q['options'] as $letter=>$option)
@if($attempt->completed_at)<div @class(['option','correct'=>$letter===$q['correct'],'incorrect'=>$letter===$attempt->answers[$q['id']] && $letter!==$q['correct']])><strong>{{ $letter }}</strong> {{ $option }} @if($letter===$q['correct'])<span>{{ __('✓ Correct answer') }}</span>@endif @if($letter===$attempt->answers[$q['id']])<span>{{ __('· Your answer') }}</span>@endif</div>
@else<label class="option"><input type="radio" name="answers[{{ $q['id'] }}]" value="{{ $letter }}" required @checked(old('answers.'.$q['id'],$attempt->answers[$q['id']] ?? null)===$letter)><strong>{{ $letter }}</strong> {{ $option }}</label>@endif
@endforeach
@if($attempt->completed_at && $q['explanation'])<div class="explanation"><strong>{{ __('Why?') }}</strong><p class="prose">{{ $q['explanation'] }}</p></div>@endif<a class="report-link" href="{{ route('learn.report',['question',$q['id']]) }}" target="_blank" rel="noopener">{{ __('Report an issue with this question ↗') }}</a></fieldset>@endforeach
@unless($attempt->completed_at)<p class="muted">{{ __('You can resume until :time.',['time'=>$attempt->expires_at->timezone('Asia/Kathmandu')->format('H:i')]) }} {{ __('Nepal time.') }}</p><p><span>{{ __('Time remaining') }}: </span><span data-session-countdown="{{ $attempt->expires_at->toIso8601String() }}" role="timer" data-server-now="{{ now()->toIso8601String() }}" data-auto-finalize="{{ $attempt->mode==='mock'?'true':'false' }}"></span></p><p id="draft-status" role="status" aria-live="polite"></p><button class="button secondary" type="submit" name="_method" value="PATCH" formaction="{{ route('learn.practice.draft',$attempt) }}" formnovalidate>{{ __('Save and continue later') }}</button><button class="button">{{ __('Check my answers →') }}</button>@endunless</form>
@if($attempt->completed_at && auth()->user()->hasPremium())
<div class="actions"><a class="button" href="{{ route('learn.premium.modules') }}">{{ __('Build practice from my mistakes →') }}</a><a href="{{ route('learn.premium.chat') }}">{{ __('Ask the Gemini study coach') }}</a></div>
@endif
@unless($attempt->completed_at)<script src="{{ asset('js/learner-practice.js') }}" defer></script>@endunless
<script src="{{ asset('js/learner-countdown.js') }}" defer></script>
@endsection

