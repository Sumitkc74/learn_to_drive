@props([
    'action',
    'introIcon',
    'introTitle',
    'introText',
    'cancelUrl',
    'submitLabel',
    'submitIcon' => 'fas fa-plus',
])
<div class="ltd-form-shell">
    <form class="ltd-form-card" action="{{ $action }}" method="POST" enctype="multipart/form-data" data-add-form>
        @csrf
        <div class="ltd-form-card__intro"><i class="{{ $introIcon }}" aria-hidden="true"></i><div><h2>{{ $introTitle }}</h2><p>{{ $introText }}</p></div></div>
        {{ $slot }}
        <div class="ltd-form-actions"><a href="{{ $cancelUrl }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit" data-submit-button><i class="{{ $submitIcon }} mr-2" aria-hidden="true"></i>{{ $submitLabel }}</button></div>
    </form>
</div>
