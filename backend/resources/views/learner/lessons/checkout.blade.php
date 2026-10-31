@extends('learner.layout')
@section('title',__('Continue to payment provider'))
@section('content')
<form method="post" action="{{ $checkout['action'] }}" class="card">
@foreach($checkout['fields'] as $name=>$value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
<button class="button">{{ __('Continue to payment provider') }}</button></form>
@endsection
