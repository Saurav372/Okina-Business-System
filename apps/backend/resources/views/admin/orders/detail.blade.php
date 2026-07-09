<x-layouts.admin :title="'Order Detail - ' . $order->public_id . ' | Okina Craft'">
    <x-slot:header>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl font-bold text-neutral-800">Order {{ $order->public_id }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200">
                        {{ $order->status }}
                    </span>
                </div>
                <p class="text-xs text-neutral-400 mt-1">Placed on {{ $order->placed_at?->format('d M Y H:i') ?? $order->created_at->format('d M Y H:i') }}</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <button 
                    @click="$dispatch('open-pdf-preview')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-neutral-100 text-neutral-700 hover:bg-neutral-200 font-bold rounded-xl text-xs transition-all duration-150 focus:outline-none"
                >
                    <x-icons.lucide name="lucide-eye" class="w-4 h-4" />
                    Preview Document
                </button>
                <a 
                    href="{{ route('admin.orders.pdf.download', ['order' => $order->public_id]) }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-[color:var(--color-brand-600)] text-white hover:bg-[color:var(--color-brand-700)] font-bold rounded-xl text-xs transition-all duration-150 focus:outline-none"
                >
                    <x-icons.lucide name="lucide-download" class="w-4 h-4" />
                    Download PDF
                </a>
            </div>
        </div>
    </x-slot:header>
 
    <!-- Tabbed Layout Container -->
    <div x-data="{ activeTab: 'items' }" class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        <!-- Tab content & detail panels (Left / 2 Columns) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Tabs Header Navigation -->
            <div class="flex border-b border-neutral-200 bg-white px-4 py-2 rounded-2xl shadow-xs gap-2">
                <button 
                    @click="activeTab = 'items'" 
                    :class="activeTab === 'items' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-500 hover:bg-neutral-50 hover:text-neutral-800'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none"
                >
                    Items
                </button>
                <button 
                    @click="activeTab = 'payments'" 
                    :class="activeTab === 'payments' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-500 hover:bg-neutral-50 hover:text-neutral-800'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none"
                >
                    Payments & Refunds
                </button>
                <button 
                    @click="activeTab = 'shipping'" 
                    :class="activeTab === 'shipping' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-500 hover:bg-neutral-50 hover:text-neutral-800'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none"
                >
                    Shipping & Tracking
                </button>
                <button 
                    @click="activeTab = 'mockups'" 
                    :class="activeTab === 'mockups' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-500 hover:bg-neutral-50 hover:text-neutral-800'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none"
                >
                    Mockups ({{ $order->mockups->count() }})
                </button>
                <button 
                    @click="activeTab = 'timeline'" 
                    :class="activeTab === 'timeline' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-500 hover:bg-neutral-50 hover:text-neutral-800'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none"
                >
                    History Log
                </button>
            </div>
 
            <!-- 1. Items Panel -->
            <div x-show="activeTab === 'items'" class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-6">
                <h3 class="text-sm font-bold text-neutral-800 uppercase tracking-wider border-b border-neutral-100 pb-3">Order Items</h3>
                <div class="space-y-4">
                    @foreach ($summary['items'] as $item)
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between border border-neutral-100 rounded-xl p-4 gap-4 bg-neutral-50/50 hover:bg-neutral-50 transition-colors">
                            <div class="flex items-center gap-4">
                                @php $cs = $item['customization_snapshot'] ?? null; @endphp
                                @if (is_array($cs) && isset($cs['mockup_preview_url']) && $cs['mockup_preview_url'])
                                    <img class="w-16 h-16 object-cover rounded-lg border border-neutral-200" src="{{ $cs['mockup_preview_url'] }}" alt="Preview">
                                @else
                                    <div class="w-16 h-16 bg-neutral-100 rounded-lg border border-neutral-200 flex items-center justify-center text-[10px] text-neutral-400 font-bold uppercase">No Preview</div>
                                @endif
                                <div>
                                    <h4 class="text-sm font-bold text-neutral-800">{{ $item['product_name'] }}</h4>
                                    <p class="text-xs text-neutral-400 mt-0.5">SKU: {{ $item['sku_code'] }}</p>
                                    @if(!empty($cs) && is_array($cs))
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach($cs as $ckey => $cval)
                                                @if(is_scalar($cval) && !in_array($ckey, ['mockup_preview_url', 'expires_in_minutes', 'route_name']))
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-neutral-200/60 text-[9px] font-bold text-neutral-600">
                                                        {{ ucwords(str_replace('_', ' ', $ckey)) }}: {{ $cval }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-neutral-400 font-medium">₹{{ number_format($item['unit_price_minor'] / 100, 2) }} &times; {{ $item['quantity'] }}</div>
                                <div class="text-sm font-bold text-neutral-800 mt-1 font-mono">₹{{ number_format($item['line_total_minor'] / 100, 2) }}</div>
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
 
            <!-- 4. Mockups Panel -->
            <div x-show="activeTab === 'mockups'" class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-6">
                <h3 class="text-sm font-bold text-neutral-800 uppercase tracking-wider border-b border-neutral-100 pb-3">Featured & Production Mockups</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($order->mockups as $mockup)
                        <div class="border border-neutral-100 rounded-xl p-4 bg-neutral-50 flex flex-col justify-between">
                            <div>
                                @if($mockup->file)
                                    <img class="w-full h-44 object-cover rounded-lg border border-neutral-200 mb-3" src="{{ $mockup->file->url ?? Storage::disk('private')->url($mockup->file->path) }}" alt="{{ $mockup->display_name }}">
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
                                    <p class="text-xs text-neutral-500 mt-2 italic">Notes: {{ $mockup->notes }}</p>
                                @endif
                            </div>
                            <div class="text-xs text-neutral-400 mt-4 border-t border-neutral-200/50 pt-2 font-mono">
                                Sort Order: {{ $mockup->sort_order }}
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 text-center text-xs text-neutral-400 py-8">
                            No mockups attached to this order.
                        </div>
                    @endforelse
                </div>
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
 
</x-layouts.admin>
