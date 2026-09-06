<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Packing Slips ({{ $orders->count() }} Orders) - Okina</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #111111;
            background-color: #f4f4f5;
            font-size: 12px;
            line-height: 1.4;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .no-print {
            position: sticky;
            top: 0;
            z-index: 100;
            background-color: #18181b;
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .no-print .title-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .no-print h1 {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .no-print .badge {
            background-color: #E83535;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 9999px;
        }

        .no-print .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn-print {
            background-color: #E83535;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s ease;
        }

        .btn-print:hover {
            background-color: #c92424;
        }

        .btn-close {
            background-color: #27272a;
            color: #e4e4e7;
            border: 1px solid #3f3f46;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-close:hover {
            background-color: #3f3f46;
        }

        .sheets-container {
            max-width: 800px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .packing-slip-sheet {
            background: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            page-break-after: always;
            break-after: page;
        }

        .packing-slip-sheet:last-child {
            margin-bottom: 0;
            page-break-after: auto;
            break-after: auto;
        }

        /* Sheet Header */
        .sheet-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #111111;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .brand-block h2 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #111111;
        }

        .brand-block h2 span {
            color: #E83535;
        }

        .brand-block p {
            font-size: 10px;
            color: #71717a;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 2px;
        }

        .slip-meta-block {
            text-align: right;
        }

        .doc-badge {
            display: inline-block;
            background: #111111;
            color: #ffffff;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 4px 10px;
            border-radius: 4px;
            margin-bottom: 6px;
        }

        .order-id-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 16px;
            font-weight: 700;
            color: #111111;
        }

        .order-date {
            font-size: 11px;
            color: #71717a;
            margin-top: 2px;
        }

        /* Two-column Address Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .info-card {
            background-color: #fafafa;
            border: 1px solid #e4e4e7;
            border-radius: 8px;
            padding: 14px;
        }

        .card-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #71717a;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .cust-name {
            font-size: 13px;
            font-weight: 700;
            color: #111111;
            margin-bottom: 4px;
        }

        .cust-address {
            font-size: 11px;
            color: #3f3f46;
            line-height: 1.5;
        }

        .cust-contact {
            margin-top: 8px;
            font-size: 11px;
            color: #52525b;
            font-family: 'JetBrains Mono', monospace;
        }

        /* Items Table */
        .items-section {
            margin-bottom: 24px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .items-table th {
            background-color: #f4f4f5;
            color: #27272a;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 8px 12px;
            border-top: 1px solid #e4e4e7;
            border-bottom: 1px solid #e4e4e7;
        }

        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f4f4f5;
            vertical-align: top;
            font-size: 11px;
        }

        .item-title {
            font-weight: 700;
            color: #111111;
            margin-bottom: 2px;
        }

        .item-sku {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            color: #71717a;
        }

        .customization-pill {
            display: inline-block;
            background: #f4f4f5;
            color: #3f3f46;
            font-size: 9px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
            margin-top: 4px;
            border: 1px solid #e4e4e7;
        }

        .item-check-col {
            width: 40px;
            text-align: center;
        }

        .pick-checkbox {
            width: 16px;
            height: 16px;
            border: 1.5px solid #71717a;
            border-radius: 3px;
            display: inline-block;
        }

        .qty-badge {
            display: inline-block;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 700;
            color: #111111;
            background: #f4f4f5;
            padding: 2px 8px;
            border-radius: 4px;
        }

        /* Sign-off & Verification Footer */
        .verification-footer {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px dashed #d4d4d8;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            font-size: 10px;
        }

        .sign-box {
            border: 1px solid #e4e4e7;
            border-radius: 6px;
            padding: 8px 12px;
            min-height: 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sign-title {
            color: #71717a;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .sign-line {
            border-bottom: 1px dotted #a1a1aa;
            margin-top: 16px;
        }

        /* Barcode Aesthetic Box */
        .barcode-strip {
            margin-top: 12px;
            text-align: center;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 4px;
            color: #71717a;
            font-size: 10px;
        }

        @media print {
            body {
                background: #ffffff;
            }
            .no-print {
                display: none !important;
            }
            .sheets-container {
                max-width: 100%;
                margin: 0;
                padding: 0;
            }
            .packing-slip-sheet {
                border: none;
                border-radius: 0;
                padding: 20px 24px;
                box-shadow: none;
                margin-bottom: 0;
                page-break-after: always;
                break-after: page;
            }
            .packing-slip-sheet:last-child {
                page-break-after: auto;
                break-after: auto;
            }
        }
    </style>
</head>
<body>

    <!-- Sticky Top Actions Bar (Hidden when printed) -->
    <div class="no-print">
        <div class="title-group">
            <h1>Okina Warehouse Fulfillment</h1>
            <span class="badge">{{ $orders->count() }} Orders in Batch</span>
        </div>
        <div class="btn-group">
            <button type="button" onclick="window.close()" class="btn-close">Close</button>
            <button type="button" onclick="window.print()" class="btn-print">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Print All Slips (Ctrl+P)
            </button>
        </div>
    </div>

    <!-- Slips Container -->
    <div class="sheets-container">
        @foreach($orders as $order)
            @php
                $cust = (array) ($order->customer_snapshot ?: []);
                $custName = $cust['name'] ?? $order->customer?->display_name ?? 'Customer';
                $custPhone = $cust['phone'] ?? $order->customer?->phone ?? 'N/A';
                $custEmail = $cust['email'] ?? $order->customer?->email ?? '';

                $addr = (array) ($order->shipping_address_snapshot ?: []);
                $line1 = $addr['address_line_1'] ?? $addr['line1'] ?? $order->shippingAddress?->address_line_1 ?? '';
                $line2 = $addr['address_line_2'] ?? $addr['line2'] ?? $order->shippingAddress?->address_line_2 ?? '';
                $city = $addr['city'] ?? $order->shippingAddress?->city ?? '';
                $state = $addr['state'] ?? $order->shippingAddress?->state ?? '';
                $pincode = $addr['postal_code'] ?? $addr['pincode'] ?? $order->shippingAddress?->postal_code ?? '';
                $country = $addr['country_code'] ?? $order->shippingAddress?->country_code ?? 'India';

                $totalItems = $order->items->sum('quantity') ?: 1;
            @endphp

            <div class="packing-slip-sheet">
                <!-- Header -->
                <div class="sheet-header">
                    <div class="brand-block">
                        <h2>OKINA<span>.</span></h2>
                        <p>Apparel Production & Fulfillment Slip</p>
                    </div>
                    <div class="slip-meta-block">
                        <span class="doc-badge">PACKING SLIP</span>
                        <div class="order-id-badge">{{ $order->public_id }}</div>
                        <div class="order-date">
                            Ordered: {{ $order->placed_at ? $order->placed_at->format('d M Y, h:i A') : ($order->created_at ? $order->created_at->format('d M Y') : 'N/A') }}
                        </div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="info-grid">
                    <!-- Delivery Details -->
                    <div class="info-card">
                        <div class="card-label">
                            <span>Ship To Customer</span>
                            <span style="color: #111111; font-weight: 700;">{{ strtoupper((string) ($order->order_source ?? 'website')) }}</span>
                        </div>
                        <div class="cust-name">{{ $custName }}</div>
                        <div class="cust-address">
                            @if($line1){{ $line1 }}<br>@endif
                            @if($line2){{ $line2 }}<br>@endif
                            {{ $city }}@if($city && $state), @endif{{ $state }}@if($pincode) - <strong>{{ $pincode }}</strong>@endif<br>
                            {{ $country }}
                        </div>
                        <div class="cust-contact">
                            Phone: {{ $custPhone }}
                            @if($custEmail) &bull; {{ $custEmail }}@endif
                        </div>
                    </div>

                    <!-- Dispatch & Courier Details -->
                    <div class="info-card">
                        <div class="card-label">Logistics & Handling</div>
                        <div style="font-size: 11px; color: #27272a; line-height: 1.6;">
                            <div><strong>Courier:</strong> {{ $order->courier_name ?: 'Standard Carrier' }}</div>
                            <div><strong>Tracking:</strong> {{ $order->tracking_number ?: 'Pending Dispatch' }}</div>
                            <div><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $order->status)) }}</div>
                            <div><strong>Total Units:</strong> <span class="qty-badge">{{ $totalItems }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Items to Pick & Pack -->
                <div class="items-section">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th class="item-check-col">Check</th>
                                <th>Item Details & Customization</th>
                                <th style="width: 140px;">SKU Code</th>
                                <th style="width: 80px; text-align: center;">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->items as $item)
                                @php
                                    $custom = (array) ($item->customization_snapshot ?: []);
                                    $position = $custom['print_position'] ?? $custom['position'] ?? null;
                                    $method = $custom['print_method'] ?? $custom['method'] ?? null;
                                @endphp
                                <tr>
                                    <td class="item-check-col">
                                        <div class="pick-checkbox"></div>
                                    </td>
                                    <td>
                                        <div class="item-title">{{ $item->product_name_snapshot ?: 'Custom Apparel Product' }}</div>
                                        @if($position || $method)
                                            <div class="customization-pill">
                                                @if($position) Print: {{ strtoupper((string) $position) }} @endif
                                                @if($position && $method) &bull; @endif
                                                @if($method) {{ strtoupper((string) $method) }} @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="item-sku">{{ $item->sku_code_snapshot ?: 'N/A' }}</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="qty-badge">{{ $item->quantity }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #71717a; padding: 16px;">No items recorded for this order.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Warehouse Check & Sign-off -->
                <div class="verification-footer">
                    <div class="sign-box">
                        <span class="sign-title">Picked By</span>
                        <div class="sign-line"></div>
                    </div>
                    <div class="sign-box">
                        <span class="sign-title">Quality Checked By</span>
                        <div class="sign-line"></div>
                    </div>
                    <div class="sign-box">
                        <span class="sign-title">Packed & Sealed By</span>
                        <div class="sign-line"></div>
                    </div>
                </div>

                <div class="barcode-strip">
                    * {{ $order->public_id }} *
                </div>
            </div>
        @endforeach
    </div>

    <script>
        // Trigger print dialog once loaded if opened in dedicated window
        window.addEventListener('load', () => {
            setTimeout(() => {
                window.print();
            }, 400);
        });
    </script>
</body>
</html>
