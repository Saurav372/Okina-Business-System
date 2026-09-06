<x-layouts.admin title="Business Ledger | Okina Craft">
    <x-slot:header>
        <div class="flex items-center gap-4">
            <h1 class="text-xl font-bold text-neutral-800">Business Ledger</h1>
        </div>
    </x-slot:header>
 
    <!-- Cashflow & Summaries Card Grid -->
    <x-stat.grid>
        <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Total Sales (Confirmed)</div>
            <div class="text-xl font-extrabold text-neutral-800 mt-1 font-mono">₹{{ number_format($summary['total_sales'], 2) }}</div>
        </div>
        <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Collections (Received)</div>
            <div class="text-xl font-extrabold text-emerald-600 mt-1 font-mono">₹{{ number_format($summary['total_collections'], 2) }}</div>
        </div>
        <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Refunds Disbursed</div>
            <div class="text-xl font-extrabold text-rose-600 mt-1 font-mono">₹{{ number_format($summary['total_refunds'], 2) }}</div>
        </div>
        <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Operating Expenses</div>
            <div class="text-xl font-extrabold text-rose-600 mt-1 font-mono">₹{{ number_format($summary['total_expenses'], 2) }}</div>
        </div>
        <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Vendor Payouts</div>
            <div class="text-xl font-extrabold text-rose-600 mt-1 font-mono">₹{{ number_format($summary['total_vendor_payouts'], 2) }}</div>
        </div>
        <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Net Cashflow Balance</div>
            <div class="text-xl font-extrabold @if($summary['net_cashflow'] >= 0) text-emerald-600 @else text-rose-600 @endif mt-1 font-mono">
                ₹{{ number_format($summary['net_cashflow'], 2) }}
            </div>
        </div>
    </x-stat.grid>
 
    <!-- Ledger Transactions Table -->
    <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs mt-8">
        <h3 class="text-sm font-bold text-neutral-800 mb-4 uppercase tracking-wider">Recent Ledger Postings</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-neutral-200 text-neutral-500 font-bold text-xs uppercase">
                        <th class="py-3 px-4">Posting Date</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Reference</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4 text-right">Debit (Charge/Out)</th>
                        <th class="py-3 px-4 text-right">Credit (Receipt/In)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 text-sm">
                    @forelse($recentTransactions as $t)
                        <tr class="hover:bg-neutral-50">
                            <td class="py-3 px-4 text-neutral-500 text-xs font-mono">
                                {{ $t['date'] instanceof \Carbon\Carbon ? $t['date']->format('d M Y H:i') : date('d M Y H:i', strtotime($t['date'])) }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-md font-bold text-[10px] uppercase bg-neutral-100 text-neutral-700">
                                    {{ $t['type'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-xs font-mono text-neutral-600">{{ $t['reference'] }}</td>
                            <td class="py-3 px-4 text-neutral-700 font-medium">{{ $t['description'] }}</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-600 font-bold">
                                {{ $t['debit'] > 0 ? '₹' . number_format($t['debit'], 2) : '-' }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-600 font-bold">
                                {{ $t['credit'] > 0 ? '₹' . number_format($t['credit'], 2) : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-neutral-400 text-xs">
                                No ledger transactions registered yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.admin>
