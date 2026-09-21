@extends('learner.layout')
@section('title',__('Practice archive'))
@section('content')
<div class="page-heading"><a href="{{ route('learn.account') }}">{{ __('My learning') }}</a><h1>{{ __('Practice archive') }}</h1><p>{{ __('Saved practice records from the website and mobile app, including earlier sessions.') }}</p></div>
@forelse($records as $record)
<details class="card"><summary>{{ __('Practice record') }} #{{ $record->id }} · {{ $record->created_at?->format('Y-m-d H:i') }}</summary>
@php($questions=json_decode($record->attempted_questions,true) ?: [])
@php($correct=json_decode($record->correct_options,true) ?: [])
@php($selected=json_decode($record->selected_options,true) ?: [])
@foreach($questions as $index=>$question)
<section><h2>{{ is_scalar($question)?$question:'' }}</h2>
@foreach(['A','B','C','D'] as $letter)@php($options=json_decode($record->{'option'.$letter},true) ?: [])<p>{{ $letter }}. {{ is_scalar($options[$index] ?? null)?$options[$index]:'' }}</p>@endforeach
<p>{{ __('Correct answer') }}: {{ is_scalar($correct[$index] ?? null)?$correct[$index]:'' }} · {{ __('Your answer') }}: {{ is_scalar($selected[$index] ?? null)?$selected[$index]:'' }}</p></section>
@endforeach
</details>
@empty<div class="empty">{{ __('No practice records yet.') }}</div>@endforelse
{{ $records->links('pagination::simple-default') }}
@endsection
