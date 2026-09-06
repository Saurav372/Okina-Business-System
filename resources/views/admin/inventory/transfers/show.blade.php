<x-layouts.admin title="Transfer {{ $transfer->transfer_code }}">
    <div class="space-y-6 max-w-4xl mx-auto">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-neutral-200 pb-5">
            <div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.inventory.transfers.index') }}" class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors">
                        <x-icons.lucide name="lucide-arrow-left" class="w-5 h-5" />
                    </a>
                    <h1 class="text-2xl font-bold text-neutral-900 font-mono">{{ $transfer->transfer_code }}</h1>
                    @if ($transfer->status->value === 'completed')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Completed</span>
                    @elseif ($transfer->status->value === 'in_transit')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">In Transit</span>
                    @elseif ($transfer->status->value === 'cancelled')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">Cancelled</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200">Draft</span>
                    @endif
                </div>
                <p class="text-xs text-neutral-500 mt-1">Transferring <strong class="text-neutral-900 font-mono">{{ number_format($transfer->quantity) }} units</strong> of {{ $transfer->productSku?->product?->name }}</p>
            </div>

            <!-- Lifecycle Action Buttons -->
            <div class="flex items-center gap-2">
                @can('manage', 'inventory')
                    @if ($transfer->status === \App\Enums\WarehouseTransferStatus::DRAFT)
                        <form action="{{ route('admin.inventory.transfers.ship', $transfer) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-[color:var(--color-brand-600)] hover:bg-[color:var(--color-brand-700)] rounded-xl shadow-xs transition-colors">
                                <x-icons.lucide name="lucide-truck" class="w-4 h-4" />
                                <span>Dispatch Transfer</span>
                            </button>
                        </form>
                    @endif

                    @if ($transfer->status === \App\Enums\WarehouseTransferStatus::IN_TRANSIT)
                        <form action="{{ route('admin.inventory.transfers.receive', $transfer) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition-colors">
                                <x-icons.lucide name="lucide-check-circle-2" class="w-4 h-4" />
                                <span>Receive Stock-In</span>
                            </button>
                        </form>
                    @endif

                    @if (in_array($transfer->status, [\App\Enums\WarehouseTransferStatus::DRAFT, \App\Enums\WarehouseTransferStatus::IN_TRANSIT], true))
                        <form action="{{ route('admin.inventory.transfers.cancel', $transfer) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this warehouse transfer?');">
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

        <!-- Transfer Metadata Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Source & Destination Location Card -->
            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs space-y-4">
                <h3 class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Route &amp; Location Flow</h3>
                
                <div class="flex items-center justify-between p-4 rounded-xl bg-neutral-50 border border-neutral-200">
                    <div>
                        <div class="text-[11px] text-neutral-500">Source Location</div>
                        <div class="text-sm font-bold text-neutral-900 mt-0.5">{{ $transfer->source_location->label() }}</div>
                    </div>
                    <x-icons.lucide name="lucide-arrow-right" class="w-5 h-5 text-[color:var(--color-brand-600)] flex-shrink-0" />
                    <div class="text-right">
                        <div class="text-[11px] text-neutral-500">Destination</div>
                        <div class="text-sm font-bold text-neutral-900 mt-0.5">{{ $transfer->destination_location->label() }}</div>
                    </div>
                </div>

                <div class="space-y-1 text-xs text-neutral-600">
                    <div>Quantity to Transfer: <strong class="text-neutral-900 font-mono">{{ number_format($transfer->quantity) }} units</strong></div>
                    <div>Notes: {{ $transfer->notes ?? 'No notes specified' }}</div>
                </div>
            </div>

            <!-- SKU & Item Metadata Card -->
            <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs space-y-3">
                <h3 class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Product SKU Information</h3>

                <div>
                    <div class="text-sm font-bold text-neutral-900">{{ $transfer->productSku?->product?->name }}</div>
                    <div class="text-xs font-mono text-[color:var(--color-brand-600)] font-bold mt-1">SKU Code: {{ $transfer->productSku?->sku_code }}</div>
                    <div class="text-xs text-neutral-500 mt-0.5">Barcode: {{ $transfer->productSku?->barcode ?? 'N/A' }}</div>
                </div>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500">
                    <span>Initiated By: {{ $transfer->initiator?->name ?? 'System' }}</span>
                    <span>Completed By: {{ $transfer->completer?->name ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Transfer Lifecycle Timeline -->
        <div class="p-6 rounded-2xl bg-white border border-neutral-200 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-neutral-900 border-b border-neutral-100 pb-3">Transfer Audit Timeline</h3>

            <div class="space-y-4">
                <!-- Step 1: Initiated -->
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-700 flex-shrink-0">
                        <x-icons.lucide name="lucide-file-plus" class="w-4 h-4" />
                    </div>
                    <div>
                        <div class="text-xs font-bold text-neutral-900">Transfer Record Created</div>
                        <div class="text-[11px] text-neutral-400 mt-0.5">{{ $transfer->created_at ? $transfer->created_at->format('Y-m-d H:i:s') : 'N/A' }}</div>
                    </div>
                </div>

                <!-- Step 2: Shipped -->
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full {{ $transfer->shipped_at ? 'bg-amber-50 border border-amber-200 text-amber-700' : 'bg-neutral-50 border border-neutral-200 text-neutral-300' }} flex items-center justify-center flex-shrink-0">
                        <x-icons.lucide name="lucide-truck" class="w-4 h-4" />
                    </div>
                    <div>
                        <div class="text-xs font-bold {{ $transfer->shipped_at ? 'text-neutral-900' : 'text-neutral-400' }}">Dispatched In-Transit</div>
                        <div class="text-[11px] text-neutral-400 mt-0.5">{{ $transfer->shipped_at ? $transfer->shipped_at->format('Y-m-d H:i:s') : 'Awaiting dispatch' }}</div>
                    </div>
                </div>

                <!-- Step 3: Completed -->
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full {{ $transfer->completed_at ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-neutral-50 border border-neutral-200 text-neutral-300' }} flex items-center justify-center flex-shrink-0">
                        <x-icons.lucide name="lucide-check-check" class="w-4 h-4" />
                    </div>
                    <div>
                        <div class="text-xs font-bold {{ $transfer->completed_at ? 'text-neutral-900' : 'text-neutral-400' }}">Goods Receipt Completed</div>
                        <div class="text-[11px] text-neutral-400 mt-0.5">{{ $transfer->completed_at ? $transfer->completed_at->format('Y-m-d H:i:s') : 'Awaiting goods receipt' }}</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-layouts.admin>
