<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Support\Inventory\InventoryMovementFilters;
use App\Support\Inventory\InventoryMovementQueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryMovementCsvExporter
{
    /**
     * Generate streamed CSV file response for movements based on active filters.
     */
    public function export(InventoryMovementFilters $filters): StreamedResponse
    {
        $filename = 'inventory-movements-export-'.now()->format('Y-m-d-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $callback = function () use ($filters) {
            $file = fopen('php://output', 'w');
            if ($file === false) {
                return;
            }

            // Output UTF-8 BOM for Microsoft Excel compatibility
            fwrite($file, "\xEF\xBB\xBF");

            // Write CSV Header Row
            fputcsv($file, [
                'Movement ID',
                'Timestamp (ISO)',
                'SKU Code',
                'Barcode',
                'Product Name',
                'Movement Type',
                'Direction',
                'Reason Code',
                'Quantity Delta',
                'Before On-Hand',
                'After On-Hand',
                'Before Reserved',
                'After Reserved',
                'Before Available',
                'After Available',
                'Performed By User',
                'Reference Type',
                'Reference ID',
                'Idempotency Key',
                'Audit Notes',
            ]);

            $query = InventoryMovementQueryBuilder::buildQuery($filters)
                ->with(['productSku.product', 'user']);

            // Stream database cursor chunk by chunk to prevent memory overload
            $query->chunk(250, function ($movements) use ($file) {
                /** @var InventoryMovement $movement */
                foreach ($movements as $movement) {
                    $sku = $movement->productSku;
                    $product = $sku?->product;
                    $user = $movement->user;

                    fputcsv($file, [
                        (string) $movement->id,
                        $movement->occurred_at ? $movement->occurred_at->toIso8601String() : $movement->created_at->toIso8601String(),
                        $sku?->sku_code ?? 'N/A',
                        $sku?->barcode ?? '',
                        $product?->name ?? 'Unknown Product',
                        $movement->movement_type ? $movement->movement_type->label() : '',
                        $movement->direction ? $movement->direction->label() : '',
                        $movement->reason_code ? $movement->reason_code->label() : '',
                        (string) $movement->quantity,
                        (string) $movement->before_on_hand_quantity,
                        (string) $movement->after_on_hand_quantity,
                        (string) $movement->before_reserved_quantity,
                        (string) $movement->after_reserved_quantity,
                        (string) $movement->before_available_quantity,
                        (string) $movement->after_available_quantity,
                        $user ? "{$user->name} ({$user->email})" : 'System / Automated',
                        $movement->reference_type ?? ($movement->order_id ? 'Order' : ($movement->vendor_order_id ? 'VendorOrder' : 'N/A')),
                        (string) ($movement->reference_id ?? $movement->order_id ?? $movement->vendor_order_id ?? ''),
                        $movement->idempotency_key ?? '',
                        $movement->notes ?? '',
                    ]);
                }
            });

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
