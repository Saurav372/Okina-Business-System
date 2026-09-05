<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CustomerAddressRequest;
use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Services\CartService;
use App\Support\Storefront\CustomerPortalPresenter;
use App\Support\Storefront\MoneyFormatter;
use App\Support\Storefront\StorefrontViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerPortalController extends Controller
{
    public function __construct(
        private readonly CustomerPortalPresenter $portal,
        private readonly StorefrontViewData $viewData,
        private readonly MoneyFormatter $money,
        private readonly CartService $carts,
    ) {}

    public function index(Request $request): View
    {
        $customer = $this->customer();

        return view('storefront.account.index', [
            ...$this->viewData->base($request),
            'profile' => $this->portal->profile($customer),
            'addresses' => $this->portal->addresses($customer),
            'orders' => $this->portal->orders($customer),
            'money' => $this->money,
        ]);
    }

    public function order(Request $request, string $order): View
    {
        return view('storefront.account.order', [
            ...$this->viewData->base($request),
            'order' => $this->portal->order($this->customer(), $order),
            'money' => $this->money,
        ]);
    }

    public function storeAddress(CustomerAddressRequest $request): RedirectResponse
    {
        $customer = $this->customer();
        $validated = $request->validated();

        DB::transaction(function () use ($customer, $validated): void {
            $this->clearDefaults($customer, $validated);
            $customer->addresses()->create($validated);
        });

        return back()->with('status', 'Address saved.');
    }

    public function updateAddress(CustomerAddressRequest $request, int $address): RedirectResponse
    {
        $customer = $this->customer();
        $model = $this->ownedAddress($customer, $address);
        $validated = $request->validated();

        DB::transaction(function () use ($customer, $model, $validated): void {
            $this->clearDefaults($customer, $validated);
            $model->update($validated);
        });

        return back()->with('status', 'Address updated.');
    }

    public function destroyAddress(int $address): RedirectResponse
    {
        $customer = $this->customer();
        $this->ownedAddress($customer, $address)->delete();

        return back()->with('status', 'Address removed.');
    }

    public function setDefaultAddress(Request $request, int $address): RedirectResponse
    {
        $validated = $request->validate(['type' => ['required', Rule::in(['shipping', 'billing', 'both'])]]);
        $customer = $this->customer();
        $model = $this->ownedAddress($customer, $address);

        DB::transaction(function () use ($customer, $model, $validated): void {
            if (in_array($validated['type'], ['shipping', 'both'], true)) {
                $customer->addresses()->update(['is_default_shipping' => false]);
                $model->is_default_shipping = true;
            }
            if (in_array($validated['type'], ['billing', 'both'], true)) {
                $customer->addresses()->update(['is_default_billing' => false]);
                $model->is_default_billing = true;
            }
            $model->save();
        });

        return back()->with('status', 'Default address updated.');
    }

    public function reorder(Request $request, string $order): RedirectResponse
    {
        $model = Order::query()
            ->where('customer_id', $this->customer()->id)
            ->where('public_id', $order)
            ->with('items')
            ->firstOrFail();

        $added = 0;
        foreach ($model->items as $item) {
            try {
                $this->carts->addItem($request, [
                    'quantity' => $item->quantity,
                    'product_slug' => $item->product_slug_snapshot,
                    'sku_code' => $item->sku_code_snapshot,
                    'customization_snapshot' => $item->customization_snapshot ?? [],
                ]);
                $added++;
            } catch (ValidationException) {
                // Unavailable items are skipped while valid items are retained.
            }
        }

        if ($added === 0) {
            return back()->withErrors(['reorder' => 'No items from this order are currently available.']);
        }

        return redirect()->route('storefront.cart')->with('status', "{$added} order item(s) added to your bag.");
    }

    private function customer(): Customer
    {
        $account = Auth::guard('customer')->user();
        abort_unless($account instanceof CustomerAccount && $account->customer instanceof Customer, 403);

        return $account->customer;
    }

    private function ownedAddress(Customer $customer, int $address): CustomerAddress
    {
        return $customer->addresses()->whereKey($address)->firstOrFail();
    }

    /** @param array<string, mixed> $input */
    private function clearDefaults(Customer $customer, array $input): void
    {
        if ((bool) ($input['is_default_shipping'] ?? false)) {
            $customer->addresses()->update(['is_default_shipping' => false]);
        }
        if ((bool) ($input['is_default_billing'] ?? false)) {
            $customer->addresses()->update(['is_default_billing' => false]);
        }
    }
}
