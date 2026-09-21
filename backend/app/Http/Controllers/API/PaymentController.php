<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function payment(): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Payments are unavailable until provider verification is configured.',
        ], 503);
    }
}
