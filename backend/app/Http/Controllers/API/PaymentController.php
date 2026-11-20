<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PremiumPayment;
use App\Services\PremiumGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    // Starts a Premium payment for API clients (mobile app): same
    // server-priced, gateway-gated initiation as the web checkout.
    // Khalti returns the provider payment URL to open; eSewa returns the
    // signed form action and fields. Verification stays server-side via
    // POST learner/payments/{id}/verify, exactly as the web return does.
    public function payment(Request $request, PremiumGateway $gateway): JsonResponse
    {
        $data = $request->validate(['gateway' => 'required|in:khalti,esewa']);
        abort_unless($gateway->ready($data['gateway']), 503, __('This payment option is not configured.'));
        $p = PremiumPayment::create(['id' => (string) Str::uuid(), 'user_id' => $request->user()->id, 'gateway' => $data['gateway'], 'live' => config('payments.live'), 'merchant' => $data['gateway'] === 'esewa' ? config('payments.esewa_code') : null, 'amount_paisa' => config('payments.amount_paisa'), 'access_days' => config('payments.lifetime') ? 0 : config('payments.days')]);
        try {
            if ($p->gateway === 'khalti') {
                return response()->json(['id' => $p->id, 'gateway' => 'khalti', 'amount_paisa' => $p->amount_paisa, 'payment_url' => $gateway->startKhalti($p)]);
            }
            $form = $gateway->esewaForm($p);
            return response()->json(['id' => $p->id, 'gateway' => 'esewa', 'amount_paisa' => $p->amount_paisa] + $form);
        } catch (\Throwable $e) {
            $p->update(['status' => 'Initiation failed']);
            return response()->json(['message' => __('Checkout could not start. No Premium access has been added.')], 503);
        }
    }
}
