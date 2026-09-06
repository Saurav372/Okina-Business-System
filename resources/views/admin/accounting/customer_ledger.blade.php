<x-layouts.admin title="Customer Ledger | Okina Craft">
    <x-slot:header>
        <div class="flex items-center gap-4">
            <h1 class="text-xl font-bold text-neutral-800">Customer Ledger</h1>
        </div>
    </x-slot:header>
 
    <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs">
        <form method="GET" action="{{ route('admin.accounting.customer_ledger') }}" class="mb-6 flex gap-3">
            <input 
                type="text" 
                name="search" 
                value="{{ $search }}" 
                placeholder="Search by customer name, email..."
                class="flex-1 max-w-sm px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm"
            >
            <button type="submit" class="px-4 py-2 bg-[color:var(--color-brand-600)] text-white font-bold rounded-xl text-sm hover:bg-[color:var(--color-brand-700)] transition-colors">
                Search
            </button>
            @if(!empty($search))
                <a href="{{ route('admin.accounting.customer_ledger') }}" class="px-4 py-2 bg-neutral-100 text-neutral-700 font-bold rounded-xl text-sm hover:bg-neutral-200 transition-colors">
                    Clear
                </a>
            @endif
        </form>
 
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-neutral-200 text-neutral-500 font-bold text-xs uppercase">
                        <th class="py-3 px-4">Customer</th>
                        <th class="py-3 px-4 text-right">Total Invoiced</th>
                        <th class="py-3 px-4 text-right">Total Paid</th>
                        <th class="py-3 px-4 text-right">Total Refunded</th>
                        <th class="py-3 px-4 text-right">Outstanding Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 text-sm">
                    @forelse($customers as $c)
                        <tr class="hover:bg-neutral-50">
                            <td class="py-3 px-4">
                                <div class="font-bold text-neutral-800">{{ $c['name'] }}</div>
                                <div class="text-xs text-neutral-400">{{ $c['email'] }}</div>
                            </td>
                            <td class="py-3 px-4 text-right font-mono">₹{{ number_format($c['total_invoiced'], 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-600 font-bold">₹{{ number_format($c['total_paid'], 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-600">₹{{ number_format($c['total_refunded'], 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold @if($c['outstanding_balance'] > 0) text-rose-600 @else text-neutral-700 @endif">
                                ₹{{ number_format($c['outstanding_balance'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-neutral-400 text-xs">
                                No customer records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.admin>
