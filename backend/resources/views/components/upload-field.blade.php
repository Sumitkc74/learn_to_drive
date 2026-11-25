@props([
    'name',
    'id' => null,
    'accept' => null,
    'multiple' => false,
    'required' => false,
    'label' => null,
    'hint' => null,
    'current' => null,
    'currentAlt' => '',
    'maxSizeKb' => null,
    'invalid' => false,
    'variant' => 'dropzone',
    'autosubmit' => false,
])
@php
    $inputId = $id ?? preg_replace('/[^A-Za-z0-9_-]+/', '-', rtrim($name, '[]'));
    $maxSizeMb = $maxSizeKb ? number_format($maxSizeKb / 1024, $maxSizeKb % 1024 === 0 ? 0 : 1) : null;
@endphp
<div {{ $attributes->merge(['class' => 'ltd-upload' . ($variant === 'avatar' ? ' ltd-upload--avatar' : '')]) }} data-upload-field>
    @if($variant === 'avatar')
        <button type="button" class="ltd-avatar-button" data-upload-trigger aria-label="{{ __('Change profile photo') }}">
            <img src="{{ $current }}" alt="{{ $currentAlt }}" data-avatar-img>
            <span class="ltd-avatar-button__edit"><i class="fas fa-camera" aria-hidden="true"></i></span>
        </button>
        <input id="{{ $inputId }}" name="{{ $name }}" type="file" aria-label="{{ __('Change profile photo') }}"
            @if($accept) accept="{{ $accept }}" @endif
            @if($required) required @endif
            @if($maxSizeKb) data-max-size-kb="{{ $maxSizeKb }}" @endif
            data-msg-invalid-type="{{ __('This file type is not accepted.') }}"
            @if($maxSizeKb) data-msg-too-large="{{ __('This file is larger than :limit MB.', ['limit' => $maxSizeMb]) }}" @endif
            data-msg-remove="{{ __('Remove') }}"
            @if($autosubmit) data-autosubmit @endif
            class="ltd-upload__input @if($invalid) is-invalid @endif">
        <button type="submit" class="ltd-upload__save">{{ __('Save photo') }}</button>
        @if($hint)<p class="ltd-avatar-help">{{ $hint }}</p>@endif
        <p class="ltd-upload__error" data-file-error role="alert" hidden></p>
    @else
        @if($current)
            <img src="{{ $current }}" alt="{{ $currentAlt }}" class="ltd-upload__current">
        @endif
        {{-- One label per input: the field caption (when given) is the first
            line inside the dropzone label, so the accessible name contains
            the visible label and there is no second <label for>. --}}
        <label class="ltd-upload__dropzone" for="{{ $inputId }}" data-dropzone tabindex="0">
            @if($label)<span class="ltd-upload__caption">{{ $label }}</span>@endif
            <svg class="ltd-upload__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4"/><path d="m6 10 6-6 6 6"/><path d="M4 20h16"/></svg>
            <span class="ltd-upload__text">{{ __('Drag & drop files here or browse') }}</span>
            @if($hint)
                <span class="ltd-upload__hint">{{ $hint }}</span>
            @endif
        </label>
        <input id="{{ $inputId }}" name="{{ $name }}" type="file"
            @if($accept) accept="{{ $accept }}" @endif
            @if($multiple) multiple @endif
            @if($required) required @endif
            @if($maxSizeKb) data-max-size-kb="{{ $maxSizeKb }}" @endif
            data-msg-invalid-type="{{ __('This file type is not accepted.') }}"
            @if($maxSizeKb) data-msg-too-large="{{ __('This file is larger than :limit MB.', ['limit' => $maxSizeMb]) }}" @endif
            data-msg-remove="{{ __('Remove') }}"
            class="ltd-upload__input @if($invalid) is-invalid @endif">
        <ul class="ltd-upload__files" data-file-list aria-label="{{ __('Selected files') }}" hidden></ul>
        <p class="ltd-upload__error" data-file-error role="alert" hidden></p>
    @endif
</div>
