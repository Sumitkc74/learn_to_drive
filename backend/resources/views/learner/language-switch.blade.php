<details class="language-switch">
    <summary aria-label="{{ __('App language') }}" title="{{ __('App language') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18M5 6.5h14M5 17.5h14"/></svg>
    </summary>
    <form class="language-options" method="post" action="{{ route('learn.language') }}" aria-label="{{ __('App language') }}">
        @csrf
        <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
        @foreach(['en'=>'English','ne'=>'नेपाली'] as $locale=>$label)
            <button type="submit" name="locale" value="{{ $locale }}" lang="{{ $locale }}" aria-pressed="{{ app()->getLocale()===$locale?'true':'false' }}">{{ $label }} @if(app()->getLocale()===$locale)<span aria-hidden="true">✓</span>@endif</button>
        @endforeach
    </form>
</details>
