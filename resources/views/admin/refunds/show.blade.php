<x-layouts.admin title="Refund #{{ $refund->id }}">
    <div class="space-y-6 max-w-4xl mx-auto">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-neutral-200 pb-5">
            <div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.refunds.index') }}" class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors">
                        <x-icons.lucide name="lucide-arrow-left" class="w-5 h-5" />
                    </a>
                    <h1 class="text-2xl font-bold text-neutral-900 font-mono">Refund #{{ $refund->id }}</h1>
                    @if ($refund->status === 'succeeded')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Succeeded</span>
                    @elseif ($refund->status === 'approved')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Approved</span>
                    @elseif ($refund->status === 'requested')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">Requested</span>
                    @elseif ($refund->status === 'failed')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">Failed</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200">{{ ucfirst($refund->status) }}</span>
                    @endif
                </div>
                <p class="text-xs text-neutral-500 mt-1">Refund for Order <strong class="text-neutral-900 font-mono">#{{ $refund->order?->public_id }}</strong></p>
            </div>

            <!-- Action Controls -->
            <div class="flex items-center gap-2">
                @can('approve', $refund)
                    @if ($refund->status === 'requested')
                        <form action="{{ route('admin.refunds.approve', $refund) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-[color:var(--color-brand-600)] hover:bg-[color:var(--color-brand-700)] rounded-xl shadow-xs transition-colors">
                                <x-icons.lucide name="lucide-check" class="w-4 h-4" />
                                <span>Approve Refund</span>
                            </button>
                        </form>
                    @endif
                @endcan

                @can('process', $refund)
                    @if ($refund->status === 'approved')
                        <form action="{{ route('admin.refunds.process', $refund) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition-colors">
                                <x-icons.lucide name="lucide-send" class="w-4 h-4" />
                                <span>Process Payout</span>
                            </button>
                        </form>
                    @endif
                @endcan

                @can('retry', $refund)
                    @if ($refund->status === 'failed')
                        <form action="{{ route('admin.refunds.retry', $refund) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-xs transition-colors">
                                <x-icons.lucide name="lucide-refresh-cw" class="w-4 h-4" />
                                <span>Retry Payout Execution</span>
                            </button>
                        </form>
                    @endif
                @endcan

                @can('cancel', $refund)
                    @if (in_array($refund->status, ['requested', 'approved'], true))
                        <form action="{{ route('admin.refunds.cancel', $refund) }}" method="POST" onsubmit="return confirm('Cancel this customer refund request?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl transition-colors">
                                <x-icons.lucide name="lucide-x" class="w-4 h-4" />
                                <span>Cancel</span>
                            </button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>

        <!-- Session Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-3 shadow-xs">
                <x-icons.lucide name="lucide-check-circle-2" class="w-4 h-4 flex-shrink-0 text-emerald-600" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Metadata Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Amount & Reason Card -->
            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs space-y-4">
                <h3 class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Refund Summary</h3>

                <div class="space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-neutral-500">Refund Amount:</span>
                        <span class="font-mono font-extrabold text-2xl text-amber-600">₹{{ number_format($refund->amount_minor / 100, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-neutral-500">Refund Type:</span>
                        <span class="font-medium text-neutral-900 uppercase tracking-wide">{{ $refund->refund_type }} Refund</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-neutral-500">Reason Code:</span>
                        <span class="font-medium text-neutral-900 capitalize">{{ str_replace('_', ' ', $refund->reason_code) }}</span>
                    </div>
                </div>

                <div class="pt-3 border-t border-neutral-100 text-xs text-neutral-500 space-y-1">
                    <div>Reason Notes: {{ $refund->reason_note ?? 'No notes provided' }}</div>
                </div>
            </div>

            <!-- Association Metadata Card -->
            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs space-y-3">
                <h3 class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Association Metadata</h3>

                <div class="space-y-2 text-xs text-neutral-700 font-mono">
                    <div><span class="text-neutral-400 uppercase font-sans">Order Public ID:</span> #{{ $refund->order?->public_id }}</div>
                    <div><span class="text-neutral-400 uppercase font-sans">Associated Payment:</span> <a href="{{ route('admin.payments.show', $refund->payment_id) }}" class="text-[color:var(--color-brand-600)] font-bold hover:underline">PAY-#{{ $refund->payment_id }}</a></div>
                    <div><span class="text-neutral-400 uppercase font-sans">Provider Refund ID:</span> {{ $refund->provider_refund_id ?? 'N/A' }}</div>
                    <div><span class="text-neutral-400 uppercase font-sans">Requested By:</span> {{ $refund->requester?->name ?? 'System' }}</div>
                    <div><span class="text-neutral-400 uppercase font-sans">Approved By:</span> {{ $refund->approver?->name ?? 'N/A' }}</div>
                    <div><span class="text-neutral-400 uppercase font-sans">Processed By:</span> {{ $refund->processor?->name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <!-- Refund Lifecycle Timeline -->
        <div class="p-6 rounded-2xl bg-white border border-neutral-200 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-neutral-900 border-b border-neutral-100 pb-3">Refund Audit Progression</h3>

            <div class="space-y-4">
                <!-- Step 1: Requested -->
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-700 flex-shrink-0">
                        <x-icons.lucide name="lucide-file-plus" class="w-4 h-4" />
                    </div>
                    <div>
                        <div class="text-xs font-bold text-neutral-900">Refund Request Created</div>
                        <div class="text-[11px] text-neutral-400 mt-0.5">{{ $refund->requested_at ? $refund->requested_at->format('Y-m-d H:i:s') : 'N/A' }}</div>
                    </div>
                </div>

                <!-- Step 2: Approved -->
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full {{ $refund->approved_at ? 'bg-indigo-50 border border-indigo-200 text-indigo-700' : 'bg-neutral-50 border border-neutral-200 text-neutral-300' }} flex items-center justify-center flex-shrink-0">
                        <x-icons.lucide name="lucide-check-circle" class="w-4 h-4" />
                    </div>
                    <div>
                        <div class="text-xs font-bold {{ $refund->approved_at ? 'text-neutral-900' : 'text-neutral-400' }}">Finance Manager Approval</div>
                        <div class="text-[11px] text-neutral-400 mt-0.5">{{ $refund->approved_at ? $refund->approved_at->format('Y-m-d H:i:s') : 'Awaiting manager approval' }}</div>
                    </div>
                </div>

                <!-- Step 3: Processed / Succeeded -->
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full {{ $refund->processed_at ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-neutral-50 border border-neutral-200 text-neutral-300' }} flex items-center justify-center flex-shrink-0">
                        <x-icons.lucide name="lucide-check-check" class="w-4 h-4" />
                    </div>
                    <div>
                        <div class="text-xs font-bold {{ $refund->processed_at ? 'text-neutral-900' : 'text-neutral-400' }}">Payout Processed &amp; Succeeded</div>
                        <div class="text-[11px] text-neutral-400 mt-0.5">{{ $refund->processed_at ? $refund->processed_at->format('Y-m-d H:i:s') : 'Awaiting payout completion' }}</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-layouts.admin>
