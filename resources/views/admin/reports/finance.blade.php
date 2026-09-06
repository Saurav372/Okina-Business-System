<x-layouts.admin title="Finance & Operating Reports" description="Executive financial dashboard, operating income, cash flow, and receivables analytics.">
    <x-slot:header>
        @can('reports.finance.export')
            <a href="{{ $exportUrl }}" 
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-[color:var(--color-brand-600)] text-white text-xs font-bold rounded-xl shadow-xs hover:bg-[color:var(--color-brand-700)] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]"
               id="export-finance-csv-btn">
                <x-icons.lucide name="lucide-download" class="w-4 h-4" />
                <span>Export CSV</span>
            </a>
        @endcan
    </x-slot:header>

    <div class="space-y-6">
        <!-- Filter Bar -->
        <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.reports.finance.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <div>
                    <label for="preset" class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-1.5">Date Preset</label>
                    <select name="preset" id="preset" class="w-full text-xs font-semibold rounded-xl border-neutral-300 bg-white text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 py-2.5">
                        @foreach($presets as $val => $label)
                            <option value="{{ $val }}" {{ ($filters->preset === $val) ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="start_date" class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-1.5">Start Date</label>
                    <input type="date" name="start_date" id="start_date" value="{{ $filters->startDate?->toDateString() }}" class="w-full text-xs font-medium rounded-xl border-neutral-300 bg-white text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 py-2">
                </div>
                <div>
                    <label for="end_date" class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-1.5">End Date</label>
                    <input type="date" name="end_date" id="end_date" value="{{ $filters->endDate?->toDateString() }}" class="w-full text-xs font-medium rounded-xl border-neutral-300 bg-white text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 py-2">
                </div>
                <div>
                    <label for="group_by" class="block text-xs font-bold uppercase tracking-wider text-neutral-600 mb-1.5">Group By</label>
                    <select name="group_by" id="group_by" class="w-full text-xs font-semibold rounded-xl border-neutral-300 bg-white text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 py-2.5">
                        <option value="month" selected>Monthly Trend</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-neutral-900 hover:bg-neutral-800 text-white font-bold text-xs rounded-xl transition-colors shadow-xs">
                        Apply Filters
                    </button>
                    @if(request()->hasAny(['preset', 'start_date', 'end_date']) && (request('preset') !== 'this_month' || request('start_date') || request('end_date')))
                        <a href="{{ route('admin.reports.finance.index') }}" class="px-3 py-2.5 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 font-bold text-xs rounded-xl transition-colors shrink-0">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Section 1: Inflow & Outflow Volume KPIs (4 Cards) -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-400 mb-3">Gross Transaction Volume</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Booked Sales -->
                <div class="bg-white p-5 rounded-2xl border border-neutral-200 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Booked Sales Revenue</span>
                        <div class="p-2 rounded-xl bg-neutral-100 text-neutral-700">
                            <x-icons.lucide name="lucide-trending-up" class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-extrabold text-neutral-900 font-mono tracking-tight">{{ $report['metrics']['total_sales_formatted'] }}</div>
                        <div class="mt-1 text-xs text-neutral-500 font-medium">{{ $report['metrics']['total_orders_count'] }} orders booked</div>
                    </div>
                </div>

                <!-- Succeeded Payments -->
                <div class="bg-white p-5 rounded-2xl border border-neutral-200 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Succeeded Payments</span>
                        <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100">
                            <x-icons.lucide name="lucide-check-circle-2" class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-extrabold text-emerald-600 font-mono tracking-tight">{{ $report['metrics']['total_payments_formatted'] }}</div>
                        <div class="mt-1 text-xs text-neutral-500 font-medium">{{ $report['metrics']['total_payments_count'] }} successful collections</div>
                    </div>
                </div>

                <!-- Succeeded Refunds -->
                <div class="bg-white p-5 rounded-2xl border border-neutral-200 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-rose-600 uppercase tracking-wider">Succeeded Refunds</span>
                        <div class="p-2 rounded-xl bg-rose-50 text-rose-600 border border-rose-100">
                            <x-icons.lucide name="lucide-rotate-ccw" class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-extrabold text-rose-600 font-mono tracking-tight">{{ $report['metrics']['total_refunds_formatted'] }}</div>
                        <div class="mt-1 text-xs text-neutral-500 font-medium">{{ $report['metrics']['total_refunds_count'] }} customer payouts</div>
                    </div>
                </div>

                <!-- Approved Expenses -->
                <div class="bg-white p-5 rounded-2xl border border-neutral-200 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Approved Expenses</span>
                        <div class="p-2 rounded-xl bg-amber-50 text-amber-600 border border-amber-100">
                            <x-icons.lucide name="lucide-receipt" class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-extrabold text-amber-600 font-mono tracking-tight">{{ $report['metrics']['total_expenses_formatted'] }}</div>
                        <div class="mt-1 text-xs text-neutral-500 font-medium">{{ $report['metrics']['total_expenses_count'] }} operational expenditures</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Net Balances & Profitability (Balanced 3-Column Grid, No Holes) -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-400 mb-3">Profitability &amp; Liquidity Overview</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- As-Of Outstanding Receivables -->
                <div class="bg-white p-5 rounded-2xl border border-neutral-200 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider">Outstanding Receivables</span>
                        <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100">
                            <x-icons.lucide name="lucide-clock" class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-extrabold text-indigo-600 font-mono tracking-tight">{{ $report['metrics']['total_outstanding_formatted'] }}</div>
                        <div class="mt-1 text-xs text-neutral-500 font-medium">As of {{ $filters->endDate?->format('d M Y') }}</div>
                    </div>
                </div>

                <!-- Net Cash Flow -->
                <div class="bg-white p-5 rounded-2xl border border-neutral-200 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-neutral-600 uppercase tracking-wider">Net Cash Flow</span>
                        <div class="p-2 rounded-xl bg-neutral-100 text-neutral-700">
                            <x-icons.lucide name="lucide-wallet" class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-extrabold font-mono tracking-tight {{ (int)$report['metrics']['net_cash_flow_minor'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $report['metrics']['net_cash_flow_formatted'] }}
                        </div>
                        <div class="mt-1 text-xs text-neutral-400 font-medium font-mono text-[11px]">Payments - Refunds - Expenses</div>
                    </div>
                </div>

                <!-- Net Operating Income -->
                <div class="bg-white p-5 rounded-2xl border border-neutral-200 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-neutral-900 uppercase tracking-wider">Net Operating Income</span>
                        <div class="p-2 rounded-xl bg-[color:var(--color-brand-50)] text-[color:var(--color-brand-700)] border border-[color:var(--color-brand-200)]">
                            <x-icons.lucide name="lucide-landmark" class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-extrabold font-mono tracking-tight {{ (int)$report['metrics']['net_operating_income_minor'] >= 0 ? 'text-neutral-900' : 'text-rose-600' }}">
                            {{ $report['metrics']['net_operating_income_formatted'] }}
                        </div>
                        <div class="mt-1 text-xs text-neutral-400 font-medium font-mono text-[11px]">Booked Sales - Refunds - Expenses</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Performance Trend Table -->
        <div class="bg-white rounded-2xl shadow-xs border border-neutral-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-neutral-50/50">
                <div class="flex items-center gap-2">
                    <x-icons.lucide name="lucide-bar-chart-2" class="w-4 h-4 text-neutral-500" />
                    <h2 class="text-sm font-bold text-neutral-900">Monthly Performance Trend</h2>
                </div>
                <span class="text-xs text-neutral-400 font-medium">Continuous monthly accrual timeline</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-neutral-50 text-[10px] uppercase font-bold text-neutral-500 border-b border-neutral-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Period</th>
                            <th class="px-6 py-3 font-semibold text-right">Sales</th>
                            <th class="px-6 py-3 font-semibold text-right">Payments</th>
                            <th class="px-6 py-3 font-semibold text-right">Refunds</th>
                            <th class="px-6 py-3 font-semibold text-right">Expenses</th>
                            <th class="px-6 py-3 font-semibold text-right">Net Cash Flow</th>
                            <th class="px-6 py-3 font-semibold text-right">Net Operating Income</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 text-xs">
                        @forelse($report['monthly_trend'] as $row)
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="px-6 py-3.5 font-bold font-mono text-neutral-900">{{ $row['period'] }}</td>
                                <td class="px-6 py-3.5 text-right font-mono text-neutral-800">{{ $row['sales_formatted'] }}</td>
                                <td class="px-6 py-3.5 text-right font-mono font-semibold text-emerald-600">{{ $row['payments_formatted'] }}</td>
                                <td class="px-6 py-3.5 text-right font-mono text-rose-600">{{ $row['refunds_formatted'] }}</td>
                                <td class="px-6 py-3.5 text-right font-mono text-amber-600">{{ $row['expenses_formatted'] }}</td>
                                <td class="px-6 py-3.5 text-right font-mono font-bold {{ (int)($row['net_cash_flow_minor'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                    {{ $row['net_cash_flow_formatted'] }}
                                </td>
                                <td class="px-6 py-3.5 text-right font-mono font-bold {{ (int)($row['net_operating_income_minor'] ?? 0) >= 0 ? 'text-neutral-900' : 'text-rose-600' }}">
                                    {{ $row['net_operating_income_formatted'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-neutral-400 text-xs font-medium">
                                    No monthly data available for the selected range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Expense Category Breakdown Table -->
        <div class="bg-white rounded-2xl shadow-xs border border-neutral-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-neutral-50/50">
                <div class="flex items-center gap-2">
                    <x-icons.lucide name="lucide-pie-chart" class="w-4 h-4 text-neutral-500" />
                    <h2 class="text-sm font-bold text-neutral-900">Expense Category Breakdown</h2>
                </div>
                <span class="text-xs text-neutral-400 font-medium">Approved operational spending distributed by category</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-neutral-50 text-[10px] uppercase font-bold text-neutral-500 border-b border-neutral-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Category Code</th>
                            <th class="px-6 py-3 font-semibold">Category Name</th>
                            <th class="px-6 py-3 font-semibold text-right">Total Spend</th>
                            <th class="px-6 py-3 font-semibold text-center">Items Count</th>
                            <th class="px-6 py-3 font-semibold text-right">Expense Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 text-xs">
                        @forelse($report['expense_categories'] as $cat)
                            @php
                                $sharePct = number_format(($cat['share_basis_points'] ?? 0) / 100, 1);
                                $progressWidth = min(100, (float)$sharePct);
                            @endphp
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="px-6 py-3.5">
                                    <span class="font-mono text-xs font-bold text-neutral-800 px-2 py-0.5 bg-neutral-100 border border-neutral-200 rounded-md">
                                        {{ $cat['category_code'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 font-semibold text-neutral-900">
                                    {{ $cat['category_name'] }}
                                    @if(!empty($cat['is_deleted']))
                                        <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-neutral-100 text-neutral-500 border border-neutral-200">
                                            Archived
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-right font-mono font-bold text-amber-600">
                                    {{ $cat['total_formatted'] }}
                                </td>
                                <td class="px-6 py-3.5 text-center font-mono text-neutral-600">
                                    {{ $cat['expense_count'] }}
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    <div class="inline-flex items-center justify-end gap-2.5">
                                        <div class="w-16 bg-neutral-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-amber-500 h-full rounded-full transition-all duration-300" style="width: {{ $progressWidth }}%"></div>
                                        </div>
                                        <span class="font-mono text-xs font-bold text-neutral-800 w-12 text-right">
                                            {{ $sharePct }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-neutral-400 text-xs font-medium">
                                    No operational expenses recorded for the selected range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.admin>
