<x-layouts.admin title="Purchase Order {{ $order->public_id }}">
    <div class="space-y-6 max-w-4xl mx-auto" x-data="{
        receiveModalOpen: false,
        payModalOpen: false,
        activeItem: null,
        receiveQty: 1,
        payAmountRupees: 0,
        openReceiveModal(item) {
            this.activeItem = item;
            this.receiveQty = item.remaining;
            this.receiveModalOpen = true;
        },
        openPayModal(maxRemainingRupees) {
            this.payAmountRupees = maxRemainingRupees;
            this.payModalOpen = true;
        }
    }">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-neutral-200 pb-5">
            <div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.purchases.index') }}" class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors">
                        <x-icons.lucide name="lucide-arrow-left" class="w-5 h-5" />
                    </a>
                    <h1 class="text-2xl font-bold text-neutral-900 font-mono">{{ $order->public_id }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $order->status->badgeClass() }}">
                        {{ $order->status->label() }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $order->payment_status->badgeClass() }}">
                        {{ $order->payment_status->label() }}
                    </span>
                </div>
                <p class="text-xs text-neutral-500 mt-1">
                    Vendor: <strong class="text-neutral-900">{{ $order->vendor?->name }}</strong>
                    ({{ $order->vendor?->vendor_code }})
                </p>
            </div>
        </div>

        <!-- Session Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-3 shadow-xs">
                <x-icons.lucide name="lucide-check-circle-2" class="w-4 h-4 flex-shrink-0 text-emerald-600" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs font-medium space-y-1 shadow-xs">
                @foreach ($errors->all() as $error)
                    <div class="flex items-center gap-2">
                        <x-icons.lucide name="lucide-alert-circle" class="w-4 h-4 flex-shrink-0 text-red-600" />
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @php
            $totalPaidMinor = $order->payments->where('status', \App\Enums\VendorPaymentStatus::PAID)->sum('amount_minor');
            $remainingLiabilityMinor = max(0, $order->total_amount_minor - $totalPaidMinor);
            $remainingLiabilityRupees = $remainingLiabilityMinor / 100;
        @endphp

        <!-- Order Financial Summary Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <!-- Total Order Value -->
            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Total PO Amount</span>
                <div class="mt-2 text-xl font-extrabold font-mono text-neutral-900">₹{{ number_format($order->total_amount_minor / 100, 2) }}</div>
                <div class="text-[11px] text-neutral-500 mt-1">Committed Procurement</div>
            </div>

            <!-- Total Paid -->
            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Total Settled Paid</span>
                <div class="mt-2 text-xl font-extrabold font-mono text-emerald-600">₹{{ number_format($totalPaidMinor / 100, 2) }}</div>
                <div class="text-[11px] text-emerald-700/80 mt-1">Payments Settled</div>
            </div>

            <!-- Remaining Payable Balance -->
            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600">Outstanding Liability</span>
                <div class="mt-2 text-xl font-extrabold font-mono text-amber-600">₹{{ number_format($remainingLiabilityRupees, 2) }}</div>
                <div class="text-[11px] text-amber-700/80 mt-1">Unpaid Balance</div>
            </div>

            <!-- Order Dates -->
            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Timeline &amp; Terms</span>
                <div class="mt-1.5 text-xs text-neutral-800 space-y-0.5">
                    <div>Ordered: <strong class="font-mono">{{ $order->ordered_at ? $order->ordered_at->format('Y-m-d') : '—' }}</strong></div>
                    <div>Expected: <strong class="font-mono">{{ $order->expected_at ? $order->expected_at->format('Y-m-d') : '—' }}</strong></div>
                    <div>Terms: <strong>{{ $order->vendor?->payment_terms ?? 'Standard' }}</strong></div>
                </div>
            </div>
        </div>

        <!-- Line Items & Stock-In Receiving Table -->
        <div class="bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="p-5 border-b border-neutral-100">
                <h3 class="text-sm font-bold text-neutral-900">Line Items &amp; Stock-In Receiving</h3>
                <p class="text-xs text-neutral-500 mt-0.5">Click <strong>Receive Goods</strong> on any line item with remaining quantity to stock-in goods to inventory.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-neutral-800">
                    <thead class="bg-neutral-50/80 text-[10px] uppercase font-bold text-neutral-400 tracking-wider border-b border-neutral-200">
                        <tr>
                            <th class="py-3 px-4 font-semibold">SKU</th>
                            <th class="py-3 px-4 font-semibold">Product</th>
                            <th class="py-3 px-4 font-semibold text-right">Ordered</th>
                            <th class="py-3 px-4 font-semibold text-right">Received</th>
                            <th class="py-3 px-4 font-semibold text-right">Remaining</th>
                            <th class="py-3 px-4 font-semibold text-right">Unit Cost</th>
                            <th class="py-3 px-4 font-semibold text-right">Line Total</th>
                            <th class="py-3 px-4 font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($order->items as $item)
                            <tr class="hover:bg-neutral-50/60 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-[color:var(--color-brand-600)]">
                                    {{ $item->productSku?->sku_code ?? $item->sku_code_snapshot }}
                                </td>
                                <td class="py-3 px-4 text-neutral-700">
                                    {{ $item->productSku?->product?->name ?? '—' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-neutral-900">
                                    {{ number_format($item->quantity_ordered) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold {{ $item->quantity_received > 0 ? 'text-emerald-600' : 'text-neutral-400' }}">
                                    {{ number_format($item->quantity_received) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold {{ $item->remainingQuantity() > 0 ? 'text-amber-600' : 'text-neutral-400' }}">
                                    {{ number_format($item->remainingQuantity()) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-neutral-700">
                                    ₹{{ number_format($item->unit_cost_minor / 100, 2) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-neutral-900">
                                    ₹{{ number_format($item->line_total_minor / 100, 2) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if ($item->isFullyReceived())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <x-icons.lucide name="lucide-check" class="w-3 h-3 mr-1" />
                                            Fully Received
                                        </span>
                                    @elseif (in_array($order->status, [\App\Enums\VendorOrderStatus::ORDERED, \App\Enums\VendorOrderStatus::PARTIALLY_RECEIVED], true))
                                        @can('inventory.manage')
                                            <button type="button"
                                                data-item="{{ json_encode([
                                                    'id'        => $item->id,
                                                    'sku_code'  => $item->productSku?->sku_code ?? $item->sku_code_snapshot,
                                                    'product'   => $item->productSku?->product?->name ?? '',
                                                    'ordered'   => $item->quantity_ordered,
                                                    'received'  => $item->quantity_received,
                                                    'remaining' => $item->remainingQuantity(),
                                                ]) }}"
                                                @click="openReceiveModal(JSON.parse($el.dataset.item))"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-2xs transition-colors">
                                                <x-icons.lucide name="lucide-package-check" class="w-3.5 h-3.5" />
                                                <span>Receive Goods</span>
                                            </button>
                                        @endcan
                                    @else
                                        <span class="text-neutral-400 italic text-[11px]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-neutral-200 bg-neutral-50/50">
                        <tr>
                            <td colspan="6" class="py-3 px-4 text-right text-xs font-bold text-neutral-600 uppercase tracking-wide">Order Total</td>
                            <td class="py-3 px-4 text-right font-mono font-extrabold text-neutral-900">
                                ₹{{ number_format($order->total_amount_minor / 100, 2) }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Vendor Payment History & Settlement Card -->
        <div class="bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="p-5 border-b border-neutral-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-neutral-900">Vendor Payment Settlement Ledger</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">Track payments issued to vendor for this procurement order.</p>
                </div>
                @if ($remainingLiabilityMinor > 0 && ! in_array($order->status, [\App\Enums\VendorOrderStatus::DRAFT, \App\Enums\VendorOrderStatus::CANCELLED], true))
                    @can('update', $order)
                        <button type="button"
                                @click="openPayModal({{ $remainingLiabilityRupees }})"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs transition-colors">
                            <x-icons.lucide name="lucide-credit-card" class="w-4 h-4" />
                            <span>Record Vendor Payment</span>
                        </button>
                    @endcan
                @elseif ($remainingLiabilityMinor === 0 && $order->payments->count() > 0)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <x-icons.lucide name="lucide-check-circle-2" class="w-4 h-4 mr-1.5 text-emerald-600" />
                        Fully Paid &amp; Settled
                    </span>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-neutral-800">
                    <thead class="bg-neutral-50/80 text-[10px] uppercase font-bold text-neutral-400 tracking-wider border-b border-neutral-200">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Payment Date</th>
                            <th class="py-3 px-4 font-semibold">Method</th>
                            <th class="py-3 px-4 font-semibold">Reference / UTR</th>
                            <th class="py-3 px-4 font-semibold text-right">Amount Settled</th>
                            <th class="py-3 px-4 font-semibold">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($order->payments->sortByDesc('paid_at') as $payment)
                            <tr class="hover:bg-neutral-50/60 transition-colors">
                                <td class="py-3 px-4 font-mono text-neutral-800 font-medium">
                                    {{ $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i') : '—' }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $payment->payment_method?->badgeClass() ?? 'bg-neutral-100 text-neutral-700 border-neutral-200' }}">
                                        {{ $payment->payment_method?->label() ?? ucfirst($payment->payment_method->value ?? 'Other') }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono text-neutral-700">
                                    {{ $payment->reference ?? '—' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-extrabold text-emerald-600">
                                    ₹{{ number_format($payment->amount_minor / 100, 2) }}
                                </td>
                                <td class="py-3 px-4 text-neutral-600">
                                    {{ $payment->recordedBy?->name ?? 'System' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-neutral-400">
                                    <x-icons.lucide name="lucide-wallet" class="w-8 h-8 mx-auto text-neutral-300 mb-2" />
                                    <p class="text-xs font-semibold text-neutral-600">No vendor payments recorded yet</p>
                                    <p class="text-[11px] text-neutral-400 mt-0.5">Click "Record Vendor Payment" above to settle outstanding liability.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Receive Item Modal -->
        <div x-show="receiveModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="receiveModalOpen = false" class="fixed inset-0 bg-neutral-950/40 backdrop-blur-xs transition-opacity" aria-hidden="true"></div>

                <div class="inline-block align-bottom bg-white border border-neutral-200 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <template x-if="activeItem">
                        <form action="{{ route('admin.purchases.receive', $order->public_id) }}" method="POST" class="p-6 space-y-4">
                            @csrf
                            <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                            <input type="hidden" name="items[0][vendor_order_item_id]" :value="activeItem.id">

                            <div class="flex items-center justify-between border-b border-neutral-200 pb-3">
                                <div>
                                    <h3 class="text-base font-bold text-neutral-900">Receive Stock-In Goods</h3>
                                    <p class="text-xs text-neutral-500 mt-0.5" x-text="`SKU: ${activeItem.sku_code}${activeItem.product ? ' · ' + activeItem.product : ''}`"></p>
                                </div>
                                <button type="button" @click="receiveModalOpen = false" class="text-neutral-400 hover:text-neutral-600">
                                    <x-icons.lucide name="lucide-x" class="w-5 h-5" />
                                </button>
                            </div>

                            <div class="grid grid-cols-3 gap-3 text-center text-xs">
                                <div class="bg-neutral-50 rounded-xl p-2.5 border border-neutral-200">
                                    <div class="text-neutral-400 text-[10px] uppercase font-bold">Ordered</div>
                                    <div class="font-mono font-extrabold text-neutral-900 mt-0.5" x-text="activeItem.ordered"></div>
                                </div>
                                <div class="bg-emerald-50 rounded-xl p-2.5 border border-emerald-200">
                                    <div class="text-emerald-600 text-[10px] uppercase font-bold">Received</div>
                                    <div class="font-mono font-extrabold text-emerald-700 mt-0.5" x-text="activeItem.received"></div>
                                </div>
                                <div class="bg-amber-50 rounded-xl p-2.5 border border-amber-200">
                                    <div class="text-amber-600 text-[10px] uppercase font-bold">Remaining</div>
                                    <div class="font-mono font-extrabold text-amber-700 mt-0.5" x-text="activeItem.remaining"></div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">
                                    Quantity Being Received Now
                                    <span class="text-neutral-400 font-normal">(max: <span x-text="activeItem.remaining"></span>)</span>
                                </label>
                                <input type="number"
                                       name="items[0][quantity_received]"
                                       min="1"
                                       :max="activeItem.remaining"
                                       x-model.number="receiveQty"
                                       required
                                       class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 font-mono focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Receiving Notes (Optional)</label>
                                <input type="text"
                                       name="notes"
                                       placeholder="Logistics batch, condition notes..."
                                       class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-3 border-t border-neutral-200">
                                <button type="button" @click="receiveModalOpen = false"
                                        class="px-4 py-2 text-xs font-semibold text-neutral-700 hover:text-neutral-900 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-xl transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition-colors">
                                    <span class="flex items-center gap-1.5">
                                        <x-icons.lucide name="lucide-package-check" class="w-4 h-4" />
                                        Confirm Goods Receipt
                                    </span>
                                </button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </div>

        <!-- Record Vendor Payment Modal -->
        <div x-show="payModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="payModalOpen = false" class="fixed inset-0 bg-neutral-950/40 backdrop-blur-xs transition-opacity" aria-hidden="true"></div>

                <div class="inline-block align-bottom bg-white border border-neutral-200 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <form action="{{ route('admin.purchase_orders.payments.store', $order) }}" method="POST" class="p-6 space-y-4" x-data="{
                        amountRupees: {{ $remainingLiabilityRupees }},
                        get amountMinor() { return Math.round(this.amountRupees * 100); }
                    }">
                        @csrf
                        <input type="hidden" name="amount_minor" :value="amountMinor">

                        <div class="flex items-center justify-between border-b border-neutral-200 pb-3">
                            <div>
                                <h3 class="text-base font-bold text-neutral-900">Record Vendor Payment</h3>
                                <p class="text-xs text-neutral-500 mt-0.5">PO: <strong class="font-mono text-neutral-900">{{ $order->public_id }}</strong> · {{ $order->vendor?->name }}</p>
                            </div>
                            <button type="button" @click="payModalOpen = false" class="text-neutral-400 hover:text-neutral-600">
                                <x-icons.lucide name="lucide-x" class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Amount Input -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">
                                Payment Amount (₹)
                                <span class="text-neutral-400 font-normal">(Outstanding: ₹{{ number_format($remainingLiabilityRupees, 2) }})</span>
                            </label>
                            <input type="number"
                                   step="0.01"
                                   min="0.01"
                                   max="{{ $remainingLiabilityRupees }}"
                                   x-model.number="amountRupees"
                                   required
                                   class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 font-mono font-bold focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">Payment Method</label>
                            <select name="payment_method" required class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                @foreach (\App\Enums\VendorPaymentMethod::cases() as $method)
                                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Reference / UTR -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">Reference / UTR / Cheque Number</label>
                            <input type="text"
                                   name="reference"
                                   placeholder="e.g. UTR123456789 or CHQ-990812"
                                   class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                        </div>

                        <!-- Paid At Date -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">Payment Settlement Date</label>
                            <input type="datetime-local"
                                   name="paid_at"
                                   value="{{ now()->format('Y-m-d\TH:i') }}"
                                   class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">Notes (Optional)</label>
                            <input type="text"
                                   name="notes"
                                   placeholder="Payment remarks or bank transaction note..."
                                   class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                        </div>

                        <!-- Submit Bar -->
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-neutral-200">
                            <button type="button" @click="payModalOpen = false"
                                    class="px-4 py-2 text-xs font-semibold text-neutral-700 hover:text-neutral-900 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-xl transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition-colors">
                                <span class="flex items-center gap-1.5">
                                    <x-icons.lucide name="lucide-check-circle-2" class="w-4 h-4" />
                                    Confirm Vendor Payment
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-layouts.admin>
