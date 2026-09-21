<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    protected $redirectTo = '/admin';

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->role !== 'Admin' || !$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($user->is_active && in_array($user->role, ['User', 'PremiumUser'])) {
                    return redirect()->route('learn.login')->withInput($request->only('email'))
                        ->with('error', 'This page is for administrators. Sign in here to access your learner account and Premium features.');
                }
                return redirect()->back()->with('error', 'Invalid credentials');
            }

            $user->update(['last_login_at' => now()]);

            $request->session()->regenerate();
            $request->session()->forget('mfa_verified');
            if($user->mfa_secret)return redirect()->route('learn.mfa.challenge');

            $request->session()->forget('url.intended');
            return redirect()->route('adminDashboard');
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->only(['showLoginForm', 'login']);
    }
}
