<x-layouts.admin title="Landing Leads | Marketing | Okina Craft">
    <x-slot:header>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-neutral-900">Landing Page Leads</h1>
                <p class="text-xs text-neutral-500 mt-0.5">Inbound B2B quote inquiries from advertising campaigns</p>
            </div>
            <div class="flex items-center gap-3">
                @can('landing_leads.export')
                    <a href="{{ route('admin.marketing.leads.export') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl border border-neutral-300 bg-white hover:bg-neutral-50 text-neutral-700 shadow-xs transition-colors">
                        <x-icons.lucide name="lucide-download" class="w-4 h-4 text-neutral-500" />
                        Export CSV
                    </a>
                @endcan
            </div>
        </div>
    </x-slot:header>

    @if(session('status'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <x-icons.lucide name="lucide-shield-check" class="w-5 h-5 text-emerald-600 flex-shrink-0" />
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <div class="space-y-6">
        <!-- Metric Counters -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <a href="{{ route('admin.marketing.leads.index') }}" class="bg-white border {{ !$statusFilter ? 'border-neutral-900 ring-1 ring-neutral-900' : 'border-[color:var(--color-border)]' }} rounded-2xl p-4 shadow-xs transition-all">
                <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">All Leads</p>
                <p class="text-2xl font-bold text-neutral-900 mt-1">{{ number_format($counts['total']) }}</p>
            </a>
            <a href="{{ route('admin.marketing.leads.index', ['status' => 'new']) }}" class="bg-white border {{ $statusFilter === 'new' ? 'border-blue-600 ring-1 ring-blue-600' : 'border-[color:var(--color-border)]' }} rounded-2xl p-4 shadow-xs transition-all">
                <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider">New</p>
                <p class="text-2xl font-bold text-neutral-900 mt-1">{{ number_format($counts['new']) }}</p>
            </a>
            <a href="{{ route('admin.marketing.leads.index', ['status' => 'contacted']) }}" class="bg-white border {{ $statusFilter === 'contacted' ? 'border-amber-600 ring-1 ring-amber-600' : 'border-[color:var(--color-border)]' }} rounded-2xl p-4 shadow-xs transition-all">
                <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Contacted</p>
                <p class="text-2xl font-bold text-neutral-900 mt-1">{{ number_format($counts['contacted']) }}</p>
            </a>
            <a href="{{ route('admin.marketing.leads.index', ['status' => 'quoted']) }}" class="bg-white border {{ $statusFilter === 'quoted' ? 'border-purple-600 ring-1 ring-purple-600' : 'border-[color:var(--color-border)]' }} rounded-2xl p-4 shadow-xs transition-all">
                <p class="text-xs font-semibold text-purple-600 uppercase tracking-wider">Quoted</p>
                <p class="text-2xl font-bold text-neutral-900 mt-1">{{ number_format($counts['quoted']) }}</p>
            </a>
            <a href="{{ route('admin.marketing.leads.index', ['status' => 'converted']) }}" class="bg-white border {{ $statusFilter === 'converted' ? 'border-emerald-600 ring-1 ring-emerald-600' : 'border-[color:var(--color-border)]' }} rounded-2xl p-4 shadow-xs transition-all">
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Converted</p>
                <p class="text-2xl font-bold text-neutral-900 mt-1">{{ number_format($counts['converted']) }}</p>
            </a>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-4 shadow-xs flex flex-col md:flex-row gap-4 items-center justify-between">
            <form method="GET" action="{{ route('admin.marketing.leads.index') }}" class="w-full md:w-auto flex-1 flex gap-3">
                @if($statusFilter)
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                @endif
                <div class="relative flex-1 max-w-md">
                    <x-icons.lucide name="lucide-search" class="w-4 h-4 text-neutral-400 absolute left-3.5 top-3" />
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search customer, phone, requirement, city..." class="w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-neutral-200 focus:outline-none focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900">
                </div>
                <button type="submit" class="px-4 py-2 bg-neutral-900 text-white rounded-xl text-xs font-semibold hover:bg-neutral-800 transition-colors">
                    Filter
                </button>
                @if($search || $statusFilter)
                    <a href="{{ route('admin.marketing.leads.index') }}" class="px-3 py-2 text-xs text-neutral-500 hover:text-neutral-800 flex items-center">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Leads Table -->
        <div class="bg-white border border-[color:var(--color-border)] rounded-2xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-neutral-50/80 border-b border-neutral-100 text-neutral-500 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="px-5 py-3">Lead Info</th>
                            <th class="px-4 py-3">Requirement</th>
                            <th class="px-4 py-3">Qty & Target</th>
                            <th class="px-4 py-3">Artwork</th>
                            <th class="px-4 py-3">Source / UTM</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 text-neutral-700">
                        @forelse($leads as $lead)
                            @php
                                $cleanPhone = preg_replace('/[^0-9]/', '', $lead->phone);
                                if (strlen($cleanPhone) === 10) $cleanPhone = '91' . $cleanPhone;
                                $waUrl = "https://wa.me/{$cleanPhone}?text=" . rawurlencode("Hi {$lead->name}, this is Okina Craft following up on your custom apparel quote request!");
                            @endphp
                            <tr class="hover:bg-neutral-50/50 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="font-bold text-neutral-900">{{ $lead->name }}</div>
                                    <div class="text-neutral-500 mt-0.5 flex items-center gap-2">
                                        <span>{{ $lead->phone }}</span>
                                        <a href="{{ $waUrl }}" target="_blank" class="text-emerald-600 hover:text-emerald-700 font-semibold" title="Open WhatsApp Chat">
                                            [WA]
                                        </a>
                                    </div>
                                    @if($lead->city)
                                        <div class="text-[11px] text-neutral-400 mt-0.5">{{ $lead->city }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <span class="font-medium text-neutral-800">{{ $lead->product_type ?: 'General Apparel' }}</span>
                                    @if($lead->notes)
                                        <p class="text-[11px] text-neutral-500 mt-1 line-clamp-1 italic">"{{ $lead->notes }}"</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="font-medium text-neutral-900">{{ $lead->quantity_range ?: '50-100 pcs' }}</div>
                                    <div class="text-[11px] text-neutral-500 mt-0.5">Need by: {{ $lead->delivery_date ?: 'Standard' }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    @if($lead->artwork_path)
                                        <a href="{{ asset('storage/' . $lead->artwork_path) }}" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-neutral-100 text-neutral-700 hover:bg-neutral-200 transition-colors text-[11px] font-semibold">
                                            <x-icons.lucide name="lucide-image" class="w-3.5 h-3.5 text-neutral-500" />
                                            View Artwork
                                        </a>
                                    @else
                                        <span class="text-neutral-400 text-[11px]">No file</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <span class="font-mono text-[11px] bg-neutral-100 px-2 py-0.5 rounded text-neutral-700">
                                        {{ $lead->utm_source ?: ($lead->source ?: 'direct') }}
                                    </span>
                                    @if($lead->utm_campaign)
                                        <div class="text-[10px] text-neutral-400 mt-1 truncate max-w-[120px]">{{ $lead->utm_campaign }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    @can('landing_leads.update_status')
                                        <form method="POST" action="{{ route('admin.marketing.leads.status.update', $lead) }}">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" onchange="this.form.submit()" class="text-xs font-bold rounded-lg border border-neutral-200 px-2 py-1 focus:outline-none focus:ring-1 focus:ring-neutral-900
                                                {{ $lead->status === 'new' ? 'bg-blue-50 text-blue-700 border-blue-200' : '' }}
                                                {{ $lead->status === 'contacted' ? 'bg-amber-50 text-amber-700 border-amber-200' : '' }}
                                                {{ $lead->status === 'quoted' ? 'bg-purple-50 text-purple-700 border-purple-200' : '' }}
                                                {{ $lead->status === 'converted' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : '' }}
                                            ">
                                                <option value="new" {{ $lead->status === 'new' ? 'selected' : '' }}>New</option>
                                                <option value="contacted" {{ $lead->status === 'contacted' ? 'selected' : '' }}>Contacted</option>
                                                <option value="quoted" {{ $lead->status === 'quoted' ? 'selected' : '' }}>Quoted</option>
                                                <option value="converted" {{ $lead->status === 'converted' ? 'selected' : '' }}>Converted</option>
                                            </select>
                                        </form>
                                    @else
                                        <span class="inline-flex px-2 py-1 rounded text-xs font-bold
                                            {{ $lead->status === 'new' ? 'bg-blue-50 text-blue-700' : '' }}
                                            {{ $lead->status === 'contacted' ? 'bg-amber-50 text-amber-700' : '' }}
                                            {{ $lead->status === 'quoted' ? 'bg-purple-50 text-purple-700' : '' }}
                                            {{ $lead->status === 'converted' ? 'bg-emerald-50 text-emerald-700' : '' }}
                                        ">
                                            {{ ucfirst($lead->status) }}
                                        </span>
                                    @endcan
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.marketing.leads.show', $lead) }}" class="px-3 py-1.5 rounded-lg border border-neutral-200 text-neutral-700 hover:bg-neutral-100 font-semibold text-xs transition-colors">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-neutral-400">
                                    No leads recorded matching current filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($leads->hasPages())
                <div class="p-4 border-t border-neutral-100">
                    {{ $leads->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
