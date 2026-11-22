@extends('learner.layout')
@section('title',__('Learning library'))
@section('content')
<div class="page-heading"><p class="eyebrow">{{ __('LEARN AT YOUR OWN PACE') }}</p><h1>{{ __('Learning library') }}</h1><p class="lead">{{ __('Choose what you want to learn. Each collection brings its resources together in one place.') }}</p></div>
<div class="grid">@foreach(\App\Http\Controllers\Learner\LibraryController::TYPES as $type=>$info)
@continue($type==='notice')
<a class="card resource-card" href="{{ route('learn.library',$type) }}"><span class="card-number">0{{ $loop->iteration }} <span aria-hidden="true">@include('learner.icon',['icon'=>'arrow'])</span></span><h2>{{ __($info[1]) }}</h2><p>{{ __($info[3]) }}</p></a>
@endforeach</div>
@endsection
