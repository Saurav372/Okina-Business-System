<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design System Playground</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 2rem;
        }
    </style>
</head>
<body class="antialiased bg-[color:var(--color-background)] min-h-screen text-[color:var(--color-text-primary)]">
    
    @php
        $categories = config('ui-showcase.categories', []);
    @endphp

    <div class="flex flex-col lg:flex-row min-h-screen">
        
        <!-- Sidebar Navigation -->
        <aside class="lg:w-64 shrink-0 border-r border-[color:var(--color-border)] bg-white lg:sticky lg:top-0 lg:h-screen overflow-y-auto z-10 hidden lg:block">
            <div class="p-6">
                <h1 class="text-xl font-bold text-[color:var(--color-primary-600)] flex items-center gap-2">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                    Design System
                </h1>
                
                <nav class="mt-8 space-y-8 js-sidebar-nav">
                    @foreach($categories as $categoryName => $components)
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-[color:var(--color-neutral-400)] mb-3">{{ $categoryName }}</h2>
                            <ul class="space-y-1">
                                @foreach($components as $label => $id)
                                    <li>
                                        <a href="#{{ $id }}" class="js-nav-link block px-3 py-2 -mx-3 rounded-lg text-sm font-medium text-[color:var(--color-neutral-600)] hover:text-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-50)] transition-colors" data-target="{{ $id }}">
                                            {{ $label }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6 lg:p-12 xl:p-16 overflow-hidden">
            <div class="max-w-5xl mx-auto">
                <header class="mb-12">
                    <h1 class="text-3xl font-bold tracking-tight">Component Showcase</h1>
                    <p class="mt-2 text-[color:var(--color-neutral-500)] text-lg">Interactive playground and documentation for UI components.</p>
                </header>

                <div class="space-y-24">
                    
                    {{-- 1. Forms --}}
                    @if(isset($categories['Forms']))
                        <div class="space-y-16">
                            
                            {{-- Select --}}
                            @if(isset($categories['Forms']['Select']))
                                @php
                                    $selectCode = <<<'HTML'
<x-form.select 
    id="status" 
    label="Select Status" 
    :options="[
        ['value' => 'draft', 'label' => 'Draft'],
        ['value' => 'pending', 'label' => 'Pending'],
        ['value' => 'completed', 'label' => 'Completed']
    ]" 
    placeholder="Choose a status..." 
/>
HTML;
                                @endphp
                                <section id="{{ $categories['Forms']['Select'] }}" class="js-section scroll-mt-8">
                                    <h2 class="text-2xl font-bold border-b border-[color:var(--color-border)] pb-4 mb-8">Select Input</h2>
                                    
                                    <x-showcase.preview title="Standard Select with Options" :code="$selectCode" id="preview-select">
                                        <div class="w-full max-w-sm">
                                            <x-form.select id="test-select" label="Select Status" :options="[
                                                ['value' => 'draft', 'label' => 'Draft'],
                                                ['value' => 'pending', 'label' => 'Pending'],
                                                ['value' => 'completed', 'label' => 'Completed']
                                            ]" placeholder="Choose a status..." />
                                        </div>
                                    </x-showcase.preview>
                                </section>
                            @endif

                        </div>
                    @endif

                    {{-- 2. Data Display --}}
                    @if(isset($categories['Data Display']))
                        <div class="space-y-16">
                            
                            {{-- Timeline --}}
                            @if(isset($categories['Data Display']['Timeline']))
                                @php
                                    $timelineCode = <<<'HTML'
<x-timeline>
    <x-timeline.item status="success" title="Order Placed" timestamp="Jul 4, 2026 • 10:30 AM">
        <x-slot:icon>
            <x-heroicon-o-check class="w-5 h-5 text-current" stroke-width="2.5" />
        </x-slot:icon>
        Order #890-441-2 was placed by the customer.
    </x-timeline.item>
    <x-timeline.item status="primary" title="Payment Confirmed" timestamp="Jul 4, 2026 • 10:35 AM" />
</x-timeline>
HTML;
                                @endphp
                                <section id="{{ $categories['Data Display']['Timeline'] }}" class="js-section scroll-mt-8">
                                    <h2 class="text-2xl font-bold border-b border-[color:var(--color-border)] pb-4 mb-8">Timeline</h2>
                                    
                                    <x-showcase.preview title="Vertical Tracking Timeline" :code="$timelineCode" id="preview-timeline" defaultViewport="tablet">
                                        <div class="w-full max-w-lg">
                                            <x-timeline>
                                                <x-timeline.item status="success" title="Order Placed" timestamp="Jul 4, 2026 • 10:30 AM">
                                                    <x-slot:icon>
                                                        <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                                    </x-slot:icon>
                                                    Order #890-441-2 was placed by the customer.
                                                </x-timeline.item>

                                                <x-timeline.item status="primary" title="Payment Confirmed" timestamp="Jul 4, 2026 • 10:35 AM">
                                                    <x-slot:icon>
                                                        <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                                    </x-slot:icon>
                                                    Payment of ₹3,850.00 was successfully captured.
                                                </x-timeline.item>

                                                <x-timeline.item status="warning" title="Stock Warning" timestamp="Jul 4, 2026 • 12:15 PM" lineStyle="dashed">
                                                    <x-slot:icon>
                                                        <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    </x-slot:icon>
                                                    Only 2 items left in stock for "Ceramic Coffee Mug - Black".
                                                </x-timeline.item>
                                            </x-timeline>
                                        </div>
                                    </x-showcase.preview>
                                </section>
                            @endif

                            {{-- Stats Grid --}}
                            @if(isset($categories['Data Display']['Stats Grid']))
                                @php
                                    $statsGridCode = <<<'HTML'
<x-stat.grid>
    <x-stat.card 
        label="Total Revenue" 
        value="₹84,500" 
        trend="18%" 
        trendDirection="up"
        description="vs last month"
    >
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </x-slot:icon>
    </x-stat.card>
</x-stat.grid>
HTML;
                                @endphp
                                <section id="{{ $categories['Data Display']['Stats Grid'] }}" class="js-section scroll-mt-8">
                                    <h2 class="text-2xl font-bold border-b border-[color:var(--color-border)] pb-4 mb-8">Stats Grid</h2>
                                    
                                    <x-showcase.preview title="Responsive KPI Metrics" :code="$statsGridCode" id="preview-stats-grid">
                                        <x-stat.grid>
                                            <x-stat.card label="Total Revenue" value="₹84,500" trend="18%" trendDirection="up" description="vs last month">
                                                <x-slot:icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></x-slot:icon>
                                            </x-stat.card>
                                            <x-stat.card label="Bounce Rate" value="42.3%" trend="4.1%" trendDirection="down" description="vs last week">
                                                <x-slot:icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></x-slot:icon>
                                            </x-stat.card>
                                            <x-stat.card label="Active Subscriptions" value="1,204" trend="0%" trendDirection="neutral" description="vs last month">
                                                <x-slot:icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg></x-slot:icon>
                                            </x-stat.card>
                                            <x-stat.card label="Pending Tickets" value="14" trend="3" trendDirection="down" description="Action required" href="#/admin/tickets">
                                                <x-slot:icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg></x-slot:icon>
                                            </x-stat.card>
                                        </x-stat.grid>
                                    </x-showcase.preview>
                                </section>
                            @endif

                            {{-- Empty State --}}
                            @if(isset($categories['Data Display']['Empty State']))
                                @php
                                    $emptyStateCodePage = <<<'HTML'
<div class="p-8 bg-[color:var(--color-surface-primary)] rounded-lg shadow-sm">
    <x-empty-state 
        title="No users found" 
        description="Get started by creating a new user or inviting your team members."
        size="lg"
    >
        <x-slot:icon>
            <svg class="w-10 h-10 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </x-slot:icon>
        <x-slot:actions>
            <button class="px-4 py-2 bg-[color:var(--color-primary-600)] text-white rounded-md font-medium text-sm">Add User</button>
            <button class="px-4 py-2 bg-transparent text-[color:var(--color-text-primary)] border border-[color:var(--color-border)] rounded-md font-medium text-sm">Learn More</button>
        </x-slot:actions>
    </x-empty-state>
</div>
HTML;

                                    $emptyStateCodeSearch = <<<'HTML'
<div class="p-4 bg-[color:var(--color-surface-primary)] rounded-lg border border-[color:var(--color-border)]">
    <x-empty-state 
        title="No results found for 'query'" 
        description="We couldn't find anything matching your search. Please try a different term."
        size="md"
    >
        <x-slot:icon>
            <svg class="w-6 h-6 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
        </x-slot:icon>
        <x-slot:actions>
            <button class="text-sm font-medium text-[color:var(--color-primary-600)] hover:underline">Clear Search</button>
        </x-slot:actions>
    </x-empty-state>
</div>
HTML;

                                    $emptyStateCodeTable = <<<'HTML'
<table class="w-full text-left text-sm whitespace-nowrap">
    <thead class="uppercase tracking-wider border-b border-[color:var(--color-border)] bg-[color:var(--color-surface-secondary)] text-[color:var(--color-text-muted)] text-xs">
        <tr>
            <th scope="col" class="px-6 py-4 font-medium">Invoice</th>
            <th scope="col" class="px-6 py-4 font-medium">Amount</th>
            <th scope="col" class="px-6 py-4 font-medium">Status</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-[color:var(--color-border)] bg-[color:var(--color-surface-primary)]">
        <x-table.empty 
            colspan="3" 
            title="No pending invoices" 
            description="You're all caught up! There are no unpaid invoices."
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </x-slot:icon>
            <x-slot:actions>
                <button class="text-[13px] font-medium text-[color:var(--color-primary-600)] hover:underline">Create Invoice</button>
            </x-slot:actions>
        </x-table.empty>
    </tbody>
</table>
HTML;
                                @endphp
                                <section id="{{ $categories['Data Display']['Empty State'] }}" class="js-section scroll-mt-8">
                                    <h2 class="text-2xl font-bold border-b border-[color:var(--color-border)] pb-4 mb-8">Empty State</h2>
                                    
                                    <div class="space-y-8">
                                        <x-showcase.preview title="Standalone Page (Large)" :code="$emptyStateCodePage" id="preview-empty-state-lg" defaultViewport="desktop">
                                            <div class="p-8 bg-[color:var(--color-surface-primary)] rounded-lg shadow-sm border border-[color:var(--color-border)] w-full">
                                                <x-empty-state 
                                                    title="No users found" 
                                                    description="Get started by creating a new user or inviting your team members to collaborate."
                                                    size="lg"
                                                >
                                                    <x-slot:icon>
                                                        <svg class="w-10 h-10 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                        </svg>
                                                    </x-slot:icon>
                                                    <x-slot:actions>
                                                        <button class="px-4 py-2 bg-[color:var(--color-primary-600)] text-white rounded-md font-medium text-sm">Add User</button>
                                                        <button class="px-4 py-2 bg-transparent text-[color:var(--color-text-primary)] border border-[color:var(--color-border)] rounded-md font-medium text-sm">Learn More</button>
                                                    </x-slot:actions>
                                                </x-empty-state>
                                            </div>
                                        </x-showcase.preview>

                                        <x-showcase.preview title="Search Results (Medium)" :code="$emptyStateCodeSearch" id="preview-empty-state-md" defaultViewport="tablet">
                                            <div class="p-4 bg-[color:var(--color-surface-primary)] rounded-lg border border-[color:var(--color-border)] w-full">
                                                <x-empty-state 
                                                    title="No results found for 'query'" 
                                                    description="We couldn't find anything matching your search. Please try a different term."
                                                    size="md"
                                                >
                                                    <x-slot:icon>
                                                        <svg class="w-6 h-6 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                                        </svg>
                                                    </x-slot:icon>
                                                    <x-slot:actions>
                                                        <button class="text-sm font-medium text-[color:var(--color-primary-600)] hover:underline">Clear Search</button>
                                                    </x-slot:actions>
                                                </x-empty-state>
                                            </div>
                                        </x-showcase.preview>

                                        <x-showcase.preview title="Table Layout (Small)" :code="$emptyStateCodeTable" id="preview-empty-state-sm" defaultViewport="desktop">
                                            <div class="overflow-x-auto rounded-lg border border-[color:var(--color-border)] w-full bg-[color:var(--color-surface-primary)]">
                                                <table class="w-full text-left text-sm whitespace-nowrap">
                                                    <thead class="uppercase tracking-wider border-b border-[color:var(--color-border)] bg-[color:var(--color-surface-secondary)] text-[color:var(--color-text-muted)] text-xs">
                                                        <tr>
                                                            <th scope="col" class="px-6 py-4 font-medium">Invoice</th>
                                                            <th scope="col" class="px-6 py-4 font-medium">Amount</th>
                                                            <th scope="col" class="px-6 py-4 font-medium">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-[color:var(--color-border)]">
                                                        <x-table.empty 
                                                            colspan="3" 
                                                            title="No pending invoices" 
                                                            description="You're all caught up! There are no unpaid invoices."
                                                        >
                                                            <x-slot:icon>
                                                                <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                </svg>
                                                            </x-slot:icon>
                                                            <x-slot:actions>
                                                                <button class="text-[13px] font-medium text-[color:var(--color-primary-600)] hover:underline">Create Invoice</button>
                                                            </x-slot:actions>
                                                        </x-table.empty>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </x-showcase.preview>
                                        
                                        @php
                                            $emptyStateCodeUpload = <<<'HTML'
<div class="p-8 border-2 border-dashed border-[color:var(--color-border)] bg-[color:var(--color-surface-secondary)] rounded-xl w-full">
    <x-empty-state 
        title="Upload a file" 
        description="Drag and drop your files here or click to browse."
        size="md"
    />
</div>
HTML;
                                        @endphp
                                        <x-showcase.preview title="Upload Placeholder (Fallback Icon)" :code="$emptyStateCodeUpload" id="preview-empty-state-upload" defaultViewport="tablet">
                                            <div class="p-8 border-2 border-dashed border-[color:var(--color-border)] bg-[color:var(--color-surface-secondary)] rounded-xl w-full">
                                                <x-empty-state 
                                                    title="Upload a file" 
                                                    description="Drag and drop your files here or click to browse."
                                                    size="md"
                                                />
                                            </div>
                                        </x-showcase.preview>
                                    </div>
                                </section>
                            @endif

                        </div>
                    @endif

                    {{-- 3. Navigation --}}
                    @if(isset($categories['Navigation']))
                        <div class="space-y-16">
                            
                            {{-- Tabs --}}
                            @if(isset($categories['Navigation']['Tabs']))
                                @php
                                    $tabsCode = <<<'HTML'
<x-tabs defaultTab="account">
    <x-tabs.list>
        <x-tabs.trigger value="account">Account</x-tabs.trigger>
        <x-tabs.trigger value="password">Password</x-tabs.trigger>
        <x-tabs.trigger value="notifications">Notifications</x-tabs.trigger>
        <x-tabs.trigger value="billing" disabled>Billing</x-tabs.trigger>
    </x-tabs.list>
    
    <div class="p-4 bg-[color:var(--color-surface-primary)] border border-t-0 border-[color:var(--color-border)] rounded-b-md">
        <x-tabs.content value="account">
            <h3 class="text-lg font-medium">Account Settings</h3>
            <p class="text-sm text-[color:var(--color-text-muted)] mt-1">Update your account details here.</p>
        </x-tabs.content>
        <x-tabs.content value="password">
            <h3 class="text-lg font-medium">Change Password</h3>
            <p class="text-sm text-[color:var(--color-text-muted)] mt-1">Ensure your account is using a long, random password.</p>
        </x-tabs.content>
        <x-tabs.content value="notifications">
            <h3 class="text-lg font-medium">Notification Preferences</h3>
            <p class="text-sm text-[color:var(--color-text-muted)] mt-1">Choose what we can notify you about.</p>
        </x-tabs.content>
        <x-tabs.content value="billing">
            <h3 class="text-lg font-medium">Billing Information</h3>
            <p class="text-sm text-[color:var(--color-text-muted)] mt-1">Manage your payment methods.</p>
        </x-tabs.content>
    </div>
</x-tabs>
HTML;
                                @endphp
                                <section id="{{ $categories['Navigation']['Tabs'] }}" class="js-section scroll-mt-8">
                                    <h2 class="text-2xl font-bold border-b border-[color:var(--color-border)] pb-4 mb-8">Tabs</h2>
                                    
                                    <x-showcase.preview title="Standard Tabs" :code="$tabsCode" id="preview-tabs">
                                        <div class="w-full max-w-2xl">
                                            <x-tabs defaultTab="account">
                                                <x-tabs.list>
                                                    <x-tabs.trigger value="account">Account</x-tabs.trigger>
                                                    <x-tabs.trigger value="password">Password</x-tabs.trigger>
                                                    <x-tabs.trigger value="notifications">Notifications</x-tabs.trigger>
                                                    <x-tabs.trigger value="billing" disabled>Billing</x-tabs.trigger>
                                                </x-tabs.list>
                                                
                                                <div class="p-4 bg-[color:var(--color-surface-primary)] border border-t-0 border-[color:var(--color-border)] rounded-b-md">
                                                    <x-tabs.content value="account">
                                                        <h3 class="text-lg font-medium">Account Settings</h3>
                                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">Update your account details here.</p>
                                                    </x-tabs.content>
                                                    <x-tabs.content value="password">
                                                        <h3 class="text-lg font-medium">Change Password</h3>
                                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">Ensure your account is using a long, random password.</p>
                                                    </x-tabs.content>
                                                    <x-tabs.content value="notifications">
                                                        <h3 class="text-lg font-medium">Notification Preferences</h3>
                                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">Choose what we can notify you about.</p>
                                                    </x-tabs.content>
                                                    <x-tabs.content value="billing">
                                                        <h3 class="text-lg font-medium">Billing Information</h3>
                                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">Manage your payment methods.</p>
                                                    </x-tabs.content>
                                                </div>
                                            </x-tabs>
                                        </div>
                                    </x-showcase.preview>
                                </section>
                            @endif

                            {{-- Breadcrumb --}}
                            @if(isset($categories['Navigation']['Breadcrumb']))
                                @php
                                    $breadcrumbStandardCode = <<<'HTML'
<x-breadcrumb>
    <x-breadcrumb.item href="/">Home</x-breadcrumb.item>
    <x-breadcrumb.item href="/products">Products</x-breadcrumb.item>
    <x-breadcrumb.item href="/products/laptops">Laptops</x-breadcrumb.item>
    <x-breadcrumb.item active>MacBook Pro</x-breadcrumb.item>
</x-breadcrumb>
HTML;

                                    $breadcrumbIconsCode = <<<'HTML'
<x-breadcrumb>
    <x-breadcrumb.item href="/">
        <x-slot:icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
        </x-slot:icon>
        Home
    </x-breadcrumb.item>
    <x-breadcrumb.item href="/products">Products</x-breadcrumb.item>
    <x-breadcrumb.item active>Accessories</x-breadcrumb.item>
</x-breadcrumb>
HTML;

                                    $breadcrumbCustomSeparatorCode = <<<'HTML'
<x-breadcrumb>
    <x-slot:separator>
        <span class="px-1 text-lg leading-none">&bull;</span>
    </x-slot:separator>
    <x-breadcrumb.item href="/">Home</x-breadcrumb.item>
    <x-breadcrumb.item href="/orders">Orders</x-breadcrumb.item>
    <x-breadcrumb.item active>Invoice #INV-2026-89</x-breadcrumb.item>
</x-breadcrumb>
HTML;

                                    $breadcrumbLongCode = <<<'HTML'
<x-breadcrumb>
    <x-breadcrumb.item href="/">Products</x-breadcrumb.item>
    <x-breadcrumb.item href="/products/gaming">Gaming</x-breadcrumb.item>
    <x-breadcrumb.item href="/products/gaming/keyboards">Mechanical Keyboards</x-breadcrumb.item>
    <x-breadcrumb.item active>HyperX Alloy Origins 65 RGB Aqua Switch Version with Custom Keycaps</x-breadcrumb.item>
</x-breadcrumb>
HTML;
                                @endphp
                                <section id="{{ $categories['Navigation']['Breadcrumb'] }}" class="js-section scroll-mt-8 mt-16">
                                    <h2 class="text-2xl font-bold border-b border-[color:var(--color-border)] pb-4 mb-8">Breadcrumb</h2>
                                    
                                    <div class="space-y-8">
                                        <x-showcase.preview title="Standard" :code="$breadcrumbStandardCode" id="preview-breadcrumb-standard" :noClip="true">
                                            <x-breadcrumb>
                                                <x-breadcrumb.item href="/">Home</x-breadcrumb.item>
                                                <x-breadcrumb.item href="/products">Products</x-breadcrumb.item>
                                                <x-breadcrumb.item href="/products/laptops">Laptops</x-breadcrumb.item>
                                                <x-breadcrumb.item active>MacBook Pro</x-breadcrumb.item>
                                            </x-breadcrumb>
                                        </x-showcase.preview>

                                        <x-showcase.preview title="With Icons" :code="$breadcrumbIconsCode" id="preview-breadcrumb-icons" :noClip="true">
                                            <x-breadcrumb>
                                                <x-breadcrumb.item href="/">
                                                    <x-slot:icon>
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                                                    </x-slot:icon>
                                                    Home
                                                </x-breadcrumb.item>
                                                <x-breadcrumb.item href="/products">Products</x-breadcrumb.item>
                                                <x-breadcrumb.item active>Accessories</x-breadcrumb.item>
                                            </x-breadcrumb>
                                        </x-showcase.preview>

                                        <x-showcase.preview title="Custom Separator" :code="$breadcrumbCustomSeparatorCode" id="preview-breadcrumb-custom-separator" :noClip="true">
                                            <x-breadcrumb>
                                                <x-slot:separator>
                                                    <span class="px-1 text-lg leading-none">&bull;</span>
                                                </x-slot:separator>
                                                <x-breadcrumb.item href="/">Home</x-breadcrumb.item>
                                                <x-breadcrumb.item href="/orders">Orders</x-breadcrumb.item>
                                                <x-breadcrumb.item active>Invoice #INV-2026-89</x-breadcrumb.item>
                                            </x-breadcrumb>
                                        </x-showcase.preview>

                                        <x-showcase.preview title="Long Breadcrumb (Truncation & Scrolling)" :code="$breadcrumbLongCode" id="preview-breadcrumb-long" defaultViewport="mobile" :noClip="true">
                                            <x-breadcrumb>
                                                <x-breadcrumb.item href="/">Products</x-breadcrumb.item>
                                                <x-breadcrumb.item href="/products/gaming">Gaming</x-breadcrumb.item>
                                                <x-breadcrumb.item href="/products/gaming/keyboards">Mechanical Keyboards</x-breadcrumb.item>
                                                <x-breadcrumb.item active>HyperX Alloy Origins 65 RGB Aqua Switch Version with Custom Keycaps</x-breadcrumb.item>
                                            </x-breadcrumb>
                                        </x-showcase.preview>
                                    </div>
                                </section>
                            @endif

                            {{-- Dropdown --}}
                            @if(isset($categories['Navigation']['Dropdown']))
                                @php
                                    $dropdownBasicCode = <<<'HTML'
<x-dropdown>
    <x-dropdown.trigger>
        <button class="px-4 py-2 text-sm font-semibold bg-white border border-[color:var(--color-border)] rounded-lg shadow-sm hover:bg-[color:var(--color-surface-secondary)] transition-colors">
            Actions
        </button>
    </x-dropdown.trigger>
    <x-dropdown.content>
        <x-dropdown.header>Options</x-dropdown.header>
        <x-dropdown.item href="#profile" as="link">Profile</x-dropdown.item>
        <x-dropdown.item href="#settings" as="link">Settings</x-dropdown.item>
        <x-dropdown.divider />
        <x-dropdown.item variant="danger">Logout</x-dropdown.item>
    </x-dropdown.content>
</x-dropdown>
HTML;

                                    $dropdownIconsCode = <<<'HTML'
<x-dropdown>
    <x-dropdown.trigger>
        <button class="px-4 py-2 text-sm font-semibold bg-white border border-[color:var(--color-border)] rounded-lg shadow-sm hover:bg-[color:var(--color-surface-secondary)] transition-colors">
            Manage Document
        </button>
    </x-dropdown.trigger>
    <x-dropdown.content>
        <x-dropdown.item shortcut="Ctrl+E">
            <x-slot:icon>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
            </x-slot:icon>
            Edit
        </x-dropdown.item>
        <x-dropdown.item shortcut="Ctrl+D">
            <x-slot:icon>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" /></svg>
            </x-slot:icon>
            Duplicate
        </x-dropdown.item>
        <x-dropdown.divider />
        <x-dropdown.item variant="danger" shortcut="Del">
            <x-slot:icon>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            </x-slot:icon>
            Delete
        </x-dropdown.item>
    </x-dropdown.content>
</x-dropdown>
HTML;

                                    $dropdownConfirmCode = <<<'HTML'
<x-dropdown @dropdown-confirm="if (!confirm($event.detail.message)) $event.preventDefault()">
    <x-dropdown.trigger>
        <button class="px-4 py-2 text-sm font-semibold bg-white border border-[color:var(--color-border)] rounded-lg shadow-sm hover:bg-[color:var(--color-surface-secondary)] transition-colors">
            Database Settings
        </button>
    </x-dropdown.trigger>
    <x-dropdown.content>
        <x-dropdown.item as="submit" confirm="Are you sure you want to run database migrations?">
            Run Migrations
        </x-dropdown.item>
        <x-dropdown.item variant="danger" confirm="Are you sure you want to permanently drop the database?">
            Drop Database
        </x-dropdown.item>
    </x-dropdown.content>
</x-dropdown>
HTML;

                                    $dropdownKeepOpenCode = <<<'HTML'
<x-dropdown :closeOnClick="false">
    <x-dropdown.trigger>
        <button class="px-4 py-2 text-sm font-semibold bg-white border border-[color:var(--color-border)] rounded-lg shadow-sm hover:bg-[color:var(--color-surface-secondary)] transition-colors">
            Filter Options
        </button>
    </x-dropdown.trigger>
    <x-dropdown.content width="sm">
        <x-dropdown.header>Include Status</x-dropdown.header>
        <label class="flex items-center gap-2 px-4 py-2 text-sm font-medium hover:bg-[color:var(--color-surface-secondary)] cursor-pointer">
            <input type="checkbox" checked class="rounded border-[color:var(--color-border)] text-[color:var(--color-primary)] focus:ring-[color:var(--color-primary-500)]">
            <span>Pending</span>
        </label>
        <label class="flex items-center gap-2 px-4 py-2 text-sm font-medium hover:bg-[color:var(--color-surface-secondary)] cursor-pointer">
            <input type="checkbox" checked class="rounded border-[color:var(--color-border)] text-[color:var(--color-primary)] focus:ring-[color:var(--color-primary-500)]">
            <span>Completed</span>
        </label>
        <label class="flex items-center gap-2 px-4 py-2 text-sm font-medium hover:bg-[color:var(--color-surface-secondary)] cursor-pointer">
            <input type="checkbox" class="rounded border-[color:var(--color-border)] text-[color:var(--color-primary)] focus:ring-[color:var(--color-primary-500)]">
            <span>Archived</span>
        </label>
    </x-dropdown.content>
</x-dropdown>
HTML;

                                    $dropdownStatesCode = <<<'HTML'
<x-dropdown>
    <x-dropdown.trigger>
        <button class="px-4 py-2 text-sm font-semibold bg-white border border-[color:var(--color-border)] rounded-lg shadow-sm hover:bg-[color:var(--color-surface-secondary)] transition-colors">
            File Actions
        </button>
    </x-dropdown.trigger>
    <x-dropdown.content>
        <x-dropdown.item disabled>
            <x-slot:icon>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
            </x-slot:icon>
            Download (Disabled)
        </x-dropdown.item>
        <x-dropdown.item busy>
            Converting File...
        </x-dropdown.item>
        <x-dropdown.item busy>
            <x-slot:busyIcon>
                <svg class="animate-bounce h-4 w-4 text-[color:var(--color-primary-500)]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
            </x-slot:busyIcon>
            Custom Loader...
        </x-dropdown.item>
    </x-dropdown.content>
</x-dropdown>
HTML;
                                @endphp

                                <section id="{{ $categories['Navigation']['Dropdown'] }}" class="js-section scroll-mt-8 mt-16">
                                    <h2 class="text-2xl font-bold border-b border-[color:var(--color-border)] pb-4 mb-8">Dropdown</h2>

                                    <div class="space-y-8">
                                        {{-- Basic --}}
                                        <x-showcase.preview title="Basic Dropdown" :code="$dropdownBasicCode" id="preview-dropdown-basic" :noClip="true">
                                            <x-dropdown>
                                                <x-dropdown.trigger>
                                                    <button class="px-4 py-2 text-sm font-semibold bg-white border border-[color:var(--color-border)] rounded-lg shadow-sm hover:bg-[color:var(--color-surface-secondary)] transition-colors cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-[color:var(--color-primary-500)]">
                                                        Open Dropdown
                                                    </button>
                                                </x-dropdown.trigger>
                                                <x-dropdown.content>
                                                    <x-dropdown.header>Options</x-dropdown.header>
                                                    <x-dropdown.item href="#profile" as="link">Profile</x-dropdown.item>
                                                    <x-dropdown.item href="#settings" as="link">Settings</x-dropdown.item>
                                                    <x-dropdown.divider />
                                                    <x-dropdown.item variant="danger">Logout</x-dropdown.item>
                                                </x-dropdown.content>
                                            </x-dropdown>
                                        </x-showcase.preview>

                                        {{-- With Icons & Shortcuts --}}
                                        <x-showcase.preview title="With Icons & Shortcuts" :code="$dropdownIconsCode" id="preview-dropdown-icons" :noClip="true">
                                            <x-dropdown>
                                                <x-dropdown.trigger>
                                                    <button class="px-4 py-2 text-sm font-semibold bg-white border border-[color:var(--color-border)] rounded-lg shadow-sm hover:bg-[color:var(--color-surface-secondary)] transition-colors cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-[color:var(--color-primary-500)]">
                                                        Manage Document
                                                    </button>
                                                </x-dropdown.trigger>
                                                <x-dropdown.content>
                                                    <x-dropdown.item shortcut="Ctrl+E">
                                                        <x-slot:icon>
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                        </x-slot:icon>
                                                        Edit
                                                    </x-dropdown.item>
                                                    <x-dropdown.item shortcut="Ctrl+D">
                                                        <x-slot:icon>
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" /></svg>
                                                        </x-slot:icon>
                                                        Duplicate
                                                    </x-dropdown.item>
                                                    <x-dropdown.divider />
                                                    <x-dropdown.item variant="danger" shortcut="Del">
                                                        <x-slot:icon>
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                        </x-slot:icon>
                                                        Delete
                                                    </x-dropdown.item>
                                                </x-dropdown.content>
                                            </x-dropdown>
                                        </x-showcase.preview>

                                        {{-- Form Actions & Confirmation Events --}}
                                        <x-showcase.preview title="Form Submissions & Confirmation Events" :code="$dropdownConfirmCode" id="preview-dropdown-confirm" :noClip="true">
                                            <x-dropdown @dropdown-confirm="if (!confirm($event.detail.message)) $event.preventDefault()">
                                                <x-dropdown.trigger>
                                                    <button class="px-4 py-2 text-sm font-semibold bg-white border border-[color:var(--color-border)] rounded-lg shadow-sm hover:bg-[color:var(--color-surface-secondary)] transition-colors cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-[color:var(--color-primary-500)]">
                                                        Database Actions
                                                    </button>
                                                </x-dropdown.trigger>
                                                <x-dropdown.content>
                                                    <x-dropdown.item as="submit" confirm="Are you sure you want to run database migrations?">
                                                        Run Migrations
                                                    </x-dropdown.item>
                                                    <x-dropdown.item variant="danger" confirm="Are you sure you want to permanently drop the database?">
                                                        Drop Database
                                                    </x-dropdown.item>
                                                </x-dropdown.content>
                                            </x-dropdown>
                                        </x-showcase.preview>

                                        {{-- Filters / Keep Open --}}
                                        <x-showcase.preview title="Keep Open on Option Selection" :code="$dropdownKeepOpenCode" id="preview-dropdown-keep-open" :noClip="true">
                                            <x-dropdown :closeOnClick="false">
                                                <x-dropdown.trigger>
                                                    <button class="px-4 py-2 text-sm font-semibold bg-white border border-[color:var(--color-border)] rounded-lg shadow-sm hover:bg-[color:var(--color-surface-secondary)] transition-colors cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-[color:var(--color-primary-500)]">
                                                        Filter Options
                                                    </button>
                                                </x-dropdown.trigger>
                                                <x-dropdown.content width="sm">
                                                    <x-dropdown.header>Include Status</x-dropdown.header>
                                                    <label class="flex items-center gap-2 px-4 py-2 text-sm font-medium hover:bg-[color:var(--color-surface-secondary)] cursor-pointer select-none">
                                                        <input type="checkbox" checked class="rounded border-[color:var(--color-border)] text-[color:var(--color-primary)] focus:ring-[color:var(--color-primary-500)]">
                                                        <span>Pending</span>
                                                    </label>
                                                    <label class="flex items-center gap-2 px-4 py-2 text-sm font-medium hover:bg-[color:var(--color-surface-secondary)] cursor-pointer select-none">
                                                        <input type="checkbox" checked class="rounded border-[color:var(--color-border)] text-[color:var(--color-primary)] focus:ring-[color:var(--color-primary-500)]">
                                                        <span>Completed</span>
                                                    </label>
                                                    <label class="flex items-center gap-2 px-4 py-2 text-sm font-medium hover:bg-[color:var(--color-surface-secondary)] cursor-pointer select-none">
                                                        <input type="checkbox" class="rounded border-[color:var(--color-border)] text-[color:var(--color-primary)] focus:ring-[color:var(--color-primary-500)]">
                                                        <span>Archived</span>
                                                    </label>
                                                </x-dropdown.content>
                                            </x-dropdown>
                                        </x-showcase.preview>

                                        {{-- Disabled & Busy Loading States --}}
                                        <x-showcase.preview title="Disabled & Busy Loading States" :code="$dropdownStatesCode" id="preview-dropdown-states" :noClip="true">
                                            <x-dropdown>
                                                <x-dropdown.trigger>
                                                    <button class="px-4 py-2 text-sm font-semibold bg-white border border-[color:var(--color-border)] rounded-lg shadow-sm hover:bg-[color:var(--color-surface-secondary)] transition-colors cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-[color:var(--color-primary-500)]">
                                                        Export Actions
                                                    </button>
                                                </x-dropdown.trigger>
                                                <x-dropdown.content>
                                                    <x-dropdown.item disabled>
                                                        <x-slot:icon>
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                                        </x-slot:icon>
                                                        Download (Disabled)
                                                    </x-dropdown.item>
                                                    <x-dropdown.item busy>
                                                        Exporting CSV...
                                                    </x-dropdown.item>
                                                    <x-dropdown.item busy>
                                                        <x-slot:busyIcon>
                                                            <svg class="animate-bounce h-4 w-4 text-[color:var(--color-primary-500)]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                                        </x-slot:busyIcon>
                                                        Custom Loader...
                                                    </x-dropdown.item>
                                                </x-dropdown.content>
                                            </x-dropdown>
                                        </x-showcase.preview>

                                        {{-- Alignment, Offset, Width, MaxHeight & Collision Flipping --}}
                                        <x-showcase.preview title="Custom Positioning, Width Fit & Collision Boundary Checks" code="&lt;x-dropdown side=&quot;right&quot; align=&quot;center&quot; offset=&quot;16&quot;&gt;" id="preview-dropdown-positioning" :noClip="true">
                                            <div class="flex flex-wrap items-center gap-4">
                                                <x-dropdown side="top" align="start">
                                                    <x-dropdown.trigger>
                                                        <button class="px-3 py-1.5 text-xs font-semibold bg-white border border-[color:var(--color-border)] rounded-md hover:bg-[color:var(--color-surface-secondary)] cursor-pointer">
                                                            Top Start
                                                        </button>
                                                    </x-dropdown.trigger>
                                                    <x-dropdown.content width="sm">
                                                        <x-dropdown.item>Action 1</x-dropdown.item>
                                                        <x-dropdown.item>Action 2</x-dropdown.item>
                                                    </x-dropdown.content>
                                                </x-dropdown>

                                                <x-dropdown side="bottom" align="end" offset="16">
                                                    <x-dropdown.trigger>
                                                        <button class="px-3 py-1.5 text-xs font-semibold bg-white border border-[color:var(--color-border)] rounded-md hover:bg-[color:var(--color-surface-secondary)] cursor-pointer">
                                                            Bottom End (Offset 16)
                                                        </button>
                                                    </x-dropdown.trigger>
                                                    <x-dropdown.content width="sm">
                                                        <x-dropdown.item>Action 1</x-dropdown.item>
                                                        <x-dropdown.item>Action 2</x-dropdown.item>
                                                    </x-dropdown.content>
                                                </x-dropdown>

                                                <x-dropdown side="right" align="center">
                                                    <x-dropdown.trigger>
                                                        <button class="px-3 py-1.5 text-xs font-semibold bg-white border border-[color:var(--color-border)] rounded-md hover:bg-[color:var(--color-surface-secondary)] cursor-pointer">
                                                            Right Center
                                                        </button>
                                                    </x-dropdown.trigger>
                                                    <x-dropdown.content width="sm">
                                                        <x-dropdown.item>Action 1</x-dropdown.item>
                                                        <x-dropdown.item>Action 2</x-dropdown.item>
                                                    </x-dropdown.content>
                                                </x-dropdown>

                                                <x-dropdown side="bottom" align="start">
                                                    <x-dropdown.trigger>
                                                        <button class="px-3 py-1.5 text-xs font-semibold bg-white border border-[color:var(--color-border)] rounded-md hover:bg-[color:var(--color-surface-secondary)] cursor-pointer">
                                                            Width fit trigger width
                                                        </button>
                                                    </x-dropdown.trigger>
                                                    <x-dropdown.content width="fit">
                                                        <x-dropdown.item>Exactly trigger size</x-dropdown.item>
                                                    </x-dropdown.content>
                                                </x-dropdown>
                                            </div>
                                        </x-showcase.preview>

                                        {{-- Label Wrapping vs Truncation --}}
                                        <x-showcase.preview title="Label Wrapping vs Truncation" code="&lt;x-dropdown.item :truncate=&quot;false&quot;&gt;" id="preview-dropdown-wrapping" :noClip="true">
                                            <div class="flex items-center gap-4">
                                                <x-dropdown>
                                                    <x-dropdown.trigger>
                                                        <button class="px-4 py-2 text-sm font-semibold bg-white border border-[color:var(--color-border)] rounded-lg shadow-sm hover:bg-[color:var(--color-surface-secondary)] cursor-pointer">
                                                            Show Wrap vs Truncate
                                                        </button>
                                                    </x-dropdown.trigger>
                                                    <x-dropdown.content width="sm">
                                                        <x-dropdown.item :truncate="true">
                                                            Very long label option that will definitely truncate in default sizing
                                                        </x-dropdown.item>
                                                        <x-dropdown.divider />
                                                        <x-dropdown.item :truncate="false">
                                                            Very long label option that will wrap to multiple lines correctly
                                                        </x-dropdown.item>
                                                    </x-dropdown.content>
                                                </x-dropdown>
                                            </div>
                                        </x-showcase.preview>

                                        {{-- Table Row Actions --}}
                                        <x-showcase.preview title="Table Row Actions Integrations" code="&lt;x-table.row&gt; &lt;x-dropdown&gt;..." id="preview-dropdown-table-actions" :noClip="true">
                                            <div class="w-full overflow-x-auto border border-[color:var(--color-border,#e5e7eb)] rounded-xl">
                                                <table class="w-full text-left text-sm whitespace-nowrap">
                                                    <thead class="bg-[color:var(--color-surface-secondary,#f9fafb)] text-[color:var(--color-text-muted,#6b7280)] border-b border-[color:var(--color-border,#e5e7eb)]">
                                                        <tr>
                                                            <th class="px-6 py-3 font-semibold">User</th>
                                                            <th class="px-6 py-3 font-semibold">Role</th>
                                                            <th class="px-6 py-3 font-semibold text-right">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-[color:var(--color-border,#e5e7eb)]">
                                                        <tr>
                                                            <td class="px-6 py-4 font-medium text-[color:var(--color-text-primary,#111827)]">Saurav Kumar</td>
                                                            <td class="px-6 py-4 text-[color:var(--color-text-secondary,#4b5563)]">Administrator</td>
                                                            <td class="px-6 py-4 text-right">
                                                                <x-dropdown side="left" align="start">
                                                                    <x-dropdown.trigger>
                                                                        <button class="p-1.5 rounded-lg border border-[color:var(--color-border,#e5e7eb)] bg-white text-[color:var(--color-text-muted)] hover:text-[color:var(--color-text-primary)] cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-[color:var(--color-primary-500)]">
                                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z" /></svg>
                                                                        </button>
                                                                    </x-dropdown.trigger>
                                                                    <x-dropdown.content width="sm">
                                                                        <x-dropdown.item>Edit Profile</x-dropdown.item>
                                                                        <x-dropdown.item>Change Role</x-dropdown.item>
                                                                        <x-dropdown.divider />
                                                                        <x-dropdown.item variant="danger">Remove User</x-dropdown.item>
                                                                    </x-dropdown.content>
                                                                </x-dropdown>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </x-showcase.preview>
                                    </div>
                                </section>
                            @endif

                            {{-- Stepper --}}
                            @if(isset($categories['Navigation']['Stepper']))
                                @php
                                    $stepperBasicCode = <<<'HTML'
<x-stepper :active="2" :total="3">
    <x-stepper.step :step="1" title="Billing Info" />
    <x-stepper.step :step="2" title="Shipping Address" />
    <x-stepper.step :step="3" title="Payment" />
</x-stepper>
HTML;

                                    $stepperDescCode = <<<'HTML'
<x-stepper :active="2" :total="4">
    <x-stepper.step :step="1" title="Create Account" description="Choose credentials and verify email" />
    <x-stepper.step :step="2" title="Company Profile" description="Provide corporate details and registry" />
    <x-stepper.step :step="3" title="Billing Details" description="Configure invoicing accounts" />
    <x-stepper.step :step="4" title="Review & Launch" description="Final verification checks" />
</x-stepper>
HTML;

                                    $stepperVerticalCode = <<<'HTML'
<x-stepper :active="2" :total="4" orientation="vertical">
    <x-stepper.step :step="1" title="Security Settings" description="Configure two-factor authentication" />
    <x-stepper.step :step="2" title="Notification Preferences" description="Manage push alerts and system updates" />
    <x-stepper.step :step="3" title="Invoicing Settings" description="Set default currencies" />
    <x-stepper.step :step="4" title="Integrations" description="Authorize external webhooks" />
</x-stepper>
HTML;

                                    $stepperStatusBadgeCode = <<<'HTML'
<x-stepper :active="3" :total="4">
    <x-stepper.step :step="1" title="Account Setup" />
    <x-stepper.step :step="2" status="error" title="Payment Verification">
        <x-slot:badge>3</x-slot:badge>
    </x-stepper.step>
    <x-stepper.step :step="3" title="Deploy Settings" />
    <x-stepper.step :step="4" disabled title="Launch Instance" />
</x-stepper>
HTML;

                                    $stepperInteractiveCode = <<<'HTML'
<x-stepper :active="2" :total="3">
    <x-stepper.step :step="1" href="#preview-stepper-interactive" title="Step One (Clickable)" />
    <x-stepper.step :step="2" href="#preview-stepper-interactive" title="Step Two (Clickable)" />
    <x-stepper.step :step="3" href="#preview-stepper-interactive" title="Step Three (Clickable)" />
</x-stepper>
HTML;

                                    $stepperSizesCode = <<<'HTML'
<div class="space-y-8 w-full">
    <div class="space-y-2">
        <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Small (sm)</span>
        <x-stepper :active="2" :total="3" size="sm">
            <x-stepper.step :step="1" title="Cart" />
            <x-stepper.step :step="2" title="Checkout" />
            <x-stepper.step :step="3" title="Confirmation" />
        </x-stepper>
    </div>

    <div class="space-y-2">
        <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Medium (md - default)</span>
        <x-stepper :active="2" :total="3" size="md">
            <x-stepper.step :step="1" title="Cart" />
            <x-stepper.step :step="2" title="Checkout" />
            <x-stepper.step :step="3" title="Confirmation" />
        </x-stepper>
    </div>

    <div class="space-y-2">
        <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Large (lg)</span>
        <x-stepper :active="2" :total="3" size="lg">
            <x-stepper.step :step="1" title="Cart" />
            <x-stepper.step :step="2" title="Checkout" />
            <x-stepper.step :step="3" title="Confirmation" />
        </x-stepper>
    </div>
</div>
HTML;

                                    $stepperDotsCode = <<<'HTML'
<x-stepper :active="2" :total="4" :showNumbers="false">
    <x-stepper.step :step="1" title="General Info" />
    <x-stepper.step :step="2" title="Password Reset" />
    <x-stepper.step :step="3" title="Two-Factor Setup" />
    <x-stepper.step :step="4" title="Finish" />
</x-stepper>
HTML;
                                 @endphp

                                 <section id="stepper" class="js-section mb-12">
                                     <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                         <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Stepper</h2>
                                         <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                             A flexible progress wizard bar supporting multiple sizes, layouts, links, and validation states.
                                         </p>
                                     </div>

                                     <div class="space-y-8">
                                         <!-- Basic Stepper -->
                                         <x-showcase.preview title="Standard Horizontal" :code="$stepperBasicCode" id="preview-stepper-basic">
                                             <x-stepper :active="2" :total="3">
                                                 <x-stepper.step :step="1" title="Billing Info" />
                                                 <x-stepper.step :step="2" title="Shipping Address" />
                                                 <x-stepper.step :step="3" title="Payment" />
                                             </x-stepper>
                                         </x-showcase.preview>

                                         <!-- Stepper with Descriptions -->
                                         <x-showcase.preview title="With Descriptions & Wrapping" :code="$stepperDescCode" id="preview-stepper-desc">
                                             <x-stepper :active="2" :total="4">
                                                 <x-stepper.step :step="1" title="Create Account" description="Choose credentials and verify email" />
                                                 <x-stepper.step :step="2" title="Company Profile" description="Provide corporate details and registry" />
                                                 <x-stepper.step :step="3" title="Billing Details" description="Configure invoicing accounts" />
                                                 <x-stepper.step :step="4" title="Review & Launch" description="Final verification checks" />
                                             </x-stepper>
                                         </x-showcase.preview>

                                         <!-- Vertical Layout -->
                                         <x-showcase.preview title="Vertical Orientation" :code="$stepperVerticalCode" id="preview-stepper-vertical">
                                             <div class="w-full max-w-lg">
                                                 <x-stepper :active="2" :total="4" orientation="vertical">
                                                     <x-stepper.step :step="1" title="Security Settings" description="Configure two-factor authentication" />
                                                     <x-stepper.step :step="2" title="Notification Preferences" description="Manage push alerts and system updates" />
                                                     <x-stepper.step :step="3" title="Invoicing Settings" description="Set default currencies" />
                                                     <x-stepper.step :step="4" title="Integrations" description="Authorize external webhooks" />
                                                 </x-stepper>
                                             </div>
                                         </x-showcase.preview>

                                         <!-- Status States and Badges -->
                                         <x-showcase.preview title="Status States & Badges" :code="$stepperStatusBadgeCode" id="preview-stepper-status">
                                             <x-stepper :active="3" :total="4">
                                                 <x-stepper.step :step="1" title="Account Setup" />
                                                 <x-stepper.step :step="2" status="error" title="Payment Verification">
                                                     <x-slot:badge>3</x-slot:badge>
                                                 </x-stepper.step>
                                                 <x-stepper.step :step="3" title="Deploy Settings" />
                                                 <x-stepper.step :step="4" disabled title="Launch Instance" />
                                             </x-stepper>
                                         </x-showcase.preview>

                                         <!-- Interactive Clickable Navigation -->
                                         <x-showcase.preview title="Interactive Navigation (Clickable Links)" :code="$stepperInteractiveCode" id="preview-stepper-interactive">
                                             <x-stepper :active="2" :total="3">
                                                 <x-stepper.step :step="1" href="#preview-stepper-interactive" title="Step One (Clickable)" />
                                                 <x-stepper.step :step="2" href="#preview-stepper-interactive" title="Step Two (Clickable)" />
                                                 <x-stepper.step :step="3" href="#preview-stepper-interactive" title="Step Three (Clickable)" />
                                             </x-stepper>
                                         </x-showcase.preview>

                                         <!-- Sizes Options -->
                                         <x-showcase.preview title="Sizes Options (sm, md, lg)" :code="$stepperSizesCode" id="preview-stepper-sizes">
                                             <div class="space-y-8 w-full">
                                                 <div class="space-y-2">
                                                     <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Small (sm)</span>
                                                     <x-stepper :active="2" :total="3" size="sm">
                                                         <x-stepper.step :step="1" title="Cart" />
                                                         <x-stepper.step :step="2" title="Checkout" />
                                                         <x-stepper.step :step="3" title="Confirmation" />
                                                     </x-stepper>
                                                 </div>

                                                 <div class="space-y-2">
                                                     <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Medium (md - default)</span>
                                                     <x-stepper :active="2" :total="3" size="md">
                                                         <x-stepper.step :step="1" title="Cart" />
                                                         <x-stepper.step :step="2" title="Checkout" />
                                                         <x-stepper.step :step="3" title="Confirmation" />
                                                     </x-stepper>
                                                 </div>

                                                 <div class="space-y-2">
                                                     <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Large (lg)</span>
                                                     <x-stepper :active="2" :total="3" size="lg">
                                                         <x-stepper.step :step="1" title="Cart" />
                                                         <x-stepper.step :step="2" title="Checkout" />
                                                         <x-stepper.step :step="3" title="Confirmation" />
                                                     </x-stepper>
                                                 </div>
                                             </div>
                                         </x-showcase.preview>

                                         <!-- Without Numbers (Dots Only) -->
                                         <x-showcase.preview title="Without Numbers (Dot Variants)" :code="$stepperDotsCode" id="preview-stepper-dots">
                                             <x-stepper :active="2" :total="4" :showNumbers="false">
                                                 <x-stepper.step :step="1" title="General Info" />
                                                 <x-stepper.step :step="2" title="Password Reset" />
                                                 <x-stepper.step :step="3" title="Two-Factor Setup" />
                                                 <x-stepper.step :step="4" title="Finish" />
                                             </x-stepper>
                                         </x-showcase.preview>
                                     </div>
                                 </section>
                             @endif

                    @endif

                    {{-- Feedback --}}
                    @if(isset($categories['Feedback']))
                        <div class="space-y-8">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-[color:var(--color-neutral-400)] mb-3">Feedback</h2>

                            {{-- Modal --}}
                            @if(isset($categories['Feedback']['Modal']))
                                @php
                                    $modalBasicCode = <<<'HTML'
<button 
    onclick="window.openModal('modal-alert')"
    class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] rounded-lg hover:bg-[color:var(--color-primary-700)] transition-colors"
>
    Open Alert Modal
</button>

<x-modal id="modal-alert" title="Deactivate Account" size="sm">
    <div class="space-y-3">
        <p>Are you sure you want to deactivate your account? All of your data will be permanently removed. This action cannot be undone.</p>
    </div>
    <x-slot:footer>
        <button 
            onclick="window.closeModal('modal-alert')"
            class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] hover:bg-[color:var(--color-surface-secondary)] rounded-lg transition-colors border border-[color:var(--color-border)]"
        >
            Cancel
        </button>
        <button 
            onclick="window.closeModal('modal-alert')"
            class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-danger-600)] hover:bg-[color:var(--color-danger-700)] rounded-lg transition-colors"
        >
            Deactivate
        </button>
    </x-slot>
</x-modal>
HTML;

                                    $modalFormCode = <<<'HTML'
<button 
    onclick="window.openModal('modal-form')"
    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
>
    Open Form Modal
</button>

<x-modal id="modal-form" title="Add New User" size="md" initialFocus="new-user-email">
    <div class="space-y-4">
        <p class="text-xs text-[color:var(--color-text-muted)]">Please fill in the user details. Email field will receive focus automatically.</p>
        <x-form.input id="new-user-name" name="name" label="Full Name" placeholder="John Doe" />
        <x-form.input id="new-user-email" name="email" label="Email Address" type="email" placeholder="john@example.com" />
    </div>
    <x-slot:footer>
        <button 
            onclick="window.closeModal('modal-form')"
            class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] hover:bg-[color:var(--color-surface-secondary)] rounded-lg transition-colors border"
        >
            Cancel
        </button>
        <button 
            onclick="window.closeModal('modal-form')"
            class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] rounded-lg transition-colors"
        >
            Save User
        </button>
    </x-slot>
</x-modal>
HTML;

                                    $modalScrollCode = <<<'HTML'
<button 
    onclick="window.openModal('modal-scroll')"
    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
>
    Open Long Content Modal
</button>

<x-modal id="modal-scroll" title="Terms of Service" size="lg">
    <div class="space-y-4 pr-1">
        <h4 class="font-bold text-[color:var(--color-text-primary)]">1. Acceptance of Terms</h4>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam feugiat leo in sem rhoncus accumsan. Integer dictum erat eget lorem scelerisque, nec maximus erat pellentesque. Donec a tristique ipsum. Fusce rhoncus dolor in felis pharetra cursus.</p>
        <h4 class="font-bold text-[color:var(--color-text-primary)]">2. User Conduct</h4>
        <p>Suspendisse potenti. Etiam vel dictum tellus. Pellentesque dictum erat ut ex vulputate, sodales maximus odio cursus. Praesent rhoncus lacus eu facilisis elementum. Vivamus volutpat massa ac imperdiet dignissim.</p>
        <h4 class="font-bold text-[color:var(--color-text-primary)]">3. Limitation of Liability</h4>
        <p>In ac neque quis lacus sodales imperdiet. In accumsan tristique nibh vel euismod. Curabitur pulvinar pretium nunc eu dictum. Suspendisse eu diam et neque accumsan porttitor eu ut massa. Aliquam nec efficitur ex, sed bibendum elit.</p>
        <p>Donec tincidunt tristique sem vel feugiat. Nunc eget erat vitae ex ultrices laoreet. Vivamus vel turpis vel nisl finibus interdum non at magna. Suspendisse quis sollicitudin justo, eu lobortis est. Duis sodales magna et lectus feugiat, non egestas ex sollicitudin.</p>
    </div>
    <x-slot:footer>
        <button 
            onclick="window.closeModal('modal-scroll')"
            class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] rounded-lg transition-colors w-full sm:w-auto"
        >
            I Accept
        </button>
    </x-slot>
</x-modal>
HTML;

                                    $modalSizesCode = <<<'HTML'
<div class="flex flex-wrap gap-3">
    <button onclick="window.openModal('modal-size-sm')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Small (sm)</button>
    <button onclick="window.openModal('modal-size-md')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Medium (md)</button>
    <button onclick="window.openModal('modal-size-lg')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Large (lg)</button>
    <button onclick="window.openModal('modal-size-xl')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Extra Large (xl)</button>
    <button onclick="window.openModal('modal-size-2xl')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">2xl</button>
    <button onclick="window.openModal('modal-size-full')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Full Screen</button>
</div>

<x-modal id="modal-size-sm" title="Small Preset" size="sm">
    <p>This is a small layout dialog.</p>
</x-modal>
<x-modal id="modal-size-md" title="Medium Preset" size="md">
    <p>This is a medium layout dialog.</p>
</x-modal>
<x-modal id="modal-size-lg" title="Large Preset" size="lg">
    <p>This is a large layout dialog.</p>
</x-modal>
<x-modal id="modal-size-xl" title="Extra Large Preset" size="xl">
    <p>This is an extra large layout dialog.</p>
</x-modal>
<x-modal id="modal-size-2xl" title="2XL Preset" size="2xl">
    <p>This is a 2xl layout dialog.</p>
</x-modal>
<x-modal id="modal-size-full" title="Full Screen Preset" size="full">
    <div class="h-full flex flex-col justify-between">
        <p>This is a full screen layout overlay dialog.</p>
        <button onclick="window.closeModal('modal-size-full')" class="px-4 py-2 text-white bg-[color:var(--color-primary-600)] rounded-lg">Close Full Screen</button>
    </div>
</x-modal>
HTML;

                                    $modalPersistentCode = <<<'HTML'
<button 
    onclick="window.openModal('modal-persistent')"
    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
>
    Open Persistent Modal
</button>

<x-modal id="modal-persistent" title="Mandatory Acceptance" :persistent="true">
    <div class="space-y-3">
        <p>You cannot dismiss this modal by clicking outside or pressing Escape. You must explicitly choose an action below to proceed.</p>
    </div>
    <x-slot:footer>
        <button 
            onclick="window.closeModal('modal-persistent')"
            class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] rounded-lg transition-colors"
        >
            Confirm & Close
        </button>
    </x-slot>
</x-modal>
HTML;

                                    $modalBusyCode = <<<'HTML'
<button 
    onclick="window.openModal('modal-busy')"
    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
>
    Open Busy Modal
</button>

<x-modal id="modal-busy" title="Processing Document" :busy="true">
    <div class="space-y-4 text-center py-6">
        <svg class="animate-spin h-8 w-8 text-[color:var(--color-primary-600)] mx-auto" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-sm font-medium text-[color:var(--color-text-secondary)]">Analyzing layout schemas, please wait...</p>
    </div>
    <x-slot:footer>
        <button 
            disabled
            class="px-4 py-2 text-sm font-semibold text-white bg-neutral-300 rounded-lg cursor-not-allowed pointer-events-none"
        >
            Processing...
        </button>
    </x-slot>
</x-modal>
HTML;

                                    $modalStackedCode = <<<'HTML'
<button 
    onclick="window.openModal('modal-stack-a')"
    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
>
    Open Stacked Modals
</button>

<!-- Modal A -->
<x-modal id="modal-stack-a" title="Modal A (Parent)">
    <div class="space-y-4">
        <p>This is the first modal layer. Click the button below to launch a nested modal.</p>
        <button 
            onclick="window.openModal('modal-stack-b')"
            class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] rounded-lg"
        >
            Launch Modal B
        </button>
    </div>
    <x-slot:footer>
        <button onclick="window.closeModal('modal-stack-a')" class="px-4 py-2 text-sm border rounded-lg">Close A</button>
    </x-slot>
</x-modal>

<!-- Modal B -->
<x-modal id="modal-stack-b" title="Modal B (Child)" size="sm">
    <div class="space-y-3">
        <p>This is the topmost modal. Pressing Escape will close ONLY Modal B and return focus to Modal A. Background body scroll lock remains active.</p>
    </div>
    <x-slot:footer>
        <button 
            onclick="window.closeModal('modal-stack-b')"
            class="px-4 py-2 text-sm text-white bg-[color:var(--color-danger-600)] rounded-lg"
        >
            Close B
        </button>
    </x-slot>
</x-modal>
HTML;
                                @endphp

                                <section id="modal" class="js-section mb-12">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Modal</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            Premium dialog overlay screens managed by Alpine.js. Includes scroll blocking, portals, sizing options, busy loaders, and accessibility focus traps.
                                        </p>
                                    </div>

                                    <div class="space-y-8">
                                        <!-- Basic Alert Modal -->
                                        <x-showcase.preview title="Basic Alert Prompt" :code="$modalBasicCode" id="preview-modal-basic">
                                            <div class="flex items-center gap-3">
                                                <button 
                                                    onclick="window.openModal('modal-alert')"
                                                    class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] rounded-lg hover:bg-[color:var(--color-primary-700)] transition-colors"
                                                >
                                                    Open Alert Modal
                                                </button>
                                                
                                                <x-modal id="modal-alert" title="Deactivate Account" size="sm">
                                                    <div class="space-y-3">
                                                        <p>Are you sure you want to deactivate your account? All of your data will be permanently removed. This action cannot be undone.</p>
                                                    </div>
                                                    <x-slot:footer>
                                                        <button 
                                                            onclick="window.closeModal('modal-alert')"
                                                            class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] hover:bg-[color:var(--color-surface-secondary)] rounded-lg transition-colors border border-[color:var(--color-border)]"
                                                        >
                                                            Cancel
                                                        </button>
                                                        <button 
                                                            onclick="window.closeModal('modal-alert')"
                                                            class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-danger-600)] hover:bg-[color:var(--color-danger-700)] rounded-lg transition-colors"
                                                        >
                                                            Deactivate
                                                        </button>
                                                    </x-slot>
                                                </x-modal>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Form Input Modal -->
                                        <x-showcase.preview title="Form Input Dialog (initialFocus)" :code="$modalFormCode" id="preview-modal-form">
                                            <div>
                                                <button 
                                                    onclick="window.openModal('modal-form')"
                                                    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
                                                >
                                                    Open Form Modal
                                                </button>

                                                <x-modal id="modal-form" title="Add New User" size="md" initialFocus="new-user-email">
                                                    <div class="space-y-4">
                                                        <p class="text-xs text-[color:var(--color-text-muted)]">Please fill in the user details. Email field will receive focus automatically.</p>
                                                        <x-form.input id="new-user-name" name="name" label="Full Name" placeholder="John Doe" />
                                                        <x-form.input id="new-user-email" name="email" label="Email Address" type="email" placeholder="john@example.com" />
                                                    </div>
                                                    <x-slot:footer>
                                                        <button 
                                                            onclick="window.closeModal('modal-form')"
                                                            class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] hover:bg-[color:var(--color-surface-secondary)] rounded-lg transition-colors border"
                                                        >
                                                            Cancel
                                                        </button>
                                                        <button 
                                                            onclick="window.closeModal('modal-form')"
                                                            class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] rounded-lg transition-colors"
                                                        >
                                                            Save User
                                                        </button>
                                                    </x-slot>
                                                </x-modal>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Scrollable Content Dialog -->
                                        <x-showcase.preview title="Scrollable Content" :code="$modalScrollCode" id="preview-modal-scroll">
                                            <div>
                                                <button 
                                                    onclick="window.openModal('modal-scroll')"
                                                    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
                                                >
                                                    Open Long Content Modal
                                                </button>

                                                <x-modal id="modal-scroll" title="Terms of Service" size="lg">
                                                    <div class="space-y-4 pr-1">
                                                        <h4 class="font-bold text-[color:var(--color-text-primary)]">1. Acceptance of Terms</h4>
                                                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam feugiat leo in sem rhoncus accumsan. Integer dictum erat eget lorem scelerisque, nec maximus erat pellentesque. Donec a tristique ipsum. Fusce rhoncus dolor in felis pharetra cursus.</p>
                                                        <h4 class="font-bold text-[color:var(--color-text-primary)]">2. User Conduct</h4>
                                                        <p>Suspendisse potenti. Etiam vel dictum tellus. Pellentesque dictum erat ut ex vulputate, sodales maximus odio cursus. Praesent rhoncus lacus eu facilisis elementum. Vivamus volutpat massa ac imperdiet dignissim.</p>
                                                        <h4 class="font-bold text-[color:var(--color-text-primary)]">3. Limitation of Liability</h4>
                                                        <p>In ac neque quis lacus sodales imperdiet. In accumsan tristique nibh vel euismod. Curabitur pulvinar pretium nunc eu dictum. Suspendisse eu diam et neque accumsan porttitor eu ut massa. Aliquam nec efficitur ex, sed bibendum elit.</p>
                                                        <p>Donec tincidunt tristique sem vel feugiat. Nunc eget erat vitae ex ultrices laoreet. Vivamus vel turpis vel nisl finibus interdum non at magna. Suspendisse quis sollicitudin justo, eu lobortis est. Duis sodales magna et lectus feugiat, non egestas ex sollicitudin.</p>
                                                    </div>
                                                    <x-slot:footer>
                                                        <button 
                                                            onclick="window.closeModal('modal-scroll')"
                                                            class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] rounded-lg transition-colors w-full sm:w-auto"
                                                        >
                                                            I Accept
                                                        </button>
                                                    </x-slot>
                                                </x-modal>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Dimensions Presets -->
                                        <x-showcase.preview title="Dimension Presets (sm, md, lg, xl, 2xl, full)" :code="$modalSizesCode" id="preview-modal-sizes">
                                            <div>
                                                <div class="flex flex-wrap gap-3">
                                                    <button onclick="window.openModal('modal-size-sm')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Small (sm)</button>
                                                    <button onclick="window.openModal('modal-size-md')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Medium (md)</button>
                                                    <button onclick="window.openModal('modal-size-lg')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Large (lg)</button>
                                                    <button onclick="window.openModal('modal-size-xl')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Extra Large (xl)</button>
                                                    <button onclick="window.openModal('modal-size-2xl')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">2xl</button>
                                                    <button onclick="window.openModal('modal-size-full')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Full Screen</button>
                                                </div>

                                                <x-modal id="modal-size-sm" title="Small Preset" size="sm">
                                                    <p>This is a small layout dialog.</p>
                                                </x-modal>
                                                <x-modal id="modal-size-md" title="Medium Preset" size="md">
                                                    <p>This is a medium layout dialog.</p>
                                                </x-modal>
                                                <x-modal id="modal-size-lg" title="Large Preset" size="lg">
                                                    <p>This is a large layout dialog.</p>
                                                </x-modal>
                                                <x-modal id="modal-size-xl" title="Extra Large Preset" size="xl">
                                                    <p>This is an extra large layout dialog.</p>
                                                </x-modal>
                                                <x-modal id="modal-size-2xl" title="2XL Preset" size="2xl">
                                                    <p>This is a 2xl layout dialog.</p>
                                                </x-modal>
                                                <x-modal id="modal-size-full" title="Full Screen Preset" size="full">
                                                    <div class="h-full flex flex-col justify-between p-6">
                                                        <p class="text-base font-semibold">This is a full screen layout overlay dialog.</p>
                                                        <button onclick="window.closeModal('modal-size-full')" class="mt-8 px-4 py-2 text-white bg-[color:var(--color-primary-600)] rounded-lg self-start">Close Full Screen</button>
                                                    </div>
                                                </x-modal>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Persistent Mode -->
                                        <x-showcase.preview title="Persistent Mode" :code="$modalPersistentCode" id="preview-modal-persistent">
                                            <div>
                                                <button 
                                                    onclick="window.openModal('modal-persistent')"
                                                    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
                                                >
                                                    Open Persistent Modal
                                                </button>

                                                <x-modal id="modal-persistent" title="Mandatory Acceptance" :persistent="true">
                                                    <div class="space-y-3">
                                                        <p>You cannot dismiss this modal by clicking outside or pressing Escape. You must explicitly choose an action below to proceed.</p>
                                                    </div>
                                                    <x-slot:footer>
                                                        <button 
                                                            onclick="window.closeModal('modal-persistent')"
                                                            class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] rounded-lg transition-colors"
                                                        >
                                                            Confirm & Close
                                                        </button>
                                                    </x-slot>
                                                </x-modal>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Busy Loading State -->
                                        <x-showcase.preview title="Busy Loading State" :code="$modalBusyCode" id="preview-modal-busy">
                                            <div>
                                                <button 
                                                    onclick="window.openModal('modal-busy')"
                                                    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
                                                >
                                                    Open Busy Modal
                                                </button>

                                                <x-modal id="modal-busy" title="Processing Document" :busy="true">
                                                    <div class="space-y-4 text-center py-6">
                                                        <svg class="animate-spin h-8 w-8 text-[color:var(--color-primary-600)] mx-auto" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        <p class="text-sm font-medium text-[color:var(--color-text-secondary)]">Analyzing layout schemas, please wait...</p>
                                                    </div>
                                                    <x-slot:footer>
                                                        <button 
                                                            disabled
                                                            class="px-4 py-2 text-sm font-semibold text-white bg-neutral-300 rounded-lg cursor-not-allowed pointer-events-none"
                                                        >
                                                            Processing...
                                                        </button>
                                                    </x-slot>
                                                </x-modal>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Stacked Modals -->
                                        <x-showcase.preview title="Stacked/Nested Modals" :code="$modalStackedCode" id="preview-modal-stacked">
                                            <div>
                                                <button 
                                                    onclick="window.openModal('modal-stack-a')"
                                                    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
                                                >
                                                    Open Stacked Modals
                                                </button>

                                                <!-- Modal A -->
                                                <x-modal id="modal-stack-a" title="Modal A (Parent)">
                                                    <div class="space-y-4">
                                                        <p>This is the first modal layer. Click the button below to launch a nested modal.</p>
                                                        <button 
                                                            onclick="window.openModal('modal-stack-b')"
                                                            class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] rounded-lg"
                                                        >
                                                            Launch Modal B
                                                        </button>
                                                    </div>
                                                    <x-slot:footer>
                                                        <button onclick="window.closeModal('modal-stack-a')" class="px-4 py-2 text-sm border rounded-lg bg-white text-[color:var(--color-text-secondary)]">Close A</button>
                                                    </x-slot>
                                                </x-modal>

                                                <!-- Modal B -->
                                                <x-modal id="modal-stack-b" title="Modal B (Child)" size="sm">
                                                    <div class="space-y-3">
                                                        <p>This is the topmost modal. Pressing Escape will close ONLY Modal B and return focus to Modal A. Background body scroll lock remains active.</p>
                                                    </div>
                                                    <x-slot:footer>
                                                        <button 
                                                            onclick="window.closeModal('modal-stack-b')"
                                                            class="px-4 py-2 text-sm text-white bg-[color:var(--color-danger-600)] rounded-lg"
                                                        >
                                                            Close B
                                                        </button>
                                                    </x-slot>
                                                </x-modal>
                                            </div>
                                        </x-showcase.preview>
                                    </div>
                                </section>
                            @endif

                            {{-- Drawer --}}
                            @if(isset($categories['Feedback']['Drawer']))
                                @php
                                    $drawerBasicCode = <<<'HTML'
<button 
    onclick="window.openDrawer('drawer-filters')"
    class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] rounded-lg hover:bg-[color:var(--color-primary-700)] transition-colors"
>
    Open Filter Drawer
</button>

<x-drawer id="drawer-filters" title="Filter Products" size="md">
    <div class="space-y-4">
        <div>
            <label class="block text-xs font-semibold text-[color:var(--color-text-secondary)] mb-2">Category</label>
            <x-form.select id="filter-category" name="category">
                <option value="">All Categories</option>
                <option value="electronics">Electronics</option>
                <option value="apparel">Apparel</option>
                <option value="home">Home & Kitchen</option>
            </x-form.select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-[color:var(--color-text-secondary)] mb-2">Price Range</label>
            <div class="grid grid-cols-2 gap-2">
                <x-form.input id="filter-price-min" name="price_min" type="number" placeholder="Min" />
                <x-form.input id="filter-price-max" name="price_max" type="number" placeholder="Max" />
            </div>
        </div>
        <div>
            <label class="block text-xs font-semibold text-[color:var(--color-text-secondary)] mb-2">Sort By</label>
            <x-form.select id="filter-sort" name="sort">
                <option value="newest">Newest Arrivals</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
            </x-form.select>
        </div>
    </div>
    <x-slot:footer>
        <button 
            onclick="window.closeDrawer('drawer-filters')"
            class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] hover:bg-[color:var(--color-surface-secondary)] rounded-lg transition-colors border border-[color:var(--color-border)]"
        >
            Reset Filters
        </button>
        <button 
            onclick="window.closeDrawer('drawer-filters')"
            class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] rounded-lg transition-colors"
        >
            Apply Filters
        </button>
    </x-slot>
</x-drawer>
HTML;

                                    $drawerLeftCode = <<<'HTML'
<button 
    onclick="window.openDrawer('drawer-nav')"
    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
>
    Open Left Side Navigation Drawer
</button>

<x-drawer id="drawer-nav" title="Navigation Menu" placement="left" size="sm">
    <div class="space-y-4">
        <p class="text-xs text-[color:var(--color-text-muted)]">This side drawer panel slides out from the left edge.</p>
        <nav class="flex flex-col gap-2">
            <a href="#" class="px-4 py-2.5 rounded-lg text-sm font-medium text-[color:var(--color-primary-700)] bg-[color:var(--color-primary-50)]">Dashboard</a>
            <a href="#" class="px-4 py-2.5 rounded-lg text-sm font-medium text-[color:var(--color-text-secondary)] hover:bg-[color:var(--color-surface-secondary)]">Orders</a>
            <a href="#" class="px-4 py-2.5 rounded-lg text-sm font-medium text-[color:var(--color-text-secondary)] hover:bg-[color:var(--color-surface-secondary)]">Products</a>
            <a href="#" class="px-4 py-2.5 rounded-lg text-sm font-medium text-[color:var(--color-text-secondary)] hover:bg-[color:var(--color-surface-secondary)]">Analytics</a>
            <a href="#" class="px-4 py-2.5 rounded-lg text-sm font-medium text-[color:var(--color-text-secondary)] hover:bg-[color:var(--color-surface-secondary)]">Settings</a>
        </nav>
    </div>
</x-drawer>
HTML;

                                    $drawerSizesCode = <<<'HTML'
<div class="flex flex-wrap gap-3">
    <button onclick="window.openDrawer('drawer-size-sm')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Small (sm)</button>
    <button onclick="window.openDrawer('drawer-size-md')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Medium (md)</button>
    <button onclick="window.openDrawer('drawer-size-lg')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Large (lg)</button>
    <button onclick="window.openDrawer('drawer-size-xl')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Extra Large (xl)</button>
    <button onclick="window.openDrawer('drawer-size-2xl')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">2xl</button>
    <button onclick="window.openDrawer('drawer-size-full')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Full Screen</button>
</div>

<x-drawer id="drawer-size-sm" title="Small Side Drawer" size="sm">
    <p>This is a small 384px width drawer.</p>
</x-drawer>
<x-drawer id="drawer-size-md" title="Medium Side Drawer" size="md">
    <p>This is a medium 448px width drawer.</p>
</x-drawer>
<x-drawer id="drawer-size-lg" title="Large Side Drawer" size="lg">
    <p>This is a large 512px width drawer.</p>
</x-drawer>
<x-drawer id="drawer-size-xl" title="Extra Large Side Drawer" size="xl">
    <p>This is an extra large 576px width drawer.</p>
</x-drawer>
<x-drawer id="drawer-size-2xl" title="2XL Side Drawer" size="2xl">
    <p>This is a 2xl 672px width drawer.</p>
</x-drawer>
<x-drawer id="drawer-size-full" title="Full Width Drawer" size="full">
    <div class="flex flex-col justify-between h-full">
        <p>This is a full width overlay drawer.</p>
        <button onclick="window.closeDrawer('drawer-size-full')" class="px-4 py-2 bg-[color:var(--color-primary-600)] text-white rounded-lg self-start">Close Full Drawer</button>
    </div>
</x-drawer>
HTML;

                                    $drawerScrollCode = <<<'HTML'
<button 
    onclick="window.openDrawer('drawer-scroll')"
    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
>
    Open Scrollable Drawer
</button>

<x-drawer id="drawer-scroll" title="System Event Logs" size="lg">
    <div class="space-y-4">
        <p class="text-xs text-[color:var(--color-text-muted)]">This demonstrates the sticky header/footer layout. Scroll the logs to test.</p>
        @for($i = 1; $i <= 30; $i++)
            <div class="flex items-start gap-3 p-3 rounded-lg border bg-[color:var(--color-neutral-50)] text-xs">
                <span class="px-1.5 py-0.5 rounded bg-[color:var(--color-neutral-200)] font-mono text-[10px] text-[color:var(--color-text-muted)]">14:02:{{ sprintf('%02d', $i) }}</span>
                <div>
                    <p class="font-semibold text-[color:var(--color-text-primary)]">User auth event log #{{ $i }}</p>
                    <p class="text-[color:var(--color-text-muted)] mt-0.5">Calculated layout scroll position, active overlay index initialized.</p>
                </div>
            </div>
        @endfor
    </div>
    <x-slot:footer>
        <button onclick="window.closeDrawer('drawer-scroll')" class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] rounded-lg w-full">Dismiss Logs</button>
    </x-slot>
</x-drawer>
HTML;

                                    $drawerPersistentCode = <<<'HTML'
<button 
    onclick="window.openDrawer('drawer-persistent-a')"
    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
>
    Open Stacked Persistent Drawer
</button>

<!-- Drawer A: Persistent -->
<x-drawer id="drawer-persistent-a" title="Drawer A (Persistent)" :persistent="true" size="lg">
    <div class="space-y-4">
        <p>This drawer is persistent and cannot be closed by Escape or clicking the backdrop overlay.</p>
        <p class="text-xs text-[color:var(--color-text-muted)]">You can click below to launch a stacked modal on top of it. Escape will close ONLY that modal first.</p>
        <button 
            onclick="window.openModal('modal-confirm-drawer')"
            class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] rounded-lg"
        >
            Launch Confirmation Modal
        </button>
    </div>
    <x-slot:footer>
        <button onclick="window.closeDrawer('drawer-persistent-a')" class="px-4 py-2 text-sm border rounded-lg bg-white">Cancel & Close</button>
    </x-slot>
</x-drawer>

<!-- Stacked Modal -->
<x-modal id="modal-confirm-drawer" title="Confirm Closure" size="sm">
    <p>Are you sure you want to dismiss the stacked drawer overlay?</p>
    <x-slot:footer>
        <button onclick="window.closeModal('modal-confirm-drawer')" class="px-4 py-2 text-sm border rounded-lg bg-white">Go Back</button>
        <button 
            onclick="window.closeModal('modal-confirm-drawer'); window.closeDrawer('drawer-persistent-a')" 
            class="px-4 py-2 text-sm text-white bg-[color:var(--color-danger-600)] rounded-lg"
        >
            Yes, Close All
        </button>
    </x-slot>
</x-modal>
HTML;
                                @endphp

                                <section id="drawer" class="js-section mb-12">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Drawer</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            Premium slide-out side panel overlay screens managed by Alpine.js. Supports left/right edge transitions, scrollable body segments, stacked overlay depth resolution, and keyboard focus trap.
                                        </p>
                                    </div>

                                    <div class="space-y-8">
                                        <!-- Basic Right Drawer -->
                                        <x-showcase.preview title="Standard Drawer (Right Placement)" :code="$drawerBasicCode" id="preview-drawer-basic">
                                            <div class="flex items-center gap-3">
                                                <button 
                                                    onclick="window.openDrawer('drawer-filters')"
                                                    class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] rounded-lg hover:bg-[color:var(--color-primary-700)] transition-colors"
                                                >
                                                    Open Filter Drawer
                                                </button>
                                                
                                                <x-drawer id="drawer-filters" title="Filter Products" size="md">
                                                    <div class="space-y-4">
                                                        <div>
                                                            <label class="block text-xs font-semibold text-[color:var(--color-text-secondary)] mb-2">Category</label>
                                                            <x-form.select id="filter-category" name="category">
                                                                <option value="">All Categories</option>
                                                                <option value="electronics">Electronics</option>
                                                                <option value="apparel">Apparel</option>
                                                                <option value="home">Home & Kitchen</option>
                                                            </x-form.select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-semibold text-[color:var(--color-text-secondary)] mb-2">Price Range</label>
                                                            <div class="grid grid-cols-2 gap-2">
                                                                <x-form.input id="filter-price-min" name="price_min" type="number" placeholder="Min" />
                                                                <x-form.input id="filter-price-max" name="price_max" type="number" placeholder="Max" />
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-semibold text-[color:var(--color-text-secondary)] mb-2">Sort By</label>
                                                            <x-form.select id="filter-sort" name="sort">
                                                                <option value="newest">Newest Arrivals</option>
                                                                <option value="price_asc">Price: Low to High</option>
                                                                <option value="price_desc">Price: High to Low</option>
                                                            </x-form.select>
                                                        </div>
                                                    </div>
                                                    <x-slot:footer>
                                                        <button 
                                                            onclick="window.closeDrawer('drawer-filters')"
                                                            class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] hover:bg-[color:var(--color-surface-secondary)] rounded-lg transition-colors border border-[color:var(--color-border)]"
                                                        >
                                                            Reset Filters
                                                        </button>
                                                        <button 
                                                            onclick="window.closeDrawer('drawer-filters')"
                                                            class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] rounded-lg transition-colors"
                                                        >
                                                            Apply Filters
                                                        </button>
                                                    </x-slot>
                                                </x-drawer>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Left Side Drawer -->
                                        <x-showcase.preview title="Left Placement Navigation Drawer" :code="$drawerLeftCode" id="preview-drawer-left">
                                            <div>
                                                <button 
                                                    onclick="window.openDrawer('drawer-nav')"
                                                    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
                                                >
                                                    Open Left Side Navigation Drawer
                                                </button>

                                                <x-drawer id="drawer-nav" title="Navigation Menu" placement="left" size="sm">
                                                    <div class="space-y-4">
                                                        <p class="text-xs text-[color:var(--color-text-muted)]">This side drawer panel slides out from the left edge.</p>
                                                        <nav class="flex flex-col gap-2">
                                                            <a href="#" class="px-4 py-2.5 rounded-lg text-sm font-medium text-[color:var(--color-primary-700)] bg-[color:var(--color-primary-50)]">Dashboard</a>
                                                            <a href="#" class="px-4 py-2.5 rounded-lg text-sm font-medium text-[color:var(--color-text-secondary)] hover:bg-[color:var(--color-surface-secondary)]">Orders</a>
                                                            <a href="#" class="px-4 py-2.5 rounded-lg text-sm font-medium text-[color:var(--color-text-secondary)] hover:bg-[color:var(--color-surface-secondary)]">Products</a>
                                                            <a href="#" class="px-4 py-2.5 rounded-lg text-sm font-medium text-[color:var(--color-text-secondary)] hover:bg-[color:var(--color-surface-secondary)]">Analytics</a>
                                                            <a href="#" class="px-4 py-2.5 rounded-lg text-sm font-medium text-[color:var(--color-text-secondary)] hover:bg-[color:var(--color-surface-secondary)]">Settings</a>
                                                        </nav>
                                                    </div>
                                                </x-drawer>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Dimensions Presets -->
                                        <x-showcase.preview title="Dimension Presets (sm, md, lg, xl, 2xl, full)" :code="$drawerSizesCode" id="preview-drawer-sizes">
                                            <div>
                                                <div class="flex flex-wrap gap-3">
                                                    <button onclick="window.openDrawer('drawer-size-sm')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Small (sm)</button>
                                                    <button onclick="window.openDrawer('drawer-size-md')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Medium (md)</button>
                                                    <button onclick="window.openDrawer('drawer-size-lg')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Large (lg)</button>
                                                    <button onclick="window.openDrawer('drawer-size-xl')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Extra Large (xl)</button>
                                                    <button onclick="window.openDrawer('drawer-size-2xl')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">2xl</button>
                                                    <button onclick="window.openDrawer('drawer-size-full')" class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition">Full Screen</button>
                                                </div>

                                                <x-drawer id="drawer-size-sm" title="Small Side Drawer" size="sm">
                                                    <p>This is a small 384px width drawer.</p>
                                                </x-drawer>
                                                <x-drawer id="drawer-size-md" title="Medium Side Drawer" size="md">
                                                    <p>This is a medium 448px width drawer.</p>
                                                </x-drawer>
                                                <x-drawer id="drawer-size-lg" title="Large Side Drawer" size="lg">
                                                    <p>This is a large 512px width drawer.</p>
                                                </x-drawer>
                                                <x-drawer id="drawer-size-xl" title="Extra Large Side Drawer" size="xl">
                                                    <p>This is an extra large 576px width drawer.</p>
                                                </x-drawer>
                                                <x-drawer id="drawer-size-2xl" title="2XL Side Drawer" size="2xl">
                                                    <p>This is a 2xl 672px width drawer.</p>
                                                </x-drawer>
                                                <x-drawer id="drawer-size-full" title="Full Width Drawer" size="full">
                                                    <div class="flex flex-col justify-between h-full p-6">
                                                        <p class="text-base font-semibold">This is a full width overlay drawer.</p>
                                                        <button onclick="window.closeDrawer('drawer-size-full')" class="mt-8 px-4 py-2 bg-[color:var(--color-primary-600)] text-white rounded-lg self-start">Close Full Drawer</button>
                                                    </div>
                                                </x-drawer>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Sticky Layout & Scrollable content -->
                                        <x-showcase.preview title="Sticky Layout with Scrollable Content" :code="$drawerScrollCode" id="preview-drawer-scroll">
                                            <div>
                                                <button 
                                                    onclick="window.openDrawer('drawer-scroll')"
                                                    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
                                                >
                                                    Open Scrollable Drawer
                                                </button>

                                                <x-drawer id="drawer-scroll" title="System Event Logs" size="lg">
                                                    <div class="space-y-4">
                                                        <p class="text-xs text-[color:var(--color-text-muted)]">This demonstrates the sticky header/footer layout. Scroll the logs to test.</p>
                                                        @for($i = 1; $i <= 30; $i++)
                                                            <div class="flex items-start gap-3 p-3 rounded-lg border bg-[color:var(--color-neutral-50)] text-xs">
                                                                <span class="px-1.5 py-0.5 rounded bg-[color:var(--color-neutral-200)] font-mono text-[10px] text-[color:var(--color-text-muted)]">14:02:{{ sprintf('%02d', $i) }}</span>
                                                                <div>
                                                                    <p class="font-semibold text-[color:var(--color-text-primary)]">User auth event log #{{ $i }}</p>
                                                                    <p class="text-[color:var(--color-text-muted)] mt-0.5">Calculated layout scroll position, active overlay index initialized.</p>
                                                                </div>
                                                            </div>
                                                        @endfor
                                                    </div>
                                                    <x-slot:footer>
                                                        <button onclick="window.closeDrawer('drawer-scroll')" class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] rounded-lg w-full">Dismiss Logs</button>
                                                    </x-slot>
                                                </x-drawer>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Stacked / Persistent Drawer -->
                                        <x-showcase.preview title="Persistent Mode & Stacked Overlays" :code="$drawerPersistentCode" id="preview-drawer-persistent">
                                            <div>
                                                <button 
                                                    onclick="window.openDrawer('drawer-persistent-a')"
                                                    class="px-4 py-2 text-sm font-semibold text-[color:var(--color-text-secondary)] border border-[color:var(--color-border)] rounded-lg hover:bg-[color:var(--color-surface-secondary)] transition-colors bg-white"
                                                >
                                                    Open Stacked Persistent Drawer
                                                </button>

                                                <!-- Drawer A: Persistent -->
                                                <x-drawer id="drawer-persistent-a" title="Drawer A (Persistent)" :persistent="true" size="lg">
                                                    <div class="space-y-4">
                                                        <p>This drawer is persistent and cannot be closed by Escape or clicking the backdrop overlay.</p>
                                                        <p class="text-xs text-[color:var(--color-text-muted)]">You can click below to launch a stacked modal on top of it. Escape will close ONLY that modal first.</p>
                                                        <button 
                                                            onclick="window.openModal('modal-confirm-drawer')"
                                                            class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] rounded-lg"
                                                        >
                                                            Launch Confirmation Modal
                                                        </button>
                                                    </div>
                                                    <x-slot:footer>
                                                        <button onclick="window.closeDrawer('drawer-persistent-a')" class="px-4 py-2 text-sm border rounded-lg bg-white text-[color:var(--color-text-secondary)]">Cancel & Close</button>
                                                    </x-slot>
                                                </x-drawer>

                                                <!-- Stacked Modal -->
                                                <x-modal id="modal-confirm-drawer" title="Confirm Closure" size="sm">
                                                    <p>Are you sure you want to dismiss the stacked drawer overlay?</p>
                                                    <x-slot:footer>
                                                        <button onclick="window.closeModal('modal-confirm-drawer')" class="px-4 py-2 text-sm border rounded-lg bg-white text-[color:var(--color-text-secondary)]">Go Back</button>
                                                        <button 
                                                            onclick="window.closeModal('modal-confirm-drawer'); window.closeDrawer('drawer-persistent-a')" 
                                                            class="px-4 py-2 text-sm text-white bg-[color:var(--color-danger-600)] rounded-lg"
                                                        >
                                                            Yes, Close All
                                                        </button>
                                                    </x-slot>
                                                </x-modal>
                                            </div>
                                        </x-showcase.preview>
                                    </div>
                                </section>
                            @endif

                            {{-- Toast --}}
                            @if(isset($categories['Feedback']['Toast']))
                                @php
                                    $toastBasicCode = <<<'HTML'
<div class="flex flex-wrap gap-3">
    <button 
        onclick="window.toast({ message: 'Success! Your settings have been saved.', type: 'success' })" 
        class="px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition"
    >
        Trigger Success Toast
    </button>
    <button 
        onclick="window.toast({ message: 'An error occurred while saving user data.', type: 'danger' })" 
        class="px-4 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition"
    >
        Trigger Error Toast
    </button>
    <button 
        onclick="window.toast({ message: 'Warning: Storage quota is reaching 90% capacity.', type: 'warning' })" 
        class="px-4 py-2 text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-lg transition"
    >
        Trigger Warning Toast
    </button>
    <button 
        onclick="window.toast({ message: 'Info: System will undergo scheduled maintenance at 02:00 AM.', type: 'info' })" 
        class="px-4 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg transition"
    >
        Trigger Info Toast
    </button>
</div>
HTML;

                                    $toastDurationsCode = <<<'HTML'
<div class="flex flex-wrap gap-3">
    <button 
        onclick="window.toast({ message: 'This notification will dismiss in 2 seconds.', duration: 2000 })" 
        class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition"
    >
        2-Second Fast Toast
    </button>
    <button 
        onclick="window.toast({ message: 'This is a persistent alert. You must manually dismiss it.', duration: 0, type: 'warning' })" 
        class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition"
    >
        Persistent Toast (duration: 0)
    </button>
    <button 
        onclick="window.toast.clear()" 
        class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 transition"
    >
        Clear All Toasts
    </button>
</div>
HTML;
                                @endphp

                                <section id="toast" class="js-section mb-12">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Toast Notification</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            Premium stacked toast alerts managed by Alpine.js. Supports custom self-dismiss timings, click to dismiss, pause-on-hover countdown indicators, and type variants.
                                        </p>
                                    </div>

                                    <div class="space-y-8">
                                        <!-- Basic Status Triggers -->
                                        <x-showcase.preview title="Status Type Triggers" :code="$toastBasicCode" id="preview-toast-basic">
                                            <div class="flex flex-wrap gap-3">
                                                <button 
                                                    onclick="window.toast({ message: 'Success! Your settings have been saved.', type: 'success' })" 
                                                    class="px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition"
                                                >
                                                    Trigger Success Toast
                                                </button>
                                                <button 
                                                    onclick="window.toast({ message: 'An error occurred while saving user data.', type: 'danger' })" 
                                                    class="px-4 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition"
                                                >
                                                    Trigger Error Toast
                                                </button>
                                                <button 
                                                    onclick="window.toast({ message: 'Warning: Storage quota is reaching 90% capacity.', type: 'warning' })" 
                                                    class="px-4 py-2 text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-lg transition"
                                                >
                                                    Trigger Warning Toast
                                                </button>
                                                <button 
                                                    onclick="window.toast({ message: 'Info: System will undergo scheduled maintenance at 02:00 AM.', type: 'info' })" 
                                                    class="px-4 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg transition"
                                                >
                                                    Trigger Info Toast
                                                </button>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Custom Durations -->
                                        <x-showcase.preview title="Custom Durations & Controls" :code="$toastDurationsCode" id="preview-toast-durations">
                                            <div class="flex flex-wrap gap-3">
                                                <button 
                                                    onclick="window.toast({ message: 'This notification will dismiss in 2 seconds.', duration: 2000 })" 
                                                    class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition"
                                                >
                                                    2-Second Fast Toast
                                                </button>
                                                <button 
                                                    onclick="window.toast({ message: 'This is a persistent alert. You must manually dismiss it.', duration: 0, type: 'warning' })" 
                                                    class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-white hover:bg-neutral-50 transition"
                                                >
                                                    Persistent Toast (duration: 0)
                                                </button>
                                                <button 
                                                    onclick="window.toast.clear()" 
                                                    class="px-3 py-1.5 text-xs font-semibold border rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 transition"
                                                >
                                                    Clear All Toasts
                                                </button>
                                            </div>
                                        </x-showcase.preview>
                                    </div>
                                </section>
                            @endif

                            {{-- Skeleton --}}
                            @if(isset($categories['Feedback']['Skeleton']))
                                @php
                                    $skeletonBasicCode = <<<'HTML'
<!-- Line variant (default) -->
<x-skeleton variant="line" class="mb-4" />

<!-- Circle variant (avatar size override) -->
<x-skeleton variant="circle" size="md" class="mb-4" />

<!-- Block variant (standard rectangle) -->
<x-skeleton variant="block" class="h-16 rounded-lg mb-4" />

<!-- Custom raw CSS dimensions (inline-style overrides Tailwind) -->
<x-skeleton width="12rem" height="32px" rounded="lg" class="mb-4" />

<!-- Accept numeric input (automatically converts to px) -->
<x-skeleton width="240" height="24" rounded="md" />
HTML;

                                    $skeletonAnimationsCode = <<<'HTML'
<!-- Shimmer (Default) -->
<x-skeleton variant="line" animate="shimmer" class="mb-4" />

<!-- Pulse (Opacity transitions) -->
<x-skeleton variant="line" animate="pulse" class="mb-4" />

<!-- Static (Solid color) -->
<x-skeleton variant="line" animate="static" />
HTML;

                                    $skeletonCompositeCode = <<<'HTML'
<!-- Avatar preset (standard circle profile icon) -->
<x-skeleton.avatar class="mb-4" />
<x-skeleton.avatar size="3.5rem" class="mb-4" />

<!-- Image preset (aspect ratio mapping: video, square, portrait, auto) -->
<x-skeleton.image aspect="video" class="rounded-lg mb-4" />
<x-skeleton.image aspect="portrait" class="w-32 rounded-lg mb-4" />
<x-skeleton.image aspect="square" class="w-16 rounded-lg" />
HTML;

                                    $skeletonComplexCode = <<<'HTML'
<!-- Stats placeholder card (toggleable icon) -->
<x-skeleton.stats :icon="true" class="mb-6" />

<!-- Single Card (Vertical orientation) -->
<x-skeleton.card layout="vertical" class="max-w-xs mb-6" />

<!-- Grid Card Repetitions (layout classes forwarded directly) -->
<x-skeleton.card count="3" layout="vertical" class="grid-cols-1 md:grid-cols-3 mb-6" />

<!-- List items feed (repetition + dividers) -->
<x-skeleton.list items="3" :divided="true" />
HTML;

                                    $skeletonTableCode = <<<'HTML'
<!-- Programmatic Table Builder (dynamic rows, columns, and header toggles) -->
<x-skeleton.table rows="4" columns="5" :header="true" />
HTML;

                                    $skeletonConditionalCode = <<<'HTML'
<!-- 1. Real Blade conditional state loading -->
<x-skeleton :loading="true" class="w-full h-12 rounded-lg">
    <div class="p-3 bg-emerald-50 text-emerald-800 rounded-lg">
        This actual content is hidden because loading is true.
    </div>
</x-skeleton>

<x-skeleton :loading="false" class="w-full h-12 rounded-lg mt-4">
    <div class="p-4 bg-emerald-50 text-emerald-800 rounded-lg border border-emerald-200 font-semibold">
        This actual content is visible because loading is false!
    </div>
</x-skeleton>
HTML;
                                @endphp

                                <section id="skeleton" class="js-section mb-12">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Skeleton Loader</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            A fluid loading placeholder system supporting pulsing or shimmer animations, shapes, customization hooks, and prebuilt composite elements.
                                        </p>
                                    </div>

                                    <div class="space-y-12">
                                        <!-- Base Shapes -->
                                        <x-showcase.preview title="Base Shapes & Sizes" :code="$skeletonBasicCode" id="preview-skeleton-basic">
                                            <div class="w-full max-w-sm space-y-4">
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-1 block">Line (Default)</span>
                                                    <x-skeleton variant="line" />
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-1 block">Circle</span>
                                                    <x-skeleton variant="circle" />
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-1 block">Block (Default size)</span>
                                                    <x-skeleton variant="block" class="h-16 rounded-lg" />
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-1 block">Custom Dimension (width="12rem" height="32px")</span>
                                                    <x-skeleton width="12rem" height="32px" rounded="lg" />
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-1 block">Accept Numeric (width="240" height="24")</span>
                                                    <x-skeleton width="240" height="24" rounded="md" />
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Animations -->
                                        <x-showcase.preview title="Animation Modes" :code="$skeletonAnimationsCode" id="preview-skeleton-animations">
                                            <div class="w-full max-w-sm space-y-4">
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-1 block">Shimmer (GPU-Accelerated)</span>
                                                    <x-skeleton variant="line" animate="shimmer" />
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-1 block">Pulse (Tailwind Opacity)</span>
                                                    <x-skeleton variant="line" animate="pulse" />
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-1 block">Static (Solid Background)</span>
                                                    <x-skeleton variant="line" animate="static" />
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Presets -->
                                        <x-showcase.preview title="Media & Avatar Presets" :code="$skeletonCompositeCode" id="preview-skeleton-presets">
                                            <div class="w-full max-w-md space-y-6">
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">Avatar Presets</span>
                                                    <div class="flex items-center gap-3">
                                                        <x-skeleton.avatar />
                                                        <x-skeleton.avatar size="3rem" />
                                                        <x-skeleton.avatar size="3.5rem" />
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">Image Presets (Aspect Ratios)</span>
                                                    <div class="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <span class="text-xs text-[color:var(--color-text-muted)] mb-1 block">Video aspect (16/9)</span>
                                                            <x-skeleton.image aspect="video" class="rounded-lg" />
                                                        </div>
                                                        <div>
                                                            <span class="text-xs text-[color:var(--color-text-muted)] mb-1 block">Square aspect (1/1)</span>
                                                            <x-skeleton.image aspect="square" class="rounded-lg w-28 mx-auto" />
                                                        </div>
                                                        <div>
                                                            <span class="text-xs text-[color:var(--color-text-muted)] mb-1 block">Portrait aspect (3/4)</span>
                                                            <x-skeleton.image aspect="portrait" class="rounded-lg w-24" />
                                                        </div>
                                                        <div>
                                                            <span class="text-xs text-[color:var(--color-text-muted)] mb-1 block">Auto aspect</span>
                                                            <x-skeleton.image aspect="auto" class="rounded-lg h-24 w-full" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Card & List Composites -->
                                        <x-showcase.preview title="Card & List Layouts" :code="$skeletonComplexCode" id="preview-skeleton-composites">
                                            <div class="w-full space-y-8">
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">Stats KPI Card Preset</span>
                                                    <x-skeleton.stats :icon="true" class="max-w-sm" />
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">Card Layouts</span>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                        <div>
                                                            <span class="text-xs text-[color:var(--color-text-muted)] mb-2 block">Vertical Card</span>
                                                            <x-skeleton.card layout="vertical" />
                                                        </div>
                                                        <div>
                                                            <span class="text-xs text-[color:var(--color-text-muted)] mb-2 block">Horizontal Card</span>
                                                            <x-skeleton.card layout="horizontal" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">Grid Cards Repeater (count="3" with custom classes)</span>
                                                    <x-skeleton.card count="3" layout="vertical" class="grid-cols-1 sm:grid-cols-3" />
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">List Feed Layout</span>
                                                    <x-skeleton.list items="3" :divided="true" />
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Table Builder -->
                                        <x-showcase.preview title="Table Grid Builder" :code="$skeletonTableCode" id="preview-skeleton-table">
                                            <div class="w-full space-y-6">
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">Table rows=3, columns=4</span>
                                                    <x-skeleton.table rows="3" columns="4" :header="true" />
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">Table body-only (header=false)</span>
                                                    <x-skeleton.table rows="3" columns="3" :header="false" />
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Conditional Loading wrapper -->
                                        <x-showcase.preview title="Conditional Loading & Slot API" :code="$skeletonConditionalCode" id="preview-skeleton-conditional">
                                            <div class="w-full space-y-8">
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">Static Blade Compilation States</span>
                                                    <div class="space-y-4 max-w-xl">
                                                        <div>
                                                            <span class="text-[11px] text-[color:var(--color-text-muted)] block mb-1">State: :loading="true"</span>
                                                            <x-skeleton :loading="true" class="w-full h-12 rounded-lg">
                                                                <div class="p-3 bg-emerald-50 text-emerald-800 rounded-lg">
                                                                    Hidden content.
                                                                </div>
                                                            </x-skeleton>
                                                        </div>
                                                        <div>
                                                            <span class="text-[11px] text-[color:var(--color-text-muted)] block mb-1">State: :loading="false"</span>
                                                            <x-skeleton :loading="false" class="w-full h-12 rounded-lg">
                                                                <div class="p-4 bg-emerald-50 text-emerald-800 rounded-lg border border-emerald-200 flex items-center gap-2">
                                                                    <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                                    <span>Loaded data rendered successfully!</span>
                                                                </div>
                                                            </x-skeleton>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Live Interactive Sandbox -->
                                                <div x-data="{ loading: true }" class="p-6 bg-[color:var(--color-neutral-100)] rounded-xl border border-[color:var(--color-border)]">
                                                    <span class="text-xs font-bold uppercase tracking-wider text-[color:var(--color-text-muted)] block mb-4">Interactive Sandbox</span>
                                                    <div class="flex items-center gap-3 mb-6">
                                                        <button 
                                                            @click="loading = !loading" 
                                                            class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] active:scale-95 rounded-lg transition"
                                                            x-text="loading ? 'Simulate Loaded State' : 'Simulate Loading State'"
                                                        ></button>
                                                        <span class="text-xs text-[color:var(--color-text-muted)] font-mono" x-text="loading ? 'Status: LOADING (aria-busy=true)' : 'Status: READY (aria-busy=false)'"></span>
                                                    </div>

                                                    <!-- Transition Container -->
                                                    <div class="p-6 bg-white dark:bg-[color:var(--color-neutral-900)] border border-[color:var(--color-border)] rounded-xl transition duration-300">
                                                        <!-- Placeholder Loader visible when loading -->
                                                        <div x-show="loading" class="space-y-4">
                                                            <div class="flex items-center gap-3">
                                                                <x-skeleton.avatar />
                                                                <div class="space-y-2 flex-1">
                                                                    <x-skeleton variant="line" class="w-1/3" />
                                                                    <x-skeleton variant="line" class="w-1/4" />
                                                                </div>
                                                            </div>
                                                            <x-skeleton.text :rows="3" />
                                                        </div>

                                                        <!-- Actual Content visible when finished -->
                                                        <div x-show="!loading" x-cloak class="flex items-start gap-4">
                                                            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=96&h=96&q=80" class="w-10 h-10 rounded-full shrink-0 object-cover border border-[color:var(--color-border)]" alt="User avatar">
                                                            <div class="flex-1 space-y-2">
                                                                <div class="flex items-center justify-between">
                                                                    <h4 class="font-bold text-base text-[color:var(--color-text-primary)]">Saurav Sharma</h4>
                                                                    <span class="text-xs px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full font-semibold">Active User</span>
                                                                </div>
                                                                <p class="text-sm text-[color:var(--color-text-muted)] leading-relaxed">
                                                                    Pair programming with the AI assistant on building the customizable SkeletonLoader system. This is a real loaded component with native layout elements.
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </x-showcase.preview>
                                    </div>
                                </section>
                            @endif
                        </div>
                    @endif

                </div>
            </div>
        </main>
    </div>

    <!-- Engine Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            
            // 1. Theme Toggles
            document.querySelectorAll('.js-theme-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const showcase = btn.closest('.showcase-component');
                    const wrapper = showcase.querySelector('.js-preview-wrapper');
                    const theme = btn.dataset.theme;
                    
                    if (theme === 'dark') {
                        wrapper.classList.add('dark');
                    } else {
                        wrapper.classList.remove('dark');
                    }
                    
                    showcase.querySelectorAll('.js-theme-btn').forEach(b => {
                        b.setAttribute('aria-pressed', b.dataset.theme === theme ? 'true' : 'false');
                    });
                });
            });

            // 2. Viewport Toggles
            document.querySelectorAll('.js-viewport-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const showcase = btn.closest('.showcase-component');
                    const wrapper = showcase.querySelector('.js-preview-wrapper');
                    const viewport = btn.dataset.viewport;
                    const targetWidthClass = btn.dataset.width;
                    
                    const allWidthClasses = Array.from(showcase.querySelectorAll('.js-viewport-btn')).map(b => b.dataset.width);
                    wrapper.classList.remove(...allWidthClasses);
                    
                    if (targetWidthClass) {
                        wrapper.classList.add(targetWidthClass);
                    }
                    
                    showcase.querySelectorAll('.js-viewport-btn').forEach(b => {
                        b.setAttribute('aria-pressed', b.dataset.viewport === viewport ? 'true' : 'false');
                    });
                });
            });

            // 3. Copy to Clipboard
            document.querySelectorAll('.js-copy-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const codeScript = btn.querySelector('.js-code-template');
                    if (!codeScript) return;
                    
                    const codeText = codeScript.textContent;
                    
                    try {
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            await navigator.clipboard.writeText(codeText);
                        } else {
                            const textarea = document.createElement('textarea');
                            textarea.value = codeText;
                            textarea.style.position = 'fixed';
                            textarea.style.opacity = '0';
                            document.body.appendChild(textarea);
                            textarea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textarea);
                        }
                        
                        const iconCopy = btn.querySelector('.js-copy-icon');
                        const iconCheck = btn.querySelector('.js-check-icon');
                        const textSpan = btn.querySelector('.js-copy-text');
                        
                        iconCopy.classList.add('hidden');
                        iconCheck.classList.remove('hidden');
                        textSpan.textContent = 'Copied ✓';
                        
                        setTimeout(() => {
                            iconCopy.classList.remove('hidden');
                            iconCheck.classList.add('hidden');
                            textSpan.textContent = 'Copy Code';
                        }, 2000);
                        
                    } catch (err) {
                        console.error('Failed to copy text: ', err);
                    }
                });
            });

            // 4. Intersection Observer for Sidebar
            const sections = document.querySelectorAll('.js-section');
            const navLinks = document.querySelectorAll('.js-nav-link');
            
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const id = entry.target.getAttribute('id');
                            navLinks.forEach(link => {
                                if (link.dataset.target === id) {
                                    link.classList.add('bg-[color:var(--color-primary-50)]', 'text-[color:var(--color-primary-700)]');
                                    link.classList.remove('text-[color:var(--color-neutral-600)]');
                                } else {
                                    link.classList.remove('bg-[color:var(--color-primary-50)]', 'text-[color:var(--color-primary-700)]');
                                    link.classList.add('text-[color:var(--color-neutral-600)]');
                                }
                            });
                        }
                    });
                }, { rootMargin: '-20% 0px -80% 0px' });
                
                sections.forEach(section => observer.observe(section));
            }
            
        });
    </script>
    <x-toast />
</body>
</html>
