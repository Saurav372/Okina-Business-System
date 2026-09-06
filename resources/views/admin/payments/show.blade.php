<x-layouts.admin title="Payment {{ $payment->receipt_number ?? 'PAY-'.$payment->id }}">
    <div class="space-y-6 max-w-4xl mx-auto">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-neutral-200 pb-5">
            <div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.payments.index') }}" class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors">
                        <x-icons.lucide name="lucide-arrow-left" class="w-5 h-5" />
                    </a>
                    <h1 class="text-2xl font-bold text-neutral-900 font-mono">{{ $payment->receipt_number ?? "PAY-#{$payment->id}" }}</h1>
                    @if ($payment->status === 'succeeded')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Succeeded</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">{{ ucfirst($payment->status) }}</span>
                    @endif
                </div>
                <p class="text-xs text-neutral-500 mt-1">Payment collected for Order <strong class="text-neutral-900 font-mono">#{{ $payment->order?->public_id }}</strong></p>
            </div>

            <!-- Action Controls -->
            <div class="flex items-center gap-2">
                @if ($payment->status === 'succeeded')
                    <a href="{{ route('admin.refunds.index') }}?search={{ $payment->order?->public_id }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-[color:var(--color-brand-600)] hover:bg-[color:var(--color-brand-700)] rounded-xl shadow-xs transition-colors">
                        <x-icons.lucide name="lucide-undo-2" class="w-4 h-4" />
                        <span>Issue Refund Request</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Payment Breakdown Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Amount Breakdown Card -->
            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs space-y-4">
                <h3 class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Financial Breakdown</h3>

                <div class="space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-neutral-500">Gross Payment Amount:</span>
                        <span class="font-mono font-bold text-neutral-900">₹{{ number_format($payment->amount_minor / 100, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-neutral-500">Gateway Fee:</span>
                        <span class="font-mono text-red-600">₹{{ number_format(($payment->gateway_fee_minor ?? 0) / 100, 2) }}</span>
                    </div>
                    <div class="pt-3 border-t border-neutral-100 flex justify-between items-center text-base font-bold">
                        <span class="text-neutral-900">Net Revenue Collected:</span>
                        <span class="font-mono text-emerald-600">₹{{ number_format(($payment->net_amount_minor ?? $payment->amount_minor) / 100, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Provider & Gateway Card -->
            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs space-y-3">
                <h3 class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Gateway &amp; Reference Info</h3>

                <div class="space-y-2 text-xs text-neutral-700 font-mono">
                    <div><span class="text-neutral-400 uppercase font-sans">Payment Method:</span> {{ strtoupper($payment->method ?? 'N/A') }}</div>
                    <div><span class="text-neutral-400 uppercase font-sans">Gateway Provider:</span> {{ ucfirst($payment->provider ?? 'manual') }}</div>
                    <div><span class="text-neutral-400 uppercase font-sans">Provider Payment ID:</span> {{ $payment->provider_payment_id ?? 'N/A' }}</div>
                    <div><span class="text-neutral-400 uppercase font-sans">Provider Order ID:</span> {{ $payment->provider_order_id ?? 'N/A' }}</div>
                    <div><span class="text-neutral-400 uppercase font-sans">Provider Reference:</span> {{ $payment->provider_reference ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <!-- Associated Customer & Refunds -->
        <div class="p-6 rounded-2xl bg-white border border-neutral-200 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-neutral-900 border-b border-neutral-100 pb-3">Associated Refunds History</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-neutral-800">
                    <thead class="bg-neutral-50/80 text-[10px] uppercase font-bold text-neutral-400 tracking-wider border-b border-neutral-200">
                        <tr>
                            <th class="py-2.5 px-3 font-semibold">Refund ID</th>
                            <th class="py-2.5 px-3 font-semibold">Reason</th>
                            <th class="py-2.5 px-3 font-semibold text-right">Amount</th>
                            <th class="py-2.5 px-3 font-semibold">Status</th>
                            <th class="py-2.5 px-3 font-semibold">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($payment->refunds as $rf)
                            <tr class="hover:bg-neutral-50/60">
                                <td class="py-2.5 px-3 font-mono font-bold text-[color:var(--color-brand-600)]">
                                    <a href="{{ route('admin.refunds.show', $rf) }}" class="hover:underline">#{{ $rf->id }}</a>
                                </td>
                                <td class="py-2.5 px-3 text-xs text-neutral-700 capitalize">{{ str_replace('_', ' ', $rf->reason_code) }}</td>
                                <td class="py-2.5 px-3 font-mono font-bold text-amber-600 text-right">₹{{ number_format($rf->amount_minor / 100, 2) }}</td>
                                <td class="py-2.5 px-3"><span class="text-xs font-semibold uppercase text-neutral-700">{{ $rf->status }}</span></td>
                                <td class="py-2.5 px-3 text-xs font-mono text-neutral-500">{{ $rf->created_at ? $rf->created_at->format('Y-m-d H:i') : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-neutral-400 text-xs">No refunds issued against this payment.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-layouts.admin>
