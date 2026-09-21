<?php

namespace App\Http\Controllers\Learner;

use Illuminate\Http\Request;

class LanguageController extends \App\Http\Controllers\Controller
{
    public function update(Request $request)
    {
        $data = $request->validate(['locale' => ['required', 'in:en,ne'], 'return_to' => ['nullable', 'string', 'max:2048']]);
        $request->session()->put('learner_locale', $data['locale']);
        // Only allow local learner paths; never trust a Referer or arbitrary return URL.
        $target = $data['return_to'] ?? '/';
        if (!preg_match('~^/(?:\?.*|learn(?:/[^\\\\\r\n]*)?(?:\?.*)?)?$~', $target)
            || str_contains($target, '\\') || preg_match('/[\x00-\x1f]/', $target)) $target = '/';
        return redirect($target)->withCookie(cookie('learner_locale', $data['locale'], 525600, '/', null, $request->isSecure(), true, false, 'lax'));
    }
}
