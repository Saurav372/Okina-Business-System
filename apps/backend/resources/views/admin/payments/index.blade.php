<x-layouts.admin title="Customer Payments">
    <div class="space-y-6">

        <!-- Scopes (Tabs Toolbar) -->
        <div class="flex items-center gap-1.5 border-b border-neutral-200 pb-3 mb-4 overflow-x-auto flex-nowrap scrollbar-none" style="scrollbar-width: none; -ms-overflow-style: none;">
            <a href="{{ route('admin.payments.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[color:var(--color-brand-50)] text-[color:var(--color-brand-700)] border border-[color:var(--color-brand-200)] shrink-0">
                Payments Log
            </a>
            <a href="{{ route('admin.refunds.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 transition-colors shrink-0">
                Customer Refunds
            </a>
            <a href="{{ route('admin.accounting.customer_ledger') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 transition-colors shrink-0">
                Customer Ledger
            </a>
        </div>

        <!-- Session Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-3 shadow-xs">
                <x-icons.lucide name="lucide-check-circle-2" class="w-4 h-4 flex-shrink-0 text-emerald-600" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- KPI Metrics Grid (4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Gross Collections -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Gross Collections</span>
                    <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100">
                        <x-icons.lucide name="lucide-indian-rupee" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-emerald-600">₹{{ number_format($summary->grossCollectionsMinor / 100, 2) }}</div>
                <div class="mt-1 text-xs text-emerald-700/80">Succeeded payment transactions</div>
            </div>

            <!-- Gateway Fees -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-red-600 uppercase tracking-wider">Gateway Fees</span>
                    <div class="p-2.5 rounded-xl bg-red-50 text-red-600 border border-red-100">
                        <x-icons.lucide name="lucide-percent" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-red-600">₹{{ number_format($summary->totalGatewayFeesMinor / 100, 2) }}</div>
                <div class="mt-1 text-xs text-red-700/80">Processing charges &amp; fees</div>
            </div>

            <!-- Refund Volume -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Refund Volume</span>
                    <div class="p-2.5 rounded-xl bg-amber-50 text-amber-600 border border-amber-100">
                        <x-icons.lucide name="lucide-undo-2" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-amber-600">₹{{ number_format($summary->refundVolumeMinor / 100, 2) }}</div>
                <div class="mt-1 text-xs text-amber-700/80">Returned to customers</div>
            </div>

            <!-- Net Collected Revenue -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Net Revenue</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-700">
                        <x-icons.lucide name="lucide-wallet" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-neutral-900">₹{{ number_format($summary->netRevenueMinor / 100, 2) }}</div>
                <div class="mt-1 text-xs text-neutral-500">Gross − Refunds − Fees</div>
            </div>
        </div>

        <!-- Filter Bar Form -->
        <div class="bg-white border border-neutral-200 rounded-2xl p-4 sm:p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.payments.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search Input -->
                <div class="lg:col-span-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <x-icons.lucide name="lucide-search" class="w-4 h-4" />
                    </div>
                    <input type="text" name="search" value="{{ $filters->search ?? '' }}" placeholder="Search receipt #, payment ID, order ID, or customer..." class="w-full pl-9 pr-4 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                </div>

                <!-- Payment Method Selector -->
                <div>
                    <select name="method" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ ($filters->method ?? '') === '' ? 'selected' : '' }}>All Payment Methods</option>
                        <option value="cash" {{ ($filters->method ?? '') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="bank_transfer" {{ ($filters->method ?? '') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="upi" {{ ($filters->method ?? '') === 'upi' ? 'selected' : '' }}>UPI</option>
                        <option value="cheque" {{ ($filters->method ?? '') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="razorpay" {{ ($filters->method ?? '') === 'razorpay' ? 'selected' : '' }}>Razorpay Gateway</option>
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
                        <option value="succeeded" {{ ($filters->status ?? '') === 'succeeded' ? 'selected' : '' }}>Succeeded</option>
                        <option value="pending_verification" {{ ($filters->status ?? '') === 'pending_verification' ? 'selected' : '' }}>Pending Verification</option>
                        <option value="failed" {{ ($filters->status ?? '') === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="cancelled" {{ ($filters->status ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    <button type="submit" class="px-5 py-2 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 transition-colors shrink-0">Filter</button>
                </div>
            </form>
        </div>

        <!-- Payments Table -->
        <div class="bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-neutral-800">
                    <thead class="bg-neutral-50/80 text-[10px] uppercase font-bold text-neutral-400 tracking-wider border-b border-neutral-200">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">Receipt # / Ref</th>
                            <th class="py-3.5 px-4 font-semibold">Order &amp; Customer</th>
                            <th class="py-3.5 px-4 font-semibold">Method &amp; Provider</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Amount</th>
                            <th class="py-3.5 px-4 font-semibold">Status</th>
                            <th class="py-3.5 px-4 font-semibold">Date</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($payments as $payment)
                            <tr class="hover:bg-neutral-50/60 transition-colors">
                                <!-- Receipt Number -->
                                <td class="py-3.5 px-4 font-mono font-bold text-[color:var(--color-brand-600)]">
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="hover:underline">
                                        {{ $payment->receipt_number ?? "PAY-#{$payment->id}" }}
                                    </a>
                                </td>

                                <!-- Order & Customer -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-neutral-900">
                                        <a href="{{ route('admin.orders.show', $payment->order?->public_id ?? '') }}" class="hover:underline">
                                            Order #{{ $payment->order?->public_id ?? 'N/A' }}
                                        </a>
                                    </div>
                                    <div class="text-[11px] text-neutral-400 mt-0.5">{{ $payment->order?->customer?->name ?? 'Guest / N/A' }}</div>
                                </td>

                                <!-- Method & Provider -->
                                <td class="py-3.5 px-4 text-xs">
                                    <div class="font-semibold text-neutral-900 uppercase tracking-wide">{{ str_replace('_', ' ', $payment->method ?? 'N/A') }}</div>
                                    <div class="text-neutral-500 capitalize mt-0.5">Via {{ $payment->provider ?? 'manual' }}</div>
                                </td>

                                <!-- Amount -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-600">
                                    ₹{{ number_format($payment->amount_minor / 100, 2) }}
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-4">
                                    @if ($payment->status === 'succeeded')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Succeeded</span>
                                    @elseif ($payment->status === 'pending_verification')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">Pending Verification</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200">{{ ucfirst($payment->status) }}</span>
                                    @endif
                                </td>

                                <!-- Date -->
                                <td class="py-3.5 px-4 text-xs text-neutral-500 font-mono">
                                    {{ $payment->created_at ? $payment->created_at->format('Y-m-d H:i') : 'N/A' }}
                                </td>

                                <!-- Action -->
                                <td class="py-3.5 px-4 text-center">
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-neutral-700 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-lg shadow-2xs transition-colors">
                                        <x-icons.lucide name="lucide-eye" class="w-3.5 h-3.5 text-neutral-500" />
                                        <span>View</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-neutral-400">
                                    <x-icons.lucide name="lucide-indian-rupee" class="w-10 h-10 mx-auto text-neutral-300 mb-3" />
                                    <p class="text-sm font-semibold text-neutral-700">No payment transactions found</p>
                                    <p class="text-xs text-neutral-400 mt-1">Try adjusting active search filters or check customer orders.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Server-Side Pagination -->
            @if ($payments->hasPages())
                <div class="p-4 border-t border-neutral-200">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.admin>
