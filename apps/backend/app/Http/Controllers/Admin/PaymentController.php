<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Payment::class);

        $payments = Payment::query()->with('order')->paginate(20);

        return PaymentResource::collection($payments);
    }

    public function show(Payment $payment)
    {
        Gate::authorize('view', $payment);

        $payment->load('order');

        return new PaymentResource($payment);
    }
}
