<x-layouts.admin title="Edit Product: {{ $product->name }}">
    <x-slot:header>
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold border border-neutral-300 rounded-xl text-neutral-700 bg-white hover:bg-neutral-50 transition-colors focus-visible:outline-none">
            Back to Products
        </a>
    </x-slot:header>

    @if(session('success'))
        <div class="mb-4">
            <x-alert type="success" dismissible="true">
                {{ session('success') }}
            </x-alert>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.products.update', $product) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Main Panel (Colspan 2) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- General Information Card -->
                <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                    <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3">General Information</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-form.input 
                            id="name" 
                            name="name" 
                            label="Product Name" 
                            value="{{ old('name', $product->name) }}" 
                            required="true"
                            error="{{ $errors->first('name') }}"
                        />

                        <x-form.input 
                            id="slug" 
                            name="slug" 
                            label="Product Slug" 
                            value="{{ old('slug', $product->slug) }}" 
                            required="true"
                            error="{{ $errors->first('slug') }}"
                            hint="Normalized to lowercase, alphanumeric, and hyphens automatically."
                        />
                    </div>

                    <div class="space-y-4">
                        <x-form.wrapper id="short_description" label="Short Description" :error="$errors->first('short_description')">
                            <textarea 
                                id="short_description" 
                                name="short_description" 
                                rows="2" 
                                class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                            >{{ old('short_description', $product->short_description) }}</textarea>
                        </x-form.wrapper>

                        <x-form.wrapper id="description" label="Description" :error="$errors->first('description')">
                            <textarea 
                                id="description" 
                                name="description" 
                                rows="5" 
                                class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                            >{{ old('description', $product->description) }}</textarea>
                        </x-form.wrapper>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
                        @php
                            $categoryOptions = $categories->map(fn($c) => ['value' => $c->id, 'label' => $c->name])->all();
                            $typeOptions = [
                                ['value' => \App\Models\Product::TYPE_SIMPLE, 'label' => 'Simple'],
                                ['value' => \App\Models\Product::TYPE_VARIABLE, 'label' => 'Variable'],
                                ['value' => \App\Models\Product::TYPE_BUNDLE, 'label' => 'Bundle']
                            ];
                            $customOptions = [
                                ['value' => \App\Models\Product::CUSTOMIZATION_NONE, 'label' => 'None'],
                                ['value' => \App\Models\Product::CUSTOMIZATION_OPTIONAL, 'label' => 'Optional'],
                                ['value' => \App\Models\Product::CUSTOMIZATION_REQUIRED, 'label' => 'Required']
                            ];
                            $fulfillmentOptions = [
                                ['value' => \App\Models\Product::FULFILLMENT_STOCKED, 'label' => 'Stocked'],
                                ['value' => \App\Models\Product::FULFILLMENT_MADE_TO_ORDER, 'label' => 'Made to Order']
                            ];
                        @endphp

                        <x-form.select 
                            id="primary_category_id" 
                            name="primary_category_id" 
                            label="Category" 
                            placeholder="Select Category"
                            value="{{ old('primary_category_id', $product->primary_category_id) }}" 
                            :options="$categoryOptions"
                            error="{{ $errors->first('primary_category_id') }}"
                        />

                        <x-form.select 
                            id="product_type" 
                            name="product_type" 
                            label="Product Type" 
                            value="{{ old('product_type', $product->product_type) }}" 
                            :options="$typeOptions"
                            error="{{ $errors->first('product_type') }}"
                        />

                        <x-form.select 
                            id="customization_mode" 
                            name="customization_mode" 
                            label="Customization" 
                            value="{{ old('customization_mode', $product->customization_mode) }}" 
                            :options="$customOptions"
                            error="{{ $errors->first('customization_mode') }}"
                        />

                        <x-form.select 
                            id="fulfillment_type" 
                            name="fulfillment_type" 
                            label="Fulfillment" 
                            value="{{ old('fulfillment_type', $product->fulfillment_type) }}" 
                            :options="$fulfillmentOptions"
                            error="{{ $errors->first('fulfillment_type') }}"
                        />
                    </div>
                </div>

                <!-- Pricing & Ordering Settings -->
                <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                    <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3">Pricing & Ordering</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-form.input 
                            id="base_price_minor" 
                            name="base_price_minor" 
                            type="number"
                            label="Base Price (INR minor units)" 
                            value="{{ old('base_price_minor', $product->base_price_minor) }}" 
                            required="true"
                            error="{{ $errors->first('base_price_minor') }}"
                            hint="Stored as integer minor units (e.g. 1000 = 10.00 INR). No decimal parsing occurs."
                        />

                        <!-- Currency - disabled/read-only text -->
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Currency</label>
                            <input 
                                type="text" 
                                value="INR" 
                                disabled
                                class="block w-full rounded-xl border border-neutral-300 px-4 py-2 text-xs text-neutral-500 bg-neutral-50 cursor-not-allowed"
                            />
                            <input type="hidden" name="currency" value="INR" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                        <x-form.input 
                            id="min_order_quantity" 
                            name="min_order_quantity" 
                            type="number"
                            label="Min Order Qty" 
                            value="{{ old('min_order_quantity', $product->min_order_quantity) }}" 
                            required="true"
                            error="{{ $errors->first('min_order_quantity') }}"
                        />

                        <x-form.input 
                            id="max_order_quantity" 
                            name="max_order_quantity" 
                            type="number"
                            label="Max Order Qty" 
                            value="{{ old('max_order_quantity', $product->max_order_quantity) }}" 
                            error="{{ $errors->first('max_order_quantity') }}"
                        />

                        <x-form.input 
                            id="bulk_threshold_quantity" 
                            name="bulk_threshold_quantity" 
                            type="number"
                            label="Bulk Threshold Qty" 
                            value="{{ old('bulk_threshold_quantity', $product->bulk_threshold_quantity) }}" 
                            error="{{ $errors->first('bulk_threshold_quantity') }}"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <x-form.wrapper id="direct_checkout_enabled" label="Direct Checkout" :error="$errors->first('direct_checkout_enabled')">
                            <div class="flex items-center gap-2 mt-1">
                                <input 
                                    type="checkbox" 
                                    id="direct_checkout_enabled" 
                                    name="direct_checkout_enabled" 
                                    value="1" 
                                    @if(old('direct_checkout_enabled', $product->direct_checkout_enabled)) checked @endif
                                    class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-pointer"
                                />
                                <label for="direct_checkout_enabled" class="text-xs text-neutral-600 select-none">Enable direct checkout (skip quote request)</label>
                            </div>
                        </x-form.wrapper>

                        <x-form.wrapper id="quote_enabled" label="Quote Request" :error="$errors->first('quote_enabled')">
                            <div class="flex items-center gap-2 mt-1">
                                <input 
                                    type="checkbox" 
                                    id="quote_enabled" 
                                    name="quote_enabled" 
                                    value="1" 
                                    @if(old('quote_enabled', $product->quote_enabled)) checked @endif
                                    class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-pointer"
                                />
                                <label for="quote_enabled" class="text-xs text-neutral-600 select-none">Enable quotes for this product</label>
                            </div>
                        </x-form.wrapper>
                    </div>
                </div>

                <!-- SEO Information -->
                <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                    <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3">SEO Information</h3>

                    <x-form.input 
                        id="seo_title" 
                        name="seo_title" 
                        label="SEO Title" 
                        value="{{ old('seo_title', $product->seo_title) }}" 
                        error="{{ $errors->first('seo_title') }}"
                    />

                    <x-form.wrapper id="seo_description" label="SEO Description" :error="$errors->first('seo_description')">
                        <textarea 
                            id="seo_description" 
                            name="seo_description" 
                            rows="3" 
                            class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                        >{{ old('seo_description', $product->seo_description) }}</textarea>
                    </x-form.wrapper>
                </div>
            </div>

            <!-- Right Sidebar Panel -->
            <div class="space-y-6">
                <!-- Status & Visibility Settings -->
                <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                    <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3">Status & Visibility</h3>

                    @php
                        $statusOptions = [
                            ['value' => \App\Models\Product::STATUS_DRAFT, 'label' => 'Draft'],
                            ['value' => \App\Models\Product::STATUS_ACTIVE, 'label' => 'Active'],
                            ['value' => \App\Models\Product::STATUS_OUT_OF_STOCK, 'label' => 'Out of Stock'],
                            ['value' => \App\Models\Product::STATUS_BULK_ONLY, 'label' => 'Bulk Only'],
                            ['value' => \App\Models\Product::STATUS_DISCONTINUED, 'label' => 'Discontinued']
                        ];
                        $visibilityOptions = [
                            ['value' => \App\Models\Product::VISIBILITY_PUBLIC, 'label' => 'Public'],
                            ['value' => \App\Models\Product::VISIBILITY_PRIVATE, 'label' => 'Private']
                        ];
                    @endphp

                    <x-form.select 
                        id="status" 
                        name="status" 
                        label="Product Status" 
                        value="{{ old('status', $product->status) }}" 
                        :options="$statusOptions"
                        error="{{ $errors->first('status') }}"
                    />

                    <!-- Storefront Status Badge Warning -->
                    @if($product->status === \App\Models\Product::STATUS_DRAFT)
                        <div class="mt-2 text-xs text-neutral-600 bg-neutral-50 border border-neutral-200 rounded-lg p-2.5 flex items-center gap-1.5 font-semibold">
                            <x-icons.lucide name="lucide-eye-off" class="w-4 h-4 text-neutral-400 shrink-0" />
                            <span>Not visible on storefront</span>
                        </div>
                    @endif

                    <x-form.select 
                        id="visibility" 
                        name="visibility" 
                        label="Visibility" 
                        value="{{ old('visibility', $product->visibility) }}" 
                        :options="$visibilityOptions"
                        error="{{ $errors->first('visibility') }}"
                    />

                    <!-- Storefront Visibility Banner -->
                    @if($product->visibility === \App\Models\Product::VISIBILITY_PRIVATE)
                        <div class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg p-2.5 flex items-center gap-1.5 font-semibold">
                            <x-icons.lucide name="lucide-eye-off" class="w-4 h-4 text-amber-500 shrink-0" />
                            <span>Hidden from customers</span>
                        </div>
                    @endif

                    <x-form.input 
                        id="sort_order" 
                        name="sort_order" 
                        type="number"
                        label="Sort Order" 
                        value="{{ old('sort_order', $product->sort_order) }}" 
                        required="true"
                        error="{{ $errors->first('sort_order') }}"
                    />

                    <x-form.input 
                        id="published_at" 
                        name="published_at" 
                        type="date"
                        label="Published Date" 
                        value="{{ old('published_at', $product->published_at?->format('Y-m-d')) }}" 
                        error="{{ $errors->first('published_at') }}"
                    />
                </div>

                <!-- Read-Only Metadata Card -->
                <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
                    <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3 mb-3">Metadata</h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-neutral-50">
                            <span class="text-neutral-400">Product ID</span>
                            <span class="font-mono font-bold text-neutral-700">{{ $product->id }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-neutral-50">
                            <span class="text-neutral-400">Created At</span>
                            <span class="font-mono text-neutral-600">{{ $product->created_at ? $product->created_at->format('Y-m-d H:i:s') : 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-neutral-400">Updated At</span>
                            <span class="font-mono text-neutral-600">{{ $product->updated_at ? $product->updated_at->format('Y-m-d H:i:s') : 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action Card -->
                <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-3">
                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold bg-[color:var(--color-brand-600)] hover:bg-[color:var(--color-brand-700)] text-white rounded-xl transition-colors focus-visible:outline-none cursor-pointer">
                        Save Changes
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-semibold border border-neutral-300 hover:bg-neutral-50 text-neutral-700 rounded-xl transition-colors focus-visible:outline-none text-center">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
