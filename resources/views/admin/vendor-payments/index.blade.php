<x-layouts.admin title="Vendor Payments & Payables Ledger">
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between border-b border-neutral-200 pb-3 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 tracking-tight">Vendor Payments (Accounts Payable)</h1>
                <p class="text-xs text-neutral-500 mt-1">Track supplier procurement payments, outstanding vendor liabilities, and settlement audit logs.</p>
            </div>
            <a href="{{ route('admin.purchases.index') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-neutral-900 text-white rounded-xl hover:bg-neutral-800 transition-colors shadow-xs">
                <x-icons.lucide name="lucide-shopping-bag" class="w-4 h-4" />
                <span>Purchase Orders</span>
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
            <!-- Total Paid Settled -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Total Paid Settled</span>
                    <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100">
                        <x-icons.lucide name="lucide-indian-rupee" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold font-mono text-emerald-600">₹{{ number_format($metrics->totalPaidMinor / 100, 2) }}</div>
                <div class="mt-1 text-xs text-emerald-700/80">Completed vendor payouts</div>
            </div>

            <!-- Outstanding Vendor Liability -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Outstanding Liability</span>
                    <div class="p-2.5 rounded-xl bg-amber-50 text-amber-600 border border-amber-100">
                        <x-icons.lucide name="lucide-wallet" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold font-mono text-amber-600">₹{{ number_format($metrics->unpaidLiabilityMinor / 100, 2) }}</div>
                <div class="mt-1 text-xs text-amber-700/80">Unpaid procurement balance</div>
            </div>

            <!-- Active Payee Suppliers -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Active Payee Suppliers</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-700">
                        <x-icons.lucide name="lucide-building-2" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-neutral-900">{{ number_format($metrics->activeVendorsPaidCount) }}</div>
                <div class="mt-1 text-xs text-neutral-500">Vendors with payment logs</div>
            </div>

            <!-- Total Transactions -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Payment Transactions</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-700">
                        <x-icons.lucide name="lucide-receipt" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-neutral-900">{{ number_format($metrics->paymentCount) }}</div>
                <div class="mt-1 text-xs text-neutral-500">In active filter scope</div>
            </div>
        </div>

        <!-- Filter Bar Form -->
        <div class="bg-white border border-neutral-200 rounded-2xl p-4 sm:p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.vendor_payments.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search Input -->
                <div class="lg:col-span-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <x-icons.lucide name="lucide-search" class="w-4 h-4" />
                    </div>
                    <input type="text" name="search" value="{{ $filters->search }}"
                           placeholder="Search reference, UTR, PO number, vendor name..."
                           class="w-full pl-9 pr-4 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                </div>

                <!-- Vendor Selector -->
                <div>
                    <select name="vendor_id" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ ($filters->vendorId ?? '') === null ? 'selected' : '' }}>All Vendors</option>
                        @foreach ($vendors as $v)
                            <option value="{{ $v->id }}" {{ $filters->vendorId === $v->id ? 'selected' : '' }}>
                                {{ $v->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Payment Method Selector -->
                <div>
                    <select name="payment_method" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ $filters->paymentMethod === '' ? 'selected' : '' }}>All Methods</option>
                        @foreach ($paymentMethods as $pm)
                            <option value="{{ $pm->value }}" {{ $filters->paymentMethod === $pm->value ? 'selected' : '' }}>
                                {{ $pm->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="w-full py-2 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 transition-colors">Filter</button>
                </div>
            </form>
        </div>

        <!-- Vendor Payments Table -->
        <div class="bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-neutral-800">
                    <thead class="bg-neutral-50/80 text-[10px] uppercase font-bold text-neutral-400 tracking-wider border-b border-neutral-200">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">Payment Date</th>
                            <th class="py-3.5 px-4 font-semibold">Vendor</th>
                            <th class="py-3.5 px-4 font-semibold">Purchase Order</th>
                            <th class="py-3.5 px-4 font-semibold">Method</th>
                            <th class="py-3.5 px-4 font-semibold">Reference / UTR</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Amount Settled</th>
                            <th class="py-3.5 px-4 font-semibold">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($payments as $payment)
                            <tr class="hover:bg-neutral-50/60 transition-colors">
                                <!-- Payment Date -->
                                <td class="py-3.5 px-4 font-mono text-neutral-800 font-medium">
                                    {{ $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i') : '—' }}
                                </td>

                                <!-- Vendor -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-neutral-900">{{ $payment->vendorOrder?->vendor?->name ?? 'Unknown Vendor' }}</div>
                                    <div class="text-[11px] font-mono text-neutral-400 mt-0.5">{{ $payment->vendorOrder?->vendor?->vendor_code }}</div>
                                </td>

                                <!-- PO Public ID -->
                                <td class="py-3.5 px-4 font-mono font-bold text-[color:var(--color-brand-600)]">
                                    @if ($payment->vendorOrder)
                                        <a href="{{ route('admin.purchases.show', $payment->vendorOrder->public_id) }}" class="hover:underline">
                                            {{ $payment->vendorOrder->public_id }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>

                                <!-- Payment Method -->
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $payment->payment_method?->badgeClass() ?? 'bg-neutral-100 text-neutral-700 border-neutral-200' }}">
                                        {{ $payment->payment_method?->label() ?? ucfirst($payment->payment_method->value ?? 'Other') }}
                                    </span>
                                </td>

                                <!-- Reference -->
                                <td class="py-3.5 px-4 font-mono text-neutral-700">
                                    {{ $payment->reference ?? '—' }}
                                </td>

                                <!-- Amount Settled -->
                                <td class="py-3.5 px-4 text-right font-mono font-extrabold text-emerald-600">
                                    ₹{{ number_format($payment->amount_minor / 100, 2) }}
                                </td>

                                <!-- Recorded By -->
                                <td class="py-3.5 px-4 text-neutral-600">
                                    {{ $payment->recordedBy?->name ?? 'System' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-neutral-400">
                                    <x-icons.lucide name="lucide-credit-card" class="w-10 h-10 mx-auto text-neutral-300 mb-3" />
                                    <p class="text-sm font-semibold text-neutral-700">No vendor payments found</p>
                                    <p class="text-xs text-neutral-400 mt-1">Try adjusting filter parameters or record a payment on a purchase order page.</p>
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
