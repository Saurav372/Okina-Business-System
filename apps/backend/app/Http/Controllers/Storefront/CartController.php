<?php

namespace App\Http\Controllers\Storefront;

use App\Contracts\CustomizationOptionContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddCartItemRequest;
use App\Services\CartService;
use App\Support\Products\CustomizationSnapshotBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function __construct(
        private readonly CustomizationOptionContract $customization,
        private readonly CustomizationSnapshotBuilder $snapshots,
        private readonly CartService $carts,
    ) {}

    public function store(AddCartItemRequest $request, string $product): RedirectResponse
    {
        $selection = [
            'sku_code' => $request->string('sku_code')->toString(),
            'selected_options' => $request->validated('selected_options', []),
            'print_position' => $request->validated('print_position'),
            'print_method' => $request->validated('print_method'),
        ];
        $validation = $this->customization->validateSelection($product, $selection);

        if (! ($validation['valid'] ?? false)) {
            throw ValidationException::withMessages([
                'customization' => array_map(
                    fn (string $error): string => $this->messageFor($error),
                    $validation['errors'] ?? ['invalid_customization'],
                ),
            ]);
        }

        $productOptions = $this->customization->product($product);
        abort_if($productOptions === null, 404);

        $matchedSku = $validation['matched_sku'];
        $snapshot = array_filter([
            'schema_version' => CustomizationSnapshotBuilder::SCHEMA_VERSION,
            'product' => [
                'slug' => $product,
                'name' => data_get($productOptions, 'product.name'),
            ],
            'sku_code' => $matchedSku['sku_code'],
            'variant_key' => $validation['resolved_variant_key'],
            'selected_options_snapshot' => $this->snapshots->selectedOptionsSnapshot(
                $selection['selected_options'],
                $matchedSku['option_values'] ?? [],
            ),
            'print_method' => $selection['print_method'],
            'print_position' => $selection['print_position'],
            'placement' => $this->snapshots->normalizePlacement([]),
            'files' => [],
            'customer_note' => $request->validated('customer_note'),
        ], static fn (mixed $value): bool => $value !== null && $value !== [] && $value !== '');

        $this->carts->addItem($request, [
            'product_slug' => $product,
            'sku_code' => $matchedSku['sku_code'],
            'quantity' => $request->integer('quantity'),
            'customization_snapshot' => $snapshot,
        ]);

        return redirect()->away(rtrim((string) config('app.frontend_url'), '/').'/cart');
    }

    private function messageFor(string $error): string
    {
        if (str_starts_with($error, 'missing_option:')) {
            return 'Choose a '.str_replace('_', ' ', substr($error, strlen('missing_option:'))).' option.';
        }

        if (str_starts_with($error, 'invalid_option_value:')) {
            return 'One of the selected product options is not available.';
        }

        return match ($error) {
            'print_position_required' => 'Choose where the artwork should be printed.',
            'print_position_invalid' => 'The selected print position is not available.',
            'print_method_required' => 'Choose a print method.',
            'print_method_invalid' => 'The selected print method is not available.',
            'print_method_position_incompatible' => 'That print method is not available for the selected position.',
            'sku_not_found_for_selection', 'sku_code_mismatch' => 'That option combination is not available. Please choose another.',
            'sku_direct_checkout_disabled' => 'This option requires a quote before ordering.',
            'product_not_found' => 'This product is no longer available.',
            default => 'Check your customization choices and try again.',
        };
    }
}
