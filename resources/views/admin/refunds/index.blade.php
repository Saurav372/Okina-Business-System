<x-layouts.admin title="Customer Refunds">
    <div class="space-y-6" x-data="{ openRequestModal: false }">

        <!-- Scopes (Tabs Toolbar) -->
        <div class="flex items-center justify-between border-b border-neutral-200 pb-3 mb-4">
            <div class="flex items-center gap-1.5 overflow-x-auto flex-nowrap scrollbar-none" style="scrollbar-width: none; -ms-overflow-style: none;">
                <a href="{{ route('admin.payments.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 transition-colors shrink-0">
                    Payments Log
                </a>
                <a href="{{ route('admin.refunds.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[color:var(--color-brand-50)] text-[color:var(--color-brand-700)] border border-[color:var(--color-brand-200)] shrink-0">
                    Customer Refunds
                </a>
                <a href="{{ route('admin.accounting.customer_ledger') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 transition-colors shrink-0">
                    Customer Ledger
                </a>
            </div>

            @can('create', \App\Models\Refund::class)
                <button @click="openRequestModal = true" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-xl hover:bg-[color:var(--color-brand-700)] transition-colors shadow-xs">
                    <x-icons.lucide name="lucide-plus" class="w-4 h-4" />
                    <span>Request Customer Refund</span>
                </button>
            @endcan
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

        <!-- KPI Metrics Grid (4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Refunded Volume -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Total Refunded</span>
                    <div class="p-2.5 rounded-xl bg-amber-50 text-amber-600 border border-amber-100">
                        <x-icons.lucide name="lucide-undo-2" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-amber-600">₹{{ number_format($metrics->totalRefundedVolumeMinor / 100, 2) }}</div>
                <div class="mt-1 text-xs text-amber-700/80">Succeeded payout volume</div>
            </div>

            <!-- Pending Approval Count -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Pending Approval</span>
                    <div class="p-2.5 rounded-xl bg-amber-50 text-amber-600 border border-amber-100">
                        <x-icons.lucide name="lucide-clock" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-amber-600">{{ number_format($metrics->requestedCount) }}</div>
                <div class="mt-1 text-xs text-amber-700/80">Awaiting manager approval</div>
            </div>

            <!-- Approved Count -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Approved Payouts</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-700">
                        <x-icons.lucide name="lucide-check-circle-2" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-neutral-900">{{ number_format($metrics->approvedCount) }}</div>
                <div class="mt-1 text-xs text-neutral-500">Ready for gateway execution</div>
            </div>

            <!-- Succeeded Count -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Succeeded Count</span>
                    <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100">
                        <x-icons.lucide name="lucide-check-check" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-emerald-600">{{ number_format($metrics->succeededCount) }}</div>
                <div class="mt-1 text-xs text-emerald-700/80">Completed customer payouts</div>
            </div>
        </div>

        <!-- Filter Bar Form -->
        <div class="bg-white border border-neutral-200 rounded-2xl p-4 sm:p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.refunds.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search Input -->
                <div class="lg:col-span-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <x-icons.lucide name="lucide-search" class="w-4 h-4" />
                    </div>
                    <input type="text" name="search" value="{{ $filters->search ?? '' }}" placeholder="Search refund ID, provider ref, order ID, or customer..." class="w-full pl-9 pr-4 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                </div>

                <!-- Refund Type Selector -->
                <div>
                    <select name="refund_type" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ ($filters->refundType ?? '') === '' ? 'selected' : '' }}>All Refund Types</option>
                        <option value="full" {{ ($filters->refundType ?? '') === 'full' ? 'selected' : '' }}>Full Refund</option>
                        <option value="partial" {{ ($filters->refundType ?? '') === 'partial' ? 'selected' : '' }}>Partial Refund</option>
                    </select>
                </div>

                <!-- Provider Selector -->
                <div>
                    <select name="provider" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ ($filters->provider ?? '') === '' ? 'selected' : '' }}>All Providers</option>
                        <option value="manual" {{ ($filters->provider ?? '') === 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="razorpay" {{ ($filters->provider ?? '') === 'razorpay' ? 'selected' : '' }}>Razorpay</option>
                    </select>
                </div>

                <!-- Status Selector & Submit -->
                <div class="flex items-center gap-2">
                    <select name="status" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ ($filters->status ?? '') === '' ? 'selected' : '' }}>All Statuses</option>
                        <option value="requested" {{ ($filters->status ?? '') === 'requested' ? 'selected' : '' }}>Requested</option>
                        <option value="approved" {{ ($filters->status ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="processing" {{ ($filters->status ?? '') === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="succeeded" {{ ($filters->status ?? '') === 'succeeded' ? 'selected' : '' }}>Succeeded</option>
                        <option value="failed" {{ ($filters->status ?? '') === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="cancelled" {{ ($filters->status ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    <button type="submit" class="px-5 py-2 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 transition-colors shrink-0">Filter</button>
                </div>
            </form>
        </div>

        <!-- Refunds Table -->
        <div class="bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-neutral-800">
                    <thead class="bg-neutral-50/80 text-[10px] uppercase font-bold text-neutral-400 tracking-wider border-b border-neutral-200">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">Refund ID</th>
                            <th class="py-3.5 px-4 font-semibold">Order &amp; Customer</th>
                            <th class="py-3.5 px-4 font-semibold">Reason &amp; Type</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Amount</th>
                            <th class="py-3.5 px-4 font-semibold">Status</th>
                            <th class="py-3.5 px-4 font-semibold">Date</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($refunds as $rf)
                            <tr class="hover:bg-neutral-50/60 transition-colors">
                                <!-- Refund ID -->
                                <td class="py-3.5 px-4 font-mono font-bold text-[color:var(--color-brand-600)]">
                                    <a href="{{ route('admin.refunds.show', $rf) }}" class="hover:underline">
                                        #{{ $rf->id }}
                                    </a>
                                </td>

                                <!-- Order & Customer -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-neutral-900">Order #{{ $rf->order?->public_id ?? 'N/A' }}</div>
                                    <div class="text-[11px] text-neutral-400 mt-0.5">{{ $rf->order?->customer?->name ?? 'Guest / N/A' }}</div>
                                </td>

                                <!-- Reason & Type -->
                                <td class="py-3.5 px-4 text-xs">
                                    <div class="font-semibold text-neutral-900 capitalize">{{ str_replace('_', ' ', $rf->reason_code) }}</div>
                                    <div class="text-neutral-500 uppercase tracking-wide mt-0.5">{{ $rf->refund_type }} Refund</div>
                                </td>

                                <!-- Amount -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-amber-600">
                                    ₹{{ number_format($rf->amount_minor / 100, 2) }}
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-4">
                                    @if ($rf->status === 'succeeded')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Succeeded</span>
                                    @elseif ($rf->status === 'approved')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Approved</span>
                                    @elseif ($rf->status === 'requested')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">Requested</span>
                                    @elseif ($rf->status === 'failed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200">Failed</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200">{{ ucfirst($rf->status) }}</span>
                                    @endif
                                </td>

                                <!-- Date -->
                                <td class="py-3.5 px-4 text-xs text-neutral-500 font-mono">
                                    {{ $rf->created_at ? $rf->created_at->format('Y-m-d H:i') : 'N/A' }}
                                </td>

                                <!-- Action -->
                                <td class="py-3.5 px-4 text-center">
                                    <a href="{{ route('admin.refunds.show', $rf) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-neutral-700 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-lg shadow-2xs transition-colors">
                                        <x-icons.lucide name="lucide-eye" class="w-3.5 h-3.5 text-neutral-500" />
                                        <span>View</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-neutral-400">
                                    <x-icons.lucide name="lucide-undo-2" class="w-10 h-10 mx-auto text-neutral-300 mb-3" />
                                    <p class="text-sm font-semibold text-neutral-700">No customer refunds found</p>
                                    <p class="text-xs text-neutral-400 mt-1">Try adjusting search filters or request a new refund.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Server-Side Pagination -->
            @if ($refunds->hasPages())
                <div class="p-4 border-t border-neutral-200">
                    {{ $refunds->links() }}
                </div>
            @endif
        </div>

        <!-- Request Refund Modal -->
        <div x-show="openRequestModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="openRequestModal = false" class="fixed inset-0 bg-neutral-950/40 backdrop-blur-xs transition-opacity" aria-hidden="true"></div>

                <div class="inline-block align-bottom bg-white border border-neutral-200 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('admin.refunds.store') }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        <div class="flex items-center justify-between border-b border-neutral-200 pb-3">
                            <h3 class="text-base font-bold text-neutral-900">Request Customer Refund</h3>
                            <button type="button" @click="openRequestModal = false" class="text-neutral-400 hover:text-neutral-600">
                                <x-icons.lucide name="lucide-x" class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Select Payment -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Select Succeeded Payment</label>
                            <select name="payment_id" required class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                <option value="">-- Select Succeeded Payment --</option>
                                @foreach ($succeededPayments as $p)
                                    <option value="{{ $p->id }}">Order #{{ $p->order?->public_id }} - ₹{{ number_format($p->amount_minor / 100, 2) }} ({{ $p->order?->customer?->name ?? 'Guest' }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Refund Amount (Rupees) -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Refund Amount (in ₹ Rupees)</label>
                            <input type="number" name="amount_rupees" step="0.01" min="0.01" placeholder="e.g. 500.00" required class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 font-mono focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]" onchange="document.getElementById('amount_minor_input').value = Math.round(parseFloat(this.value) * 100);">
                            <input type="hidden" name="amount_minor" id="amount_minor_input">
                        </div>

                        <!-- Mandatory Reason Code -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Mandatory Reason Code</label>
                            <select name="reason_code" required class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                <option value="customer_cancellation">Customer Cancellation</option>
                                <option value="damaged_goods">Damaged / Defective Goods</option>
                                <option value="duplicate_payment">Duplicate Payment</option>
                                <option value="pricing_correction">Pricing Correction</option>
                                <option value="order_adjustment">Order Adjustment</option>
                                <option value="other">Other Reason</option>
                            </select>
                        </div>

                        <!-- Reason Note -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Reason Notes &amp; Explanation</label>
                            <textarea name="reason_note" rows="2" placeholder="Details regarding this refund request..." class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"></textarea>
                        </div>

                        <!-- Submit Bar -->
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-neutral-200">
                            <button type="button" @click="openRequestModal = false" class="px-4 py-2 text-xs font-semibold text-neutral-700 hover:text-neutral-900 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-xl">Cancel</button>
                            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-[color:var(--color-brand-600)] hover:bg-[color:var(--color-brand-700)] rounded-xl shadow-xs">Create Refund Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-layouts.admin>
