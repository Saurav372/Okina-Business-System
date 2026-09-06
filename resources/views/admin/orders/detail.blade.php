<x-layouts.admin :title="'Order Detail - ' . $order->public_id . ' | Okina Craft'" :hideTitle="true">
    @if (session('success'))
        <div role="status" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div role="alert" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-bold">
                @if ($errors->hasAny(['proof_file', 'proof_name', 'message_for_customer']))
                    The proof could not be uploaded.
                @elseif ($errors->hasAny(['reason_code', 'reason_note', 'material_consumed', 'customization_applied', 'scrap_incurred', 'production_impact_notes', 'physical_interception_confirmed', 'refund_amount']))
                    Order cancellation could not be processed.
                @else
                    There were problems with your submission.
                @endif
            </p>
            <ul class="mt-1 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <!-- Breadcrumbs -->
    <div class="mb-4">
        <!-- Desktop/Tablet breadcrumb -->
        <div class="hidden md:flex items-center gap-1.5 text-xs text-[color:var(--color-text-muted)] font-medium">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-[color:var(--color-text-body)]">Dashboard</a>
            <span>&gt;</span>
            <a href="{{ route('admin.orders.index') }}" class="hover:text-[color:var(--color-text-body)]">Sales Orders</a>
            <span>&gt;</span>
            <span class="text-[color:var(--color-text-body)] font-semibold">
                {{ strlen($order->public_id) > 14 ? substr($order->public_id, 0, 6) . '...' . substr($order->public_id, -4) : $order->public_id }}
            </span>
        </div>
        
        <!-- Mobile breadcrumb -->
        <div class="flex md:hidden items-center">
            <a href="{{ route('admin.orders.index') }}" onclick="if(document.referrer.includes('/admin/orders')) { history.back(); return false; }" class="inline-flex items-center gap-1 text-sm font-semibold text-[color:var(--color-text-muted)] hover:text-[color:var(--color-text-body)]">
                <x-icons.lucide name="lucide-chevron-left" class="w-5 h-5 text-neutral-400 -ml-1" />
                Orders
            </a>
        </div>
    </div>

    <!-- Header Details Block -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full border-b border-neutral-200 pb-6 mb-6">
        <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-neutral-900">
                    {{ $order->public_id }}
                </h1>
                
                @php
                    $orderStatus = strtolower($order->status);
                    $statusColor = 'bg-blue-50 text-blue-700 border-blue-200';
                    if (in_array($orderStatus, ['confirmed', 'delivered'])) {
                        $statusColor = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    } elseif (in_array($orderStatus, ['pending_payment', 'pending'])) {
                        $statusColor = 'bg-amber-50 text-amber-700 border-amber-200';
                    } elseif ($orderStatus === 'cancelled') {
                        $statusColor = 'bg-rose-50 text-rose-700 border-rose-200';
                    }
                @endphp
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider border {{ $statusColor }}">
                    {{ str_replace('_', ' ', $order->status) }}
                </span>
            </div>
            
            <p class="text-xs md:text-sm text-neutral-500 font-medium">
                Placed on {{ $order->placed_at?->format('d M Y • h:i A') ?? $order->created_at->format('d M Y • h:i A') }}
            </p>
        </div>
        
        <!-- Action Buttons (Responsive 50/50 on Mobile) -->
        <div class="flex items-center gap-3 w-full md:w-auto">
            @if ($order->canBeCancelled() && auth()->user()->can('cancel', $order))
                <button 
                    @click="$dispatch('open-cancel-modal')"
                    type="button"
                    class="flex-1 md:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-rose-300 text-rose-700 hover:bg-rose-50 hover:border-rose-400 font-bold rounded-xl text-xs transition-all duration-150 shadow-xs focus:outline-none focus:ring-2 focus:ring-rose-500/20"
                >
                    <x-icons.lucide name="lucide-x-circle" class="w-4 h-4 text-rose-600" />
                    Cancel Order
                </button>
            @endif
            <button 
                @click="$dispatch('open-pdf-preview')"
                class="flex-1 md:flex-initial inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-neutral-100 text-neutral-700 hover:bg-neutral-200 font-bold rounded-xl text-xs transition-all duration-150 focus:outline-none"
            >
                <x-icons.lucide name="lucide-eye" class="w-4 h-4" />
                Preview
            </button>
            <a 
                href="{{ route('admin.orders.pdf.download', ['order' => $order->public_id]) }}"
                class="flex-1 md:flex-initial inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-red-600 text-white hover:bg-red-700 font-bold rounded-xl text-xs transition-all duration-150 focus:outline-none"
            >
                <x-icons.lucide name="lucide-download" class="w-4 h-4" />
                Download PDF
            </a>
        </div>
    </div>

    @if ($order->status === 'cancelled' && $order->cancellation)
        @php $c = $order->cancellation; @endphp
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50/70 p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-rose-200/60 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="p-1.5 rounded-lg bg-rose-100 text-rose-700">
                        <x-icons.lucide name="lucide-alert-octagon" class="w-4 h-4" />
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-rose-900">Order Cancelled</h3>
                        <p class="text-xs text-rose-700">
                            Cancelled on {{ $c->created_at->format('d M Y • h:i A') }}
                            @if($c->cancelledBy)
                                by <span class="font-semibold">{{ $c->cancelledBy->name }}</span>
                            @endif
                            (Previous Status: <span class="font-semibold uppercase font-mono">{{ str_replace('_', ' ', $c->previous_status) }}</span>)
                        </p>
                    </div>
                </div>
                <div class="text-xs font-semibold px-2.5 py-1 rounded-full bg-rose-100 text-rose-800 border border-rose-300">
                    Reason: {{ ucwords(str_replace('_', ' ', $c->reason_code)) }}
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-rose-950">
                @if($c->reason_note)
                    <div class="md:col-span-3">
                        <span class="font-bold text-rose-800">Explanation:</span>
                        <p class="mt-0.5 text-neutral-800 bg-white/80 p-2.5 rounded-xl border border-rose-100">{{ $c->reason_note }}</p>
                    </div>
                @endif

                @if($c->previous_status === 'in_production')
                    <div>
                        <span class="font-bold text-rose-800">Material Consumed:</span>
                        <span class="ml-1 font-semibold">{{ $c->material_consumed ? 'Yes' : 'No' }}</span>
                    </div>
                    <div>
                        <span class="font-bold text-rose-800">Scrap Incurred:</span>
                        <span class="ml-1 font-semibold">{{ $c->scrap_incurred ? 'Yes' : 'No' }}</span>
                        @if($c->scrap_quantity)
                            ({{ $c->scrap_quantity }} pcs)
                        @endif
                    </div>
                    <div>
                        <span class="font-bold text-rose-800">Estimated Scrap Loss:</span>
                        <span class="ml-1 font-mono font-bold">{{ $c->scrap_amount_minor ? '₹' . number_format($c->scrap_amount_minor / 100, 2) : 'None' }}</span>
                    </div>
                    @if($c->production_impact_notes)
                        <div class="md:col-span-3">
                            <span class="font-bold text-rose-800">Production Impact Notes:</span>
                            <p class="mt-0.5 text-neutral-800 bg-white/80 p-2.5 rounded-xl border border-rose-100">{{ $c->production_impact_notes }}</p>
                        </div>
                    @endif
                @endif

                @if($c->physical_interception_confirmed)
                    <div class="md:col-span-3 flex items-center gap-2 text-emerald-800 bg-emerald-50 border border-emerald-200 p-2 rounded-xl">
                        <x-icons.lucide name="lucide-check-circle" class="w-4 h-4 text-emerald-600" />
                        <span>Physical parcel interception confirmed before courier handoff.</span>
                    </div>
                @endif
            </div>
        </div>
    @endif
 
    <!-- Tabbed Layout Container -->
    <div x-data="{ activeTab: @js(session('proof_uploaded') || $errors->hasAny(['proof_file', 'proof_name', 'message_for_customer']) ? 'mockups' : 'items') }" class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        <!-- Tab content & detail panels (Left / 2 Columns) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Tabs Header Navigation (Responsive scrollable flex-nowrap bar) -->
            <div class="flex border-b border-neutral-200 bg-white px-4 py-2 rounded-2xl shadow-xs gap-2 overflow-x-auto scrollbar-none flex-nowrap whitespace-nowrap">
                <button 
                    @click="activeTab = 'items'" 
                    :class="activeTab === 'items' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-500 hover:bg-neutral-50 hover:text-neutral-800'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none shrink-0"
                >
                    Items
                </button>
                <button 
                    @click="activeTab = 'payments'" 
                    :class="activeTab === 'payments' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-500 hover:bg-neutral-50 hover:text-neutral-800'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none shrink-0"
                >
                    Payments
                </button>
                <button 
                    @click="activeTab = 'shipping'" 
                    :class="activeTab === 'shipping' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-500 hover:bg-neutral-50 hover:text-neutral-800'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none shrink-0"
                >
                    Shipping
                </button>
                <button 
                    @click="activeTab = 'mockups'" 
                    :class="activeTab === 'mockups' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-500 hover:bg-neutral-50 hover:text-neutral-800'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none shrink-0"
                >
                    Artwork & Proofs ({{ $artworkUploads->count() + $order->mockups->count() }})
                </button>
                <button 
                    @click="activeTab = 'timeline'" 
                    :class="activeTab === 'timeline' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-500 hover:bg-neutral-50 hover:text-neutral-800'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none shrink-0"
                >
                    History
                </button>
            </div>
 
            <!-- 1. Items Panel -->
            <div x-show="activeTab === 'items'" class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-6">
                <h3 class="text-sm font-bold text-neutral-800 uppercase tracking-wider border-b border-neutral-100 pb-3">Order Items</h3>
                <div class="space-y-4">
                    @foreach ($summary['items'] as $item)
                        <div class="border border-neutral-100 rounded-xl p-4 bg-neutral-50/50 hover:bg-neutral-50 transition-colors flex items-start gap-4">
                            @php $cs = $item['customization_snapshot'] ?? null; @endphp
                            <div class="w-[60px] h-[60px] bg-neutral-100 rounded-lg border border-neutral-200 flex items-center justify-center text-neutral-400 shrink-0" aria-hidden="true">
                                <x-icons.lucide name="lucide-package" class="w-6 h-6" />
                            </div>
                            
                            <!-- Details -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-1 sm:gap-4">
                                    <div>
                                        <h4 class="text-sm font-bold text-neutral-800">{{ $item['product_name'] }}</h4>
                                        <p class="text-xs text-neutral-500 mt-0.5">SKU: <span class="font-medium font-mono">{{ $item['sku_code'] }}</span></p>
                                    </div>
                                    <div class="text-left sm:text-right mt-1 sm:mt-0">
                                        <span class="block text-[10px] text-neutral-400 uppercase font-bold tracking-wider">Total</span>
                                        <span class="text-sm font-extrabold text-neutral-900 font-mono">₹{{ number_format($item['line_total_minor'] / 100, 2) }}</span>
                                    </div>
                                </div>
                                
                                <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-t border-neutral-100/80 pt-2 text-xs">
                                    <div class="text-neutral-500">
                                        <span class="font-semibold text-neutral-700">Qty:</span> <span class="font-bold text-neutral-800">{{ $item['quantity'] }}</span>
                                        <span class="text-neutral-300 mx-2">|</span>
                                        <span class="font-mono text-neutral-600">₹{{ number_format($item['unit_price_minor'] / 100, 2) }} &times; {{ $item['quantity'] }}</span>
                                    </div>
                                    
                                    @if(!empty($cs) && is_array($cs))
                                        <div class="flex flex-wrap gap-1 mt-1 sm:mt-0">
                                            @foreach($cs as $ckey => $cval)
                                                @if(is_scalar($cval) && !in_array($ckey, ['mockup_preview_url', 'expires_in_minutes', 'route_name', 'schema_version']))
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-neutral-200/50 text-[9px] font-bold text-neutral-600">
                                                        {{ ucwords(str_replace('_', ' ', $ckey)) }}: {{ $cval }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
 
                <!-- Totals Aggregation Block -->
                <div class="border-t border-neutral-100 pt-4 flex justify-end">
                    <div class="w-full md:w-80 space-y-2">
                        <div class="flex justify-between text-xs text-neutral-500">
                            <span>Subtotal</span>
                            <span class="font-mono">₹{{ number_format($summary['amounts']['subtotal_amount_minor'] / 100, 2) }}</span>
                        </div>
                        @if($summary['amounts']['discount_amount_minor'] > 0)
                            <div class="flex justify-between text-xs text-rose-600">
                                <span>Discount</span>
                                <span class="font-mono">-₹{{ number_format($summary['amounts']['discount_amount_minor'] / 100, 2) }}</span>
                            </div>
                        @endif
                        @if($summary['amounts']['shipping_amount_minor'] > 0)
                            <div class="flex justify-between text-xs text-neutral-500">
                                <span>Shipping</span>
                                <span class="font-mono">₹{{ number_format($summary['amounts']['shipping_amount_minor'] / 100, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-sm font-extrabold text-neutral-800 border-t border-neutral-100 pt-2">
                            <span>Grand Total</span>
                            <span class="font-mono">₹{{ number_format($summary['amounts']['total_amount_minor'] / 100, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
 
            <!-- 2. Payments & Refunds Panel -->
            <div x-show="activeTab === 'payments'" class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-6">
                <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                    <h3 class="text-sm font-bold text-neutral-800 uppercase tracking-wider">Payment Attempts & Transactions</h3>
                    <div class="text-xs text-neutral-500 font-bold">
                        Outstanding: <span class="font-mono text-rose-600">₹{{ number_format($summary['amounts']['outstanding_balance_minor'] / 100, 2) }}</span>
                    </div>
                </div>

                @if($order->status === 'cancelled' && $order->getAvailableRefundRequestAmountMinor() > 0)
                    @php
                        $firstRefundablePayment = $order->payments->where('status', \App\Models\Payment::STATUS_SUCCEEDED)->first(function($p) {
                            $refunded = $p->refunds->whereIn('status', [
                                \App\Models\Refund::STATUS_REQUESTED,
                                \App\Models\Refund::STATUS_APPROVED,
                                \App\Models\Refund::STATUS_PROCESSING,
                                \App\Models\Refund::STATUS_SUCCEEDED,
                            ])->sum('amount_minor');
                            return ($p->amount_minor - $refunded) > 0;
                        });
                    @endphp
                    <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-0.5">
                            <h5 class="text-xs font-bold text-amber-900 flex items-center gap-1.5">
                                <x-icons.lucide name="lucide-alert-circle" class="w-4 h-4 text-amber-600" />
                                Cancelled Order with Captured Payments
                            </h5>
                            <p class="text-xs text-amber-800">
                                This order has <span class="font-mono font-bold">₹{{ number_format($order->getAvailableRefundRequestAmountMinor() / 100, 2) }}</span> captured and not yet refunded.
                            </p>
                        </div>
                        <a href="{{ route('admin.refunds.index', array_filter(['open' => 1, 'payment_id' => $firstRefundablePayment?->id])) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition-colors shrink-0 shadow-xs">
                            <x-icons.lucide name="lucide-external-link" class="w-3.5 h-3.5" />
                            Request Customer Refund
                        </a>
                    </div>
                @endif

                <!-- Recorded Payments -->
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-neutral-400 uppercase tracking-wider">Payments Log</h4>
                    @forelse ($summary['payments'] as $p)
                        <div class="flex items-center justify-between border border-neutral-100 rounded-xl p-4 bg-neutral-50/50">
                            <div>
                                <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase">
                                    {{ $p['status'] }}
                                </span>
                                <span class="text-xs text-neutral-400 ml-2 font-mono">ID: {{ $p['provider_payment_id'] }}</span>
                                <p class="text-xs text-neutral-500 mt-1 font-semibold">Collected via {{ ucwords($p['provider']) }} ({{ $p['payment_type'] }})</p>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-neutral-400 font-mono">{{ date('d M Y H:i', strtotime($p['paid_at'])) }}</div>
                                <div class="text-sm font-bold text-neutral-800 mt-1 font-mono">₹{{ number_format($p['amount_minor'] / 100, 2) }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-neutral-400 text-center py-4">No successful payments registered.</p>
                    @endforelse
                </div>
 
                <!-- Refunds -->
                <div class="space-y-4 border-t border-neutral-100 pt-6">
                    <h4 class="text-xs font-bold text-neutral-400 uppercase tracking-wider">Refunds Log</h4>
                    @forelse ($summary['refunds'] as $r)
                        <div class="flex items-center justify-between border border-neutral-100 rounded-xl p-4 bg-neutral-50/50">
                            <div>
                                <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-bold uppercase">
                                    {{ $r['status'] }}
                                </span>
                                <span class="text-xs text-neutral-400 ml-2 font-mono">ID: {{ $r['provider_refund_id'] }}</span>
                                <p class="text-xs text-neutral-500 mt-1 font-semibold">Reason: {{ $r['reason_code'] }}</p>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-neutral-400 font-mono">{{ date('d M Y H:i', strtotime($r['processed_at'] ?? $r['requested_at'])) }}</div>
                                <div class="text-sm font-bold text-neutral-800 mt-1 font-mono">-₹{{ number_format($r['amount_minor'] / 100, 2) }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-neutral-400 text-center py-4">No refunds recorded.</p>
                    @endforelse
                </div>
            </div>
 
            <!-- 3. Shipping & Tracking Panel -->
            <div x-show="activeTab === 'shipping'" class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-6">
                <h3 class="text-sm font-bold text-neutral-800 uppercase tracking-wider border-b border-neutral-100 pb-3">Shipping & Delivery Details</h3>
                
                @if($summary['shipping_address_snapshot'])
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-neutral-50 p-4 rounded-xl border border-neutral-100">
                            <h4 class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Recipient Info</h4>
                            <strong class="text-sm text-neutral-800">{{ $summary['shipping_address_snapshot']['contact_name'] }}</strong>
                            <p class="text-xs text-neutral-500 mt-1">Phone: {{ $summary['shipping_address_snapshot']['phone'] }}</p>
                            @if(!empty($summary['shipping_address_snapshot']['gstin']))
                                <p class="text-xs text-neutral-500 mt-0.5">GSTIN: {{ $summary['shipping_address_snapshot']['gstin'] }}</p>
                            @endif
                        </div>
                        <div class="bg-neutral-50 p-4 rounded-xl border border-neutral-100">
                            <h4 class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Delivery Address</h4>
                            <p class="text-xs text-neutral-600 leading-relaxed">
                                {{ $summary['shipping_address_snapshot']['address_line_1'] }}<br>
                                @if($summary['shipping_address_snapshot']['address_line_2'])
                                    {{ $summary['shipping_address_snapshot']['address_line_2'] }}<br>
                                @endif
                                {{ $summary['shipping_address_snapshot']['city'] }}, {{ $summary['shipping_address_snapshot']['state'] }} - {{ $summary['shipping_address_snapshot']['postal_code'] }}<br>
                                {{ $summary['shipping_address_snapshot']['country_code'] ?? 'IN' }}
                            </p>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-neutral-400 text-center py-4">No shipping address recorded on this order.</p>
                @endif
            </div>
 
            <!-- 4. Customer Artwork & Staff Proofs Panel -->
            <div x-show="activeTab === 'mockups'" class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-6">
                <div class="border-b border-neutral-100 pb-4">
                    <h3 class="text-sm font-bold text-neutral-800 uppercase tracking-wider">Artwork & Customer Proofs</h3>
                    <p class="mt-1 text-xs leading-5 text-neutral-500">Download the customer's original artwork, prepare the mockup in Photoshop or another editor, then upload the finished proof here. Uploaded proofs are immediately available in the customer's order page.</p>
                </div>

                <section aria-labelledby="customer-artwork-heading" class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <h4 id="customer-artwork-heading" class="text-xs font-bold uppercase tracking-wider text-neutral-500">Customer artwork & protected previews</h4>
                        <span class="text-[10px] font-bold text-neutral-400">ORDER FILES</span>
                    </div>

                    <div class="space-y-3">
                        @forelse($artworkUploads as $upload)
                            @php
                                $file = $upload['file'];
                                $displayFilename = $file->original_filename . ($file->extension ? '.' . $file->extension : '');
                                $isProtectedPreview = ($upload['reference']['role'] ?? null) === 'protected_mockup';
                            @endphp
                            <article class="flex flex-col gap-4 rounded-xl border border-neutral-200 bg-neutral-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-start gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-white text-neutral-500" aria-hidden="true">
                                        <x-icons.lucide name="lucide-file-image" class="w-5 h-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-neutral-800">{{ $displayFilename }}</p>
                                        <p class="mt-0.5 text-xs text-neutral-500">{{ $upload['item']->product_name_snapshot }} · {{ strtoupper((string) ($upload['print_position'] ?? 'position not set')) }} · {{ strtoupper((string) ($upload['print_method'] ?? 'method not set')) }}</p>
                                        <p class="mt-1 text-[11px] text-neutral-400">{{ number_format($file->size_bytes / 1024, 0) }} KB · {{ $isProtectedPreview ? 'System-generated protected preview' : 'Original artwork uploaded by customer' }}</p>
                                        @if($upload['customer_note'])
                                            <p class="mt-2 text-xs text-neutral-600"><span class="font-semibold">Customer note:</span> {{ $upload['customer_note'] }}</p>
                                        @endif
                                    </div>
                                </div>
                                @can('download', $file)
                                    <div class="flex shrink-0 gap-2">
                                        <a href="{{ route('admin.orders.files.preview', ['order' => $order->public_id, 'file' => $file->public_id]) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-3 py-2 text-xs font-bold text-neutral-700 hover:bg-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-400">
                                            <x-icons.lucide name="lucide-eye" class="w-4 h-4" /> View
                                        </a>
                                        <a href="{{ route('admin.orders.files.download', ['order' => $order->public_id, 'file' => $file->public_id]) }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-neutral-900 px-3 py-2 text-xs font-bold text-white hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-neutral-500">
                                            <x-icons.lucide name="lucide-download" class="w-4 h-4" /> {{ $isProtectedPreview ? 'Download preview' : 'Download original' }}
                                        </a>
                                    </div>
                                @endcan
                            </article>
                        @empty
                            <div class="rounded-xl border border-dashed border-neutral-300 px-4 py-8 text-center">
                                <p class="text-sm font-semibold text-neutral-700">No customer artwork on this order</p>
                                <p class="mt-1 text-xs text-neutral-500">Artwork uploaded during product customization will appear here.</p>
                            </div>
                        @endforelse
                    </div>
                </section>

                @can('update', $order)
                    <section aria-labelledby="upload-proof-heading" class="rounded-xl border border-neutral-200 bg-neutral-50 p-4 sm:p-5">
                        <h4 id="upload-proof-heading" class="text-sm font-bold text-neutral-800">Upload finished customer proof</h4>
                        <p class="mt-1 text-xs leading-5 text-neutral-500">Use a JPG, PNG, WebP, GIF, or PDF up to 5 MB. This file becomes visible to the customer immediately.</p>

                        <form action="{{ route('admin.orders.proofs.store', ['order' => $order->public_id]) }}" method="POST" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @csrf
                            <div class="sm:col-span-2">
                                <label for="proof_file" class="mb-1.5 block text-xs font-bold text-neutral-700">Finished proof file <span class="text-rose-600">*</span></label>
                                <input id="proof_file" name="proof_file" type="file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" required class="block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-700 file:mr-3 file:rounded-md file:border-0 file:bg-neutral-900 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white focus:border-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-300">
                            </div>
                            <div>
                                <label for="display_name" class="mb-1.5 block text-xs font-bold text-neutral-700">Proof name</label>
                                <input id="display_name" name="display_name" type="text" maxlength="180" value="{{ old('display_name') }}" placeholder="Example: Front print proof v1" class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-800 placeholder:text-neutral-400 focus:border-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-300">
                            </div>
                            <div class="flex items-end">
                                <label class="flex min-h-10 w-full cursor-pointer items-center gap-2 rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm font-semibold text-neutral-700">
                                    <input name="is_featured" type="hidden" value="0">
                                    <input name="is_featured" type="checkbox" value="1" {{ old('is_featured', '1') ? 'checked' : '' }} class="h-4 w-4 rounded border-neutral-300 text-neutral-900 focus:ring-neutral-500">
                                    Mark as current proof
                                </label>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="proof_notes" class="mb-1.5 block text-xs font-bold text-neutral-700">Message for customer <span class="font-normal text-neutral-400">(optional)</span></label>
                                <textarea id="proof_notes" name="notes" rows="3" maxlength="1000" placeholder="Example: Please check the logo size and placement." class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-800 placeholder:text-neutral-400 focus:border-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-300">{{ old('notes') }}</textarea>
                            </div>
                            <div class="sm:col-span-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-[11px] text-neutral-500">Uploading a new current proof moves the order back to design review.</p>
                                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2">
                                    <x-icons.lucide name="lucide-upload" class="w-4 h-4" /> Upload & share with customer
                                </button>
                            </div>
                        </form>
                    </section>
                @endcan

                <section aria-labelledby="shared-proofs-heading" class="space-y-3">
                    <h4 id="shared-proofs-heading" class="text-xs font-bold uppercase tracking-wider text-neutral-500">Proofs shared with customer</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($order->mockups as $mockup)
                        <div class="border border-neutral-100 rounded-xl p-4 bg-neutral-50 flex flex-col justify-between">
                            <div>
                                @if($mockup->file)
                                    @if($mockup->file->isImage())
                                        <img class="w-full h-44 object-contain bg-white rounded-lg border border-neutral-200 mb-3" src="{{ route('admin.orders.files.preview', ['order' => $order->public_id, 'file' => $mockup->file->public_id]) }}" alt="{{ $mockup->display_name }}">
                                    @else
                                        <div class="w-full h-44 bg-white rounded-lg border border-neutral-200 flex flex-col gap-2 items-center justify-center text-xs text-neutral-500 font-bold mb-3">
                                            <x-icons.lucide name="lucide-file-text" class="w-8 h-8" /> PDF proof
                                        </div>
                                    @endif
                                @else
                                    <div class="w-full h-44 bg-neutral-200 rounded-lg flex items-center justify-center text-xs text-neutral-400 uppercase font-bold mb-3">No Image</div>
                                @endif
                                <h4 class="text-sm font-bold text-neutral-800">{{ $mockup->display_name }}</h4>
                                @if($mockup->is_featured)
                                    <span class="inline-flex items-center px-2 py-0.5 text-[9px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 mt-1 uppercase">
                                        Featured
                                    </span>
                                @endif
                                @if($mockup->notes)
                                    <p class="text-xs text-neutral-500 mt-2">{{ $mockup->notes }}</p>
                                @endif
                            </div>
                            @if($mockup->file)
                                <div class="mt-4 flex gap-2 border-t border-neutral-200/50 pt-3">
                                    <a href="{{ route('admin.orders.files.preview', ['order' => $order->public_id, 'file' => $mockup->file->public_id]) }}" target="_blank" rel="noopener" class="flex-1 rounded-lg border border-neutral-200 bg-white px-3 py-2 text-center text-xs font-bold text-neutral-700 hover:bg-neutral-100">View</a>
                                    <a href="{{ route('admin.orders.files.download', ['order' => $order->public_id, 'file' => $mockup->file->public_id]) }}" class="flex-1 rounded-lg bg-neutral-900 px-3 py-2 text-center text-xs font-bold text-white hover:bg-neutral-800">Download</a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-2 text-center text-xs text-neutral-400 py-8">
                            No finished proofs have been shared yet.
                        </div>
                    @endforelse
                    </div>
                </section>
            </div>
 
            <!-- 5. History Log Panel -->
            <div x-show="activeTab === 'timeline'" class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-6">
                <h3 class="text-sm font-bold text-neutral-800 uppercase tracking-wider border-b border-neutral-100 pb-3">Audit Logs & Status Timeline</h3>
                
                <div class="relative border-l border-neutral-200 ml-4 pl-6 space-y-6">
                    @forelse($timelineLogs as $log)
                        <div class="relative">
                            <!-- timeline circle marker -->
                            <span class="absolute -left-10 top-0.5 bg-neutral-900 border-4 border-white w-4 h-4 rounded-full"></span>
                            
                            <div>
                                <span class="text-[10px] font-mono text-neutral-400">
                                    {{ $log->occurred_at?->format('d M Y H:i:s') ?? $log->created_at->format('d M Y H:i:s') }}
                                </span>
                                <h4 class="text-xs font-bold text-neutral-800 mt-0.5 uppercase tracking-wide">
                                    {{ str_replace('.', ' / ', $log->action) }}
                                </h4>
                                <p class="text-xs text-neutral-600 mt-1 leading-relaxed">{{ $log->summary }}</p>
                                <div class="text-[10px] text-neutral-400 font-medium mt-1">
                                    Actor: <span class="font-semibold">{{ $log->actor_label_snapshot ?? 'System' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-neutral-400 py-4 ml-2">No timeline events logged.</p>
                    @endforelse
                </div>
            </div>
 
        </div>
        
        <!-- Sidebar Panel Info (Right / 1 Column) -->
        <div class="space-y-6">
            <!-- Customer Detail Card -->
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-4">
                <h3 class="text-xs font-bold text-neutral-400 uppercase tracking-wider border-b border-neutral-100 pb-2">Customer Profile</h3>
                <div>
                    <strong class="text-sm text-neutral-800">{{ $summary['customer_snapshot']['name'] }}</strong>
                    <p class="text-xs text-neutral-500 mt-1">{{ $summary['customer_snapshot']['email'] }}</p>
                    @if($summary['customer_snapshot']['phone'])
                        <p class="text-xs text-neutral-500 mt-0.5">{{ $summary['customer_snapshot']['phone'] }}</p>
                    @endif
                    @if($summary['customer_snapshot']['company_name'])
                        <p class="text-xs text-neutral-500 mt-0.5 font-semibold">Company: {{ $summary['customer_snapshot']['company_name'] }}</p>
                    @endif
                </div>
            </div>
 
            <!-- Internal / Customer Notes Card -->
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-4">
                <h3 class="text-xs font-bold text-neutral-400 uppercase tracking-wider border-b border-neutral-100 pb-2">Order Notes</h3>
                <div>
                    <h4 class="text-xs font-bold text-neutral-500 mb-1">Customer Notes</h4>
                    <p class="text-xs text-neutral-600 italic bg-neutral-50 p-2.5 rounded-lg border border-neutral-100">
                        {{ $order->customer_notes ?? 'No customer notes entered.' }}
                    </p>
                </div>
                <div class="border-t border-neutral-100 pt-3">
                    <h4 class="text-xs font-bold text-neutral-500 mb-1">Internal Notes</h4>
                    <p class="text-xs text-neutral-600 bg-neutral-50 p-2.5 rounded-lg border border-neutral-100">
                        {{ $order->internal_notes ?? 'No internal notes entered.' }}
                    </p>
                </div>
            </div>
        </div>
        
    </div>
 
    <!-- 6. PDF Print Preview Modal/Overlay Interface -->
    <div 
        x-data="{ showPreview: false }"
        @open-pdf-preview.window="showPreview = true"
        x-show="showPreview"
        x-transition
        class="fixed inset-0 z-50 overflow-hidden flex items-center justify-center p-4 bg-black/60"
        style="display: none;"
    >
        <div 
            @click.away="showPreview = false" 
            class="bg-white rounded-3xl w-full max-w-4xl h-[85vh] flex flex-col shadow-2xl relative overflow-hidden"
        >
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-neutral-200 flex items-center justify-between shrink-0">
                <h3 class="text-sm font-bold text-neutral-800 uppercase tracking-wider">Document Preview - Order Confirmation</h3>
                <button 
                    @click="showPreview = false"
                    class="p-1 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors focus:outline-none"
                >
                    <x-icons.lucide name="lucide-x" class="w-5 h-5" />
                </button>
            </div>
            <!-- Modal Content Iframe wrapper -->
            <div class="flex-1 bg-neutral-100 overflow-hidden relative">
                <iframe 
                    class="w-full h-full border-0" 
                    src="{{ route('admin.orders.pdf.preview', ['order' => $order->public_id]) }}"
                    title="Order Confirmation Preview"
                ></iframe>
            </div>
            <!-- Modal Footer Actions -->
            <div class="px-6 py-4 border-t border-neutral-200 flex justify-end gap-3 shrink-0">
                <button 
                    @click="showPreview = false"
                    class="px-4 py-2 border border-neutral-300 text-neutral-700 font-bold rounded-xl text-xs hover:bg-neutral-50 transition-colors focus:outline-none"
                >
                    Close Preview
                </button>
                <a 
                    href="{{ route('admin.orders.pdf.download', ['order' => $order->public_id]) }}"
                    class="px-4 py-2 bg-[color:var(--color-brand-600)] text-white font-bold rounded-xl text-xs hover:bg-[color:var(--color-brand-700)] transition-colors focus:outline-none flex items-center gap-1.5"
                >
                    <x-icons.lucide name="lucide-download" class="w-3.5 h-3.5" />
                    Download File
                </a>
            </div>
        </div>
    </div>

    <!-- 7. Order Cancellation Modal -->
    @if ($order->canBeCancelled() && auth()->user()->can('cancel', $order))
        <template x-teleport="body">
            <div 
                x-data="{ 
                    showCancelModal: {{ $errors->hasAny(['reason_code', 'reason_note', 'material_consumed', 'customization_applied', 'scrap_incurred', 'affected_quantity', 'scrap_quantity', 'scrap_amount', 'production_impact_notes', 'physical_interception_confirmed', 'create_refund_request', 'refund_amount']) ? 'true' : 'false' }},
                    reasonCode: '{{ old('reason_code', 'customer_request') }}',
                    reasonNote: '{{ old('reason_note', '') }}',
                    materialConsumed: '{{ old('material_consumed', '0') }}',
                    customizationApplied: '{{ old('customization_applied', '0') }}',
                    scrapIncurred: '{{ old('scrap_incurred', '0') }}',
                    affectedQuantity: '{{ old('affected_quantity', '') }}',
                    scrapQuantity: '{{ old('scrap_quantity', '') }}',
                    scrapAmount: '{{ old('scrap_amount', '') }}',
                    productionImpactNotes: '{{ old('production_impact_notes', '') }}',
                    physicalInterceptionConfirmed: {{ old('physical_interception_confirmed') ? 'true' : 'false' }},
                    createRefundRequest: {{ old('create_refund_request') ? 'true' : 'false' }},
                    refundAmount: '{{ old('refund_amount', number_format($order->getAvailableRefundRequestAmountMinor() / 100, 2, '.', '')) }}',
                    availableRefund: {{ $order->getAvailableRefundRequestAmountMinor() / 100 }},
                    isSubmitting: false,
                }"
                @open-cancel-modal.window="showCancelModal = true"
                @keydown.escape.window="showCancelModal = false"
                x-show="showCancelModal"
                x-cloak
                class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
                style="display: none;"
            >
                <div 
                    @click.away="showCancelModal = false"
                    class="bg-white rounded-3xl w-full max-w-xl max-h-[90vh] flex flex-col shadow-2xl relative overflow-hidden my-8"
                >
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-neutral-200 flex items-center justify-between shrink-0 bg-neutral-50/50">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-rose-100 text-rose-700">
                                <x-icons.lucide name="lucide-alert-triangle" class="w-4 h-4" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-neutral-900">
                                    @if ($order->status === 'in_production')
                                        Cancel Order in Production (Manager Override)
                                    @elseif ($order->status === 'ready_to_ship')
                                        Cancel Ready to Ship Order (Interception)
                                    @else
                                        Cancel Order {{ $order->public_id }}
                                    @endif
                                </h3>
                                <p class="text-xs text-neutral-500">Current Status: <span class="font-bold uppercase font-mono">{{ str_replace('_', ' ', $order->status) }}</span></p>
                            </div>
                        </div>
                        <button 
                            @click="showCancelModal = false"
                            class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors focus:outline-none"
                        >
                            <x-icons.lucide name="lucide-x" class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form 
                        action="{{ route('admin.orders.cancel', ['order' => $order->public_id]) }}" 
                        method="POST" 
                        @submit="isSubmitting = true"
                        class="flex-1 overflow-y-auto p-6 space-y-5"
                    >
                        @csrf

                        <!-- Stage-Specific Warning Callouts -->
                        @if ($order->status === 'in_production')
                            <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 space-y-2">
                                <div class="flex items-start gap-2 text-amber-900 font-bold text-xs">
                                    <x-icons.lucide name="lucide-alert-triangle" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                                    <span>⚠ Production has already started</span>
                                </div>
                                <p class="text-xs text-amber-800 leading-relaxed">
                                    Cancelling this order may create customized inventory or production waste that cannot be returned to normal saleable stock. Operational notes and loss assessments are mandatory.
                                </p>
                            </div>
                        @elseif ($order->status === 'ready_to_ship')
                            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 space-y-2">
                                <div class="flex items-start gap-2 text-blue-900 font-bold text-xs">
                                    <x-icons.lucide name="lucide-shield-alert" class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
                                    <span>Verify Physical Custody & Shipping Interception</span>
                                </div>
                                <p class="text-xs text-blue-800 leading-relaxed">
                                    Confirm that the parcel has been physically held and has NOT been collected by the courier partner. If handed over, use the Customer Return workflow.
                                </p>
                            </div>
                        @endif

                        <!-- Reason Code -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-neutral-700">
                                Cancellation Reason <span class="text-rose-600">*</span>
                            </label>
                            <select 
                                name="reason_code" 
                                x-model="reasonCode" 
                                required
                                class="w-full rounded-xl border border-neutral-300 px-3.5 py-2 text-xs font-semibold text-neutral-800 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                            >
                                <option value="customer_request">Customer Request</option>
                                <option value="artwork_issue">Artwork / Design Issue</option>
                                <option value="lead_time_delay">Lead Time Delay</option>
                                <option value="pricing_error">Pricing / Quotation Error</option>
                                <option value="duplicate_order">Duplicate Order</option>
                                <option value="other">Other Reason</option>
                            </select>
                        </div>

                        <!-- Reason Note / Explanation -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-neutral-700">
                                Explanation Notes <span x-show="reasonCode === 'other'" class="text-rose-600">*</span>
                            </label>
                            <textarea 
                                name="reason_note" 
                                x-model="reasonNote" 
                                :required="reasonCode === 'other'"
                                rows="2" 
                                placeholder="Details about customer cancellation or context..."
                                class="w-full rounded-xl border border-neutral-300 px-3.5 py-2 text-xs text-neutral-800 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                            ></textarea>
                        </div>

                        <!-- In-Production Loss Metrics -->
                        @if ($order->status === 'in_production')
                            <div class="border-t border-neutral-100 pt-4 space-y-4">
                                <h4 class="text-xs font-extrabold uppercase tracking-wider text-neutral-900 flex items-center gap-1.5">
                                    <x-icons.lucide name="lucide-factory" class="w-3.5 h-3.5 text-neutral-500" />
                                    Production Loss & Material Assessment
                                </h4>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-semibold text-neutral-700">Material Consumed?</label>
                                        <select 
                                            name="material_consumed" 
                                            x-model="materialConsumed"
                                            class="w-full rounded-xl border border-neutral-300 px-3.5 py-2 text-xs font-semibold text-neutral-800 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                                        >
                                            <option value="0">No (Untouched blanks)</option>
                                            <option value="1">Yes (Customization started)</option>
                                        </select>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-semibold text-neutral-700">Scrap Incurred?</label>
                                        <select 
                                            name="scrap_incurred" 
                                            x-model="scrapIncurred"
                                            class="w-full rounded-xl border border-neutral-300 px-3.5 py-2 text-xs font-semibold text-neutral-800 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                                        >
                                            <option value="0">No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-semibold text-neutral-700">Affected Pieces</label>
                                        <input 
                                            type="number" 
                                            name="affected_quantity" 
                                            x-model="affectedQuantity" 
                                            min="0" 
                                            placeholder="e.g. 50"
                                            class="w-full rounded-xl border border-neutral-300 px-3 py-2 text-xs text-neutral-800 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                                        />
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-semibold text-neutral-700">Scrap Pieces</label>
                                        <input 
                                            type="number" 
                                            name="scrap_quantity" 
                                            x-model="scrapQuantity" 
                                            min="0" 
                                            placeholder="e.g. 20"
                                            class="w-full rounded-xl border border-neutral-300 px-3 py-2 text-xs text-neutral-800 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                                        />
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-semibold text-neutral-700">Estimated Scrap Loss</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-bold text-neutral-400">₹</span>
                                            <input 
                                                type="number" 
                                                step="0.01" 
                                                name="scrap_amount" 
                                                x-model="scrapAmount" 
                                                min="0" 
                                                placeholder="0.00"
                                                class="w-full rounded-xl border border-neutral-300 pl-7 pr-3 py-2 text-xs font-mono text-neutral-800 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-neutral-700">
                                        Production Impact Notes <span class="text-rose-600">*</span>
                                    </label>
                                    <textarea 
                                        name="production_impact_notes" 
                                        x-model="productionImpactNotes" 
                                        required 
                                        rows="2" 
                                        placeholder="e.g., 35 shirts already screen-printed with customer branding; remaining 65 blank garments untouched."
                                        class="w-full rounded-xl border border-neutral-300 px-3.5 py-2 text-xs text-neutral-800 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                                    ></textarea>
                                </div>
                            </div>
                        @endif

                        <!-- Ready-to-Ship Physical Interception Checkbox -->
                        @if ($order->status === 'ready_to_ship')
                            <div class="border-t border-neutral-100 pt-4">
                                <label class="flex items-start gap-3 cursor-pointer select-none">
                                    <input 
                                        type="checkbox" 
                                        name="physical_interception_confirmed" 
                                        value="1" 
                                        x-model="physicalInterceptionConfirmed" 
                                        required
                                        class="mt-1 w-4 h-4 text-neutral-900 rounded border-neutral-300 focus:ring-neutral-900"
                                    />
                                    <span class="text-xs text-neutral-800 font-semibold leading-relaxed">
                                        I confirm that fulfillment is under our physical control and the package has been intercepted prior to courier dispatch. <span class="text-rose-600">*</span>
                                    </span>
                                </label>
                            </div>
                        @endif

                        <!-- Financial Summary & Refund Request Option -->
                        <div class="border-t border-neutral-100 pt-4 space-y-3">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-neutral-900 flex items-center gap-1.5">
                                <x-icons.lucide name="lucide-credit-card" class="w-3.5 h-3.5 text-neutral-500" />
                                Financial Exposure & Refund
                            </h4>

                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3 space-y-1.5 text-xs">
                                <div class="flex justify-between text-neutral-600">
                                    <span>Captured Payments:</span>
                                    <span class="font-mono font-semibold">₹{{ number_format($order->getCapturedPaymentsAmountMinor() / 100, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-neutral-600">
                                    <span>Already Refunded:</span>
                                    <span class="font-mono font-semibold">₹{{ number_format($order->getSucceededRefundsAmountMinor() / 100, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-neutral-900 font-bold border-t border-neutral-200 pt-1.5">
                                    <span>Available to Request:</span>
                                    <span class="font-mono text-emerald-700">₹{{ number_format($order->getAvailableRefundRequestAmountMinor() / 100, 2) }}</span>
                                </div>
                            </div>

                            @if ($order->getAvailableRefundRequestAmountMinor() > 0)
                                <div class="space-y-2 pt-1">
                                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                        <input 
                                            type="checkbox" 
                                            name="create_refund_request" 
                                            value="1" 
                                            x-model="createRefundRequest" 
                                            class="w-4 h-4 text-neutral-900 rounded border-neutral-300 focus:ring-neutral-900"
                                        />
                                        <span class="text-xs font-bold text-neutral-800">
                                            Create internal customer refund request
                                        </span>
                                    </label>

                                    <div x-show="createRefundRequest" x-transition class="pl-6 space-y-1.5">
                                        <label class="block text-xs font-semibold text-neutral-700">Requested Refund Amount (₹)</label>
                                        <div class="relative max-w-xs">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-bold text-neutral-400">₹</span>
                                            <input 
                                                type="number" 
                                                step="0.01" 
                                                name="refund_amount" 
                                                x-model="refundAmount" 
                                                min="0.01" 
                                                :max="availableRefund"
                                                class="w-full rounded-xl border border-neutral-300 pl-7 pr-3 py-1.5 text-xs font-mono text-neutral-800 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                                            />
                                        </div>
                                        <p class="text-[11px] text-neutral-500">
                                            Creates an internal refund request in <span class="font-semibold text-neutral-700">requested</span> status for finance disbursement; money is not disbursed automatically.
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-4 border-t border-neutral-200 flex items-center justify-end gap-3">
                            <button 
                                type="button" 
                                @click="showCancelModal = false"
                                class="px-4 py-2 border border-neutral-300 text-neutral-700 font-bold rounded-xl text-xs hover:bg-neutral-50 transition-colors focus:outline-none"
                            >
                                Keep Order
                            </button>
                            <button 
                                type="submit" 
                                :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-rose-500/20 disabled:opacity-50"
                            >
                                <x-icons.lucide name="lucide-x-circle" class="w-4 h-4" />
                                <span x-text="isSubmitting ? 'Cancelling...' : 'Confirm Cancellation'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    @endif

</x-layouts.admin>
