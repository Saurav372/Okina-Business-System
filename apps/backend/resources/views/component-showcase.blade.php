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
