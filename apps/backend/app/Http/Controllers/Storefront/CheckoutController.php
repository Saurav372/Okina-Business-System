<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\CheckoutValidationRequest;
use App\Models\CustomerAccount;
use App\Services\CartResponsePresenter;
use App\Services\CartService;
use App\Services\CheckoutPendingOrderService;
use App\Services\SettingsService;
use App\Support\Storefront\CustomerPortalPresenter;
use App\Support\Storefront\MoneyFormatter;
use App\Support\Storefront\StorefrontViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CartResponsePresenter $cartPresenter,
        private readonly CheckoutPendingOrderService $checkout,
        private readonly CustomerPortalPresenter $portal,
        private readonly StorefrontViewData $viewData,
        private readonly SettingsService $settings,
        private readonly MoneyFormatter $money,
    ) {}

    public function show(Request $request): View
    {
        $account = $this->account($request);
        $addresses = $this->portal->addresses($account->customer);

        return view('storefront.checkout', [
            ...$this->viewData->base($request),
            'cart' => $this->cartPresenter->payload($this->carts->current($request, false)),
            'addresses' => $addresses,
            'defaultShippingId' => data_get(collect($addresses)->firstWhere('is_default_shipping', true), 'id')
                ?? data_get($addresses, '0.id'),
            'defaultBillingId' => data_get(collect($addresses)->firstWhere('is_default_billing', true), 'id'),
            'onlinePaymentsEnabled' => (bool) $this->settings->get('payment', 'online_payments_enabled', true),
            'money' => $this->money,
        ]);
    }

    public function store(CheckoutValidationRequest $request): RedirectResponse
    {
        $result = $this->checkout->payload($request, $request->validated());

        if (($result['bulk_handoff']['required'] ?? false) === true) {
            return back()->withInput()->with('bulk_handoff', $result['bulk_handoff']['message']);
        }

        if (($result['valid'] ?? false) !== true) {
            $errors = collect($result['errors'] ?? [])->mapWithKeys(
                fn (array $error): array => [(string) ($error['field'] ?? 'checkout') => (string) ($error['message'] ?? 'Checkout could not continue.')],
            )->all();

            return back()->withInput()->withErrors($errors ?: ['checkout' => 'Checkout could not continue.']);
        }

        $orderId = data_get($result, 'pending_order.public_id');
        abort_unless(is_string($orderId) && $orderId !== '', 422, 'The order could not be created.');

        $checkoutUrl = data_get($result, 'payment_attempt.checkout_url');
        if (is_string($checkoutUrl) && $checkoutUrl !== '') {
            return redirect()->away($checkoutUrl);
        }

        return redirect()->route('storefront.order-confirmation', ['order' => $orderId]);
    }

    private function account(Request $request): CustomerAccount
    {
        $account = $request->user('customer');
        abort_unless($account instanceof CustomerAccount, 403);

        return $account;
    }
}
