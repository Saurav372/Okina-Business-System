<x-layouts.admin title="Lead #{{ $lead->id }} | {{ $lead->name }} | Okina Craft">
    <x-slot:header>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.marketing.leads.index') }}" class="p-2 rounded-xl text-neutral-500 hover:text-neutral-800 hover:bg-neutral-100 transition-colors">
                    <x-icons.lucide name="lucide-chevron-down" class="w-5 h-5 rotate-90" />
                </a>
                <div>
                    <h1 class="text-xl font-bold text-neutral-900">Lead #{{ $lead->id }}: {{ $lead->name }}</h1>
                    <p class="text-xs text-neutral-500 mt-0.5">Received: {{ $lead->created_at->format('M d, Y h:i A') }} ({{ $lead->created_at->diffForHumans() }})</p>
                </div>
            </div>

            @php
                $cleanPhone = preg_replace('/[^0-9]/', '', $lead->phone);
                if (strlen($cleanPhone) === 10) $cleanPhone = '91' . $cleanPhone;
                $waUrl = "https://wa.me/{$cleanPhone}?text=" . rawurlencode("Hi {$lead->name}, this is Okina Craft following up on your custom apparel quote request!");
            @endphp
            <div class="flex items-center gap-3">
                <a href="{{ $waUrl }}" target="_blank" class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition-colors inline-flex items-center gap-2">
                    <span>Chat on WhatsApp</span>
                    <x-icons.lucide name="lucide-arrow-up-right" class="w-3.5 h-3.5" />
                </a>
            </div>
        </div>
    </x-slot:header>

    @if(session('status'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <x-icons.lucide name="lucide-shield-check" class="w-5 h-5 text-emerald-600 flex-shrink-0" />
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
        <!-- Lead Information Column (2 cols) -->
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-6">
                <h2 class="text-sm font-bold text-neutral-900 uppercase tracking-wider border-b border-neutral-100 pb-3">Quote Requirement Details</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs text-neutral-500 uppercase tracking-wider font-semibold">Customer Name</p>
                        <p class="text-base font-bold text-neutral-900 mt-1">{{ $lead->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-neutral-500 uppercase tracking-wider font-semibold">Phone / WhatsApp</p>
                        <p class="text-base font-mono font-bold text-neutral-900 mt-1">{{ $lead->phone }}</p>
                    </div>
                    @if($lead->email)
                        <div>
                            <p class="text-xs text-neutral-500 uppercase tracking-wider font-semibold">Email Address</p>
                            <p class="text-sm font-semibold text-neutral-900 mt-1"><a href="mailto:{{ $lead->email }}" class="text-blue-600 hover:underline">{{ $lead->email }}</a></p>
                        </div>
                    @endif
                    @if($lead->business_type)
                        <div>
                            <p class="text-xs text-neutral-500 uppercase tracking-wider font-semibold">Business Type</p>
                            <p class="text-sm font-semibold text-neutral-900 mt-1">{{ $lead->business_type }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-xs text-neutral-500 uppercase tracking-wider font-semibold">Printing / Product Type</p>
                        <p class="text-sm font-semibold text-neutral-900 mt-1">{{ $lead->product_type ?: 'Not specified' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-neutral-500 uppercase tracking-wider font-semibold">Quantity Range</p>
                        <p class="text-sm font-semibold text-neutral-900 mt-1">{{ $lead->quantity_range ?: '50-100 pcs' }}</p>
                    </div>
                    @if($lead->design_readiness)
                        <div>
                            <p class="text-xs text-neutral-500 uppercase tracking-wider font-semibold">Design Readiness</p>
                            <p class="text-sm font-semibold text-neutral-900 mt-1">{{ $lead->design_readiness }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-xs text-neutral-500 uppercase tracking-wider font-semibold">Target Delivery / Timeline</p>
                        <p class="text-sm font-semibold text-neutral-900 mt-1">{{ $lead->delivery_date ?: 'Standard Delivery' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-neutral-500 uppercase tracking-wider font-semibold">City / Location</p>
                        <p class="text-sm font-semibold text-neutral-900 mt-1">{{ $lead->city ?: 'Not provided' }}</p>
                    </div>
                </div>

                @if($lead->notes)
                    <div class="border-t border-neutral-100 pt-4">
                        <p class="text-xs text-neutral-500 uppercase tracking-wider font-semibold mb-2">Customer Special Instructions / Notes</p>
                        <div class="bg-neutral-50 rounded-xl p-4 text-sm text-neutral-800 italic">
                            "{{ $lead->notes }}"
                        </div>
                    </div>
                @endif
            </div>

            <!-- Artwork & Uploaded Mockup -->
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-4">
                <h2 class="text-sm font-bold text-neutral-900 uppercase tracking-wider border-b border-neutral-100 pb-3">Attached Logo / Artwork File</h2>

                @if($lead->artwork_path)
                    <div class="flex flex-col sm:flex-row items-center gap-6 p-4 bg-neutral-50 rounded-xl border border-neutral-200">
                        <div class="w-32 h-32 bg-white border border-neutral-200 rounded-xl overflow-hidden flex items-center justify-center p-2">
                            <img src="{{ asset('storage/' . $lead->artwork_path) }}" alt="Artwork preview" class="max-w-full max-h-full object-contain">
                        </div>
                        <div class="space-y-3">
                            <p class="text-xs text-neutral-600 font-mono">{{ basename($lead->artwork_path) }}</p>
                            <a href="{{ asset('storage/' . $lead->artwork_path) }}" target="_blank" download class="inline-flex items-center gap-2 px-4 py-2 bg-neutral-900 text-white rounded-xl text-xs font-semibold hover:bg-neutral-800 transition-colors shadow-xs">
                                <x-icons.lucide name="lucide-download" class="w-4 h-4" />
                                Download Full Resolution
                            </a>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-neutral-500 italic py-4">No artwork uploaded with this quote request.</p>
                @endif
            </div>
        </div>

        <!-- Sidebar Actions & UTM Attribution Column -->
        <div class="space-y-6">
            <!-- Status Update Box -->
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-4">
                <h3 class="text-xs font-bold text-neutral-700 uppercase tracking-wider">Lead Status Pipeline</h3>

                @can('landing_leads.update_status')
                    <form method="POST" action="{{ route('admin.marketing.leads.status.update', $lead) }}" class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="w-full text-xs font-bold rounded-xl border border-neutral-200 p-2.5 focus:border-neutral-900 focus:outline-none">
                            <option value="new" {{ $lead->status === 'new' ? 'selected' : '' }}>New Inquiry</option>
                            <option value="contacted" {{ $lead->status === 'contacted' ? 'selected' : '' }}>Contacted / Requirements Taken</option>
                            <option value="quoted" {{ $lead->status === 'quoted' ? 'selected' : '' }}>Quoted (Commercial Proposal Sent)</option>
                            <option value="converted" {{ $lead->status === 'converted' ? 'selected' : '' }}>Converted (Order Placed)</option>
                        </select>
                        <button type="submit" class="w-full py-2 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 transition-colors shadow-xs">
                            Update Status
                        </button>
                    </form>
                @else
                    <p class="text-sm font-bold text-neutral-900 capitalize">{{ $lead->status }}</p>
                @endcan
            </div>

            <!-- Campaign & UTM Attribution Box -->
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-4">
                <h3 class="text-xs font-bold text-neutral-700 uppercase tracking-wider">Campaign Attribution</h3>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-neutral-500">Source:</span>
                        <span class="font-mono font-bold text-neutral-800 float-right">{{ $lead->utm_source ?: ($lead->source ?: 'direct') }}</span>
                    </div>
                    <div>
                        <span class="text-neutral-500">Medium:</span>
                        <span class="font-mono text-neutral-800 float-right">{{ $lead->utm_medium ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-neutral-500">Campaign:</span>
                        <span class="font-mono text-neutral-800 float-right">{{ $lead->utm_campaign ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-neutral-500">Content / Ad:</span>
                        <span class="font-mono text-neutral-800 float-right">{{ $lead->utm_content ?: '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
