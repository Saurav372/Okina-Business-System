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

        * { box-sizing: border-box; }

        body {
            font-family: "DejaVu Sans", sans-serif;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        @if(!empty($settings['documents']['watermark_text']))
        .watermark {
            position: fixed;
            top: 40%;
            left: 15%;
            font-size: 70px;
            font-weight: bold;
            color: #000000;
            opacity: 0.03;
            z-index: -999;
            text-transform: uppercase;
            white-space: nowrap;
            transform: rotate(-35deg);
        }
        @endif

        /* ===== HEADER STYLING ===== */
        .header-container {
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header-logo {
            max-height: 55px;
            max-width: 180px;
            margin: 0 auto 6px auto;
            display: block;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
        }
        .company-tagline {
            font-size: 9px;
            color: #64748b;
            margin: 2px 0 6px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .doc-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 8px 0 2px 0;
        }
        .doc-order-id {
            font-size: 11px;
            color: #475569;
            margin: 0;
            font-weight: bold;
        }

        /* ===== ADDRESS / CARD STYLING ===== */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .card-cell {
            width: 48%;
            vertical-align: top;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background-color: #f8fafc;
            padding: 12px;
        }
        .card-spacer {
            width: 4%;
        }
        .card-title {
            font-weight: bold;
            font-size: 9px;
            color: #334155;
            text-transform: uppercase;
            margin-bottom: 6px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            letter-spacing: 0.5px;
        }

        /* ===== ITEMS TABLE ===== */
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .item-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-align: left;
            padding: 8px 10px;
            border-bottom: 2px solid #cbd5e1;
            font-size: 9px;
            text-transform: uppercase;
        }
        .item-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            font-size: 10px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* ===== TOTALS ===== */
        .totals-outer {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .bank-cell {
            width: 53%;
            vertical-align: top;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background-color: #f8fafc;
            padding: 12px;
        }
        .totals-cell {
            width: 44%;
            vertical-align: top;
        }
        .totals-inner {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-inner td {
            padding: 4px 6px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10px;
        }
        .grand-total-row td {
            font-weight: bold;
            font-size: 12px;
            background-color: #e2e8f0;
            border-top: 1.5px solid #cbd5e1 !important;
            border-bottom: 1.5px solid #cbd5e1 !important;
            color: #0f172a;
            padding: 6px;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-weight: bold;
            font-size: 8px;
            border-radius: 4px;
            text-transform: uppercase;
            color: #ffffff;
        }
        .badge-success { background-color: #10b981; }
        .badge-warning { background-color: #f59e0b; }
        .badge-danger { background-color: #ef4444; }
        .badge-info { background-color: #3b82f6; }

        /* ===== MOCKUPS PAGE ===== */
        .mockups-title {
            border-bottom: 2.5px solid #0f172a;
            padding-bottom: 8px;
            color: #0f172a;
            text-transform: uppercase;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 20px;
            letter-spacing: 0.5px;
        }
        .mockup-table-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .mockup-grid-cell {
            width: 50%;
            padding: 10px;
            vertical-align: top;
        }
        .mockup-item-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px;
            background-color: #ffffff;
        }
        .mockup-img-large {
            max-width: 100%;
            max-height: 230px;
            display: block;
            margin: 0 auto 10px auto;
            border-radius: 4px;
        }
        .mockup-meta-block {
            font-size: 10px;
            color: #475569;
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
            margin-top: 6px;
        }

        /* ===== FOOTER ===== */
        .footer-table {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
            font-size: 9px;
            color: #64748b;
            line-height: 1.5;
        }
    </style>
</head>
<body>

    @if(!empty($settings['documents']['watermark_text']))
        <div class="watermark">{{ $settings['documents']['watermark_text'] }}</div>
    @endif

    {{-- ===== HEADER ===== --}}
    <div class="header-container">
        @if(!empty($settings['documents']['logo_path']))
            <img class="header-logo" src="{{ $settings['documents']['logo_path'] }}" alt="Logo">
        @endif
        <h1 class="company-name">{{ $settings['business']['legal_name'] ?? $settings['business']['company_name'] ?? 'Okina Craft' }}</h1>
        <div class="company-tagline">Premium Custom Apparel Printing</div>
        <div class="doc-title">Order Confirmation</div>
        <div class="doc-order-id">Order #{{ $order->public_id }}</div>
    </div>

    {{-- ===== META GRID ===== --}}
    <table class="meta-table">
        <tr>
            {{-- Order Summary --}}
            <td class="card-cell">
                <div class="card-title">Order Info</div>
                <table style="width: 100%; font-size: 10px; border-spacing: 0 4px;">
                    <tr>
                        <td style="color:#64748b; width:40%;">Order ID:</td>
                        <td style="font-weight: bold; color: #0f172a;">{{ $order->public_id }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;">Date Placed:</td>
                        <td style="color: #334155;">{{ $order->placed_at?->format('d M Y') ?? $order->created_at->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;">Order Status:</td>
                        <td>
                            @php
                                $orderStatus = strtolower($order->status);
                                $orderClass = 'badge-info';
                                if (in_array($orderStatus, ['confirmed', 'delivered'])) $orderClass = 'badge-success';
                                elseif (in_array($orderStatus, ['pending_payment', 'pending'])) $orderClass = 'badge-warning';
                                elseif ($orderStatus === 'cancelled') $orderClass = 'badge-danger';
                            @endphp
                            <span class="badge {{ $orderClass }}">{{ str_replace('_', ' ', $order->status) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;">Payment Status:</td>
                        <td>
                            @php
                                $payStatus = strtolower($payment_status);
                                $payClass = 'badge-danger';
                                if ($payStatus === 'paid') $payClass = 'badge-success';
                                elseif (in_array($payStatus, ['partially_paid', 'advance_paid', 'partial'])) $payClass = 'badge-warning';
                            @endphp
                            <span class="badge {{ $payClass }}">{{ str_replace('_', ' ', $payment_status) }}</span>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="card-spacer"></td>
            {{-- Customer & Addresses --}}
            <td class="card-cell">
                <div class="card-title">Customer Profile</div>
                <strong style="font-size: 11px; color: #0f172a;">{{ $order->customer_snapshot['name'] ?? $order->customer?->name }}</strong><br>
                <span style="font-size: 9px; color: #475569;">
                    @if(!empty($order->customer_snapshot['email']) || !empty($order->customer?->email))
                        {{ $order->customer_snapshot['email'] ?? $order->customer?->email }}
                    @endif
                    @if(!empty($order->customer_snapshot['phone']) || !empty($order->customer?->phone))
                        | {{ $order->customer_snapshot['phone'] ?? $order->customer?->phone }}
                    @endif
                </span>
                
                <table style="width: 100%; margin-top: 8px; border-top: 1px dashed #cbd5e1; padding-top: 6px;">
                    <tr>
                        <td style="width: 50%; vertical-align: top; font-size: 9px; padding-right: 6px;">
                            <span style="font-weight: bold; color: #64748b; font-size: 8px; text-transform: uppercase;">Billing</span><br>
                            @if($billing_address)
                                <strong>{{ $billing_address['contact_name'] }}</strong><br>
                                {{ $billing_address['address_line_1'] }}<br>
                                {{ $billing_address['city'] }}, {{ $billing_address['state'] }} - {{ $billing_address['postal_code'] }}
                            @else
                                <span style="color:#94a3b8;">Not recorded.</span>
                            @endif
                        </td>
                        <td style="width: 50%; vertical-align: top; font-size: 9px; padding-left: 6px; border-left: 1px dashed #cbd5e1;">
                            <span style="font-weight: bold; color: #64748b; font-size: 8px; text-transform: uppercase;">Shipping</span><br>
                            @if($shipping_address)
                                <strong>{{ $shipping_address['contact_name'] }}</strong><br>
                                {{ $shipping_address['address_line_1'] }}<br>
                                {{ $shipping_address['city'] }}, {{ $shipping_address['state'] }} - {{ $shipping_address['postal_code'] }}
                            @else
                                <span style="color:#94a3b8;">Not recorded.</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== ITEMS TABLE ===== --}}
    <table class="item-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Product</th>
                <th style="width: 15%; text-align: center;">Size</th>
                <th style="width: 10%; text-align: center;">Qty</th>
                <th style="width: 12%; text-align: right;">Unit Price</th>
                <th style="width: 13%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item['index'] }}</td>
                    <td>
                        <strong style="color: #0f172a; font-size: 10.5px;">{{ $item['name'] }}</strong>
                        @if($item['sku'])
                            <br><span style="font-size: 8.5px; color: #64748b;">SKU: {{ $item['sku'] }}</span>
                        @endif
                        
                        {{-- Production Details Summary (For Staff Use) --}}
                        @if(!empty($item['customization_details']))
                            <table style="width: 100%; margin-top: 6px; background-color: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 4px; padding: 4px 6px; border-spacing: 0;">
                                <tr>
                                    @php $count = 0; @endphp
                                    @foreach($item['customization_details'] as $ckey => $cval)
                                        @if($count > 0 && $count % 3 === 0)
                                            </tr><tr>
                                        @endif
                                        <td style="font-size: 8px; color: #475569; padding: 1px 3px;">
                                            <strong>{{ $ckey }}:</strong> {{ $cval }}
                                        </td>
                                        @php $count++; @endphp
                                    @endforeach
                                </tr>
                            </table>
                        @endif
                    </td>
                    <td style="text-align: center; font-weight: bold; color: #334155;">{{ $item['size'] }}</td>
                    <td style="text-align: center; color: #334155;">{{ $item['qty'] }}</td>
                    <td class="text-right" style="color: #334155;">₹{{ number_format($item['unit_price_minor'] / 100, 2) }}</td>
                    <td class="text-right" style="font-weight: bold; color: #0f172a;">₹{{ number_format($item['line_total_minor'] / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ===== TOTALS + BANK/PAYMENT DETAILS ===== --}}
    <table class="totals-outer">
        <tr>
            {{-- Payment Details & UPI QR --}}
            <td class="bank-cell">
                @if($payment_status !== 'paid' && !empty($settings['payments']['bank_name']))
                    <div class="card-title">Payment Instructions</div>
                    <table style="width: 100%; font-size: 9px; border-spacing: 0 2px;">
                        <tr>
                            <td style="color: #64748b; width: 35%;">Bank Name:</td>
                            <td style="font-weight: bold; color: #334155;">{{ $settings['payments']['bank_name'] }}</td>
                        </tr>
                        @if(!empty($settings['payments']['account_holder']))
                            <tr>
                                <td style="color: #64748b;">A/C Holder:</td>
                                <td style="font-weight: bold; color: #334155;">{{ $settings['payments']['account_holder'] }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td style="color: #64748b;">Account No:</td>
                            <td style="font-weight: bold; color: #334155;">{{ $settings['payments']['account_number'] }}</td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;">IFSC Code:</td>
                            <td style="font-weight: bold; color: #334155;">{{ $settings['payments']['ifsc_code'] }}</td>
                        </tr>
                    </table>
                    
                    {{-- Base64 Embedded UPI QR Code --}}
                    @if(!empty($qr_code_base64))
                        <table style="width: 100%; margin-top: 10px; border-top: 1px dashed #cbd5e1; padding-top: 8px;">
                            <tr>
                                <td style="vertical-align: middle;">
                                    <div style="font-size: 8px; color: #64748b; font-weight: bold; text-transform: uppercase;">Scan To Pay Instantly via UPI</div>
                                    <div style="font-size: 8px; color: #94a3b8; margin-top: 2px;">Account ID: {{ $settings['payments']['upi_id'] }}</div>
                                </td>
                                <td style="text-align: right; width: 90px;">
                                    <img src="{{ $qr_code_base64 }}" alt="Scan to Pay" style="width: 75px; height: 75px; border: 1px solid #e2e8f0; border-radius: 4px; padding: 2px; background: white;">
                                    <div style="font-size: 7px; color: #64748b; font-weight: bold; text-align: center; margin-top: 2px;">Scan with UPI App</div>
                                </td>
                            </tr>
                        </table>
                    @endif
                @else
                    <div style="font-size: 11px; color: #10b981; font-weight: bold; padding: 8px 0;">✓ Order Fully Paid. Thank you!</div>
                @endif
                
                @if(!empty($order->customer_notes))
                    <div style="margin-top: 10px; font-size: 9px; color:#475569; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px;">
                        <strong>Customer Notes:</strong><br>
                        <span style="font-style: italic;">{{ $order->customer_notes }}</span>
                    </div>
                @endif
            </td>
            <td style="width: 3%;"></td>
            {{-- Totals --}}
            <td class="totals-cell">
                <table class="totals-inner">
                    <tr>
                        <td style="color:#64748b;">Subtotal</td>
                        <td class="text-right" style="color: #334155;">₹{{ number_format($subtotal_amount_minor / 100, 2) }}</td>
                    </tr>
                    @if($order->discount_amount_minor > 0)
                        <tr>
                            <td style="color:#64748b;">Discount</td>
                            <td class="text-right" style="color: #ef4444;">-₹{{ number_format($order->discount_amount_minor / 100, 2) }}</td>
                        </tr>
                    @endif
                    @if($order->shipping_amount_minor > 0)
                        <tr>
                            <td style="color:#64748b;">Shipping</td>
                            <td class="text-right" style="color: #334155;">₹{{ number_format($order->shipping_amount_minor / 100, 2) }}</td>
                        </tr>
                    @endif

                    @if(!empty($settings['tax']['enable_gst']) && $order->tax_amount_minor > 0)
                        @php
                            $taxAmount = $order->tax_amount_minor / 100;
                            $halfTax = $taxAmount / 2;
                            $isSameState = empty($settings['tax']['registered_state']) || empty($shipping_address['state']) || 
                                           strcasecmp($settings['tax']['registered_state'], $shipping_address['state']) === 0;
                        @endphp
                        @if($isSameState)
                            <tr>
                                <td style="color:#64748b;">CGST (9%)</td>
                                <td class="text-right" style="color: #334155;">₹{{ number_format($halfTax, 2) }}</td>
                            </tr>
                            <tr>
                                <td style="color:#64748b;">SGST (9%)</td>
                                <td class="text-right" style="color: #334155;">₹{{ number_format($halfTax, 2) }}</td>
                            </tr>
                        @else
                            <tr>
                                <td style="color:#64748b;">IGST (18%)</td>
                                <td class="text-right" style="color: #334155;">₹{{ number_format($taxAmount, 2) }}</td>
                            </tr>
                        @endif
                    @endif

                    <tr class="grand-total-row">
                        <td style="font-weight: bold;">Grand Total</td>
                        <td class="text-right" style="font-weight: bold; font-size: 13px;">₹{{ number_format($total_amount_minor / 100, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 9.5px; color:#64748b;">Total Paid</td>
                        <td class="text-right" style="font-size: 9.5px; color:#10b981; font-weight: bold;">₹{{ number_format($paid_total / 100, 2) }}</td>
                    </tr>
                    @if($refund_total > 0)
                        <tr>
                            <td style="font-size: 9.5px; color:#ef4444;">Total Refunded</td>
                            <td class="text-right" style="font-size: 9.5px; color:#ef4444;">₹{{ number_format($refund_total / 100, 2) }}</td>
                        </tr>
                    @endif
                    <tr style="border-top: 1px solid #cbd5e1;">
                        <td style="font-weight: bold; color: #0f172a; padding-top: 5px;">Balance Due</td>
                        <td class="text-right" style="font-weight: bold; color: #ef4444; font-size: 11.5px; padding-top: 5px;">₹{{ number_format($balance_due / 100, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== ORDER TIMELINE ===== --}}
    <table style="width: 100%; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 12px; border-spacing: 0;">
        <tr>
            <td style="width: 20%; text-align: center; font-size: 8px; font-weight: bold; color: #10b981;">✓ Placed</td>
            <td style="width: 20%; text-align: center; font-size: 8px; font-weight: bold; color: #10b981;">✓ Confirmed</td>
            <td style="width: 20%; text-align: center; font-size: 8px; font-weight: bold; color: #94a3b8;">○ Production</td>
            <td style="width: 20%; text-align: center; font-size: 8px; font-weight: bold; color: #94a3b8;">○ Shipped</td>
            <td style="width: 20%; text-align: center; font-size: 8px; font-weight: bold; color: #94a3b8;">○ Delivered</td>
        </tr>
        <tr>
            <td colspan="5" style="padding-top: 4px;">
                <div style="height: 3px; background-color: #e2e8f0; border-radius: 1.5px;">
                    <div style="width: 40%; height: 100%; background-color: #10b981; border-radius: 1.5px;"></div>
                </div>
            </td>
        </tr>
    </table>

    @if(!empty($settings['documents']['stamp_path']))
        <div style="margin-top: 15px; text-align: right;">
            <div style="font-size: 8.5px; color: #64748b; margin-bottom: 2px;">Authorized Signatory</div>
            <img src="{{ $settings['documents']['stamp_path'] }}" alt="Stamp" style="max-height: 55px;">
        </div>
    @endif

    {{-- ===== SECOND PAGE (MOCKUPS & APPROVAL) ===== --}}
    @if(isset($mockup_images) && count($mockup_images) > 0)
        <div style="page-break-before: always;">
            <h2 class="mockups-title">Featured Mockups &amp; Production Previews</h2>
            
            <table class="mockup-table-grid">
                @if(count($mockup_images) === 1)
                    {{-- 1 Mockup: Large Visual Centerpiece --}}
                    @php $mockup = $mockup_images[0]; @endphp
                    <tr>
                        <td colspan="2" style="padding: 10px; text-align: center;">
                            <div class="mockup-item-box" style="width: 70%; margin: 0 auto;">
                                @if(!empty($mockup['image_src']))
                                    <img class="mockup-img-large" src="{{ $mockup['image_src'] }}" alt="{{ $mockup['display_name'] }}" style="max-height: 280px;">
                                @endif
                                <div class="mockup-meta-block">
                                    <strong style="color: #0f172a; font-size: 11px;">{{ $mockup['display_name'] }}</strong>
                                    @if(!empty($mockup['notes']))
                                        <div style="margin-top: 4px; color: #64748b; font-size: 9px; font-style: italic;">Notes: {{ $mockup['notes'] }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @else
                    {{-- 2+ Mockups: Neat 2-Column Grid --}}
                    @php $mockupChunks = array_chunk($mockup_images, 2); @endphp
                    @foreach($mockupChunks as $chunk)
                        <tr>
                            @foreach($chunk as $mockup)
                                <td class="mockup-grid-cell">
                                    <div class="mockup-item-box">
                                        @if(!empty($mockup['image_src']))
                                            <img class="mockup-img-large" src="{{ $mockup['image_src'] }}" alt="{{ $mockup['display_name'] }}">
                                        @endif
                                        <div class="mockup-meta-block">
                                            <strong style="color: #0f172a; font-size: 10px;">{{ $mockup['display_name'] }}</strong>
                                            @if(!empty($mockup['notes']))
                                                <div style="margin-top: 4px; color: #64748b; font-size: 8.5px; font-style: italic;">Notes: {{ $mockup['notes'] }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                            @if(count($chunk) === 1)
                                <td class="mockup-grid-cell"></td>
                            @endif
                        </tr>
                    @endforeach
                @endif
            </table>

            {{-- Design Approval Box --}}
            <table style="width: 100%; border: 1.5px solid #cbd5e1; border-radius: 8px; background-color: #f8fafc; padding: 12px; margin-top: 25px; border-spacing: 0;">
                <tr>
                    <td style="font-size: 11px; font-weight: bold; color: #334155; width: 60%;">
                        Design Status: <span style="color: #10b981; font-weight: bold;">✓ Approved</span>
                    </td>
                    <td style="font-size: 9px; color: #64748b; text-align: right; font-weight: bold;">
                        Approved by Customer
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="font-size: 9px; color: #64748b; padding-top: 4px;">
                        Approval Reference Date: {{ $order->placed_at?->format('d M Y') ?? $order->created_at->format('d M Y') }}
                    </td>
                </tr>
            </table>
        </div>
    @endif

    {{-- ===== FOOTER ===== --}}
    <table class="footer-table">
        <tr>
            <td style="width: 70%; text-align: left; vertical-align: middle;">
                <strong>Need Help?</strong> Support: {{ $settings['business']['support_email'] ?? 'support@okinacraft.com' }}
                @if(!empty($settings['business']['support_phone'])) | Phone: {{ $settings['business']['support_phone'] }} @endif
                @if(!empty($settings['documents']['website_url'])) | {{ $settings['documents']['website_url'] }} @endif
            </td>
            <td style="width: 30%; text-align: right; vertical-align: middle; font-weight: bold;">
                Page 1 of 1
            </td>
        </tr>
    </table>

</body>
</html>
