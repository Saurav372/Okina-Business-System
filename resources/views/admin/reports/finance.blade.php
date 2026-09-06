<x-layouts.admin title="Finance Reports">
<div class="py-6 space-y-6">
    <!-- Header Title & Export Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Finance &amp; Operating Reports</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400">Executive financial dashboard, operating income, cash flow, and receivables analytics.</p>
        </div>
        @can('reports.finance.export')
        <div>
            <a href="{{ $exportUrl }}" 
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors shadow-sm"
               id="export-finance-csv-btn">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export CSV
            </a>
        </div>
        @endcan
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-700">
        <form method="GET" action="{{ route('admin.reports.finance.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <div>
                <label for="preset" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Date Preset</label>
                <select name="preset" id="preset" class="w-full text-sm rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                    @foreach($presets as $val => $label)
                        <option value="{{ $val }}" {{ ($filters->preset === $val) ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="start_date" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Start Date</label>
                <input type="date" name="start_date" id="start_date" value="{{ $filters->startDate?->toDateString() }}" class="w-full text-sm rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="end_date" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">End Date</label>
                <input type="date" name="end_date" id="end_date" value="{{ $filters->endDate?->toDateString() }}" class="w-full text-sm rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="group_by" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Group By</label>
                <select name="group_by" id="group_by" class="w-full text-sm rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="month" selected>Monthly Trend</option>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 bg-slate-900 hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 text-white font-medium text-sm rounded-lg transition-colors shadow-sm">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Booked Sales -->
        <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Booked Sales Revenue</span>
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ $report['metrics']['total_sales_formatted'] }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $report['metrics']['total_orders_count'] }} orders</div>
        </div>

        <!-- Succeeded Payments -->
        <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Succeeded Payments</span>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $report['metrics']['total_payments_formatted'] }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $report['metrics']['total_payments_count'] }} transactions</div>
        </div>

        <!-- Succeeded Refunds -->
        <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Succeeded Refunds</span>
            <div class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ $report['metrics']['total_refunds_formatted'] }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $report['metrics']['total_refunds_count'] }} refunds</div>
        </div>

        <!-- Approved Expenses -->
        <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Approved Expenses</span>
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $report['metrics']['total_expenses_formatted'] }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $report['metrics']['total_expenses_count'] }} expense items</div>
        </div>

        <!-- As-Of Outstanding Receivables -->
        <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Outstanding Receivables</span>
            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $report['metrics']['total_outstanding_formatted'] }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">As of {{ $filters->endDate?->format('d M Y') }}</div>
        </div>

        <!-- Net Cash Flow -->
        <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Net Cash Flow</span>
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ $report['metrics']['net_cash_flow_formatted'] }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">Payments - Refunds - Expenses</div>
        </div>

        <!-- Net Operating Income -->
        <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-2 col-span-1 md:col-span-2">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Net Operating Income</span>
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ $report['metrics']['net_operating_income_formatted'] }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">Booked Sales - Refunds - Expenses</div>
        </div>
    </div>

    <!-- Monthly Performance Trend Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Monthly Performance Trend</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">Zero-filled continuous monthly series</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-6 py-3">Period</th>
                        <th class="px-6 py-3">Sales</th>
                        <th class="px-6 py-3">Payments</th>
                        <th class="px-6 py-3">Refunds</th>
                        <th class="px-6 py-3">Expenses</th>
                        <th class="px-6 py-3">Net Cash Flow</th>
                        <th class="px-6 py-3">Net Operating Income</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($report['monthly_trend'] as $row)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ $row['period'] }}</td>
                            <td class="px-6 py-4">{{ $row['sales_formatted'] }}</td>
                            <td class="px-6 py-4 text-emerald-600 dark:text-emerald-400">{{ $row['payments_formatted'] }}</td>
                            <td class="px-6 py-4 text-rose-600 dark:text-rose-400">{{ $row['refunds_formatted'] }}</td>
                            <td class="px-6 py-4 text-amber-600 dark:text-amber-400">{{ $row['expenses_formatted'] }}</td>
                            <td class="px-6 py-4 font-semibold">{{ $row['net_cash_flow_formatted'] }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ $row['net_operating_income_formatted'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No monthly data available for the selected range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Expense Category Breakdown Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Expense Category Breakdown</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">Approved operational expenses by category</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-6 py-3">Category Code</th>
                        <th class="px-6 py-3">Category Name</th>
                        <th class="px-6 py-3">Total Amount</th>
                        <th class="px-6 py-3">Items Count</th>
                        <th class="px-6 py-3">Share (BPS)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($report['expense_categories'] as $cat)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-6 py-4 font-mono font-semibold text-indigo-600 dark:text-indigo-400">{{ $cat['category_code'] }}</td>
                            <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">
                                {{ $cat['category_name'] }}
                                @if($cat['is_deleted'])
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Soft Deleted</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold text-amber-600 dark:text-amber-400">{{ $cat['total_formatted'] }}</td>
                            <td class="px-6 py-4">{{ $cat['expense_count'] }}</td>
                            <td class="px-6 py-4 font-mono text-xs">{{ $cat['share_basis_points'] }} bps</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No operational expenses recorded for the selected range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-layouts.admin>
