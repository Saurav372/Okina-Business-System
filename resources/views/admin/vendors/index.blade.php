<x-layouts.admin title="Vendors Directory">
    <div class="space-y-6" x-data="vendorModal()">

        <!-- Header Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-neutral-200 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 tracking-tight">Vendors Directory</h1>
                <p class="text-xs text-neutral-500 mt-1">Manage supplier accounts, contact details, GSTIN tax IDs, and procurement terms.</p>
            </div>
            @can('create', App\Models\Vendor::class)
                <button type="button" @click="openCreateModal()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[color:var(--color-brand-600)] text-white text-xs font-bold rounded-xl shadow-xs hover:bg-[color:var(--color-brand-700)] transition-colors self-start sm:self-auto">
                    <x-icons.lucide name="lucide-plus" class="w-4 h-4" />
                    <span>Add New Vendor</span>
                </button>
            @endcan
        </div>

        <!-- Session Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-3 shadow-xs">
                <x-icons.lucide name="lucide-check-circle-2" class="w-4 h-4 flex-shrink-0 text-emerald-600" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs font-medium space-y-1 shadow-xs">
                @foreach ($errors->all() as $error)
                    <div class="flex items-center gap-2">
                        <x-icons.lucide name="lucide-alert-circle" class="w-4 h-4 flex-shrink-0 text-red-600" />
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- KPI Metrics Grid (4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Vendors -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Total Vendors</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-700">
                        <x-icons.lucide name="lucide-building-2" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-neutral-900">{{ number_format($totalVendors) }}</div>
                <div class="mt-1 text-xs text-neutral-500">All registered suppliers</div>
            </div>

            <!-- Active -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Active</span>
                    <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100">
                        <x-icons.lucide name="lucide-check-circle-2" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-emerald-600">{{ number_format($activeVendors) }}</div>
                <div class="mt-1 text-xs text-emerald-700/80">Available for procurement</div>
            </div>

            <!-- Inactive -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-500 uppercase tracking-wider">Inactive</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-500 border border-neutral-200">
                        <x-icons.lucide name="lucide-pause-circle" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-neutral-600">{{ number_format($inactiveVendors) }}</div>
                <div class="mt-1 text-xs text-neutral-400">Temporarily paused</div>
            </div>

            <!-- Blocked -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-red-600 uppercase tracking-wider">Blocked</span>
                    <div class="p-2.5 rounded-xl bg-red-50 text-red-600 border border-red-100">
                        <x-icons.lucide name="lucide-ban" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-red-600">{{ number_format($blockedVendors) }}</div>
                <div class="mt-1 text-xs text-red-700/80">Restricted from orders</div>
            </div>
        </div>

        <!-- Filter Bar Form -->
        <div class="bg-white border border-neutral-200 rounded-2xl p-4 sm:p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.vendors.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Search Input -->
                <div class="sm:col-span-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <x-icons.lucide name="lucide-search" class="w-4 h-4" />
                    </div>
                    <input type="text" name="search" value="{{ $filters->search }}"
                           placeholder="Search vendor name, code, contact, GSTIN, city, email, phone..."
                           class="w-full pl-9 pr-4 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                </div>

                <!-- Status Select & Submit -->
                <div class="flex items-center gap-2">
                    <select name="status" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ ($filters->status?->value ?? '') === '' ? 'selected' : '' }}>All Statuses</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st->value }}" {{ ($filters->status?->value ?? '') === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-5 py-2 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 transition-colors shrink-0">Filter</button>
                    @if ($filters->search || $filters->status)
                        <a href="{{ route('admin.vendors.index') }}" class="px-3 py-2 border border-neutral-300 rounded-xl text-xs font-semibold text-neutral-600 bg-white hover:bg-neutral-50 transition-colors shrink-0">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Vendors Table -->
        <div class="bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-neutral-800">
                    <thead class="bg-neutral-50/80 text-[10px] uppercase font-bold text-neutral-400 tracking-wider border-b border-neutral-200">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">Vendor</th>
                            <th class="py-3.5 px-4 font-semibold">Status</th>
                            <th class="py-3.5 px-4 font-semibold">Contact &amp; Location</th>
                            <th class="py-3.5 px-4 font-semibold">GSTIN</th>
                            <th class="py-3.5 px-4 font-semibold">Payment Terms</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Orders</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($vendors as $vendor)
                            <tr class="hover:bg-neutral-50/60 transition-colors">
                                <!-- Vendor Name & Code -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-neutral-900">{{ $vendor->name }}</div>
                                    <span class="inline-flex px-2 py-0.5 bg-neutral-100 text-neutral-700 rounded text-[11px] font-mono font-medium border border-neutral-200 mt-1">
                                        {{ $vendor->vendor_code }}
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $vendor->status->badgeClass() }}">
                                        {{ $vendor->status->label() }}
                                    </span>
                                </td>

                                <!-- Contact & Location -->
                                <td class="py-3.5 px-4">
                                    @if ($vendor->contact_name)
                                        <div class="font-medium text-neutral-800">{{ $vendor->contact_name }}</div>
                                    @endif
                                    @if ($vendor->email)
                                        <div class="text-neutral-500 text-[11px]">{{ $vendor->email }}</div>
                                    @endif
                                    @if ($vendor->city || $vendor->state)
                                        <div class="text-neutral-400 text-[11px] mt-0.5">{{ implode(', ', array_filter([$vendor->city, $vendor->state])) }}</div>
                                    @endif
                                </td>

                                <!-- GSTIN -->
                                <td class="py-3.5 px-4 font-mono text-[11px] text-neutral-600">
                                    {{ $vendor->gstin ?? '—' }}
                                </td>

                                <!-- Payment Terms -->
                                <td class="py-3.5 px-4 text-neutral-500">
                                    {{ $vendor->payment_terms ?? '—' }}
                                </td>

                                <!-- Orders Count -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-neutral-800">
                                    @if (\Illuminate\Support\Facades\Route::has('admin.purchases.index'))
                                        <a href="{{ route('admin.purchases.index', ['vendor_id' => $vendor->id]) }}" class="hover:text-[color:var(--color-brand-600)] transition-colors">
                                            {{ number_format($vendor->purchase_orders_count ?? 0) }}
                                        </a>
                                    @else
                                        <span class="text-neutral-400 font-normal text-[11px]">Purchase Orders — Available in U5.1.4</span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @can('update', $vendor)
                                            <button type="button"
                                                @click="openEditModal(@js([
                                                    'id' => $vendor->id,
                                                    'name' => $vendor->name,
                                                    'vendor_code' => $vendor->vendor_code,
                                                    'status' => $vendor->status->value,
                                                    'contact_name' => $vendor->contact_name,
                                                    'email' => $vendor->email,
                                                    'phone' => $vendor->phone,
                                                    'gstin' => $vendor->gstin,
                                                    'payment_terms' => $vendor->payment_terms,
                                                    'address_line1' => $vendor->address_line1,
                                                    'address_line2' => $vendor->address_line2,
                                                    'city' => $vendor->city,
                                                    'state' => $vendor->state,
                                                    'postal_code' => $vendor->postal_code,
                                                    'country_code' => $vendor->country_code,
                                                    'notes' => $vendor->notes,
                                                    'update_url' => route('admin.vendors.update', $vendor),
                                                ]))"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-neutral-700 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-lg shadow-2xs transition-colors">
                                                <x-icons.lucide name="lucide-edit-3" class="w-3.5 h-3.5 text-neutral-500" />
                                                <span>Edit</span>
                                            </button>
                                        @endcan

                                        @can('delete', $vendor)
                                            <button type="button" @click="confirmDelete(@js($vendor->id), @js($vendor->name))" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-red-600 bg-white hover:bg-red-50 border border-red-200 rounded-lg shadow-2xs transition-colors">
                                                <x-icons.lucide name="lucide-trash-2" class="w-3.5 h-3.5 text-red-500" />
                                                <span>Delete</span>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-neutral-400">
                                    <x-icons.lucide name="lucide-building-2" class="w-10 h-10 mx-auto text-neutral-300 mb-3" />
                                    <p class="text-sm font-semibold text-neutral-700">No vendors found</p>
                                    <p class="text-xs text-neutral-400 mt-1">Try adjusting your search terms or active status filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Server-Side Pagination -->
            @if ($vendors->hasPages())
                <div class="p-4 border-t border-neutral-200">
                    {{ $vendors->withQueryString()->links() }}
                </div>
            @endif
        </div>

        <!-- Vendor Create & Edit Modal Dialog -->
        <div x-show="isModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="closeModal()">
            <div class="fixed inset-0 bg-neutral-900/60 backdrop-blur-xs transition-opacity" x-show="isModalOpen" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white border border-neutral-200 rounded-2xl p-6 shadow-2xl transition-all" x-show="isModalOpen" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.away="closeModal()">

                    <!-- Modal Header -->
                    <div class="flex items-center justify-between border-b border-neutral-200 pb-4 mb-5">
                        <div>
                            <h3 class="text-lg font-bold text-neutral-900" x-text="modalMode === 'edit' ? 'Edit Vendor Account' : 'Register New Vendor'"></h3>
                            <p class="text-xs text-neutral-500 mt-0.5" x-text="modalMode === 'edit' ? 'Update supplier contact details and tax info.' : 'Add a new supplier to the procurement directory.'"></p>
                        </div>
                        <button type="button" @click="closeModal()" class="p-1 text-neutral-400 hover:text-neutral-700 rounded-lg transition-colors">
                            <x-icons.lucide name="lucide-x" class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Vendor Form -->
                    <form method="POST" :action="formAction" class="space-y-4">
                        @csrf
                        <input type="hidden" name="_method" :value="modalMode === 'edit' ? 'PUT' : 'POST'">
                        <input type="hidden" name="modal_mode" :value="modalMode">
                        <input type="hidden" name="edit_vendor_id" :value="editingVendorId">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Name -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Vendor Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" x-model="form.name" required placeholder="e.g. Acme Textile Mills" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                @error('name') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Vendor Code -->
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Vendor Code <span class="text-neutral-400 font-normal">(Optional)</span></label>
                                <input type="text" name="vendor_code" x-model="form.vendor_code" placeholder="Leave empty for VND-XXXXXX" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 font-mono focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                @error('vendor_code') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Status</label>
                                <select name="status" x-model="form.status" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                    @foreach ($statuses as $st)
                                        <option value="{{ $st->value }}">{{ $st->label() }}</option>
                                    @endforeach
                                </select>
                                @error('status') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Contact Name -->
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Contact Person</label>
                                <input type="text" name="contact_name" x-model="form.contact_name" placeholder="Contact person name" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                @error('contact_name') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Email Address</label>
                                <input type="email" name="email" x-model="form.email" placeholder="supplier@example.com" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                @error('email') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Phone Number</label>
                                <input type="text" name="phone" x-model="form.phone" placeholder="+91 9999999999" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                @error('phone') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- GSTIN -->
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">GSTIN Tax ID</label>
                                <input type="text" name="gstin" x-model="form.gstin" placeholder="29GGGGG1314R1Z8" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 font-mono uppercase focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                @error('gstin') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Payment Terms -->
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Payment Terms</label>
                                <input type="text" name="payment_terms" x-model="form.payment_terms" placeholder="Net 30, Net 15, Advance" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                @error('payment_terms') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Country Code -->
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Country Code</label>
                                <input type="text" name="country_code" x-model="form.country_code" placeholder="IN" maxlength="2" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 font-mono uppercase focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                @error('country_code') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Address Line 1 -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Address Line 1</label>
                                <input type="text" name="address_line1" x-model="form.address_line1" placeholder="Street address or P.O. box" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                @error('address_line1') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Address Line 2 -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Address Line 2</label>
                                <input type="text" name="address_line2" x-model="form.address_line2" placeholder="Suite, unit, building, floor" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                @error('address_line2') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- City -->
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">City</label>
                                <input type="text" name="city" x-model="form.city" placeholder="City name" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                @error('city') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- State -->
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">State / Region</label>
                                <input type="text" name="state" x-model="form.state" placeholder="State" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                @error('state') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Postal Code -->
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Postal Code</label>
                                <input type="text" name="postal_code" x-model="form.postal_code" placeholder="PIN / Postal Code" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 font-mono focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                @error('postal_code') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Notes -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Audit &amp; Procurement Notes</label>
                                <textarea name="notes" x-model="form.notes" rows="2" placeholder="Additional vendor notes, terms, or guidelines..." class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"></textarea>
                                @error('notes') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-200">
                            <button type="button" @click="closeModal()" class="px-4 py-2 text-xs font-semibold text-neutral-700 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-xl transition-colors">Cancel</button>
                            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-[color:var(--color-brand-600)] hover:bg-[color:var(--color-brand-700)] rounded-xl shadow-xs transition-colors" x-text="modalMode === 'edit' ? 'Save Vendor Changes' : 'Create Vendor'"></button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div x-show="isDeleteModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="isDeleteModalOpen = false">
            <div class="fixed inset-0 bg-neutral-900/60 backdrop-blur-xs transition-opacity"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white border border-neutral-200 rounded-2xl p-6 shadow-2xl">
                    <h3 class="text-lg font-bold text-neutral-900">Delete Vendor Account</h3>
                    <p class="text-xs text-neutral-600 mt-2">Are you sure you want to soft-delete vendor <strong class="font-bold text-neutral-900" x-text="deleteVendorName"></strong>? Historical purchase orders will remain intact.</p>

                    <form method="POST" :action="deleteActionUrl" class="mt-5 flex items-center justify-end gap-3">
                        @csrf
                        @method('DELETE')
                        <button type="button" @click="isDeleteModalOpen = false" class="px-4 py-2 text-xs font-semibold text-neutral-700 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-xl transition-colors">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-xs transition-colors">Confirm Delete</button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script>
        function vendorModal() {
            return {
                isModalOpen: @js($errors->any() || isset($editingVendor)),
                modalMode: @js($modalMode ?? 'create'),
                editingVendorId: @js($editingVendor->id ?? null),
                formAction: @js($formAction),

                isDeleteModalOpen: false,
                deleteVendorId: null,
                deleteVendorName: '',
                deleteActionUrl: '',

                form: {
                    name: @js(old('name', $editingVendor->name ?? '')),
                    vendor_code: @js(old('vendor_code', $editingVendor->vendor_code ?? '')),
                    status: @js(old('status', $editingVendor->status->value ?? 'active')),
                    contact_name: @js(old('contact_name', $editingVendor->contact_name ?? '')),
                    email: @js(old('email', $editingVendor->email ?? '')),
                    phone: @js(old('phone', $editingVendor->phone ?? '')),
                    gstin: @js(old('gstin', $editingVendor->gstin ?? '')),
                    payment_terms: @js(old('payment_terms', $editingVendor->payment_terms ?? '')),
                    address_line1: @js(old('address_line1', $editingVendor->address_line1 ?? '')),
                    address_line2: @js(old('address_line2', $editingVendor->address_line2 ?? '')),
                    city: @js(old('city', $editingVendor->city ?? '')),
                    state: @js(old('state', $editingVendor->state ?? '')),
                    postal_code: @js(old('postal_code', $editingVendor->postal_code ?? '')),
                    country_code: @js(old('country_code', $editingVendor->country_code ?? 'IN')),
                    notes: @js(old('notes', $editingVendor->notes ?? '')),
                },

                openCreateModal() {
                    this.modalMode = 'create';
                    this.editingVendorId = null;
                    this.formAction = @js(route('admin.vendors.store'));
                    this.resetForm();
                    this.isModalOpen = true;
                },

                openEditModal(vendor) {
                    this.modalMode = 'edit';
                    this.editingVendorId = vendor.id;
                    this.formAction = vendor.update_url;
                    this.form.name = vendor.name || '';
                    this.form.vendor_code = vendor.vendor_code || '';
                    this.form.status = vendor.status || 'active';
                    this.form.contact_name = vendor.contact_name || '';
                    this.form.email = vendor.email || '';
                    this.form.phone = vendor.phone || '';
                    this.form.gstin = vendor.gstin || '';
                    this.form.payment_terms = vendor.payment_terms || '';
                    this.form.address_line1 = vendor.address_line1 || '';
                    this.form.address_line2 = vendor.address_line2 || '';
                    this.form.city = vendor.city || '';
                    this.form.state = vendor.state || '';
                    this.form.postal_code = vendor.postal_code || '';
                    this.form.country_code = vendor.country_code || 'IN';
                    this.form.notes = vendor.notes || '';
                    this.isModalOpen = true;
                },

                confirmDelete(id, name) {
                    this.deleteVendorId = id;
                    this.deleteVendorName = name;
                    this.deleteActionUrl = '/admin/vendors/' + id;
                    this.isDeleteModalOpen = true;
                },

                closeModal() {
                    this.isModalOpen = false;
                },

                resetForm() {
                    this.form = {
                        name: '', vendor_code: '', status: 'active', contact_name: '',
                        email: '', phone: '', gstin: '', payment_terms: '', address_line1: '',
                        address_line2: '', city: '', state: '', postal_code: '', country_code: 'IN', notes: ''
                    };
                }
            };
        }
    </script>
</x-layouts.admin>
