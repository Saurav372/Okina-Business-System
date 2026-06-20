<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentWebhookProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function cashfree(Request $request, PaymentWebhookProcessingService $service): JsonResponse
    {
        $result = $service->process('cashfree', $request->all(), $request->headers->all());
        $status = (int) ($result['http_status'] ?? 200);

        unset($result['http_status']);

        return response()->json(['data' => $result], $status);
    }
}
