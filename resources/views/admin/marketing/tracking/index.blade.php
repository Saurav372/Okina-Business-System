<x-layouts.admin title="Tracking & Integrations | Marketing | Okina Craft">
    <x-slot:header>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-neutral-900">Tracking & Integrations</h1>
                <p class="text-xs text-neutral-500 mt-0.5">Manage Google Tag Manager, Meta Pixel, Meta Conversions API (CAPI), and Event Mappings</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold {{ !empty($pixel['enabled']) || !empty($capi['enabled']) ? 'bg-emerald-100 text-emerald-800' : 'bg-neutral-100 text-neutral-600' }}">
                    <span class="w-2 h-2 rounded-full {{ !empty($pixel['enabled']) || !empty($capi['enabled']) ? 'bg-emerald-500' : 'bg-neutral-400' }}"></span>
                    {{ !empty($pixel['enabled']) || !empty($capi['enabled']) ? 'Tracking Active' : 'Tracking Paused' }}
                </span>
            </div>
        </div>
    </x-slot:header>

    @if(session('status'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <x-icons.lucide name="lucide-shield-check" class="w-5 h-5 text-emerald-600 flex-shrink-0" />
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <div x-data="{ currentTab: 'meta_pixel' }" class="grid grid-cols-1 md:grid-cols-4 gap-8 items-start">
        
        <!-- Sidebar Navigation Tabs -->
        <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-4 shadow-xs space-y-1 sticky top-6">
            <button 
                @click="currentTab = 'meta_pixel'"
                :class="currentTab === 'meta_pixel' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center justify-between"
            >
                <div class="flex items-center gap-2">
                    <x-icons.lucide name="lucide-sliders-horizontal" class="w-4 h-4" />
                    Meta Pixel
                </div>
                @if(!empty($pixel['enabled']))
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                @endif
            </button>
            <button 
                @click="currentTab = 'meta_capi'"
                :class="currentTab === 'meta_capi' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center justify-between"
            >
                <div class="flex items-center gap-2">
                    <x-icons.lucide name="lucide-cpu" class="w-4 h-4" />
                    Meta Conversions API (CAPI)
                </div>
                @if(!empty($capi['enabled']))
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                @endif
            </button>
            <button 
                @click="currentTab = 'gtm'"
                :class="currentTab === 'gtm' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center justify-between"
            >
                <div class="flex items-center gap-2">
                    <x-icons.lucide name="lucide-tag" class="w-4 h-4" />
                    Google Tag Manager
                </div>
                @if(!empty($gtm['enabled']))
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                @endif
            </button>
            <button 
                @click="currentTab = 'mapping'"
                :class="currentTab === 'mapping' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-2"
            >
                <x-icons.lucide name="lucide-layout-template" class="w-4 h-4" />
                Event Mapping
            </button>
            <button 
                @click="currentTab = 'test_stream'"
                :class="currentTab === 'test_stream' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-2"
            >
                <x-icons.lucide name="lucide-bar-chart-3" class="w-4 h-4" />
                Live Event Stream
            </button>
        </div>

        <!-- Main Configuration Area -->
        <div class="md:col-span-3 space-y-6">

            <!-- 1. META PIXEL TAB -->
            <div x-show="currentTab === 'meta_pixel'">
                <form method="POST" action="{{ route('admin.marketing.tracking.update') }}" class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tab" value="pixel">

                    <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                        <div>
                            <h2 class="text-sm font-bold text-neutral-900 uppercase tracking-wider">Meta Pixel (Browser Tracking)</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Executes client-side Meta Pixel script for advertising conversion tracking and retargeting</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="pixel_enabled" value="1" {{ !empty($pixel['enabled']) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-neutral-900"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Meta Pixel ID</label>
                            <input type="text" name="pixel_id" value="{{ old('pixel_id', $pixel['pixel_id'] ?? '') }}" placeholder="e.g. 123456789012345" class="w-full rounded-xl border border-neutral-200 px-3.5 py-2.5 text-sm font-mono text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">
                            <p class="text-xs text-neutral-400 mt-1">Found in Meta Events Manager &gt; Data Sources</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Test Event Code (Optional)</label>
                            <input type="text" name="test_event_code" value="{{ old('test_event_code', $pixel['test_event_code'] ?? '') }}" placeholder="e.g. TEST12345" class="w-full rounded-xl border border-neutral-200 px-3.5 py-2.5 text-sm font-mono text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">
                            <p class="text-xs text-neutral-400 mt-1">Used to view events in real time under Meta Test Events tab</p>
                        </div>
                    </div>

                    <div class="border-t border-neutral-100 pt-4 space-y-3">
                        <p class="text-xs font-bold text-neutral-700 uppercase tracking-wider">Active Standard Browser Events</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach(['PageView', 'ViewContent', 'Lead', 'Contact', 'WhatsAppClick', 'QuoteFormSubmit'] as $ev)
                                <label class="flex items-center gap-2 p-3 rounded-xl border border-neutral-200 hover:bg-neutral-50 cursor-pointer text-xs font-medium text-neutral-800">
                                    <input type="checkbox" name="events[{{ $ev }}]" value="1" {{ !empty($pixel['events'][$ev]) ? 'checked' : '' }} class="rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900">
                                    <span>{{ $ev }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @can('marketing_tracking.edit')
                        <div class="border-t border-neutral-100 pt-4 flex justify-end">
                            <button type="submit" class="px-5 py-2.5 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 shadow-xs transition-colors">
                                Save Meta Pixel Settings
                            </button>
                        </div>
                    @endcan
                </form>
            </div>

            <!-- 2. META CONVERSIONS API (CAPI) TAB -->
            <div x-show="currentTab === 'meta_capi'">
                <form method="POST" action="{{ route('admin.marketing.tracking.update') }}" class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tab" value="capi">

                    <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                        <div>
                            <h2 class="text-sm font-bold text-neutral-900 uppercase tracking-wider">Meta Conversions API (CAPI - Server Side)</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Dispatches server-side events directly to Meta Graph API with shared event_id deduplication</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="capi_enabled" value="1" {{ !empty($capi['enabled']) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-neutral-900"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Dataset / Pixel ID</label>
                            <input type="text" name="dataset_id" value="{{ old('dataset_id', $capi['dataset_id'] ?? '') }}" placeholder="e.g. 123456789012345" class="w-full rounded-xl border border-neutral-200 px-3.5 py-2.5 text-sm font-mono text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Graph API Version</label>
                            <input type="text" name="api_version" value="{{ old('api_version', $capi['api_version'] ?? 'v19.0') }}" class="w-full rounded-xl border border-neutral-200 px-3.5 py-2.5 text-sm font-mono text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider">CAPI Access Token</label>
                            @if($hasToken)
                                <span class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                                    <x-icons.lucide name="lucide-shield-check" class="w-3.5 h-3.5" />
                                    Encrypted token configured
                                </span>
                            @else
                                <span class="text-[11px] text-amber-600 font-semibold">Token not configured</span>
                            @endif
                        </div>

                        @if($canUpdateSecret)
                            <input type="text" name="access_token" value="{{ $hasToken ? $maskedToken : '' }}" placeholder="Paste your Meta System User Access Token (EAAB...)" class="w-full rounded-xl border border-neutral-200 px-3.5 py-2.5 text-sm font-mono text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">
                            <p class="text-xs text-neutral-400 mt-1">To change, paste a new token. If leaving masked, the existing token remains untouched.</p>
                        @else
                            <input type="text" readonly value="{{ $maskedToken }}" class="w-full rounded-xl border border-neutral-200 bg-neutral-100 px-3.5 py-2.5 text-sm font-mono text-neutral-600 cursor-not-allowed">
                            <p class="text-xs text-neutral-400 mt-1">Full access token is protected and masked. Only Super Admin can update credentials.</p>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">CAPI Test Event Code</label>
                            <input type="text" name="test_event_code" value="{{ old('test_event_code', $capi['test_event_code'] ?? '') }}" placeholder="e.g. TEST12345" class="w-full rounded-xl border border-neutral-200 px-3.5 py-2.5 text-sm font-mono text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Event Source URL</label>
                            <input type="url" name="event_source_url" value="{{ old('event_source_url', $capi['event_source_url'] ?? url('/lp/custom-t-shirts')) }}" class="w-full rounded-xl border border-neutral-200 px-3.5 py-2.5 text-sm text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">
                        </div>
                    </div>

                    @can('marketing_tracking.edit')
                        <div class="border-t border-neutral-100 pt-4 flex justify-end">
                            <button type="submit" class="px-5 py-2.5 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 shadow-xs transition-colors">
                                Save Meta CAPI Configuration
                            </button>
                        </div>
                    @endcan
                </form>
            </div>

            <!-- 3. GOOGLE TAG MANAGER TAB -->
            <div x-show="currentTab === 'gtm'">
                <form method="POST" action="{{ route('admin.marketing.tracking.update') }}" class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tab" value="gtm">

                    <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                        <div>
                            <h2 class="text-sm font-bold text-neutral-900 uppercase tracking-wider">Google Tag Manager (GTM)</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Flexible tag management for GA4, Google Ads, and custom pixels</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="gtm_enabled" value="1" {{ !empty($gtm['enabled']) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-neutral-900"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">GTM Container ID</label>
                            <input type="text" name="container_id" value="{{ old('container_id', $gtm['container_id'] ?? '') }}" placeholder="GTM-XXXXXXX" class="w-full rounded-xl border border-neutral-200 px-3.5 py-2.5 text-sm font-mono text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Scope</label>
                            <select name="scope" class="w-full rounded-xl border border-neutral-200 px-3.5 py-2.5 text-sm text-neutral-800 focus:border-neutral-900 focus:outline-none">
                                <option value="all" {{ ($gtm['scope'] ?? '') === 'all' ? 'selected' : '' }}>Site-wide (All Pages)</option>
                                <option value="landing_pages" {{ ($gtm['scope'] ?? '') === 'landing_pages' ? 'selected' : '' }}>Landing Pages Only (/lp/*)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Environment</label>
                            <select name="environment" class="w-full rounded-xl border border-neutral-200 px-3.5 py-2.5 text-sm text-neutral-800 focus:border-neutral-900 focus:outline-none">
                                <option value="production" {{ ($gtm['environment'] ?? '') === 'production' ? 'selected' : '' }}>Production</option>
                                <option value="staging" {{ ($gtm['environment'] ?? '') === 'staging' ? 'selected' : '' }}>Staging</option>
                            </select>
                        </div>
                    </div>

                    @can('marketing_tracking.edit')
                        <div class="border-t border-neutral-100 pt-4 flex justify-end">
                            <button type="submit" class="px-5 py-2.5 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 shadow-xs transition-colors">
                                Save GTM Settings
                            </button>
                        </div>
                    @endcan
                </form>
            </div>

            <!-- 4. EVENT MAPPING TAB -->
            <div x-show="currentTab === 'mapping'">
                <form method="POST" action="{{ route('admin.marketing.tracking.update') }}" class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tab" value="mapping">

                    <div class="border-b border-neutral-100 pb-4">
                        <h2 class="text-sm font-bold text-neutral-900 uppercase tracking-wider">Internal Event to Meta Standard Event Mapping</h2>
                        <p class="text-xs text-neutral-500 mt-0.5">Control which advertising conversion event fires for each user action in the storefront funnel</p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-neutral-50 rounded-xl border border-neutral-200 gap-3">
                            <div>
                                <p class="text-xs font-bold text-neutral-800">Landing Page Loaded</p>
                                <p class="text-[11px] text-neutral-500">Visitor arrives at /lp/custom-t-shirts</p>
                            </div>
                            <input type="text" name="landing_page_loaded" value="{{ old('landing_page_loaded', $eventMapping['landing_page_loaded'] ?? 'PageView') }}" class="w-48 rounded-lg border border-neutral-200 px-3 py-1.5 text-xs font-mono font-bold bg-white">
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-neutral-50 rounded-xl border border-neutral-200 gap-3">
                            <div>
                                <p class="text-xs font-bold text-neutral-800">WhatsApp Button Clicked</p>
                                <p class="text-[11px] text-neutral-500">User taps either Hero or Sticky dock WhatsApp CTA</p>
                            </div>
                            <input type="text" name="whatsapp_clicked" value="{{ old('whatsapp_clicked', $eventMapping['whatsapp_clicked'] ?? 'Contact') }}" class="w-48 rounded-lg border border-neutral-200 px-3 py-1.5 text-xs font-mono font-bold bg-white">
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-neutral-50 rounded-xl border border-neutral-200 gap-3">
                            <div>
                                <p class="text-xs font-bold text-neutral-800">Quote Form Submitted</p>
                                <p class="text-[11px] text-neutral-500">Visitor successfully submits lead details & artwork</p>
                            </div>
                            <input type="text" name="quote_form_submitted" value="{{ old('quote_form_submitted', $eventMapping['quote_form_submitted'] ?? 'Lead') }}" class="w-48 rounded-lg border border-neutral-200 px-3 py-1.5 text-xs font-mono font-bold bg-white">
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-neutral-50 rounded-xl border border-neutral-200 gap-3">
                            <div>
                                <p class="text-xs font-bold text-neutral-800">Quote Proposal Sent (Admin)</p>
                                <p class="text-[11px] text-neutral-500">Lead status upgraded to 'Quoted'</p>
                            </div>
                            <input type="text" name="quote_created" value="{{ old('quote_created', $eventMapping['quote_created'] ?? 'QualifiedLead') }}" class="w-48 rounded-lg border border-neutral-200 px-3 py-1.5 text-xs font-mono font-bold bg-white">
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-neutral-50 rounded-xl border border-neutral-200 gap-3">
                            <div>
                                <p class="text-xs font-bold text-neutral-800">Order Confirmed / Converted</p>
                                <p class="text-[11px] text-neutral-500">Lead converts into finalized customer order</p>
                            </div>
                            <input type="text" name="order_confirmed" value="{{ old('order_confirmed', $eventMapping['order_confirmed'] ?? 'Purchase') }}" class="w-48 rounded-lg border border-neutral-200 px-3 py-1.5 text-xs font-mono font-bold bg-white">
                        </div>
                    </div>

                    @can('marketing_tracking.edit')
                        <div class="border-t border-neutral-100 pt-4 flex justify-end">
                            <button type="submit" class="px-5 py-2.5 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 shadow-xs transition-colors">
                                Save Event Mappings
                            </button>
                        </div>
                    @endcan
                </form>
            </div>

            <!-- 5. LIVE EVENT STREAM TAB -->
            <div x-show="currentTab === 'test_stream'" class="space-y-6">
                <!-- Test Dispatch Box -->
                @can('marketing_tracking.test')
                    <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
                        <h3 class="text-xs font-bold text-neutral-900 uppercase tracking-wider mb-3">Send Simulated Test Event</h3>
                        <form method="POST" action="{{ route('admin.marketing.tracking.test_event') }}" class="flex flex-wrap items-center gap-3">
                            @csrf
                            <select name="test_event_name" class="rounded-xl border border-neutral-200 px-3 py-2 text-xs font-semibold focus:outline-none">
                                <option value="PageView">PageView</option>
                                <option value="WhatsAppClick">WhatsAppClick</option>
                                <option value="Lead">Lead</option>
                                <option value="QualifiedLead">QualifiedLead</option>
                                <option value="Purchase">Purchase</option>
                            </select>
                            <select name="test_source" class="rounded-xl border border-neutral-200 px-3 py-2 text-xs font-semibold focus:outline-none">
                                <option value="browser">Source: Browser (Simulated)</option>
                                <option value="server">Source: Server CAPI Dispatch</option>
                            </select>
                            <button type="submit" class="px-4 py-2 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 transition-colors shadow-xs">
                                Dispatch Test Event
                            </button>
                        </form>
                    </div>
                @endcan

                <!-- Live Stream Table -->
                <div class="bg-white border border-[color:var(--color-border)] rounded-2xl shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-neutral-100 flex items-center justify-between">
                        <h3 class="text-xs font-bold text-neutral-900 uppercase tracking-wider">Recent Captured Marketing Events</h3>
                        <span class="text-xs text-neutral-400">Latest 25 events</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-neutral-50 text-neutral-500 uppercase tracking-wider font-semibold border-b border-neutral-100">
                                <tr>
                                    <th class="px-5 py-3">Event</th>
                                    <th class="px-4 py-3">Source</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Event ID (Deduplication)</th>
                                    <th class="px-4 py-3">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-100">
                                @forelse($recentEvents as $event)
                                    <tr class="hover:bg-neutral-50/50 transition-colors">
                                        <td class="px-5 py-3.5 font-bold text-neutral-900">
                                            {{ $event->event_name }}
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ $event->source === 'server' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                                {{ ucfirst($event->source) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $event->status === 'forwarded_capi' ? 'bg-emerald-100 text-emerald-800' : ($event->status === 'failed_capi' ? 'bg-red-100 text-red-800' : 'bg-neutral-100 text-neutral-700') }}">
                                                {{ ucfirst(str_replace('_', ' ', $event->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 font-mono text-[11px] text-neutral-500">
                                            {{ Str::limit($event->event_id, 16) }}
                                        </td>
                                        <td class="px-4 py-3.5 text-neutral-500 whitespace-nowrap">
                                            {{ $event->created_at->diffForHumans() }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-10 text-center text-neutral-400">
                                            No events logged yet. Visit the landing page or trigger a test event.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-layouts.admin>
