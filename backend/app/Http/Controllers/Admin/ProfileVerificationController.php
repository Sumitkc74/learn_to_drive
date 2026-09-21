<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\AppSetting;

class ProfileVerificationController extends Controller
{
    public function sendEmail(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back()->with('success', 'Your email address is already verified.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'A verification link has been sent to your email address.');
    }

    public function verifyEmail(EmailVerificationRequest $request): RedirectResponse
    {
        if (!$request->user()->hasVerifiedEmail()) {
            $request->fulfill();
        }

        return redirect()->route('profileSettings')->with('success', 'Email address verified successfully.');
    }

    public function sendPhone(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!app()->environment(['local', 'testing'])) {
            return back()->withErrors([
                'code' => 'Phone verification is unavailable until an SMS provider is configured.',
            ]);
        }

        if ($user->phone_verified_at) {
            return back()->with('success', 'Your phone number is already verified.');
        }

        $code = (string) random_int(100000, 999999);
        $expiryMinutes = AppSetting::read('otp_expiry_minutes');
        $user->forceFill([
            'phone_verification_code' => Hash::make($code),
            'phone_verification_expires_at' => now()->addMinutes($expiryMinutes),
        ])->save();

        Log::info('Local phone verification code generated.', [
            'user_id' => $user->id,
            'phone' => $user->phoneNumber,
            'code' => $code,
        ]);

        return back()
            ->with('success', "A phone verification code was generated. It expires in {$expiryMinutes} minutes.")
            ->with('local_phone_otp', $code)
            ->with('open_phone_verification', true);
    }

    public function verifyPhone(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);
        $user = $request->user();

        if (
            !$user->phone_verification_code ||
            !$user->phone_verification_expires_at ||
            $user->phone_verification_expires_at->isPast() ||
            !Hash::check($data['code'], $user->phone_verification_code)
        ) {
            return back()
                ->withErrors(['code' => 'The verification code is invalid or has expired.'])
                ->with('open_phone_verification', true);
        }

        $user->forceFill([
            'phone_verified_at' => now(),
            'phone_verification_code' => null,
            'phone_verification_expires_at' => null,
        ])->save();

        return back()->with('success', 'Phone number verified successfully.');
    }
}
