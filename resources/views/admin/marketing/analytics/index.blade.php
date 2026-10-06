<x-layouts.admin title="Marketing Analytics & Funnel | Okina Craft">
    <x-slot:header>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-neutral-900">Marketing Analytics & Funnel</h1>
                <p class="text-xs text-neutral-500 mt-0.5">Campaign performance, conversion funnel drop-off, and traffic acquisition breakdown</p>
            </div>
            <form method="GET" action="{{ route('admin.marketing.analytics.index') }}" class="flex items-center gap-2">
                <select name="range" onchange="this.form.submit()" class="rounded-xl border border-neutral-300 bg-white px-3 py-1.5 text-xs font-semibold text-neutral-700 focus:outline-none">
                    <option value="7" {{ $range == '7' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30" {{ $range == '30' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="90" {{ $range == '90' ? 'selected' : '' }}>Last 90 Days</option>
                </select>
            </form>
        </div>
    </x-slot:header>

    <div class="space-y-6">
        <!-- High-level KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
                <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Landing Page Views</p>
                <p class="text-2xl font-bold text-neutral-900 mt-2">{{ number_format($landingPageViews) }}</p>
                <p class="text-[11px] text-neutral-400 mt-1">Unique traffic to offer pages</p>
            </div>
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
                <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">WhatsApp Clicks</p>
                <p class="text-2xl font-bold text-neutral-900 mt-2">{{ number_format($whatsAppClicks) }}</p>
                <p class="text-[11px] text-neutral-400 mt-1">Direct inbound conversations</p>
            </div>
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
                <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Form Submissions</p>
                <p class="text-2xl font-bold text-neutral-900 mt-2">{{ number_format($formSubmissions) }}</p>
                <p class="text-[11px] text-neutral-400 mt-1">Verified lead quote requests</p>
            </div>
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
                <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Overall Conversion %</p>
                <p class="text-2xl font-bold text-emerald-600 mt-2">{{ $conversionRate }}%</p>
                <p class="text-[11px] text-neutral-400 mt-1">(WhatsApp + Leads) / Views</p>
            </div>
        </div>

        <!-- Conversion Funnel Section -->
        <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-6">
            <div class="border-b border-neutral-100 pb-4">
                <h2 class="text-sm font-bold text-neutral-900 uppercase tracking-wider">Conversion Funnel Drop-off</h2>
                <p class="text-xs text-neutral-500 mt-0.5">Track how visitors convert from initial ad click to finalized business customer</p>
            </div>

            @php
                $topCount = max(1, $funnel[0]['count']);
            @endphp

            <div class="space-y-4">
                @foreach($funnel as $step)
                    @php
                        $percentage = round(($step['count'] / $topCount) * 100);
                    @endphp
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-neutral-800">{{ $step['name'] }}</span>
                            <div class="flex items-center gap-3">
                                <span class="font-bold text-neutral-900">{{ number_format($step['count']) }}</span>
                                <span class="text-neutral-400 w-12 text-right">({{ $percentage }}%)</span>
                            </div>
                        </div>
                        <div class="w-full bg-neutral-100 rounded-full h-3 overflow-hidden">
                            <div class="{{ $step['color'] }} h-3 rounded-full transition-all duration-500" style="width: {{ max(4, $percentage) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Campaign & Source Breakdown Table -->
        <div class="bg-white border border-[color:var(--color-border)] rounded-2xl shadow-xs overflow-hidden">
            <div class="p-5 border-b border-neutral-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-neutral-900 uppercase tracking-wider">Campaign & Source Attribution</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Leads by UTM Source, Medium, and Campaign</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-neutral-50 text-neutral-500 uppercase tracking-wider font-semibold border-b border-neutral-100">
                        <tr>
                            <th class="px-5 py-3">Source</th>
                            <th class="px-4 py-3">Medium</th>
                            <th class="px-4 py-3">Campaign</th>
                            <th class="px-4 py-3 text-right">Leads</th>
                            <th class="px-4 py-3 text-right">Converted</th>
                            <th class="px-4 py-3 text-right">Conversion %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($utmBreakdown as $row)
                            @php
                                $cr = $row->total_leads > 0 ? round(($row->converted_leads / $row->total_leads) * 100, 1) : 0;
                            @endphp
                            <tr class="hover:bg-neutral-50/50 transition-colors">
                                <td class="px-5 py-3.5 font-bold font-mono text-neutral-900">
                                    {{ $row->source_name }}
                                </td>
                                <td class="px-4 py-3.5 text-neutral-600 font-mono">
                                    {{ $row->utm_medium ?: '—' }}
                                </td>
                                <td class="px-4 py-3.5 text-neutral-600 font-mono">
                                    {{ $row->utm_campaign ?: '—' }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-bold text-neutral-900">
                                    {{ number_format($row->total_leads) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-semibold text-emerald-600">
                                    {{ number_format($row->converted_leads) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-bold text-neutral-800">
                                    {{ $cr }}%
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-neutral-400">
                                    No campaign data recorded in selected date range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.admin>
