<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Component Playground</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[color:var(--color-background)] p-8 text-[color:var(--color-text-primary)]">
    <div class="max-w-2xl mx-auto space-y-12">
        <h1 class="text-2xl font-bold">Select Component Testing</h1>

        @php
            $options = [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'cancelled', 'label' => 'Cancelled with a very long label that should truncate ideally or just expand the box depending on browser'],
            ];
        @endphp

        <!-- 1. Standard / Empty options (just placeholder) -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">1. Empty Options (Placeholder only)</h2>
            <x-form.select id="test1" label="Select Status" placeholder="Choose a status..." />
        </section>

        <!-- 2. Standard with Options -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">2. Standard with Options</h2>
            <x-form.select id="test2" label="Select Status" :options="$options" placeholder="Choose a status..." />
        </section>

        <!-- 3. Pre-selected Value -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">3. Pre-selected Value</h2>
            <x-form.select id="test3" label="Select Status" :options="$options" value="pending" />
        </section>

        <!-- 4. Required (Should not show placeholder if selected, but if null, placeholder should be an option) -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">4. Required</h2>
            <form action="#" method="GET">
                <x-form.select id="test4" name="status" label="Select Status" :options="$options" required placeholder="Must select..." />
                <button type="submit" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded">Submit to test native validation</button>
            </form>
        </section>

        <!-- 5. Disabled -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">5. Disabled</h2>
            <x-form.select id="test5" label="Select Status" :options="$options" disabled value="draft" />
        </section>

        <!-- 6. Error State -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">6. Error State</h2>
            <x-form.select id="test6" label="Select Status" :options="$options" error="This field is invalid." />
        </section>

        <!-- 7. Hint State -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">7. Hint State</h2>
            <x-form.select id="test7" label="Select Status" :options="$options" hint="Please select the current status of the order." />
        </section>

        <!-- 8. Wire:Model and Custom Attributes -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">8. Attribute Forwarding (wire:model)</h2>
            <x-form.select id="test8" label="Livewire Select" :options="$options" wire:model.live="status" data-custom="test" class="shadow-lg" />
        </section>
    </div>
</body>
</html>
