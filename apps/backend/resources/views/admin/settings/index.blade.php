<x-layouts.admin title="Global Settings | Okina Craft">
    <x-slot:header>
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-neutral-800">Global Settings</h1>
        </div>
    </x-slot:header>
 
    @if(session('success'))
        <x-alert type="success" title="Success" dismissible="true" class="mb-6">
            {{ session('success') }}
        </x-alert>
    @endif
 
    <div x-data="{ currentSec: 'business' }" class="grid grid-cols-1 md:grid-cols-4 gap-8 items-start">
        
        <!-- Sidebar Navigation Tabs -->
        <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-4 shadow-xs space-y-1">
            <button 
                @click="currentSec = 'business'"
                :class="currentSec === 'business' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-2"
            >
                <x-icons.lucide name="lucide-building" class="w-4 h-4" />
                Business Details
            </button>
            <button 
                @click="currentSec = 'documents'"
                :class="currentSec === 'documents' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-2"
            >
                <x-icons.lucide name="lucide-file-text" class="w-4 h-4" />
                Document Design & PDF
            </button>
            <button 
                @click="currentSec = 'tax'"
                :class="currentSec === 'tax' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-2"
            >
                <x-icons.lucide name="lucide-percent" class="w-4 h-4" />
                Tax Configuration (GST)
            </button>
            <button 
                @click="currentSec = 'payments'"
                :class="currentSec === 'payments' ? 'bg-neutral-900 text-white shadow-xs' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-800'"
                class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-2"
            >
                <x-icons.lucide name="lucide-credit-card" class="w-4 h-4" />
                Bank Coordinates
            </button>
        </div>
 
        <!-- Main Forms Area -->
        <div class="md:col-span-3">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs space-y-6">
                @csrf
 
                <!-- SECTION 1: BUSINESS DETAILS -->
                <div x-show="currentSec === 'business'" class="space-y-6">
                    <h3 class="text-sm font-bold text-neutral-800 uppercase tracking-wider border-b border-neutral-100 pb-3">Business Profile</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Company Name</label>
                            <input type="text" name="business[company_name]" value="{{ $groups['business']['company_name'] ?? '' }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Legal Name</label>
                            <input type="text" name="business[legal_name]" value="{{ $groups['business']['legal_name'] ?? '' }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Support Email</label>
                            <input type="email" name="business[support_email]" value="{{ $groups['business']['support_email'] ?? '' }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Support Phone</label>
                            <input type="text" name="business[support_phone]" value="{{ $groups['business']['support_phone'] ?? '' }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                    </div>
                </div>
 
                <!-- SECTION 2: DOCUMENTS -->
                <div x-show="currentSec === 'documents'" class="space-y-6">
                    <h3 class="text-sm font-bold text-neutral-800 uppercase tracking-wider border-b border-neutral-100 pb-3">Document Design Settings</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Logo File Path</label>
                            <input type="text" name="documents[logo_path]" value="{{ $groups['documents']['logo_path'] ?? '' }}" placeholder="/storage/images/logo.png" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Website URL</label>
                            <input type="text" name="documents[website_url]" value="{{ $groups['documents']['website_url'] ?? '' }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Authenticity Verification QR Code URL</label>
                            <input type="text" name="documents[qr_code_url]" value="{{ $groups['documents']['qr_code_url'] ?? '' }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Digital Stamp File Path</label>
                            <input type="text" name="documents[stamp_path]" value="{{ $groups['documents']['stamp_path'] ?? '' }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1 col-span-2">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Watermark Text</label>
                            <input type="text" name="documents[watermark_text]" value="{{ $groups['documents']['watermark_text'] ?? '' }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1 col-span-2">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Footer Placeholder (Supports {current} and {total} tags)</label>
                            <input type="text" name="documents[footer_placeholder]" value="{{ $groups['documents']['footer_placeholder'] ?? '' }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Orientation</label>
                            <select name="documents[orientation]" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                                <option value="portrait" @selected(($groups['documents']['orientation'] ?? 'portrait') === 'portrait')>Portrait</option>
                                <option value="landscape" @selected(($groups['documents']['orientation'] ?? '') === 'landscape')>Landscape</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Page Size</label>
                            <select name="documents[size]" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                                <option value="a4" @selected(($groups['documents']['size'] ?? 'a4') === 'a4')>A4</option>
                                <option value="letter" @selected(($groups['documents']['size'] ?? '') === 'letter')>Letter</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Margin Top (mm)</label>
                            <input type="number" name="documents[margin_top]" value="{{ $groups['documents']['margin_top'] ?? 15 }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Margin Bottom (mm)</label>
                            <input type="number" name="documents[margin_bottom]" value="{{ $groups['documents']['margin_bottom'] ?? 15 }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Margin Left (mm)</label>
                            <input type="number" name="documents[margin_left]" value="{{ $groups['documents']['margin_left'] ?? 15 }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Margin Right (mm)</label>
                            <input type="number" name="documents[margin_right]" value="{{ $groups['documents']['margin_right'] ?? 15 }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                    </div>
                </div>
 
                <!-- SECTION 3: TAX -->
                <div x-show="currentSec === 'tax'" class="space-y-6">
                    <h3 class="text-sm font-bold text-neutral-800 uppercase tracking-wider border-b border-neutral-100 pb-3">GST Tax Configuration</h3>
                    
                    <div class="flex items-center gap-3 bg-neutral-50 p-4 rounded-xl border border-neutral-100">
                        <input type="checkbox" id="enable_gst_checkbox" name="tax[enable_gst]" value="1" @checked(!empty($groups['tax']['enable_gst'])) class="w-4 h-4 text-[color:var(--color-brand-600)] focus:ring-[color:var(--focus-ring-color)] border-neutral-300 rounded">
                        <div>
                            <label for="enable_gst_checkbox" class="block text-sm font-bold text-neutral-800 cursor-pointer">Enable Goods and Services Tax (GST)</label>
                            <p class="text-xs text-neutral-400 mt-0.5">Toggle active tax rules, CGST/SGST/IGST breakdown, State matching, and display GSTIN details on invoices/PDFs.</p>
                        </div>
                    </div>
 
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">GSTIN (Taxpayer Identification Number)</label>
                            <input type="text" name="tax[gstin]" value="{{ $groups['tax']['gstin'] ?? '' }}" placeholder="27AAAAA1111A1Z1" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Registered State</label>
                            <input type="text" name="tax[registered_state]" value="{{ $groups['tax']['registered_state'] ?? '' }}" placeholder="Maharashtra" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Default Tax Rate (%)</label>
                            <input type="number" name="tax[default_tax_rate]" value="{{ $groups['tax']['default_tax_rate'] ?? 18 }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                    </div>
                </div>
 
                <!-- SECTION 4: PAYMENTS -->
                <div x-show="currentSec === 'payments'" class="space-y-6">
                    <h3 class="text-sm font-bold text-neutral-800 uppercase tracking-wider border-b border-neutral-100 pb-3">Corporate Bank Payout Coordinate Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Receiving Bank Name</label>
                            <input type="text" name="payments[bank_name]" value="{{ $groups['payments']['bank_name'] ?? '' }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Corporate Account Number</label>
                            <input type="text" name="payments[account_number]" value="{{ $groups['payments']['account_number'] ?? '' }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                        <div class="space-y-1 col-span-2 md:col-span-1">
                            <label class="block text-xs font-bold text-neutral-500 uppercase">Branch Routing IFSC Route Code</label>
                            <input type="text" name="payments[ifsc_code]" value="{{ $groups['payments']['ifsc_code'] ?? '' }}" class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-sm">
                        </div>
                    </div>
                </div>
 
                <!-- Submit Action Block -->
                <div class="border-t border-neutral-100 pt-4 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-[color:var(--color-brand-600)] text-white hover:bg-[color:var(--color-brand-700)] font-bold rounded-xl text-xs transition-all duration-150 focus:outline-none">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
        
    </div>
</x-layouts.admin>
