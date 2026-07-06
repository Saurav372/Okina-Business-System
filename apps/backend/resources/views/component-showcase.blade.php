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
</body>
</html>
