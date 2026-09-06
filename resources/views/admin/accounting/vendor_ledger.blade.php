<x-layouts.admin title="Vendor Ledger | Okina Craft">
    <x-slot:header>
        <div class="flex items-center gap-4">
            <h1 class="text-xl font-bold text-neutral-800">Vendor Ledger</h1>
        </div>
    </x-slot:header>
 
    <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs">
        <form method="GET" action="{{ route('admin.accounting.vendor_ledger') }}" class="mb-6 flex gap-3">
            <input 
                type="text" 
                name="search" 
                value="{{ $search }}" 
                placeholder="Search by vendor name, code..."
                class="flex-1 max-w-sm px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm"
            >
            <button type="submit" class="px-4 py-2 bg-[color:var(--color-brand-600)] text-white font-bold rounded-xl text-sm hover:bg-[color:var(--color-brand-700)] transition-colors">
                Search
            </button>
            @if(!empty($search))
                <a href="{{ route('admin.accounting.vendor_ledger') }}" class="px-4 py-2 bg-neutral-100 text-neutral-700 font-bold rounded-xl text-sm hover:bg-neutral-200 transition-colors">
                    Clear
                </a>
            @endif
        </form>
 
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-neutral-200 text-neutral-500 font-bold text-xs uppercase">
                        <th class="py-3 px-4">Vendor</th>
                        <th class="py-3 px-4 text-right">Total PO Value</th>
                        <th class="py-3 px-4 text-right">Total Paid Payouts</th>
                        <th class="py-3 px-4 text-right">Outstanding PO Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 text-sm">
                    @forelse($vendors as $v)
                        <tr class="hover:bg-neutral-50">
                            <td class="py-3 px-4">
                                <div class="font-bold text-neutral-800">{{ $v['name'] }}</div>
                                <div class="text-xs text-neutral-400">Code: {{ $v['vendor_code'] }} | Status: {{ $v['status'] }}</div>
                            </td>
                            <td class="py-3 px-4 text-right font-mono">₹{{ number_format($v['total_po_value'], 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-600 font-bold">₹{{ number_format($v['total_paid'], 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold @if($v['outstanding_balance'] > 0) text-rose-600 @else text-neutral-700 @endif">
                                ₹{{ number_format($v['outstanding_balance'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-neutral-400 text-xs">
                                No vendor records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.admin>
