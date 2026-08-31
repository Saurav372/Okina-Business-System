<x-layouts.admin title="New Product">
    <x-slot:header>
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold border border-neutral-300 rounded-xl text-neutral-700 bg-white hover:bg-neutral-50 transition-colors focus-visible:outline-none">
            Back to Products
        </a>
    </x-slot:header>

    @if($errors->any())
        <div class="mb-4">
            <x-alert type="danger" dismissible="true">
                Please correct the errors in the form.
            </x-alert>
        </div>
    @endif

    @php
        $typeOptions = [
            ['value' => \App\Models\Product::TYPE_SIMPLE, 'label' => 'Simple'],
            ['value' => \App\Models\Product::TYPE_VARIABLE, 'label' => 'Variable'],
            ['value' => \App\Models\Product::TYPE_BUNDLE, 'label' => 'Bundle'],
        ];
        $customOptions = [
            ['value' => \App\Models\Product::CUSTOMIZATION_NONE, 'label' => 'None'],
            ['value' => \App\Models\Product::CUSTOMIZATION_OPTIONAL, 'label' => 'Optional'],
            ['value' => \App\Models\Product::CUSTOMIZATION_REQUIRED, 'label' => 'Required'],
        ];
        $fulfillmentOptions = [
            ['value' => \App\Models\Product::FULFILLMENT_STOCKED, 'label' => 'Stocked'],
            ['value' => \App\Models\Product::FULFILLMENT_MADE_TO_ORDER, 'label' => 'Made to Order'],
        ];
        $statusOptions = [
            ['value' => \App\Models\Product::STATUS_DRAFT, 'label' => 'Draft'],
            ['value' => \App\Models\Product::STATUS_ACTIVE, 'label' => 'Active'],
            ['value' => \App\Models\Product::STATUS_OUT_OF_STOCK, 'label' => 'Out of Stock'],
            ['value' => \App\Models\Product::STATUS_BULK_ONLY, 'label' => 'Bulk Only'],
            ['value' => \App\Models\Product::STATUS_DISCONTINUED, 'label' => 'Discontinued'],
        ];
        $visibilityOptions = [
            ['value' => \App\Models\Product::VISIBILITY_PRIVATE, 'label' => 'Private'],
            ['value' => \App\Models\Product::VISIBILITY_PUBLIC, 'label' => 'Public'],
        ];
        $categoryOptions = $categories->map(fn ($category) => ['value' => $category->id, 'label' => $category->name])->all();
    @endphp

    <form method="POST" action="{{ route('admin.products.store') }}" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3">General Information</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input id="name" name="name" label="Product Name" value="{{ old('name') }}" required="true" error="{{ $errors->first('name') }}" />
                    <x-form.input id="slug" name="slug" label="Product Slug" value="{{ old('slug') }}" error="{{ $errors->first('slug') }}" hint="Leave blank to generate it from the name." />
                </div>

                <x-form.select id="primary_category_id" name="primary_category_id" label="Category" value="{{ old('primary_category_id') }}" :options="$categoryOptions" placeholder="No category" error="{{ $errors->first('primary_category_id') }}" />

                <x-form.wrapper id="short_description" label="Short Description" :error="$errors->first('short_description')">
                    <textarea id="short_description" name="short_description" rows="2" class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">{{ old('short_description') }}</textarea>
                </x-form.wrapper>

                <x-form.wrapper id="description" label="Description" :error="$errors->first('description')">
                    <textarea id="description" name="description" rows="5" class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">{{ old('description') }}</textarea>
                </x-form.wrapper>
            </div>

            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3">Pricing & Ordering</h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-form.select id="product_type" name="product_type" label="Product Type" value="{{ old('product_type', \App\Models\Product::TYPE_SIMPLE) }}" :options="$typeOptions" error="{{ $errors->first('product_type') }}" />
                    <x-form.select id="customization_mode" name="customization_mode" label="Customization" value="{{ old('customization_mode', \App\Models\Product::CUSTOMIZATION_NONE) }}" :options="$customOptions" error="{{ $errors->first('customization_mode') }}" />
                    <x-form.select id="fulfillment_type" name="fulfillment_type" label="Fulfillment" value="{{ old('fulfillment_type', \App\Models\Product::FULFILLMENT_STOCKED) }}" :options="$fulfillmentOptions" error="{{ $errors->first('fulfillment_type') }}" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input id="base_price_minor" name="base_price_minor" type="number" label="Base Price (INR minor units)" value="{{ old('base_price_minor', 0) }}" required="true" error="{{ $errors->first('base_price_minor') }}" />
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Currency</label>
                        <input type="text" value="INR" disabled class="block w-full rounded-xl border border-neutral-300 px-4 py-2 text-xs text-neutral-500 bg-neutral-50 cursor-not-allowed" />
                        <input type="hidden" name="currency" value="INR" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-form.input id="min_order_quantity" name="min_order_quantity" type="number" label="Min Order Qty" value="{{ old('min_order_quantity', 1) }}" required="true" error="{{ $errors->first('min_order_quantity') }}" />
                    <x-form.input id="max_order_quantity" name="max_order_quantity" type="number" label="Max Order Qty" value="{{ old('max_order_quantity') }}" error="{{ $errors->first('max_order_quantity') }}" />
                    <x-form.input id="bulk_threshold_quantity" name="bulk_threshold_quantity" type="number" label="Bulk Threshold Qty" value="{{ old('bulk_threshold_quantity') }}" error="{{ $errors->first('bulk_threshold_quantity') }}" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.wrapper id="direct_checkout_enabled" label="Direct Checkout" :error="$errors->first('direct_checkout_enabled')">
                        <div class="flex items-center gap-2 mt-1">
                            <input type="checkbox" id="direct_checkout_enabled" name="direct_checkout_enabled" value="1" @if(old('direct_checkout_enabled')) checked @endif class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-pointer" />
                            <label for="direct_checkout_enabled" class="text-xs text-neutral-600 select-none">Enable direct checkout</label>
                        </div>
                    </x-form.wrapper>

                    <x-form.wrapper id="quote_enabled" label="Quote Request" :error="$errors->first('quote_enabled')">
                        <div class="flex items-center gap-2 mt-1">
                            <input type="checkbox" id="quote_enabled" name="quote_enabled" value="1" @if(old('quote_enabled', true)) checked @endif class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-pointer" />
                            <label for="quote_enabled" class="text-xs text-neutral-600 select-none">Allow quote requests</label>
                        </div>
                    </x-form.wrapper>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3">Publishing</h3>

                <x-form.select id="status" name="status" label="Status" value="{{ old('status', \App\Models\Product::STATUS_DRAFT) }}" :options="$statusOptions" error="{{ $errors->first('status') }}" />
                <x-form.select id="visibility" name="visibility" label="Visibility" value="{{ old('visibility', \App\Models\Product::VISIBILITY_PRIVATE) }}" :options="$visibilityOptions" error="{{ $errors->first('visibility') }}" />
                <x-form.input id="sort_order" name="sort_order" type="number" label="Sort Order" value="{{ old('sort_order', 0) }}" required="true" error="{{ $errors->first('sort_order') }}" />
                <x-form.input id="published_at" name="published_at" type="datetime-local" label="Published At" value="{{ old('published_at') }}" error="{{ $errors->first('published_at') }}" />
            </div>

            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs space-y-4">
                <h3 class="font-bold text-neutral-800 text-sm border-b border-neutral-100 pb-3">SEO</h3>

                <x-form.input id="seo_title" name="seo_title" label="SEO Title" value="{{ old('seo_title') }}" error="{{ $errors->first('seo_title') }}" />
                <x-form.wrapper id="seo_description" label="SEO Description" :error="$errors->first('seo_description')">
                    <textarea id="seo_description" name="seo_description" rows="3" class="block w-full border border-neutral-300 rounded-xl px-4 py-2 text-xs text-neutral-800 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">{{ old('seo_description') }}</textarea>
                </x-form.wrapper>
            </div>

            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-3 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-xl hover:bg-[color:var(--color-brand-700)] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]">
                Create Product
            </button>
        </div>
    </form>
</x-layouts.admin>
