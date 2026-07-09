<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation - {{ $order->public_id }}</title>
    <style>
        @page {
            size: {{ $settings['documents']['size'] ?? 'a4' }} {{ $settings['documents']['orientation'] ?? 'portrait' }};
            margin: {{ $settings['documents']['margin_top'] ?? 15 }}mm {{ $settings['documents']['margin_right'] ?? 15 }}mm {{ $settings['documents']['margin_bottom'] ?? 15 }}mm {{ $settings['documents']['margin_left'] ?? 15 }}mm;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1a202c;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            position: relative;
        }
 
        /* Watermark */
        @if(!empty($settings['documents']['watermark_text']))
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            font-weight: bold;
            color: rgba(226, 232, 240, 0.45);
            z-index: -1000;
            text-transform: uppercase;
            white-space: nowrap;
            pointer-events: none;
        }
        @endif
 
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
 
        .logo-container img {
            max-height: 60px;
            max-width: 200px;
        }
 
        .company-details {
            text-align: right;
            font-size: 12px;
        }
 
        .document-title {
            font-size: 24px;
            font-weight: bold;
            color: #2d3748;
            margin-top: 0;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
 
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
 
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            background-color: #f7fafc;
        }
 
        .card-title {
            font-weight: bold;
            font-size: 12px;
            color: #4a5568;
            text-transform: uppercase;
            margin-bottom: 6px;
            border-bottom: 1px solid #edf2f7;
            padding-bottom: 4px;
        }
 
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
 
        .item-table th {
            background-color: #edf2f7;
            color: #2d3748;
            font-weight: bold;
            text-align: left;
            padding: 8px 10px;
            border: 1px solid #cbd5e0;
            font-size: 11px;
            text-transform: uppercase;
        }
 
        .item-table td {
            padding: 8px 10px;
            border: 1px solid #cbd5e0;
            vertical-align: top;
        }
 
        .text-right {
            text-align: right;
        }
 
        .totals-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
 
        .bank-details {
            width: 50%;
        }
 
        .totals-table {
            width: 45%;
            border-collapse: collapse;
        }
 
        .totals-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
        }
 
        .totals-table .grand-total {
            font-weight: bold;
            font-size: 15px;
            background-color: #edf2f7;
            border-top: 2px solid #cbd5e0;
        }
 
        .stamp-container {
            margin-top: 15px;
            text-align: right;
        }
 
        .stamp-container img {
            max-height: 80px;
        }
 
        .mockups-section {
            margin-top: 30px;
            page-break-before: auto;
        }
 
        .mockups-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
 
        .mockup-item {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            background-color: #ffffff;
            page-break-inside: avoid;
        }
 
        .mockup-image {
            max-width: 100%;
            max-height: 400px;
            display: block;
            margin: 0 auto 10px auto;
            border-radius: 4px;
        }
 
        .mockup-meta {
            font-size: 12px;
            color: #4a5568;
            border-top: 1px solid #edf2f7;
            padding-top: 8px;
        }
 
        .footer {
            margin-top: 40px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-size: 11px;
            color: #718096;
            text-align: center;
            page-break-inside: avoid;
        }
 
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-weight: bold;
            font-size: 11px;
            border-radius: 4px;
            text-transform: uppercase;
        }
 
        .badge-success { background-color: #c6f6d5; color: #22543d; }
        .badge-warning { background-color: #feebc8; color: #744210; }
        .badge-danger { background-color: #fed7d7; color: #742a2a; }
        .badge-info { background-color: #ebf8ff; color: #2b6cb0; }
    </style>
</head>
<body>
 
    @if(!empty($settings['documents']['watermark_text']))
        <div class="watermark">{{ $settings['documents']['watermark_text'] }}</div>
    @endif
 
    <div class="header">
        <div class="logo-container">
            @if(!empty($settings['documents']['logo_path']))
                <img src="{{ $settings['documents']['logo_path'] }}" alt="Logo">
            @else
                <div style="font-size: 20px; font-weight: bold; color: #2b6cb0;">{{ $settings['business']['company_name'] }}</div>
            @endif
            @if(!empty($settings['documents']['website_url']))
                <div style="font-size: 11px; color: #718096; margin-top: 4px;">{{ $settings['documents']['website_url'] }}</div>
            @endif
        </div>
        <div class="company-details">
            <div class="document-title">Order Confirmation</div>
            <strong>{{ $settings['business']['legal_name'] ?? $settings['business']['company_name'] }}</strong><br>
            @if(!empty($settings['business']['address']))
                {!! nl2br(e($settings['business']['address'])) !!}<br>
            @endif
            @if(!empty($settings['business']['support_email']))
                Email: {{ $settings['business']['support_email'] }} |
            @endif
            @if(!empty($settings['business']['support_phone']))
                Phone: {{ $settings['business']['support_phone'] }}
            @endif
            @if(!empty($settings['tax']['enable_gst']) && !empty($settings['tax']['gstin']))
                <br><strong>GSTIN:</strong> {{ $settings['tax']['gstin'] }}
            @endif
        </div>
    </div>
 
    <div class="meta-grid">
        <div class="card">
            <div class="card-title">Order Summary</div>
            <table style="width:100%; font-size:12px;">
                <tr>
                    <td style="color:#718096; width:35%;">Order ID:</td>
                    <td><strong>{{ $order->public_id }}</strong></td>
                </tr>
                <tr>
                    <td style="color:#718096;">Date:</td>
                    <td>{{ $order->placed_at?->format('d M Y') ?? $order->created_at->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td style="color:#718096;">Order Status:</td>
                    <td><span class="badge badge-info">{{ $order->status }}</span></td>
                </tr>
                <tr>
                    <td style="color:#718096;">Payment Status:</td>
                    <td>
                        <span class="badge @if($payment_status === 'paid') badge-success @elseif(in_array($payment_status, ['partially_paid', 'advance_paid'])) badge-warning @else badge-danger @endif">
                            {{ str_replace('_', ' ', $payment_status) }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
 
        <div class="card">
            <div class="card-title">Customer Billing & Shipping</div>
            <strong style="font-size:13px;">{{ $order->customer_snapshot['name'] ?? $order->customer?->name }}</strong><br>
            @if(!empty($order->customer_snapshot['email']) || !empty($order->customer?->email))
                Email: {{ $order->customer_snapshot['email'] ?? $order->customer?->email }}<br>
            @endif
            @if(!empty($order->customer?->phone))
                Phone: {{ $order->customer?->phone }}<br>
            @endif
            @if(!empty($order->shipping_address_snapshot))
                <div style="margin-top: 6px; font-size:11px; color:#4a5568;">
                    <strong>Shipping Address:</strong><br>
                    {{ $order->shipping_address_snapshot['address_line_1'] ?? '' }},
                    {{ $order->shipping_address_snapshot['address_line_2'] ?? '' }}
                    {{ $order->shipping_address_snapshot['city'] ?? '' }}, {{ $order->shipping_address_snapshot['state'] ?? '' }} - {{ $order->shipping_address_snapshot['postal_code'] ?? '' }}
                </div>
            @endif
        </div>
    </div>
 
    <table class="item-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Product Details</th>
                <th style="width: 15%; text-align: right;">Unit Price</th>
                <th style="width: 10%; text-align: center;">Qty</th>
                <th style="width: 25%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->product_name_snapshot }}</strong>
                        @if(!empty($item->sku_code_snapshot))
                            <br><span style="font-size: 11px; color: #718096;">SKU: {{ $item->sku_code_snapshot }}</span>
                        @endif
                        @if(!empty($item->customization_snapshot))
                            <div style="margin-top: 4px; font-size: 10px; color: #4a5568; background: #edf2f7; padding: 4px 6px; border-radius: 4px;">
                                <strong>Customization:</strong>
                                @foreach($item->customization_snapshot as $ckey => $cval)
                                    @if(is_scalar($cval))
                                        <br>&bull; {{ ucwords(str_replace('_', ' ', $ckey)) }}: {{ $cval }}
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="text-right">₹{{ number_format($item->unit_price_minor / 100, 2) }}</td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td class="text-right">₹{{ number_format($item->line_total_minor / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
 
    <div class="totals-section">
        <div class="bank-details">
            @if(!empty($settings['payments']['bank_name']))
                <div class="card" style="font-size:11px;">
                    <div class="card-title">Payment Bank Details</div>
                    <strong>Bank Name:</strong> {{ $settings['payments']['bank_name'] }}<br>
                    <strong>Account No:</strong> {{ $settings['payments']['account_number'] }}<br>
                    <strong>IFSC Code:</strong> {{ $settings['payments']['ifsc_code'] }}
                </div>
            @endif
            
            @if(!empty($order->customer_notes))
                <div style="margin-top: 10px; font-size: 11px; color:#4a5568;">
                    <strong>Customer Notes:</strong><br>
                    {{ $order->customer_notes }}
                </div>
            @endif
        </div>
 
        <table class="totals-table">
            <tr>
                <td style="color:#718096;">Subtotal</td>
                <td class="text-right">₹{{ number_format($order->subtotal_amount_minor / 100, 2) }}</td>
            </tr>
            @if($order->discount_amount_minor > 0)
                <tr>
                    <td style="color:#718096;">Discount</td>
                    <td class="text-right">-₹{{ number_format($order->discount_amount_minor / 100, 2) }}</td>
                </tr>
            @endif
            @if($order->shipping_amount_minor > 0)
                <tr>
                    <td style="color:#718096;">Shipping</td>
                    <td class="text-right">₹{{ number_format($order->shipping_amount_minor / 100, 2) }}</td>
                </tr>
            @endif
 
            @if(!empty($settings['tax']['enable_gst']) && $order->tax_amount_minor > 0)
                @php
                    $taxAmount = $order->tax_amount_minor / 100;
                    $halfTax = $taxAmount / 2;
                    $isSameState = empty($settings['tax']['registered_state']) || empty($order->shipping_address_snapshot['state']) || 
                                   strcasecmp($settings['tax']['registered_state'], $order->shipping_address_snapshot['state']) === 0;
                @endphp
                @if($isSameState)
                    <tr>
                        <td style="color:#718096;">CGST (9%)</td>
                        <td class="text-right">₹{{ number_format($halfTax, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="color:#718096;">SGST (9%)</td>
                        <td class="text-right">₹{{ number_format($halfTax, 2) }}</td>
                    </tr>
                @else
                    <tr>
                        <td style="color:#718096;">IGST (18%)</td>
                        <td class="text-right">₹{{ number_format($taxAmount, 2) }}</td>
                    </tr>
                @endif
            @endif
 
            <tr class="grand-total">
                <td>Grand Total</td>
                <td class="text-right">₹{{ number_format($order->total_amount_minor / 100, 2) }}</td>
            </tr>
            <tr>
                <td style="font-size: 11px; color:#4a5568;">Total Paid</td>
                <td class="text-right" style="font-size: 11px; color:#2f855a; font-weight:bold;">₹{{ number_format($paid_total / 100, 2) }}</td>
            </tr>
            @if($refund_total > 0)
                <tr>
                    <td style="font-size: 11px; color:#742a2a;">Total Refunded</td>
                    <td class="text-right" style="font-size: 11px; color:#c53030;">₹{{ number_format($refund_total / 100, 2) }}</td>
                </tr>
            @endif
            <tr style="border-top: 1px solid #cbd5e0;">
                <td style="font-weight:bold;">Balance Due</td>
                <td class="text-right" style="font-weight:bold; color: #c53030;">₹{{ number_format($balance_due / 100, 2) }}</td>
            </tr>
        </table>
    </div>
 
    @if(!empty($settings['documents']['stamp_path']))
        <div class="stamp-container">
            <div style="font-size:10px; color:#718096; margin-bottom: 2px;">Authorized Signatory</div>
            <img src="{{ $settings['documents']['stamp_path'] }}" alt="Stamp">
        </div>
    @endif
 
    @if($order->mockups->isNotEmpty())
        <div class="mockups-section">
            <h3 style="border-bottom: 1px solid #cbd5e0; padding-bottom: 4px; color:#2d3748; text-transform:uppercase; font-size:13px; margin-bottom:15px;">Featured Mockups & Print Previews</h3>
            <div class="mockups-grid">
                @foreach($order->mockups as $mockup)
                    @if($mockup->is_featured)
                        <div class="mockup-item">
                            @if($mockup->file)
                                <img class="mockup-image" src="{{ $mockup->file->url ?? Storage::disk('private')->url($mockup->file->path) }}" alt="{{ $mockup->display_name }}">
                            @endif
                            <div class="mockup-meta">
                                <strong>{{ $mockup->display_name }}</strong>
                                @if(!empty($mockup->notes))
                                    <p style="margin: 4px 0 0 0; color:#718096;">{{ $mockup->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
 
    <div class="footer">
        {{ $settings['documents']['footer_placeholder'] ?? 'Page 1 of 1' }}
    </div>
 
</body>
</html>
