<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    public function sendResponse(mixed $result, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => $message,
        ]);
    }

    public function sendError(string $error, array $errorMessages = [], int $status = 404): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if ($errorMessages !== []) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $status);
    }
}
