<x-layouts.admin title="Edit Landing Page | {{ $page->title }} | Okina Craft">
    <x-slot:header>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.marketing.landing_pages.index') }}" class="p-2 rounded-xl text-neutral-500 hover:text-neutral-800 hover:bg-neutral-100 transition-colors">
                    <x-icons.lucide name="lucide-chevron-down" class="w-5 h-5 rotate-90" />
                </a>
                <div>
                    <h1 class="text-xl font-bold text-neutral-900">Edit Landing Page: {{ $page->title }}</h1>
                    <p class="text-xs text-neutral-500 mt-0.5">Slug: <span class="font-mono text-neutral-700">/lp/{{ $page->slug }}</span></p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ url('/lp/' . $page->slug) }}" target="_blank" class="px-3.5 py-2 text-xs font-semibold rounded-xl border border-neutral-300 bg-white hover:bg-neutral-50 text-neutral-700 shadow-xs transition-colors inline-flex items-center gap-1.5">
                    <x-icons.lucide name="lucide-globe" class="w-4 h-4 text-neutral-500" />
                    Preview Live
                    <x-icons.lucide name="lucide-arrow-up-right" class="w-3.5 h-3.5 text-neutral-400" />
                </a>

                @can('landing_pages.publish')
                    @if($page->isPublished())
                        <form method="POST" action="{{ route('admin.marketing.landing_pages.unpublish', $page) }}">
                            @csrf
                            <button type="submit" class="px-3.5 py-2 text-xs font-medium rounded-xl border border-neutral-200 text-amber-700 hover:bg-amber-50 transition-colors">
                                Set to Draft
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.marketing.landing_pages.publish', $page) }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-xs transition-colors">
                                Publish Changes Live
                            </button>
                        </form>
                    @endif
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

    <div x-data="{ currentTab: 'hero', mediaUploading: false, uploadedUrl: '' }" class="grid grid-cols-1 md:grid-cols-4 gap-8 items-start">
        
        <!-- Sidebar Navigation Tabs -->
        <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-4 shadow-xs space-y-1 sticky top-6">
            <button 
                @click="currentTab = 'hero'"
                :class="currentTab === 'hero' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-2"
            >
                <x-icons.lucide name="lucide-layout-template" class="w-4 h-4" />
                Hero & Headlines
            </button>
            <button 
                @click="currentTab = 'cta'"
                :class="currentTab === 'cta' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-2"
            >
                <x-icons.lucide name="lucide-credit-card" class="w-4 h-4" />
                CTAs & WhatsApp
            </button>
            <button 
                @click="currentTab = 'showcase'"
                :class="currentTab === 'showcase' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-2"
            >
                <x-icons.lucide name="lucide-tag" class="w-4 h-4" />
                Showcase & GSM
            </button>
            <button 
                @click="currentTab = 'use_cases'"
                :class="currentTab === 'use_cases' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-2"
            >
                <x-icons.lucide name="lucide-users" class="w-4 h-4" />
                Use Cases (B2B)
            </button>
            <button 
                @click="currentTab = 'benefits'"
                :class="currentTab === 'benefits' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-2"
            >
                <x-icons.lucide name="lucide-shield-check" class="w-4 h-4" />
                Benefits & Trust
            </button>
            <button 
                @click="currentTab = 'faq'"
                :class="currentTab === 'faq' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-2"
            >
                <x-icons.lucide name="lucide-help-circle" class="w-4 h-4" />
                FAQ Section
            </button>
            <button 
                @click="currentTab = 'media'"
                :class="currentTab === 'media' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-2"
            >
                <x-icons.lucide name="lucide-image" class="w-4 h-4" />
                Media & Uploads
            </button>
        </div>

        <!-- Main Form Area -->
        <div class="md:col-span-3">
            <form method="POST" action="{{ route('admin.marketing.landing_pages.update', $page) }}" class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-6">
                @csrf
                @method('PUT')

                <!-- Common Field: Page Title -->
                <div class="border-b border-neutral-100 pb-5">
                    <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Page Title</label>
                    <input type="text" name="title" value="{{ old('title', $page->title) }}" required class="w-full rounded-xl border border-neutral-200 px-3.5 py-2.5 text-sm font-medium text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">
                </div>

                <!-- TAB 1: HERO SECTION -->
                <div x-show="currentTab === 'hero'" class="space-y-5">
                    <h3 class="text-sm font-bold text-neutral-900 uppercase tracking-wider border-b border-neutral-100 pb-2">Hero Section Configuration</h3>
                    
                    <div>
                        <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Eyebrow / Badge Text</label>
                        <input type="text" name="hero[badge]" value="{{ old('hero.badge', $page->hero['badge'] ?? '') }}" class="w-full rounded-xl border border-neutral-200 px-3 py-2 text-sm text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Main Hero Headline</label>
                        <input type="text" name="hero[title]" value="{{ old('hero.title', $page->hero['title'] ?? '') }}" class="w-full rounded-xl border border-neutral-200 px-3 py-2 text-sm text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Subheadline / Pitch</label>
                        <textarea rows="3" name="hero[subtitle]" class="w-full rounded-xl border border-neutral-200 px-3 py-2 text-sm text-neutral-800 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">{{ old('hero.subtitle', $page->hero['subtitle'] ?? '') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-neutral-600 mb-1">Stat 1 (e.g. 50+)</label>
                            <input type="text" name="hero[stats][0][value]" value="{{ old('hero.stats.0.value', $page->hero['stats'][0]['value'] ?? '50+') }}" class="w-full rounded-xl border border-neutral-200 px-3 py-1.5 text-xs">
                            <input type="text" name="hero[stats][0][label]" value="{{ old('hero.stats.0.label', $page->hero['stats'][0]['label'] ?? 'Min Order') }}" class="w-full rounded-xl border border-neutral-200 px-3 py-1.5 text-xs mt-1">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-neutral-600 mb-1">Stat 2 (e.g. 48-72h)</label>
                            <input type="text" name="hero[stats][1][value]" value="{{ old('hero.stats.1.value', $page->hero['stats'][1]['value'] ?? '48-72h') }}" class="w-full rounded-xl border border-neutral-200 px-3 py-1.5 text-xs">
                            <input type="text" name="hero[stats][1][label]" value="{{ old('hero.stats.1.label', $page->hero['stats'][1]['label'] ?? 'Dispatch') }}" class="w-full rounded-xl border border-neutral-200 px-3 py-1.5 text-xs mt-1">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-neutral-600 mb-1">Stat 3 (e.g. 100%)</label>
                            <input type="text" name="hero[stats][2][value]" value="{{ old('hero.stats.2.value', $page->hero['stats'][2]['value'] ?? '100%') }}" class="w-full rounded-xl border border-neutral-200 px-3 py-1.5 text-xs">
                            <input type="text" name="hero[stats][2][label]" value="{{ old('hero.stats.2.label', $page->hero['stats'][2]['label'] ?? 'QC Verified') }}" class="w-full rounded-xl border border-neutral-200 px-3 py-1.5 text-xs mt-1">
                        </div>
                    </div>
                </div>

                <!-- TAB 2: CTAS & WHATSAPP -->
                <div x-show="currentTab === 'cta'" class="space-y-5">
                    <h3 class="text-sm font-bold text-neutral-900 uppercase tracking-wider border-b border-neutral-100 pb-2">Call To Action & WhatsApp Configuration</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Primary CTA Button Text</label>
                            <input type="text" name="cta_settings[primary_text]" value="{{ old('cta_settings.primary_text', $page->cta_settings['primary_text'] ?? 'Get Instant Bulk Quote') }}" class="w-full rounded-xl border border-neutral-200 px-3 py-2 text-sm focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Secondary / WhatsApp CTA Text</label>
                            <input type="text" name="cta_settings[whatsapp_text]" value="{{ old('cta_settings.whatsapp_text', $page->cta_settings['whatsapp_text'] ?? 'Chat on WhatsApp') }}" class="w-full rounded-xl border border-neutral-200 px-3 py-2 text-sm focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">
                        </div>
                    </div>

                    <div class="border-t border-neutral-100 pt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">WhatsApp Destination Phone (with Country Code, no +)</label>
                            <input type="text" name="cta_settings[whatsapp_phone]" value="{{ old('cta_settings.whatsapp_phone', $page->cta_settings['whatsapp_phone'] ?? '919876543210') }}" class="w-full rounded-xl border border-neutral-200 px-3 py-2 text-sm font-mono focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">
                            <p class="text-xs text-neutral-500 mt-1">Example: 919876543210</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Pre-filled WhatsApp Inbound Message</label>
                            <textarea rows="3" name="cta_settings[whatsapp_message]" class="w-full rounded-xl border border-neutral-200 px-3 py-2 text-sm focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 focus:outline-none">{{ old('cta_settings.whatsapp_message', $page->cta_settings['whatsapp_message'] ?? 'Hi Okina Craft, I would like to get a quote for custom apparel for my business/team.') }}</textarea>
                            <p class="text-xs text-neutral-500 mt-1">This text automatically opens when visitors click the WhatsApp CTA button.</p>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: SHOWCASE -->
                <div x-show="currentTab === 'showcase'" class="space-y-5">
                    <h3 class="text-sm font-bold text-neutral-900 uppercase tracking-wider border-b border-neutral-100 pb-2">Showcase & Fabric Specifications</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Showcase Section Title</label>
                            <input type="text" name="showcase[title]" value="{{ old('showcase.title', $page->showcase['title'] ?? 'Built for Scale, Designed to Impress') }}" class="w-full rounded-xl border border-neutral-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">GSM Weight Badge</label>
                            <input type="text" name="showcase[badge]" value="{{ old('showcase.badge', $page->showcase['badge'] ?? '180–240 GSM Premium Combed Cotton') }}" class="w-full rounded-xl border border-neutral-200 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 uppercase tracking-wider mb-1.5">Subtitle / Description</label>
                        <textarea rows="2" name="showcase[subtitle]" class="w-full rounded-xl border border-neutral-200 px-3 py-2 text-sm">{{ old('showcase.subtitle', $page->showcase['subtitle'] ?? 'Compare our corporate-grade silhouettes crafted from breathable, shrink-resistant bio-washed cotton.') }}</textarea>
                    </div>
                </div>

                <!-- TAB 4: USE CASES -->
                <div x-show="currentTab === 'use_cases'" class="space-y-5">
                    <h3 class="text-sm font-bold text-neutral-900 uppercase tracking-wider border-b border-neutral-100 pb-2">B2B Target Audiences & Use Cases</h3>

                    @php
                        $useCases = $page->use_cases['items'] ?? [];
                    @endphp
                    @for($i = 0; $i < max(4, count($useCases)); $i++)
                        <div class="border border-neutral-200 rounded-xl p-4 space-y-3 bg-neutral-50/50">
                            <p class="text-xs font-bold text-neutral-600">Audience Card #{{ $i + 1 }}</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-neutral-500 mb-1">Title</label>
                                    <input type="text" name="use_cases[items][{{ $i }}][title]" value="{{ $useCases[$i]['title'] ?? '' }}" class="w-full rounded-lg border border-neutral-200 px-3 py-1.5 text-xs bg-white">
                                </div>
                                <div>
                                    <label class="block text-xs text-neutral-500 mb-1">Tag / Category</label>
                                    <input type="text" name="use_cases[items][{{ $i }}][tag]" value="{{ $useCases[$i]['tag'] ?? '' }}" class="w-full rounded-lg border border-neutral-200 px-3 py-1.5 text-xs bg-white">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-neutral-500 mb-1">Description</label>
                                <textarea rows="2" name="use_cases[items][{{ $i }}][description]" class="w-full rounded-lg border border-neutral-200 px-3 py-1.5 text-xs bg-white">{{ $useCases[$i]['description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    @endfor
                </div>

                <!-- TAB 5: BENEFITS & TRUST -->
                <div x-show="currentTab === 'benefits'" class="space-y-5">
                    <h3 class="text-sm font-bold text-neutral-900 uppercase tracking-wider border-b border-neutral-100 pb-2">Benefits & Trust Points</h3>

                    @php
                        $benefits = $page->benefits['items'] ?? [];
                    @endphp
                    @for($i = 0; $i < max(4, count($benefits)); $i++)
                        <div class="border border-neutral-200 rounded-xl p-4 space-y-3 bg-neutral-50/50">
                            <p class="text-xs font-bold text-neutral-600">Trust Point #{{ $i + 1 }}</p>
                            <div>
                                <label class="block text-xs text-neutral-500 mb-1">Title</label>
                                <input type="text" name="benefits[items][{{ $i }}][title]" value="{{ $benefits[$i]['title'] ?? '' }}" class="w-full rounded-lg border border-neutral-200 px-3 py-1.5 text-xs bg-white">
                            </div>
                            <div>
                                <label class="block text-xs text-neutral-500 mb-1">Description</label>
                                <textarea rows="2" name="benefits[items][{{ $i }}][description]" class="w-full rounded-lg border border-neutral-200 px-3 py-1.5 text-xs bg-white">{{ $benefits[$i]['description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    @endfor
                </div>

                <!-- TAB 6: FAQ -->
                <div x-show="currentTab === 'faq'" class="space-y-5">
                    <h3 class="text-sm font-bold text-neutral-900 uppercase tracking-wider border-b border-neutral-100 pb-2">Frequently Asked Questions (FAQ)</h3>

                    @php
                        $faqs = $page->faq['items'] ?? [];
                    @endphp
                    @for($i = 0; $i < max(6, count($faqs)); $i++)
                        <div class="border border-neutral-200 rounded-xl p-4 space-y-2 bg-neutral-50/50">
                            <label class="block text-xs font-bold text-neutral-600">Q{{ $i + 1 }}: Question</label>
                            <input type="text" name="faq[items][{{ $i }}][q]" value="{{ $faqs[$i]['q'] ?? '' }}" class="w-full rounded-lg border border-neutral-200 px-3 py-1.5 text-xs bg-white font-medium">
                            <label class="block text-xs font-bold text-neutral-600 mt-2">Answer</label>
                            <textarea rows="2" name="faq[items][{{ $i }}][a]" class="w-full rounded-lg border border-neutral-200 px-3 py-1.5 text-xs bg-white">{{ $faqs[$i]['a'] ?? '' }}</textarea>
                        </div>
                    @endfor
                </div>

                <!-- TAB 7: MEDIA & UPLOADS -->
                <div x-show="currentTab === 'media'" class="space-y-5">
                    <h3 class="text-sm font-bold text-neutral-900 uppercase tracking-wider border-b border-neutral-100 pb-2">Landing Page Media Manager</h3>
                    
                    <div class="p-6 border-2 border-dashed border-neutral-200 rounded-2xl text-center space-y-3">
                        <x-icons.lucide name="lucide-image" class="w-10 h-10 text-neutral-400 mx-auto" />
                        <div>
                            <p class="text-sm font-semibold text-neutral-800">Upload New Marketing Asset</p>
                            <p class="text-xs text-neutral-500 mt-0.5">JPG, PNG, WebP up to 10MB</p>
                        </div>
                        <input type="file" id="mediaUploadInput" class="hidden" accept="image/*" @change="
                            const file = $event.target.files[0];
                            if (!file) return;
                            mediaUploading = true;
                            const fd = new FormData();
                            fd.append('file', file);
                            fd.append('_token', '{{ csrf_token() }}');
                            fetch('{{ route('admin.marketing.landing_pages.media.upload') }}', {
                                method: 'POST',
                                body: fd
                            })
                            .then(r => r.json())
                            .then(data => {
                                mediaUploading = false;
                                if (data.success) {
                                    uploadedUrl = data.url;
                                    alert('Image uploaded successfully! Copy the URL below to paste into image fields.');
                                }
                            })
                            .catch(err => {
                                mediaUploading = false;
                                alert('Upload failed.');
                            });
                        ">
                        <button type="button" onclick="document.getElementById('mediaUploadInput').click()" class="px-4 py-2 text-xs font-semibold rounded-xl bg-neutral-900 text-white hover:bg-neutral-800 transition-colors inline-flex items-center gap-2">
                            <span x-show="!mediaUploading">Choose Image File</span>
                            <span x-show="mediaUploading">Uploading...</span>
                        </button>
                    </div>

                    <div x-show="uploadedUrl" class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl space-y-1">
                        <p class="text-xs font-bold text-emerald-800">Uploaded Image URL:</p>
                        <input type="text" :value="uploadedUrl" readonly class="w-full text-xs font-mono bg-white border border-emerald-300 rounded-lg p-2 text-neutral-800">
                    </div>
                </div>

                <!-- Footer Save Action -->
                <div class="border-t border-neutral-100 pt-5 flex items-center justify-between">
                    <p class="text-xs text-neutral-500">Draft changes are saved instantly to the database.</p>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-neutral-900 text-white hover:bg-neutral-800 text-xs font-bold shadow-xs transition-colors">
                        Save Landing Page Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
