<svg class="learner-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
@switch($icon)
    @case('check')<path d="m5 12 4 4L19 6"/>@break
    @case('close')<path d="m6 6 12 12M6 18 18 6"/>@break
    @case('dot')<circle cx="12" cy="12" r="2" fill="currentColor" stroke="none"/>@break
    @default<path d="M6 18 18 6M6 6h12v12"/>
@endswitch
</svg>
