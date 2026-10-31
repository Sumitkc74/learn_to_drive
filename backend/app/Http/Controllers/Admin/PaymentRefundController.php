<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PremiumPayment;
use App\Services\PremiumGateway;
use Illuminate\Http\Request;

class PaymentRefundController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->role === 'Admin', 403);

        $payments = PremiumPayment::with('user')->latest()->paginate(25);

        return view('admin.payments.index', compact('payments'));
    }

    public function refund(Request $request, PremiumPayment $payment, PremiumGateway $gateway)
    {
        abort_unless($request->user()?->role === 'Admin', 403);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($payment->status !== 'Paid') {
            return redirect()->back()->with('error', __('This payment cannot be refunded.'));
        }

        try {
            $gateway->refund($payment, $data['reason'] ?? null);
            $payment->refund($data['reason'] ?? null);
            return redirect()->back()->with('success', __('The payment has been refunded and access was removed.'));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
