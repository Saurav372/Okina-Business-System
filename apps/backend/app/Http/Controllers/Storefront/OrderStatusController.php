<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use App\Support\Storefront\CustomerPortalPresenter;
use App\Support\Storefront\StorefrontViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderStatusController extends Controller
{
    public function __construct(
        private readonly CustomerPortalPresenter $portal,
        private readonly StorefrontViewData $viewData,
    ) {}

    public function track(Request $request): View|RedirectResponse
    {
        $orderId = trim($request->string('order_id')->toString());
        $account = $request->user('customer');

        if ($orderId !== '' && ! ($account instanceof CustomerAccount)) {
            return redirect()->guest(route('customer.login'));
        }

        $order = null;
        $notFound = false;
        if ($orderId !== '' && $account instanceof CustomerAccount) {
            try {
                $order = $this->portal->order($account->customer, $orderId);
            } catch (ModelNotFoundException) {
                $notFound = true;
            }
        }

        return view('storefront.track-order', [
            ...$this->viewData->base($request),
            'orderId' => $orderId,
            'order' => $order,
            'notFound' => $notFound,
        ]);
    }

    public function confirmation(Request $request, string $order): View
    {
        $account = $request->user('customer');
        abort_unless($account instanceof CustomerAccount, 403);

        return view('storefront.order-confirmation', [
            ...$this->viewData->base($request),
            'order' => $this->portal->order($account->customer, $order),
        ]);
    }
}
