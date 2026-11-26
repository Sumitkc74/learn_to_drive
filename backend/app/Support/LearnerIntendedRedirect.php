<?php
namespace App\Support;
use Illuminate\Http\Request;

class LearnerIntendedRedirect
{
    // Same-site learner destinations a guest may be returned to after
    // sign-in (password, Google, or MFA). Prefix-checked against this
    // app's own absolute URLs only — never an arbitrary host or path,
    // so a ?redirect= value cannot become an open redirect.
    public static function prefixes(): array
    {
        return [url('/learn/private-lessons'), url('/learn/driving-lessons'), url('/learn/verify-email/')];
    }

    public static function allowed(?string $url): bool
    {
        if (! is_string($url) || $url === '') return false;
        foreach (static::prefixes() as $prefix) {
            if (str_starts_with($url, $prefix)) return true;
        }
        return false;
    }

    public static function store(Request $request, ?string $url): void
    {
        if (static::allowed($url)) $request->session()->put('url.intended', $url);
    }

    public static function pull(Request $request): ?string
    {
        $url = $request->session()->pull('url.intended');
        return static::allowed($url) ? $url : null;
    }
}
