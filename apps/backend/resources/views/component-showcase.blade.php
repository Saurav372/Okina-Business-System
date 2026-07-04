<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Component Playground</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gradient-to-br from-[color:var(--color-background)] to-[color:var(--color-primary-50)] min-h-screen p-8 text-[color:var(--color-text-primary)]">
    <div class="max-w-6xl mx-auto space-y-12">
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

        <!-- SEARCH COMPONENT TESTS -->
        <h1 class="text-2xl font-bold mt-16 pt-8 border-t">Search Component Testing</h1>

        <!-- 1. Standard Search -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">1. Standard Search</h2>
            <x-form.search id="search1" label="Search Products" />
        </section>

        <!-- 2. Pre-filled / Long String Search -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">2. Pre-filled / Long String</h2>
            <x-form.search id="search2" label="Search Products" value="A very long search query that should eventually trigger horizontal scrolling in the input field" />
        </section>

        <!-- 3. Livewire Forwarding -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">3. Livewire Forwarding</h2>
            <x-form.search id="search3" label="Search Products" wire:model.live.debounce.300ms="searchQuery" />
        </section>

        <!-- 4. Error State -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">4. Error State</h2>
            <x-form.search id="search4" label="Search Products" error="Invalid search term provided." />
        </section>

        <!-- 5. Hint State -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">5. Hint State</h2>
            <x-form.search id="search5" label="Search Products" hint="Enter SKU, product name, or barcode." />
        </section>

        <!-- 6. Disabled State -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">6. Disabled State</h2>
            <x-form.search id="search6" label="Search Products" disabled value="Unable to search" />
        </section>

        <!-- DATE COMPONENT TESTS -->
        <h1 class="text-2xl font-bold mt-16 pt-8 border-t">Date Component Testing</h1>

        <!-- 1. Standard Empty Date -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">1. Standard Empty Date</h2>
            <x-form.date id="date1" label="Invoice Date" />
        </section>

        <!-- 2. Prefilled ISO Date -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">2. Prefilled ISO Date (YYYY-MM-DD)</h2>
            <x-form.date id="date2" label="Delivery Date" value="2026-07-03" />
        </section>

        <!-- 3. Min and Max Limits -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">3. Min, Max, and Step Attributes</h2>
            <x-form.date id="date3" label="Select a date in 2026 (Every 7 days)" min="2026-01-01" max="2026-12-31" step="7" />
        </section>

        <!-- 4. Required state -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">4. Required State</h2>
            <x-form.date id="date4" label="Date of Birth" required />
        </section>
        
        <!-- 5. Error state -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">5. Error State</h2>
            <x-form.date id="date5" label="Expiration Date" error="Date must be in the future." />
        </section>
        
        <!-- 6. Hint state -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">6. Hint State</h2>
            <x-form.date id="date6" label="Start Date" hint="The first day of the billing cycle." />
        </section>

        <!-- 7. Disabled / Readonly states -->
        <section class="space-y-4 flex gap-8">
            <div class="flex-1 space-y-4">
                <h2 class="text-xl font-semibold border-b pb-2">7a. Disabled</h2>
                <x-form.date id="date7a" label="Created At" value="2024-01-01" disabled />
            </div>
            <div class="flex-1 space-y-4">
                <h2 class="text-xl font-semibold border-b pb-2">7b. Readonly</h2>
                <x-form.date id="date7b" label="Updated At" value="2024-01-02" readonly />
            </div>
        </section>

        <!-- FILE UPLOAD COMPONENT TESTS -->
        <h1 class="text-2xl font-bold mt-16 pt-8 border-t">File Upload Component Testing</h1>

        <!-- 1. Standard Single File -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">1. Standard Single File</h2>
            <x-form.file id="file1" label="Upload Document" />
        </section>

        <!-- 2. Multiple Files -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">2. Multiple Files</h2>
            <x-form.file id="file2" label="Upload Attachments" multiple />
        </section>

        <!-- 3. Accept Images Only -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">3. Accept Specific Types (Images)</h2>
            <x-form.file id="file3" label="Profile Picture" accept="image/*" />
        </section>
        
        <!-- 4. Accept PDFs Only -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">4. Accept Specific Types (PDF)</h2>
            <x-form.file id="file4" label="Upload Resume" accept=".pdf" />
        </section>

        <!-- 5. Required & Error State -->
        <section class="space-y-4 flex gap-8">
            <div class="flex-1 space-y-4">
                <h2 class="text-xl font-semibold border-b pb-2">5a. Required State</h2>
                <x-form.file id="file5a" label="Verification Document" required />
            </div>
            <div class="flex-1 space-y-4">
                <h2 class="text-xl font-semibold border-b pb-2">5b. Error State</h2>
                <x-form.file id="file5b" label="Tax Form" error="File size exceeds 5MB limit." />
            </div>
        </section>

        <!-- 6. Hint & Disabled State -->
        <section class="space-y-4 flex gap-8">
            <div class="flex-1 space-y-4">
                <h2 class="text-xl font-semibold border-b pb-2">6a. Hint State</h2>
                <x-form.file id="file6a" label="Cover Letter" hint="Must be a PDF or DOCX file." />
            </div>
            <div class="flex-1 space-y-4">
                <h2 class="text-xl font-semibold border-b pb-2">6b. Disabled State</h2>
                <x-form.file id="file6b" label="Archived Contract" disabled />
            </div>
        </section>

        <!-- 7. Filename Edge Cases Test -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">7. Filename Edge Cases (Manual Test)</h2>
            <p class="text-sm text-[color:var(--color-text-muted)] mb-2">
                Upload files with the following characteristics to verify layout stability:
                <ul class="list-disc pl-5 text-sm text-[color:var(--color-text-muted)]">
                    <li>Very long filename</li>
                    <li>Filename containing spaces (e.g. <code>Purchase Order - Final.pdf</code>)</li>
                    <li>Filename containing Unicode characters (e.g. <code>报价单_最终版本.pdf</code>)</li>
                    <li>Filename with a very long extension</li>
                </ul>
            </p>
            <x-form.file id="file7" label="Edge Case Test" />
        </section>

        <!-- DATATABLE COMPONENT TESTS -->
        <h1 class="text-[20px] font-semibold mt-16 pt-8 border-t">DataTable Component Testing</h1>
        <p class="text-[15px] leading-[24px] text-[color:var(--color-text-muted)] mt-2">Examples of all DataTable states and variations</p>

        <!-- 1. Fully Populated Table with Toolbar & Footer -->
        <section class="space-y-4 mt-8">
            <h2 class="text-lg font-semibold border-b pb-2">Standard Table</h2>
            <x-table>
                <x-slot:toolbar>
                    <!-- Left Side: Search & Filters -->
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <x-icons.search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input type="text" placeholder="Search invoices..." class="pl-9 pr-8 py-[7px] text-[15px] border border-[color:var(--color-border)] rounded-[var(--radius-md)] bg-white w-64 focus:ring-[length:var(--focus-ring-width)] focus:ring-[color:var(--color-primary-300)] focus:border-[color:var(--color-primary-400)] focus:outline-none placeholder:text-slate-500" />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-medium text-gray-400 border border-gray-200 rounded px-1.5 bg-gray-50">/</span>
                        </div>
                        
                        <button type="button" class="inline-flex items-center gap-2 px-3 py-[7px] text-sm font-medium text-[color:var(--color-text-secondary)] bg-white border border-[color:var(--color-border)] rounded-[var(--radius-md)] hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            Status
                            <x-icons.chevron-down class="w-4 h-4 text-gray-400" />
                        </button>

                        <button type="button" class="inline-flex items-center gap-2 px-3 py-[7px] text-sm font-medium text-[color:var(--color-text-secondary)] bg-white border border-[color:var(--color-border)] rounded-[var(--radius-md)] hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Date range
                            <x-icons.chevron-down class="w-4 h-4 text-gray-400" />
                        </button>

                        <button type="button" class="inline-flex items-center gap-2 px-3 py-[7px] text-sm font-medium text-[color:var(--color-text-secondary)] bg-white border border-[color:var(--color-border)] rounded-[var(--radius-md)] hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Columns
                        </button>
                    </div>

                    <!-- Right Side: Actions -->
                    <div class="flex items-center gap-3">
                        <button type="button" class="inline-flex items-center gap-2 px-3 py-[7px] text-sm font-medium text-[color:var(--color-text-secondary)] bg-white border border-[color:var(--color-border)] rounded-[var(--radius-md)] hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            Export
                        </button>
                        <button type="button" class="inline-flex items-center gap-2 px-4 py-[7px] text-sm font-medium text-white bg-[color:var(--color-primary-600)] border border-transparent rounded-[var(--radius-md)] hover:bg-[color:var(--color-primary-700)] transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            New Invoice
                        </button>
                    </div>
                </x-slot:toolbar>

                <x-table.head class="bg-[#f8fafc]">
                    <x-table.row>
                        <x-table.heading class="w-[140px]" sortable direction="asc">Invoice ID</x-table.heading>
                        <x-table.heading class="w-[200px]" sortable direction="desc">Client Name</x-table.heading>
                        <x-table.heading class="w-[140px]" sortable>Status</x-table.heading>
                        <x-table.heading class="w-auto">Description (Long text)</x-table.heading>
                        <x-table.heading class="w-[140px]" align="right">Amount</x-table.heading>
                        <x-table.heading class="w-[60px]" align="center">Actions</x-table.heading>
                    </x-table.row>
                </x-table.head>
                <x-table.body>
                    <x-table.row>
                        <x-table.cell>
                            <a href="#" class="text-blue-600 hover:underline font-medium">INV-2026-001</a>
                        </x-table.cell>
                        <x-table.cell>Acme Corp</x-table.cell>
                        <x-table.cell>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 tracking-normal">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                Paid
                            </span>
                        </x-table.cell>
                        <x-table.cell wrap>
                            <div class="leading-[1.6]">
                                Annual software licensing renewal for enterprise tier.
                                <div class="text-slate-500 mt-1">Includes priority support and custom SLA.</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell align="right" class="font-semibold tabular-nums">$4,500.00</x-table.cell>
                        <x-table.cell align="center">
                            <button type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-md hover:bg-slate-100 text-[color:var(--color-text-muted)] transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                            </button>
                        </x-table.cell>
                    </x-table.row>
                    <x-table.row>
                        <x-table.cell>
                            <a href="#" class="text-blue-600 hover:underline font-medium">INV-2026-002</a>
                        </x-table.cell>
                        <x-table.cell>Global Industries</x-table.cell>
                        <x-table.cell>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 tracking-normal">
                                <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                                Pending
                            </span>
                        </x-table.cell>
                        <x-table.cell wrap>
                            <div class="leading-[1.6]">
                                Q3 Consulting retainer.
                                <div class="text-slate-500 mt-1">Design system and engineering hours.</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell align="right" class="font-semibold tabular-nums">$12,000.00</x-table.cell>
                        <x-table.cell align="center">
                            <button type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-md hover:bg-slate-100 text-[color:var(--color-text-muted)] transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                            </button>
                        </x-table.cell>
                    </x-table.row>
                    <x-table.row>
                        <x-table.cell>
                            <a href="#" class="text-blue-600 hover:underline font-medium">INV-2026-003</a>
                        </x-table.cell>
                        <x-table.cell>Stark Labs</x-table.cell>
                        <x-table.cell>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 tracking-normal">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                Overdue
                            </span>
                        </x-table.cell>
                        <x-table.cell wrap>
                            <div class="leading-[1.6]">
                                Hardware deployment.
                                <div class="text-slate-500 mt-1">Installation of 50 remote workstations.</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell align="right" class="font-semibold tabular-nums">$850.50</x-table.cell>
                        <x-table.cell align="center">
                            <button type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-md hover:bg-slate-100 text-[color:var(--color-text-muted)] transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                            </button>
                        </x-table.cell>
                    </x-table.row>
                </x-table.body>
                <x-slot:footer>
                    <div class="px-5 py-4 border-t border-[color:var(--color-border)] flex items-center justify-between">
                        <span class="text-sm text-gray-500">Showing 1 to 3 of 3 results</span>
                        <div class="flex items-center gap-1">
                            <button type="button" disabled class="inline-flex items-center justify-center w-8 h-8 rounded-[var(--radius-md)] border border-gray-200 text-gray-400 bg-gray-50 cursor-not-allowed">
                                <x-icons.chevron-down class="w-4 h-4 rotate-90" />
                            </button>
                            <button type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-[var(--radius-md)] border border-blue-200 text-blue-600 bg-blue-50 font-medium text-sm">
                                1
                            </button>
                            <button type="button" disabled class="inline-flex items-center justify-center w-8 h-8 rounded-[var(--radius-md)] border border-gray-200 text-gray-400 bg-gray-50 cursor-not-allowed">
                                <x-icons.chevron-down class="w-4 h-4 -rotate-90" />
                            </button>
                        </div>
                    </div>
                </x-slot:footer>
            </x-table>
        </section>

        <!-- 2. Single Row Table -->
        <section class="space-y-4">
            <h2 class="text-lg font-semibold border-b pb-2">Single Row Table</h2>
            <x-table>
                <x-table.head class="bg-[#f8fafc]">
                    <x-table.row>
                        <x-table.heading>User ID</x-table.heading>
                        <x-table.heading>Email</x-table.heading>
                        <x-table.heading>Role</x-table.heading>
                        <x-table.heading align="center">Actions</x-table.heading>
                    </x-table.row>
                </x-table.head>
                <x-table.body>
                    <x-table.row>
                        <x-table.cell>USR-1</x-table.cell>
                        <x-table.cell>admin@okina.local</x-table.cell>
                        <x-table.cell>Administrator</x-table.cell>
                        <x-table.cell align="center">
                            <button type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-md hover:bg-slate-100 text-[color:var(--color-text-muted)] transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                            </button>
                        </x-table.cell>
                    </x-table.row>
                </x-table.body>
            </x-table>
        </section>

        <!-- 3. Empty Table States -->
        <section class="space-y-4">
            <h2 class="text-lg font-semibold border-b pb-2">Empty States</h2>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                <!-- Default empty state -->
                <div class="space-y-2">
                    <h3 class="text-sm font-medium text-[color:var(--color-text-muted)]">Default Empty State</h3>
                    <x-table>
                        <x-table.body>
                            <x-table.empty colspan="1">
                                <x-slot:icon>
                                    <x-icons.inbox class="w-6 h-6 text-gray-400" />
                                </x-slot:icon>
                                <x-slot:action>
                                    <button type="button" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Refresh
                                    </button>
                                </x-slot:action>
                            </x-table.empty>
                        </x-table.body>
                    </x-table>
                </div>

                <!-- Custom empty state -->
                <div class="space-y-2">
                    <h3 class="text-sm font-medium text-[color:var(--color-text-muted)]">Custom Empty Message</h3>
                    <x-table>
                        <x-table.body>
                            <x-table.empty colspan="1" title="No products found matching your search." description="Try adjusting your search or filter to find what you need.">
                                <x-slot:icon>
                                    <x-icons.search class="w-6 h-6 text-gray-400" />
                                </x-slot:icon>
                                <x-slot:action>
                                    <button type="button" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                        Clear filters
                                    </button>
                                </x-slot:action>
                            </x-table.empty>
                        </x-table.body>
                    </x-table>
                </div>
            </div>
        </section>

        <!-- PAGINATION COMPONENT TESTS -->
        <h1 class="text-2xl font-bold mt-16 pt-8 border-t">Pagination Component Testing</h1>
        
        @php
            $mockFirstPage = new \Illuminate\Pagination\LengthAwarePaginator(collect(range(1, 10)), 582, 10, 1, ['path' => url()->current()]);
            $mockMiddlePage = new \Illuminate\Pagination\LengthAwarePaginator(collect(range(1, 10)), 582, 10, 29, ['path' => url()->current()]);
            $mockLastPage = new \Illuminate\Pagination\LengthAwarePaginator(collect(range(1, 2)), 582, 10, 59, ['path' => url()->current()]);
            $mockZeroResults = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 10, 1, ['path' => url()->current()]);
            $mockSimplePaginator = new \Illuminate\Pagination\Paginator(collect(range(1, 10)), 10, 2, ['path' => url()->current()]);
            $mockSimplePaginator->hasMorePagesWhen(true);
        @endphp

        <section class="space-y-8">
            <!-- 1. First Page -->
            <div class="space-y-2">
                <h2 class="text-xl font-semibold border-b pb-2">1. First Page</h2>
                <x-table.pagination :paginator="$mockFirstPage" />
            </div>

            <!-- 2. Middle Page -->
            <div class="space-y-2">
                <h2 class="text-xl font-semibold border-b pb-2">2. Middle Page</h2>
                <x-table.pagination :paginator="$mockMiddlePage" />
            </div>

            <!-- 3. Last Page -->
            <div class="space-y-2">
                <h2 class="text-xl font-semibold border-b pb-2">3. Last Page</h2>
                <x-table.pagination :paginator="$mockLastPage" />
            </div>

            <!-- 4. Zero Results -->
            <div class="space-y-2">
                <h2 class="text-xl font-semibold border-b pb-2">4. Zero Results</h2>
                <x-table.pagination :paginator="$mockZeroResults" />
            </div>
            
            <!-- 5. Simple Paginator (No total) -->
            <div class="space-y-2">
                <h2 class="text-xl font-semibold border-b pb-2">5. Simple Paginator (Cursor/Paginator)</h2>
                <x-table.pagination :paginator="$mockSimplePaginator" />
            </div>
        </section>

        <!-- TIMELINE COMPONENT TESTS -->
        <h1 class="text-2xl font-bold mt-16 pt-8 border-t">Timeline Component Testing</h1>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold border-b pb-2">Order Timeline Card</h2>
            <div class="flex justify-center bg-gray-50 p-8 rounded-xl border border-gray-100">
                
                <!-- The Order Card -->
                <div class="bg-white rounded-[24px] shadow-sm border border-[color:var(--color-border)] w-full max-w-xl overflow-hidden p-6">
                    
                    <!-- Top Section: Order Info -->
                    <div class="flex gap-5 mb-8 bg-gray-50 rounded-xl p-3">
                        <div class="w-[120px] h-[120px] rounded-xl overflow-hidden flex-shrink-0 relative">
                            <!-- In production: src="{{ $order->product_image }}" -->
                            <img src="{{ $order->image_url ?? 'https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?q=80&w=400&auto=format&fit=crop' }}" alt="Product Image" class="w-full h-full object-cover" />
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                                <span class="text-white font-semibold text-xs tracking-widest uppercase">Okina Craft</span>
                            </div>
                        </div>
                        
                        <div class="flex flex-col justify-center">
                            <div class="mb-2">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    Order Confirmed
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 leading-tight mb-1">Order #ORD-2026-001245</h3>
                            <div class="text-[22px] font-semibold text-gray-900 mb-1 tabular-nums tracking-tight">₹3,850<span class="text-[15px] font-medium text-gray-500">.00</span></div>
                            <p class="text-[13px] text-gray-500">Placed on Jul 4, 2026 • 12:05 PM</p>
                        </div>
                    </div>

                    <!-- Middle Section: Timeline -->
                    <div class="px-2">
                        <x-timeline as="ol">
                            <!-- Success Status -->
                            <x-timeline.item status="success" title="Order Placed" timestamp="Jul 4, 2026 • 12:05 PM">
                                <x-slot:icon>
                                    <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                </x-slot:icon>
                                Your order has been successfully placed.
                            </x-timeline.item>

                            <!-- Info Status -->
                            <x-timeline.item status="info" title="Payment Processing" timestamp="Jul 4, 2026 • 12:07 PM">
                                <x-slot:icon>
                                    <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </x-slot:icon>
                                <x-slot:badge>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-blue-100 text-blue-700">Processing</span>
                                </x-slot:badge>
                                We are processing your payment via Razorpay.
                            </x-timeline.item>

                            <!-- Success Status -->
                            <x-timeline.item status="success" title="Payment Confirmed" timestamp="Jul 4, 2026 • 12:09 PM">
                                <x-slot:icon>
                                    <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                </x-slot:icon>
                                Payment of ₹3,850.00 was successfully captured.
                            </x-timeline.item>

                            <!-- Warning Status -->
                            <x-timeline.item status="warning" title="Stock Warning" timestamp="Jul 4, 2026 • 12:15 PM" lineStyle="dashed">
                                <x-slot:icon>
                                    <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01"></path></svg>
                                </x-slot:icon>
                                Only 2 items left in stock for "Ceramic Coffee Mug - Black".
                            </x-timeline.item>

                            <!-- Danger Status -->
                            <x-timeline.item status="danger" title="Shipping Delayed" timestamp="Jul 4, 2026 • 12:25 PM" lineStyle="dashed">
                                <x-slot:icon>
                                    <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8n-2-2h-2m-4-14H9m12 0h.01"></path><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </x-slot:icon>
                                <p class="mb-1">There is a delay due to high courier volume.</p>
                                <p>Expected delivery: 2-3 business days.</p>
                            </x-timeline.item>
                        </x-timeline>
                    </div>

                    <!-- Bottom Section: Action -->
                    <div class="mt-6">
                        <button type="button" class="w-full flex items-center justify-center gap-2 py-3 px-4 border-2 border-blue-100 text-blue-600 rounded-xl hover:bg-blue-50 font-semibold transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            View Order Details
                        </button>
                    </div>

                </div>
            </div>
        </section>

    </div>
</body>
</html>
