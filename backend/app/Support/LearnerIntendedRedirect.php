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
        return [url('/learn/private-lessons'), url('/learn/driving-lessons'), url('/learn/verify-email/'), url('/learn/support'), url('/learn/report/')];
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

    // Contextual sign-in subtitle for a stored destination, as the English
    // translation key (the view translates it). Null means the generic line.
    // Support covers /learn/support and ticket details; report covers the
    // content-report forms. A private-session return explains the request;
    // a bookings return explains the bookings list; a course return with a
    // lesson id explains the booking. A plain course list (no lesson id)
    // stays generic. Admin routes never match: they live under
    // /admin/support, not under this app's /learn/support URL.
    public static function subtitleFor(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') return null;
        if (str_starts_with($url, url('/learn/report/'))) return 'Sign in to report this content.';
        if (str_starts_with($url, url('/learn/support'))) return 'Sign in to message the admin team and track their replies.';
        if (str_starts_with($url, url('/learn/private-lessons'))) return 'Sign in to request this session.';
        if (str_starts_with($url, url('/learn/driving-lessons/bookings'))) return 'Sign in to see your bookings.';
        if (str_starts_with($url, url('/learn/driving-lessons'))) {
            $query = [];
            parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
            if (isset($query['lesson'])) return 'Sign in to book this course.';
        }
        return null;
    }
}
