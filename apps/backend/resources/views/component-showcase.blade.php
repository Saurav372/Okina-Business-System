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
        <h1 class="text-2xl font-bold mt-16 pt-8 border-t">DataTable Component Testing</h1>

        <!-- 1. Fully Populated Table -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">1. Standard Populated Table (Sortable & Aligned)</h2>
            <x-table>
                <x-table.head>
                    <x-table.row>
                        <x-table.heading sortable direction="asc">Invoice ID</x-table.heading>
                        <x-table.heading sortable direction="desc">Client Name</x-table.heading>
                        <x-table.heading sortable>Status</x-table.heading>
                        <x-table.heading>Description (Long text)</x-table.heading>
                        <x-table.heading align="right">Amount</x-table.heading>
                        <x-table.heading align="center">Actions</x-table.heading>
                    </x-table.row>
                </x-table.head>
                <x-table.body>
                    <x-table.row>
                        <x-table.cell>INV-2026-001</x-table.cell>
                        <x-table.cell>Acme Corp</x-table.cell>
                        <x-table.cell>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Paid</span>
                        </x-table.cell>
                        <x-table.cell class="max-w-xs" wrap>Annual software licensing renewal for enterprise tier. Includes priority support and custom SLA.</x-table.cell>
                        <x-table.cell align="right">$4,500.00</x-table.cell>
                        <x-table.cell align="center">
                            <button type="button" class="text-[color:var(--color-primary)] hover:underline">View</button>
                        </x-table.cell>
                    </x-table.row>
                    <x-table.row>
                        <x-table.cell>INV-2026-002</x-table.cell>
                        <x-table.cell>Global Industries</x-table.cell>
                        <x-table.cell>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">Pending</span>
                        </x-table.cell>
                        <x-table.cell class="max-w-xs" wrap>Q3 Consulting retainer.</x-table.cell>
                        <x-table.cell align="right">$12,000.00</x-table.cell>
                        <x-table.cell align="center">
                            <button type="button" class="text-[color:var(--color-primary)] hover:underline">View</button>
                        </x-table.cell>
                    </x-table.row>
                    <x-table.row>
                        <x-table.cell>INV-2026-003</x-table.cell>
                        <x-table.cell>Stark Labs</x-table.cell>
                        <x-table.cell>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Overdue</span>
                        </x-table.cell>
                        <x-table.cell class="max-w-xs" wrap>Hardware deployment.</x-table.cell>
                        <x-table.cell align="right">$850.50</x-table.cell>
                        <x-table.cell align="center">
                            <button type="button" class="text-[color:var(--color-primary)] hover:underline">View</button>
                        </x-table.cell>
                    </x-table.row>
                </x-table.body>
            </x-table>
        </section>

        <!-- 2. Single Row Table -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">2. Single Row Table</h2>
            <x-table>
                <x-table.head>
                    <x-table.row>
                        <x-table.heading>User ID</x-table.heading>
                        <x-table.heading>Email</x-table.heading>
                        <x-table.heading>Role</x-table.heading>
                    </x-table.row>
                </x-table.head>
                <x-table.body>
                    <x-table.row>
                        <x-table.cell>USR-1</x-table.cell>
                        <x-table.cell>admin@okina.local</x-table.cell>
                        <x-table.cell>Administrator</x-table.cell>
                    </x-table.row>
                </x-table.body>
            </x-table>
        </section>

        <!-- 3. Empty Table States -->
        <section class="space-y-4">
            <h2 class="text-xl font-semibold border-b pb-2">3. Empty Table States</h2>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                <!-- Default empty state -->
                <div class="space-y-2">
                    <h3 class="text-sm font-medium text-[color:var(--color-text-muted)]">Default Empty State</h3>
                    <x-table>
                        <x-table.head>
                            <x-table.row>
                                <x-table.heading>ID</x-table.heading>
                                <x-table.heading>Name</x-table.heading>
                            </x-table.row>
                        </x-table.head>
                        <x-table.body>
                            <x-table.empty colspan="2" />
                        </x-table.body>
                    </x-table>
                </div>

                <!-- Custom empty state -->
                <div class="space-y-2">
                    <h3 class="text-sm font-medium text-[color:var(--color-text-muted)]">Custom Empty Message</h3>
                    <x-table>
                        <x-table.head>
                            <x-table.row>
                                <x-table.heading>SKU</x-table.heading>
                                <x-table.heading>Product</x-table.heading>
                            </x-table.row>
                        </x-table.head>
                        <x-table.body>
                            <x-table.empty colspan="2" message="No products found matching your search." />
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
            <h2 class="text-xl font-semibold border-b pb-2">1. Standard Timeline with All Statuses</h2>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <x-timeline as="ol">
                    <!-- Default Status -->
                    <x-timeline.item title="Order Placed" timestamp="Just now" datetime="2026-07-04T12:00:00Z">
                        Order #12345 has been placed by the customer.
                    </x-timeline.item>

                    <!-- Info Status with custom badge -->
                    <x-timeline.item status="info" title="Payment Processing" timestamp="2 minutes ago">
                        <x-slot:badge>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Processing</span>
                        </x-slot:badge>
                        Payment is currently being processed via Stripe.
                    </x-timeline.item>

                    <!-- Success Status with Icon -->
                    <x-timeline.item status="success" title="Payment Confirmed" timestamp="Jul 4, 2026 12:05 PM">
                        <x-slot:icon>
                            <svg class="w-4 h-4 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </x-slot:icon>
                        Payment of $150.00 was successfully captured.
                    </x-timeline.item>

                    <!-- Warning Status -->
                    <x-timeline.item status="warning" title="Stock Warning" timestamp="Jul 4, 2026 12:15 PM" lineStyle="dashed">
                        Only 2 items left in stock for SKU-992.
                    </x-timeline.item>

                    <!-- Danger Status (Multiline Content) -->
                    <x-timeline.item status="danger" title="Shipping Delayed">
                        <p class="mb-2">There was an issue with the shipping provider.</p>
                        <p>Expected delay: 2-3 business days. Please contact the customer to notify them of this change in schedule.</p>
                    </x-timeline.item>
                </x-timeline>
            </div>
        </section>

    </div>
</body>
</html>
