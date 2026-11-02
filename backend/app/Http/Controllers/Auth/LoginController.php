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

    // There is a single sign-in page for every role: the learner sign-in
    // at /learn/login. Its controller already redirects by role
    // (Admin -> admin dashboard, school staff -> school dashboard,
    // everyone else -> learner account), so this page only forwards
    // guests there. Signed-in visitors never reach here: the guest
    // middleware redirects them by role first.
    public function showLoginForm()
    {
        return redirect()->route('learn.login');
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
        $this->middleware('guest')->only(['showLoginForm']);
    }
}
