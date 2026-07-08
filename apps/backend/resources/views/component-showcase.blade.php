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

    <div x-data="{ 
        search: '',
        filterShowcase() {
            const query = this.search.toLowerCase().trim();
            document.querySelectorAll('.js-section, .showcase-component').forEach(el => {
                const text = el.getAttribute('data-search') ? el.getAttribute('data-search').toLowerCase() : el.textContent.toLowerCase();
                if (query === '' || text.includes(query)) {
                    el.style.display = '';
                } else {
                    el.style.display = 'none';
                }
            });
        },
        copyToClipboard(text, context) {
            navigator.clipboard.writeText(text).then(() => {
                window.toast('Copied ' + context + ': ' + text);
            }).catch(() => {
                window.toast('Failed to copy to clipboard');
            });
        }
    }" class="flex flex-col lg:flex-row min-h-screen">
        
        <!-- Sidebar Navigation -->
        <aside class="lg:w-64 shrink-0 border-r border-[color:var(--color-border)] bg-white lg:sticky lg:top-0 lg:h-screen overflow-y-auto z-10 hidden lg:block">
            <div class="p-6">
                <h1 class="text-xl font-bold text-[color:var(--color-primary-600)] flex items-center gap-2">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                    Design System
                </h1>
                
                <div class="mt-4">
                    <input type="text" x-model="search" @input="filterShowcase" placeholder="Search system..." class="w-full px-3 py-1.5 text-xs border rounded-lg focus:outline-none focus:ring-1 focus:ring-[color:var(--color-primary-600)] bg-white text-[color:var(--color-text-primary)]">
                </div>
                
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
                    
                    {{-- Brand & Design Tokens --}}
                    @if(isset($categories['Brand & Design Tokens']))
                        <div class="space-y-24">
                            {{-- Colors --}}
                            @if(isset($categories['Brand & Design Tokens']['Colors']))
                                <section id="{{ $categories['Brand & Design Tokens']['Colors'] }}" class="js-section scroll-mt-8">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Colors</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            Unified design system colors. Click to copy CSS variables, hex values, or utility classes.
                                        </p>
                                    </div>

                                    <div class="space-y-12">
                                        @foreach(\App\Presenters\DesignTokenCatalog::colors() as $color)
                                            @php
                                                $searchMeta = strtolower($color['name'] . ' ' . $color['variable'] . ' ' . $color['hex'] . ' ' . implode(' ', $color['aliases']) . ' ' . implode(' ', $color['used_by']) . ' ' . $color['contrast']);
                                            @endphp
                                            <div class="showcase-component space-y-4" data-search="{{ $searchMeta }}">
                                                <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-[color:var(--color-border)] pb-2">
                                                    <div>
                                                        <h3 class="text-base font-bold text-[color:var(--color-text-primary)]">{{ $color['name'] }}</h3>
                                                        <p class="text-xs text-[color:var(--color-text-muted)]">Contrast: {{ $color['contrast'] }}</p>
                                                    </div>
                                                    <div class="mt-2 md:mt-0 flex flex-wrap gap-2 text-[10px] text-[color:var(--color-text-muted)]">
                                                        <span class="font-semibold uppercase text-neutral-400">Used by:</span>
                                                        @foreach($color['used_by'] as $usage)
                                                            <span class="bg-neutral-100 px-2 py-0.5 rounded-md">{{ $usage }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-11 gap-3">
                                                    @foreach($color['shades'] as $shade)
                                                        <div class="flex flex-col gap-1.5 p-2 border border-[color:var(--color-border)] rounded-xl bg-white shadow-xs">
                                                            <div class="w-full h-12 rounded-lg" style="background-color: {{ $shade['hex'] }}"></div>
                                                            <div class="text-[10px] text-center font-bold text-neutral-800">{{ $shade['shade'] }}</div>
                                                            <div class="flex flex-col gap-1">
                                                                <button @click="copyToClipboard('{{ $shade['variable'] }}', 'CSS variable')" class="w-full text-[8px] py-0.5 px-1 bg-neutral-50 hover:bg-neutral-150 rounded text-neutral-500 font-mono overflow-hidden text-ellipsis whitespace-nowrap cursor-pointer" title="Copy CSS variable">📋 Var</button>
                                                                <button @click="copyToClipboard('{{ $shade['hex'] }}', 'hex value')" class="w-full text-[8px] py-0.5 px-1 bg-neutral-50 hover:bg-neutral-150 rounded text-neutral-500 font-mono cursor-pointer" title="Copy Hex">📋 Hex</button>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endif

                            {{-- Typography --}}
                            @if(isset($categories['Brand & Design Tokens']['Typography']))
                                <section id="{{ $categories['Brand & Design Tokens']['Typography'] }}" class="js-section scroll-mt-8">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Typography</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            Fluid and responsive typography scaling scale across device breakpoints.
                                        </p>
                                    </div>

                                    <div class="space-y-8">
                                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 font-medium">
                                            💡 Typography scale uses dynamic clamp calculations. Hover/click to view responsive boundaries.
                                        </div>

                                        <div class="overflow-x-auto border border-[color:var(--color-border)] rounded-2xl bg-white">
                                            <table class="min-w-full divide-y divide-neutral-200 text-xs">
                                                <thead class="bg-neutral-50 text-neutral-400 font-semibold uppercase text-left">
                                                    <tr>
                                                        <th class="p-4">Style</th>
                                                        <th class="p-4">Fluid Clamp Bound / CSS Var</th>
                                                        <th class="p-4">Attributes</th>
                                                        <th class="p-4">Preview</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-neutral-100 text-[color:var(--color-text-secondary)]">
                                                    @foreach(\App\Presenters\DesignTokenCatalog::typography() as $type)
                                                        @php
                                                            $searchMeta = strtolower($type['name'] . ' ' . $type['variable'] . ' ' . $type['weight'] . ' ' . implode(' ', $type['aliases']) . ' ' . implode(' ', $type['used_by']) . ' ' . $type['accessibility']);
                                                        @endphp
                                                        <tr class="showcase-component" data-search="{{ $searchMeta }}">
                                                            <td class="p-4 font-bold text-[color:var(--color-text-primary)]">
                                                                {{ $type['name'] }}
                                                                <div class="text-[10px] text-neutral-400 font-normal mt-1">Used by: {{ implode(', ', $type['used_by']) }}</div>
                                                            </td>
                                                            <td class="p-4 space-y-1 font-mono text-[10px]">
                                                                <div class="flex items-center gap-1.5">
                                                                    <span class="text-neutral-800 font-semibold">{{ $type['variable'] }}</span>
                                                                    <button @click="copyToClipboard('var({{ $type['variable'] }})', 'CSS variable')" class="text-[8px] px-1 py-0.5 bg-neutral-100 rounded text-neutral-500 cursor-pointer">📋 Copy</button>
                                                                </div>
                                                                <div class="text-neutral-400">{{ $type['clamp'] }}</div>
                                                            </td>
                                                            <td class="p-4 space-y-1">
                                                                <div>Weight: <span class="font-semibold">{{ $type['weight'] }}</span></div>
                                                                <div>Line-height: <span class="font-semibold">{{ $type['line_height'] }}</span></div>
                                                                <div class="text-[10px] text-emerald-600 font-medium">A11y: {{ $type['accessibility'] }}</div>
                                                            </td>
                                                            <td class="p-4">
                                                                <div style="font-size: var({{ $type['variable'] }}); font-weight: {{ explode(' ', $type['weight'])[0] }}; line-height: {{ $type['line_height'] }}; letter-spacing: {{ $type['letter_spacing'] }}" class="whitespace-nowrap overflow-hidden text-ellipsis max-w-[200px]">
                                                                    Okina Craft
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </section>
                            @endif

                            {{-- Spacing --}}
                            @if(isset($categories['Brand & Design Tokens']['Spacing']))
                                <section id="{{ $categories['Brand & Design Tokens']['Spacing'] }}" class="js-section scroll-mt-8">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Spacing Scale</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            Layout spacing margins and padding increments. Click variables to copy properties.
                                        </p>
                                    </div>

                                    <div class="border border-[color:var(--color-border)] rounded-2xl bg-white overflow-hidden">
                                        <div class="divide-y divide-neutral-100">
                                            @foreach(\App\Presenters\DesignTokenCatalog::spacing() as $space)
                                                @php
                                                    $searchMeta = strtolower($space['token'] . ' ' . $space['rem'] . ' ' . $space['px'] . ' ' . implode(' ', $space['used_by']));
                                                @endphp
                                                <div class="showcase-component p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4" data-search="{{ $searchMeta }}">
                                                    <div class="w-48 shrink-0 flex items-center justify-between">
                                                        <div>
                                                            <span class="font-bold text-sm text-[color:var(--color-text-primary)]">{{ $space['token'] }}</span>
                                                            <div class="text-[10px] text-neutral-400 mt-0.5">Used: {{ implode(', ', $space['used_by']) }}</div>
                                                        </div>
                                                        <button @click="copyToClipboard('var(--{{ $space['token'] }})', 'CSS variable')" class="text-[9px] px-2 py-1 bg-neutral-100 hover:bg-neutral-150 rounded text-neutral-600 font-medium cursor-pointer">📋 Copy</button>
                                                    </div>
                                                    <div class="flex items-center gap-4 flex-1">
                                                        <span class="w-16 font-mono text-[10px] text-neutral-400 text-right">{{ $space['rem'] }} ({{ $space['px'] }})</span>
                                                        <div class="h-4 bg-[color:var(--color-primary-100)] rounded-md" style="width: {{ max(4, floatval($space['px']) * 2) }}px"></div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </section>
                            @endif

                            {{-- Elevation & Radius --}}
                            @if(isset($categories['Brand & Design Tokens']['Elevation & Radius']))
                                <section id="{{ $categories['Brand & Design Tokens']['Elevation & Radius'] }}" class="js-section scroll-mt-8">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Elevation & Radius</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            Visual shadows and border radiuses. Click classes to copy properties.
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        {{-- Shadows --}}
                                        <div class="space-y-4">
                                            <h3 class="text-sm font-bold uppercase tracking-wider text-neutral-400 border-b pb-2">Box Shadows</h3>
                                            <div class="space-y-4">
                                                @foreach(collect(\App\Presenters\DesignTokenCatalog::elevation())->where('type', 'shadow') as $shadow)
                                                    @php
                                                        $searchMeta = strtolower($shadow['token'] . ' ' . $shadow['value'] . ' ' . implode(' ', $shadow['used_by']));
                                                    @endphp
                                                    <div class="showcase-component p-4 bg-white border border-neutral-100 rounded-xl flex items-center justify-between gap-4" style="box-shadow: {{ $shadow['value'] }}" data-search="{{ $searchMeta }}">
                                                        <div>
                                                            <span class="font-bold text-xs text-[color:var(--color-text-primary)]">{{ $shadow['token'] }}</span>
                                                            <div class="text-[10px] text-neutral-400 mt-0.5">Used: {{ implode(', ', $shadow['used_by']) }}</div>
                                                        </div>
                                                        <button @click="copyToClipboard('{{ $shadow['token'] }}', 'utility class')" class="text-[9px] px-2 py-1 bg-neutral-100 hover:bg-neutral-150 rounded text-neutral-600 font-medium cursor-pointer">📋 Copy</button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- Radius --}}
                                        <div class="space-y-4">
                                            <h3 class="text-sm font-bold uppercase tracking-wider text-neutral-400 border-b pb-2">Border Radiuses</h3>
                                            <div class="space-y-4">
                                                @foreach(collect(\App\Presenters\DesignTokenCatalog::elevation())->where('type', 'radius') as $radius)
                                                    @php
                                                        $searchMeta = strtolower($radius['token'] . ' ' . $radius['value'] . ' ' . implode(' ', $radius['used_by']));
                                                    @endphp
                                                    <div class="showcase-component p-4 bg-white border border-neutral-100 rounded-xl flex items-center justify-between gap-4" data-search="{{ $searchMeta }}">
                                                        <div class="flex items-center gap-4">
                                                            <div class="w-10 h-10 bg-[color:var(--color-primary-500)]" style="border-radius: {{ str_contains($radius['value'], '(') ? explode(' ', $radius['value'])[0] : $radius['value'] }}"></div>
                                                            <div>
                                                                <span class="font-bold text-xs text-[color:var(--color-text-primary)]">{{ $radius['token'] }}</span>
                                                                <div class="text-[10px] text-neutral-400 mt-0.5">Val: {{ $radius['value'] }}</div>
                                                            </div>
                                                        </div>
                                                        <button @click="copyToClipboard('{{ $radius['token'] }}', 'utility class')" class="text-[9px] px-2 py-1 bg-neutral-100 hover:bg-neutral-150 rounded text-neutral-600 font-medium cursor-pointer">📋 Copy</button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            @endif
                        </div>
                    @endif

                    {{-- Motion Patterns --}}
                    @if(isset($categories['Motion Patterns']))
                        <div class="space-y-24">
                            {{-- Durations --}}
                            @if(isset($categories['Motion Patterns']['Durations']))
                                <section id="{{ $categories['Motion Patterns']['Durations'] }}" class="js-section scroll-mt-8">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Durations Scale</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            Visual response timings. Interactive controls support mouse hovers and keyboard replays.
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                                        @foreach(\App\Presenters\DesignTokenCatalog::motion() as $motion)
                                            @if($motion['type'] === 'duration')
                                                @php
                                                    $searchMeta = strtolower($motion['token'] . ' ' . $motion['value'] . ' ' . implode(' ', $motion['used_by']));
                                                @endphp
                                                <div class="showcase-component p-5 bg-white border border-[color:var(--color-border)] rounded-2xl shadow-xs space-y-4" x-data="{ replayKey: 0 }" data-search="{{ $searchMeta }}">
                                                    <div class="flex items-center justify-between">
                                                        <div>
                                                            <span class="font-bold text-sm text-[color:var(--color-text-primary)]">{{ $motion['token'] }}</span>
                                                            <div class="text-[10px] text-neutral-400">{{ $motion['value'] }}</div>
                                                        </div>
                                                        <button @click="replayKey++" tabindex="0" class="text-[10px] py-1 px-2.5 bg-neutral-900 hover:bg-neutral-800 text-white rounded-lg transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-[color:var(--color-primary-500)] focus:outline-none cursor-pointer" title="Replay Animation">
                                                            ▶ Replay
                                                        </button>
                                                    </div>

                                                    <!-- Track and block -->
                                                    <div class="w-full bg-neutral-50 border rounded-xl p-3 flex items-center relative overflow-hidden">
                                                        <div :key="replayKey" class="w-6 h-6 bg-[color:var(--color-primary-500)] rounded-lg transition-all" style="transition-duration: {{ $motion['value'] }}; transform: translateX(0px);" x-init="
                                                            $el.classList.add('translate-x-[200%]');
                                                        "></div>
                                                    </div>

                                                    <div class="text-[10px] text-neutral-500 italic">Used: {{ implode(', ', $motion['used_by']) }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </section>
                            @endif

                            {{-- Easings --}}
                            @if(isset($categories['Motion Patterns']['Easings']))
                                <section id="{{ $categories['Motion Patterns']['Easings'] }}" class="js-section scroll-mt-8">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Easing Curves</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            Visual acceleration profiles. Interactive keyboard-friendly controllers trigger block animations in real time.
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        @foreach(\App\Presenters\DesignTokenCatalog::motion() as $motion)
                                            @if($motion['type'] === 'easing')
                                                @php
                                                    $searchMeta = strtolower($motion['token'] . ' ' . $motion['value'] . ' ' . implode(' ', $motion['used_by']));
                                                @endphp
                                                <div class="showcase-component p-5 bg-white border border-[color:var(--color-border)] rounded-2xl shadow-xs space-y-4" x-data="{ replayKey: 0 }" data-search="{{ $searchMeta }}">
                                                    <div class="flex items-center justify-between">
                                                        <div>
                                                            <span class="font-bold text-sm text-[color:var(--color-text-primary)]">{{ $motion['token'] }}</span>
                                                            <div class="text-[10px] text-neutral-400 font-mono">{{ $motion['value'] }}</div>
                                                        </div>
                                                        <button @click="replayKey++" tabindex="0" class="text-[10px] py-1 px-2.5 bg-neutral-900 hover:bg-neutral-800 text-white rounded-lg transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-[color:var(--color-primary-500)] focus:outline-none cursor-pointer" title="Replay Animation">
                                                            ▶ Replay
                                                        </button>
                                                    </div>

                                                    <!-- Track and block -->
                                                    <div class="w-full bg-neutral-50 border rounded-xl p-3 flex items-center relative overflow-hidden">
                                                        <div :key="replayKey" class="w-6 h-6 bg-[color:var(--color-secondary-500)] rounded-lg transition-all translate-x-0" style="transition-duration: 500ms; transition-timing-function: {{ $motion['value'] }};" x-init="
                                                            $el.classList.add('translate-x-[400%]');
                                                        "></div>
                                                    </div>

                                                    <div class="text-[10px] text-neutral-500 italic">Used: {{ implode(', ', $motion['used_by']) }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </section>
                            @endif

                            {{-- Reduced Motion --}}
                            @if(isset($categories['Motion Patterns']['Reduced Motion']))
                                <section id="{{ $categories['Motion Patterns']['Reduced Motion'] }}" class="js-section scroll-mt-8">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Reduced Motion</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            Visual accessibility standard guidelines for layout transitions and animations.
                                        </p>
                                    </div>

                                    <div class="p-6 bg-neutral-50 border border-[color:var(--color-border)] rounded-2xl space-y-4 text-xs leading-relaxed text-[color:var(--color-text-secondary)]">
                                        <h3 class="font-bold text-sm text-[color:var(--color-text-primary)]">Accessibility & Preferences Checklist</h3>
                                        <p>
                                            To support users with vestibular motion sensitivities or preferences, the design system respects the native browser media queries directly inside the main CSS layer.
                                        </p>
                                        <div class="p-4 bg-white border border-neutral-100 rounded-xl font-mono text-[10px] space-y-2">
                                            <div>@media (prefers-reduced-motion: reduce) {</div>
                                            <div class="pl-4">/* Disables transitions, resets opacity controls immediately */</div>
                                            <div class="pl-4">.ui-reveal, .ui-transition-fade-out, .ui-transition-fade-in {</div>
                                            <div class="pl-8">transition: none !important;</div>
                                            <div class="pl-8">animation: none !important;</div>
                                            <div class="pl-8">transform: none !important;</div>
                                            <div class="pl-8">opacity: 1 !important;</div>
                                            <div class="pl-4">}</div>
                                            <div>}</div>
                                        </div>
                                        <p class="text-emerald-700 font-semibold">
                                            ✓ System verification: verified that skeleton layouts lock animation speeds and scroll animations neutralize coordinates when prefers-reduced-motion is active.
                                        </p>
                                    </div>
                                </section>
                            @endif
                        </div>
                    @endif

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

                            {{-- Button --}}
                            @if(isset($categories['Forms']['Button']))
                                @php
                                    $buttonBasicCode = <<<'HTML'
<!-- Hierarchical Intents -->
<x-button intent="primary">Primary Action</x-button>
<x-button intent="secondary">Secondary Action</x-button>
<x-button intent="success">Success Action</x-button>
<x-button intent="danger">Danger Action</x-button>
<x-button intent="warning">Warning Action</x-button>
<x-button intent="info">Info Action</x-button>
HTML;

                                    $buttonAppearancesCode = <<<'HTML'
<!-- Solid Appearance (Default) -->
<x-button intent="primary" appearance="solid">Solid</x-button>

<!-- Outline Appearance -->
<x-button intent="primary" appearance="outline">Outline</x-button>

<!-- Ghost Appearance -->
<x-button intent="primary" appearance="ghost">Ghost</x-button>
HTML;

                                    $buttonSizesCode = <<<'HTML'
<!-- Sizing: sm (32px), md (40px), lg (48px) -->
<x-button intent="primary" size="sm">Small (32px)</x-button>
<x-button intent="primary" size="md">Medium (40px)</x-button>
<x-button intent="primary" size="lg">Large (48px)</x-button>
HTML;

                                    $buttonShapesCode = <<<'HTML'
<!-- Shape: square, circle, default -->
<x-button intent="primary" shape="square" aria-label="Square Action">
    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
</x-button>

<x-button intent="secondary" shape="circle" aria-label="Circle Action">
    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
</x-button>
HTML;

                                    $buttonStatesCode = <<<'HTML'
<!-- Disabled State -->
<x-button intent="primary" disabled>Disabled Button</x-button>

<!-- Disabled Link (Safely stripped href, pointer-events-none) -->
<x-button intent="secondary" href="/dashboard" disabled>Disabled Link</x-button>

<!-- Loading State (keeps text to prevent layout shifts, adds spinner) -->
<x-button intent="primary" :loading="true">Saving Changes</x-button>
HTML;

                                    $buttonCustomCode = <<<'HTML'
<!-- Icon prefix/suffix slots -->
<x-button intent="primary">
    <x-slot:prefix>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
    </x-slot:prefix>
    Download PDF
</x-button>

<!-- Full width (block) -->
<x-button intent="primary" :fullWidth="true">Stretch Block</x-button>

<!-- Customizable rounded (Skeleton-matching api) -->
<x-button intent="primary" rounded="none">No Rounded</x-button>
<x-button intent="primary" rounded="full">Pill Shape</x-button>
HTML;
                                @endphp

                                <section id="{{ $categories['Forms']['Button'] }}" class="js-section scroll-mt-8">
                                    <h2 class="text-2xl font-bold border-b border-[color:var(--color-border)] pb-4 mb-8">Button</h2>
                                    
                                    <div class="space-y-12">
                                        <!-- Intents -->
                                        <x-showcase.preview title="Intents" :code="$buttonBasicCode" id="preview-button-intents">
                                            <div class="flex flex-wrap gap-4 items-center">
                                                <x-button intent="primary">Primary</x-button>
                                                <x-button intent="secondary">Secondary</x-button>
                                                <x-button intent="success">Success</x-button>
                                                <x-button intent="danger">Danger</x-button>
                                                <x-button intent="warning">Warning</x-button>
                                                <x-button intent="info">Info</x-button>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Appearances -->
                                        <x-showcase.preview title="Appearances (Solid / Outline / Ghost)" :code="$buttonAppearancesCode" id="preview-button-appearances">
                                            <div class="space-y-6">
                                                <div class="flex flex-wrap gap-4 items-center">
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] w-24 block">Primary:</span>
                                                    <x-button intent="primary" appearance="solid">Solid</x-button>
                                                    <x-button intent="primary" appearance="outline">Outline</x-button>
                                                    <x-button intent="primary" appearance="ghost">Ghost</x-button>
                                                </div>
                                                <div class="flex flex-wrap gap-4 items-center">
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] w-24 block">Secondary:</span>
                                                    <x-button intent="secondary" appearance="solid">Solid</x-button>
                                                    <x-button intent="secondary" appearance="outline">Outline</x-button>
                                                    <x-button intent="secondary" appearance="ghost">Ghost</x-button>
                                                </div>
                                                <div class="flex flex-wrap gap-4 items-center">
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] w-24 block">Danger:</span>
                                                    <x-button intent="danger" appearance="solid">Solid</x-button>
                                                    <x-button intent="danger" appearance="outline">Outline</x-button>
                                                    <x-button intent="danger" appearance="ghost">Ghost</x-button>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Sizes -->
                                        <x-showcase.preview title="Sizes (Heights sm: 32px, md: 40px, lg: 48px)" :code="$buttonSizesCode" id="preview-button-sizes">
                                            <div class="flex flex-wrap gap-4 items-center">
                                                <x-button intent="primary" size="sm">Small</x-button>
                                                <x-button intent="primary" size="md">Medium</x-button>
                                                <x-button intent="primary" size="lg">Large</x-button>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Shapes -->
                                        <x-showcase.preview title="Icon Shapes (Square & Circle)" :code="$buttonShapesCode" id="preview-button-shapes">
                                            <div class="flex flex-wrap gap-4 items-center">
                                                <x-button intent="primary" shape="square" aria-label="Add item">
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                                                </x-button>
                                                <x-button intent="secondary" shape="square" aria-label="Edit item">
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                </x-button>
                                                <x-button intent="danger" shape="circle" aria-label="Delete item">
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </x-button>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- States -->
                                        <x-showcase.preview title="States & Disabled Anchors" :code="$buttonStatesCode" id="preview-button-states">
                                            <div class="flex flex-wrap gap-4 items-center">
                                                <x-button intent="primary" disabled>Disabled Button</x-button>
                                                <x-button intent="secondary" href="/dashboard" disabled>Disabled Link</x-button>
                                                <x-button intent="primary" :loading="true">Saving Changes</x-button>
                                                <x-button intent="danger" appearance="outline" :loading="true">Processing</x-button>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Custom Layouts -->
                                        <x-showcase.preview title="Prefix/Suffix Icons, Full Width & Custom Rounded" :code="$buttonCustomCode" id="preview-button-custom">
                                            <div class="space-y-6 w-full max-w-md">
                                                <div class="flex flex-wrap gap-4">
                                                    <x-button intent="primary">
                                                        <x-slot:prefix>
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                        </x-slot:prefix>
                                                        Download PDF
                                                    </x-button>
                                                    <x-button intent="secondary">
                                                        Next Step
                                                        <x-slot:suffix>
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                                                        </x-slot:suffix>
                                                    </x-button>
                                                </div>
                                                <x-button intent="primary" :fullWidth="true">Full Width Action</x-button>
                                                <div class="flex flex-wrap gap-4 items-center">
                                                    <x-button intent="primary" rounded="none">rounded="none"</x-button>
                                                    <x-button intent="primary" rounded="md">rounded="md"</x-button>
                                                    <x-button intent="primary" rounded="full">rounded="full"</x-button>
                                                </div>
                                            </div>
                                        </x-showcase.preview>
                                    </div>
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

                            {{-- Badge --}}
                            @if(isset($categories['Data Display']['Badge']))
                                @php
                                    $badgeBasicCode = <<<'HTML'
<!-- Intents paired with Solid/Light/Outline appearances -->
<x-badge intent="neutral" appearance="light">Neutral</x-badge>
<x-badge intent="primary" appearance="solid">Primary</x-badge>
<x-badge intent="success" appearance="outline">Success</x-badge>
HTML;

                                    $badgeAppearancesCode = <<<'HTML'
<!-- Solid Appearance -->
<x-badge intent="success" appearance="solid">Success</x-badge>

<!-- Light Appearance (Default) -->
<x-badge intent="success" appearance="light">Success</x-badge>

<!-- Outline Appearance -->
<x-badge intent="success" appearance="outline">Success</x-badge>
HTML;

                                    $badgeSizesCode = <<<'HTML'
<!-- Badge height: sm (20px) vs md (24px) -->
<x-badge intent="info" size="sm">Small (20px)</x-badge>
<x-badge intent="info" size="md">Medium (24px)</x-badge>
HTML;

                                    $badgeDotsCode = <<<'HTML'
<!-- Dot marker option -->
<x-badge intent="success" :dot="true">Active</x-badge>
<x-badge intent="danger" :dot="true">Offline</x-badge>
<x-badge intent="warning" :dot="true" appearance="solid">Pending</x-badge>
HTML;

                                    $badgeIconsCode = <<<'HTML'
<!-- Badge with slots prefix icons -->
<x-badge intent="primary" appearance="light">
    <x-slot:icon>
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
    </x-slot:icon>
    Create New
</x-badge>
HTML;

                                    $badgeRoundedCode = <<<'HTML'
<!-- Custom rounded bounds (defaults to full) -->
<x-badge intent="primary" rounded="none">Square</x-badge>
<x-badge intent="primary" rounded="md">rounded="md"</x-badge>
<x-badge intent="primary" rounded="full">rounded="full" (Pill)</x-badge>
HTML;
                                @endphp

                                <section id="{{ $categories['Data Display']['Badge'] }}" class="js-section scroll-mt-8 mt-16">
                                    <h2 class="text-2xl font-bold border-b border-[color:var(--color-border)] pb-4 mb-8">Badge</h2>
                                    
                                    <div class="space-y-12">
                                        <!-- Intents and appearances -->
                                        <x-showcase.preview title="Intents & Appearances" :code="$badgeBasicCode" id="preview-badge-intents">
                                            <div class="space-y-6">
                                                <div class="flex flex-wrap gap-4 items-center">
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] w-24 block">Solid:</span>
                                                    <x-badge intent="neutral" appearance="solid">Neutral</x-badge>
                                                    <x-badge intent="primary" appearance="solid">Primary</x-badge>
                                                    <x-badge intent="success" appearance="solid">Success</x-badge>
                                                    <x-badge intent="danger" appearance="solid">Danger</x-badge>
                                                    <x-badge intent="warning" appearance="solid">Warning</x-badge>
                                                    <x-badge intent="info" appearance="solid">Info</x-badge>
                                                </div>
                                                <div class="flex flex-wrap gap-4 items-center">
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] w-24 block">Light (Default):</span>
                                                    <x-badge intent="neutral" appearance="light">Neutral</x-badge>
                                                    <x-badge intent="primary" appearance="light">Primary</x-badge>
                                                    <x-badge intent="success" appearance="light">Success</x-badge>
                                                    <x-badge intent="danger" appearance="light">Danger</x-badge>
                                                    <x-badge intent="warning" appearance="light">Warning</x-badge>
                                                    <x-badge intent="info" appearance="light">Info</x-badge>
                                                </div>
                                                <div class="flex flex-wrap gap-4 items-center">
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] w-24 block">Outline:</span>
                                                    <x-badge intent="neutral" appearance="outline">Neutral</x-badge>
                                                    <x-badge intent="primary" appearance="outline">Primary</x-badge>
                                                    <x-badge intent="success" appearance="outline">Success</x-badge>
                                                    <x-badge intent="danger" appearance="outline">Danger</x-badge>
                                                    <x-badge intent="warning" appearance="outline">Warning</x-badge>
                                                    <x-badge intent="info" appearance="outline">Info</x-badge>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Sizes -->
                                        <x-showcase.preview title="Sizes (Heights sm: 20px, md: 24px)" :code="$badgeSizesCode" id="preview-badge-sizes">
                                            <div class="flex flex-wrap gap-4 items-center">
                                                <x-badge intent="info" size="sm">Small (20px)</x-badge>
                                                <x-badge intent="info" size="md">Medium (24px)</x-badge>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Dots -->
                                        <x-showcase.preview title="Status Dots" :code="$badgeDotsCode" id="preview-badge-dots">
                                            <div class="flex flex-wrap gap-4 items-center">
                                                <x-badge intent="success" :dot="true">Active</x-badge>
                                                <x-badge intent="danger" :dot="true">Offline</x-badge>
                                                <x-badge intent="warning" :dot="true" appearance="solid">Pending</x-badge>
                                                <x-badge intent="info" :dot="true" appearance="outline">Deploying</x-badge>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Icons -->
                                        <x-showcase.preview title="Badge with Icon Prefix" :code="$badgeIconsCode" id="preview-badge-icons">
                                            <div class="flex flex-wrap gap-4 items-center">
                                                <x-badge intent="primary">
                                                    <x-slot:icon>
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                                                    </x-slot:icon>
                                                    New User
                                                </x-badge>
                                                <x-badge intent="success" appearance="solid">
                                                    <x-slot:icon>
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                                    </x-slot:icon>
                                                    Verified
                                                </x-badge>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Custom Rounded -->
                                        <x-showcase.preview title="Custom Rounded Bounds" :code="$badgeRoundedCode" id="preview-badge-rounded">
                                            <div class="flex flex-wrap gap-4 items-center">
                                                <x-badge intent="primary" rounded="none">Square</x-badge>
                                                <x-badge intent="primary" rounded="md">rounded="md"</x-badge>
                                                <x-badge intent="primary" rounded="full">rounded="full" (Pill)</x-badge>
                                            </div>
                                        </x-showcase.preview>
                                    </div>
                                </section>
                            @endif

                            {{-- Avatar --}}
                            @if(isset($categories['Data Display']['Avatar']))
                                @php
                                    $avatarBasicCode = <<<'HTML'
<!-- Avatar Size Presets -->
<x-avatar src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=128&h=128&fit=crop&crop=face" name="John Smith" size="sm" />
<x-avatar src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=128&h=128&fit=crop&crop=face" name="John Smith" size="md" />
<x-avatar src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=128&h=128&fit=crop&crop=face" name="John Smith" size="lg" />
<x-avatar src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=128&h=128&fit=crop&crop=face" name="John Smith" size="xl" />
HTML;

                                    $avatarShapesCode = <<<'HTML'
<!-- Custom rounded borders (instead of distinct variant props) -->
<x-avatar name="John Smith" rounded="full" />
<x-avatar name="John Smith" rounded="lg" />
<x-avatar name="John Smith" rounded="none" />
HTML;

                                    $avatarInitialsCode = <<<'HTML'
<!-- Initials Generation & Hashing (Müller -> M, 李 小龍 -> 李小) -->
<x-avatar name="Müller" />
<x-avatar name="Élodie Martin" />
<x-avatar name="李 小龍" />
<x-avatar name="John Ronald Reuel Tolkien" />
HTML;

                                    $avatarFallbackCode = <<<'HTML'
<!-- Empty fallbacks: vector illustration when no name or source is present -->
<x-avatar />

<!-- Runtime broken URL fallback: unmounts img & displays initials -->
<x-avatar src="https://invalid-domain.xyz/broken-avatar.jpg" name="Sarah Connor" />
HTML;

                                    $avatarStatusCode = <<<'HTML'
<!-- Status dots mapped to existing design system semantic tokens -->
<x-avatar name="John Smith" status="online" statusPosition="bottom-right" />
<x-avatar name="Sarah Wilson" status="away" statusPosition="bottom-left" />
<x-avatar name="Mike Chen" status="busy" statusPosition="top-right" />
<x-avatar name="Emma Davis" status="offline" statusPosition="top-left" />
HTML;

                                    $avatarRingCode = <<<'HTML'
<!-- Rings mapped to design tokens (inner margins offset) -->
<x-avatar name="John Smith" ring="sm" />
<x-avatar name="John Smith" ring="md" />
<x-avatar name="John Smith" ring="lg" />
HTML;

                                    $avatarStackedCode = <<<'HTML'
<!-- Stacked avatars using negative margins -->
<div class="flex -space-x-3 overflow-hidden">
    <x-avatar src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=128&h=128&fit=crop&crop=face" name="User One" ring="sm" />
    <x-avatar src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=128&h=128&fit=crop&crop=face" name="User Two" ring="sm" />
    <x-avatar src="https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=128&h=128&fit=crop&crop=face" name="User Three" ring="sm" />
    <x-avatar name="Sarah Connor" ring="sm" />
</div>
HTML;

                                    $avatarDirectoryCode = <<<'HTML'
<!-- Real-World User Directory Block -->
<div class="bg-[color:var(--color-surface-primary)] border border-[color:var(--color-border)] rounded-lg divide-y divide-[color:var(--color-border)] max-w-sm">
    <div class="flex items-center justify-between p-3">
        <div class="flex items-center gap-3">
            <x-avatar src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=128&h=128&fit=crop&crop=face" name="John Smith" status="online" />
            <div>
                <h4 class="text-sm font-semibold text-[color:var(--color-text-primary)]">John Smith</h4>
                <p class="text-xs text-[color:var(--color-text-muted)]">j.smith@okina.io</p>
            </div>
        </div>
        <span class="text-xs text-[color:var(--color-success-600)] font-medium">Online</span>
    </div>
    <div class="flex items-center justify-between p-3">
        <div class="flex items-center gap-3">
            <x-avatar name="Sarah Wilson" status="away" />
            <div>
                <h4 class="text-sm font-semibold text-[color:var(--color-text-primary)]">Sarah Wilson</h4>
                <p class="text-xs text-[color:var(--color-text-muted)]">s.wilson@okina.io</p>
            </div>
        </div>
        <span class="text-xs text-[color:var(--color-warning-600)] font-medium">Away</span>
    </div>
    <div class="flex items-center justify-between p-3">
        <div class="flex items-center gap-3">
            <x-avatar src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=128&h=128&fit=crop&crop=face" name="Mike Chen" status="busy" />
            <div>
                <h4 class="text-sm font-semibold text-[color:var(--color-text-primary)]">Mike Chen</h4>
                <p class="text-xs text-[color:var(--color-text-muted)]">m.chen@okina.io</p>
            </div>
        </div>
        <span class="text-xs text-[color:var(--color-danger-600)] font-medium">Busy</span>
    </div>
    <div class="flex items-center justify-between p-3">
        <div class="flex items-center gap-3">
            <x-avatar name="Emma Davis" status="offline" />
            <div>
                <h4 class="text-sm font-semibold text-[color:var(--color-text-primary)]">Emma Davis</h4>
                <p class="text-xs text-[color:var(--color-text-muted)]">e.davis@okina.io</p>
            </div>
        </div>
        <span class="text-xs text-[color:var(--color-text-muted)] font-medium">Offline</span>
    </div>
</div>
HTML;
                                @endphp

                                <section id="{{ $categories['Data Display']['Avatar'] }}" class="js-section scroll-mt-8 mt-16">
                                    <h2 class="text-2xl font-bold border-b border-[color:var(--color-border)] pb-4 mb-8">Avatar</h2>
                                    
                                    <div class="space-y-12">
                                        <!-- Sizes -->
                                        <x-showcase.preview title="Sizes (sm: 32px, md: 40px, lg: 48px, xl: 64px)" :code="$avatarBasicCode" id="preview-avatar-sizes">
                                            <div class="flex flex-wrap gap-4 items-end">
                                                <x-avatar src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=128&h=128&fit=crop&crop=face" name="John Smith" size="sm" />
                                                <x-avatar src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=128&h=128&fit=crop&crop=face" name="John Smith" size="md" />
                                                <x-avatar src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=128&h=128&fit=crop&crop=face" name="John Smith" size="lg" />
                                                <x-avatar src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=128&h=128&fit=crop&crop=face" name="John Smith" size="xl" />
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Shapes via Rounded -->
                                        <x-showcase.preview title="Shapes (Custom border radius)" :code="$avatarShapesCode" id="preview-avatar-shapes">
                                            <div class="flex flex-wrap gap-4 items-center">
                                                <x-avatar name="John Smith" rounded="full" />
                                                <x-avatar name="John Smith" rounded="lg" />
                                                <x-avatar name="John Smith" rounded="none" />
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Initials Generation -->
                                        <x-showcase.preview title="Initials & Name-Hashing Palette (Deterministic colors)" :code="$avatarInitialsCode" id="preview-avatar-initials">
                                            <div class="flex flex-wrap gap-4 items-center">
                                                <x-avatar name="Müller" />
                                                <x-avatar name="Élodie Martin" />
                                                <x-avatar name="李 小龍" />
                                                <x-avatar name="John Ronald Reuel Tolkien" />
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Fallbacks & Errors -->
                                        <x-showcase.preview title="Fallback Defaults & Runtime Broken URL Handling" :code="$avatarFallbackCode" id="preview-avatar-fallbacks">
                                            <div class="flex flex-wrap gap-6 items-center">
                                                <div class="flex flex-col items-center gap-1.5">
                                                    <x-avatar />
                                                    <span class="text-[10px] text-[color:var(--color-text-muted)]">No Name/Src</span>
                                                </div>
                                                <div class="flex flex-col items-center gap-1.5">
                                                    <x-avatar src="https://invalid-domain.xyz/broken-avatar.jpg" name="Sarah Connor" />
                                                    <span class="text-[10px] text-[color:var(--color-text-muted)]">Broken Link</span>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Status Indicator Overlays -->
                                        <x-showcase.preview title="Status Overlays (Z-Index Layering)" :code="$avatarStatusCode" id="preview-avatar-status">
                                            <div class="flex flex-wrap gap-6 items-center">
                                                <x-avatar name="John Smith" status="online" statusPosition="bottom-right" />
                                                <x-avatar name="Sarah Wilson" status="away" statusPosition="bottom-left" />
                                                <x-avatar name="Mike Chen" status="busy" statusPosition="top-right" />
                                                <x-avatar name="Emma Davis" status="offline" statusPosition="top-left" />
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Rings -->
                                        <x-showcase.preview title="Borders & Ring Token Offsets" :code="$avatarRingCode" id="preview-avatar-rings">
                                            <div class="flex flex-wrap gap-6 items-center">
                                                <x-avatar name="John Smith" ring="sm" />
                                                <x-avatar name="John Smith" ring="md" />
                                                <x-avatar name="John Smith" ring="lg" />
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Stacked Avatars compatibility -->
                                        <x-showcase.preview title="Stacked Avatar Compatibility" :code="$avatarStackedCode" id="preview-avatar-stacked">
                                            <div class="flex -space-x-3 overflow-hidden">
                                                <x-avatar src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=128&h=128&fit=crop&crop=face" name="User One" ring="sm" />
                                                <x-avatar src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=128&h=128&fit=crop&crop=face" name="User Two" ring="sm" />
                                                <x-avatar src="https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=128&h=128&fit=crop&crop=face" name="User Three" ring="sm" />
                                                <x-avatar name="Sarah Connor" ring="sm" />
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Real-world User Directory Block -->
                                        <x-showcase.preview title="Real-World Showcase: User Directory Block" :code="$avatarDirectoryCode" id="preview-avatar-directory">
                                            <div class="bg-[color:var(--color-surface-primary)] border border-[color:var(--color-border)] rounded-lg divide-y divide-[color:var(--color-border)] w-full max-w-sm">
                                                <div class="flex items-center justify-between p-3">
                                                    <div class="flex items-center gap-3">
                                                        <x-avatar src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=128&h=128&fit=crop&crop=face" name="John Smith" status="online" />
                                                        <div>
                                                            <h4 class="text-sm font-semibold text-[color:var(--color-text-primary)]">John Smith</h4>
                                                            <p class="text-xs text-[color:var(--color-text-muted)]">j.smith@okina.io</p>
                                                        </div>
                                                    </div>
                                                    <span class="text-xs text-[color:var(--color-success-600)] font-medium">Online</span>
                                                </div>
                                                <div class="flex items-center justify-between p-3">
                                                    <div class="flex items-center gap-3">
                                                        <x-avatar name="Sarah Wilson" status="away" />
                                                        <div>
                                                            <h4 class="text-sm font-semibold text-[color:var(--color-text-primary)]">Sarah Wilson</h4>
                                                            <p class="text-xs text-[color:var(--color-text-muted)]">s.wilson@okina.io</p>
                                                        </div>
                                                    </div>
                                                    <span class="text-xs text-[color:var(--color-warning-600)] font-medium">Away</span>
                                                </div>
                                                <div class="flex items-center justify-between p-3">
                                                    <div class="flex items-center gap-3">
                                                        <x-avatar src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=128&h=128&fit=crop&crop=face" name="Mike Chen" status="busy" />
                                                        <div>
                                                            <h4 class="text-sm font-semibold text-[color:var(--color-text-primary)]">Mike Chen</h4>
                                                            <p class="text-xs text-[color:var(--color-text-muted)]">m.chen@okina.io</p>
                                                        </div>
                                                    </div>
                                                    <span class="text-xs text-[color:var(--color-danger-600)] font-medium">Busy</span>
                                                </div>
                                                <div class="flex items-center justify-between p-3">
                                                    <div class="flex items-center gap-3">
                                                        <x-avatar name="Emma Davis" status="offline" />
                                                        <div>
                                                            <h4 class="text-sm font-semibold text-[color:var(--color-text-primary)]">Emma Davis</h4>
                                                            <p class="text-xs text-[color:var(--color-text-muted)]">e.davis@okina.io</p>
                                                        </div>
                                                    </div>
                                                    <span class="text-xs text-[color:var(--color-text-muted)] font-medium">Offline</span>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Unicode internationalization manual test panel -->
                                        <x-showcase.preview title="Unicode Internationalization Test Panel" :code="'<!-- Manual checks -->'" id="preview-avatar-unicode">
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                                                <div class="flex flex-col items-center gap-1.5 p-3 border border-[color:var(--color-border)] rounded-lg bg-[color:var(--color-surface-secondary)]">
                                                    <x-avatar name="Élodie" />
                                                    <span class="text-[11px] font-medium text-[color:var(--color-text-primary)]">Élodie (EM)</span>
                                                </div>
                                                <div class="flex flex-col items-center gap-1.5 p-3 border border-[color:var(--color-border)] rounded-lg bg-[color:var(--color-surface-secondary)]">
                                                    <x-avatar name="José" />
                                                    <span class="text-[11px] font-medium text-[color:var(--color-text-primary)]">José (JO)</span>
                                                </div>
                                                <div class="flex flex-col items-center gap-1.5 p-3 border border-[color:var(--color-border)] rounded-lg bg-[color:var(--color-surface-secondary)]">
                                                    <x-avatar name="Müller" />
                                                    <span class="text-[11px] font-medium text-[color:var(--color-text-primary)]">Müller (MÜ)</span>
                                                </div>
                                                <div class="flex flex-col items-center gap-1.5 p-3 border border-[color:var(--color-border)] rounded-lg bg-[color:var(--color-surface-secondary)]">
                                                    <x-avatar name="李 小龍" />
                                                    <span class="text-[11px] font-medium text-[color:var(--color-text-primary)]">李 小龍 (李小)</span>
                                                </div>
                                                <div class="flex flex-col items-center gap-1.5 p-3 border border-[color:var(--color-border)] rounded-lg bg-[color:var(--color-surface-secondary)]">
                                                    <x-avatar name="山田 太郎" />
                                                    <span class="text-[11px] font-medium text-[color:var(--color-text-primary)]">山田 太郎 (山太)</span>
                                                </div>
                                                <div class="flex flex-col items-center gap-1.5 p-3 border border-[color:var(--color-border)] rounded-lg bg-[color:var(--color-surface-secondary)]">
                                                    <x-avatar name="محمد أحمد" />
                                                    <span class="text-[11px] font-medium text-[color:var(--color-text-primary)]">محمد أحمد (مأ)</span>
                                                </div>
                                                <div class="flex flex-col items-center gap-1.5 p-3 border border-[color:var(--color-border)] rounded-lg bg-[color:var(--color-surface-secondary)]">
                                                    <x-avatar name="अजय कुमार" />
                                                    <span class="text-[11px] font-medium text-[color:var(--color-text-primary)]">अजय कुमार (अक)</span>
                                                </div>
                                            </div>
                                        </x-showcase.preview>
                                    </div>
                                </section>
                            @endif

                            {{-- File Card --}}
                            @if(isset($categories['Data Display']['File Card']))
                                @php
                                    $fileCardBasicCode = <<<'HTML'
<div class="grid grid-cols-2 sm:grid-cols-3 gap-6 w-full">
    <!-- Image Card (Opens Preview) -->
    <x-file-card
        name="landscape.jpg"
        size="4.2 MB"
        mime="image/jpeg"
        thumbnail="https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=300&h=300&fit=crop"
        preview="preview-image-tile"
    />

    <!-- PDF Card (Opens Preview) -->
    <x-file-card
        name="annual_report_2026.pdf"
        size="2.4 MB"
        mime="application/pdf"
        preview="preview-pdf-tile"
    />

    <!-- Video Card (Opens Preview) -->
    <x-file-card
        name="presentation.mp4"
        size="18.5 MB"
        mime="video/mp4"
        preview="preview-video-tile"
    />

    <!-- Audio Card (Opens Preview) -->
    <x-file-card
        name="podcast_episode.mp3"
        size="12.1 MB"
        mime="audio/mpeg"
        preview="preview-audio-tile"
    />

    <!-- Archive Card (Download CTA) -->
    <x-file-card
        name="backup_assets.zip"
        size="145.8 MB"
        mime="application/zip"
        downloadUrl="#"
        downloadName="backup_assets.zip"
    />

    <!-- Excel Card (Download CTA) -->
    <x-file-card
        name="quarterly_metrics.xlsx"
        size="840 KB"
        mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
        downloadUrl="#"
        downloadName="quarterly_metrics.xlsx"
    />
</div>

<!-- Preview Lightboxes -->
<x-file-preview id="preview-image-tile" name="landscape.jpg" url="https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=1200&h=800&fit=crop" mime="image/jpeg" size="4.2 MB" downloadUrl="#" />
<x-file-preview id="preview-pdf-tile" name="annual_report_2026.pdf" url="https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf" mime="application/pdf" size="2.4 MB" downloadUrl="#" />
<x-file-preview id="preview-video-tile" name="presentation.mp4" url="https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4" mime="video/mp4" size="18.5 MB" downloadUrl="#" />
<x-file-preview id="preview-audio-tile" name="podcast_episode.mp3" url="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3" mime="audio/mpeg" size="12.1 MB" downloadUrl="#" />
HTML;

                                    $fileCardListCode = <<<'HTML'
<div class="space-y-3 w-full">
    <x-file-card
        name="invoice_1092.pdf"
        size="124 KB"
        mime="application/pdf"
        variant="list"
        preview="preview-list-pdf"
    />
    <x-file-card
        name="avatar_face.png"
        size="84 KB"
        mime="image/png"
        variant="list"
        thumbnail="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=128&h=128&fit=crop&crop=face"
        preview="preview-list-image"
    />
    <x-file-card
        name="database_dump.zip"
        size="48.2 MB"
        mime="application/zip"
        variant="list"
        downloadUrl="#"
    />
</div>

<x-file-preview id="preview-list-pdf" name="invoice_1092.pdf" url="https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf" mime="application/pdf" size="124 KB" downloadUrl="#" />
<x-file-preview id="preview-list-image" name="avatar_face.png" url="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=800&h=800&fit=crop&crop=face" mime="image/png" size="84 KB" downloadUrl="#" />
HTML;

                                    $fileCardStatesCode = <<<'HTML'
<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 w-full">
    <!-- Disabled Card -->
    <div>
        <h4 class="text-xs font-semibold text-[color:var(--color-text-muted)] uppercase tracking-wider mb-2">Disabled State</h4>
        <x-file-card
            name="archive_old.zip"
            size="250 MB"
            mime="application/zip"
            disabled
        />
    </div>

    <!-- Loading State -->
    <div>
        <h4 class="text-xs font-semibold text-[color:var(--color-text-muted)] uppercase tracking-wider mb-2">Loading State</h4>
        <x-file-card
            name="loading_file.mp4"
            loading
        />
    </div>

    <!-- Selectable + Selected -->
    <div>
        <h4 class="text-xs font-semibold text-[color:var(--color-text-muted)] uppercase tracking-wider mb-2">Selectable (Hover to see checkbox)</h4>
        <x-file-card
            name="selected_document.docx"
            size="1.2 MB"
            mime="application/vnd.openxmlformats-officedocument.wordprocessingml.document"
            selectable
            selected
        />
    </div>

    <!-- Static (Non-interactive) Card -->
    <div>
        <h4 class="text-xs font-semibold text-[color:var(--color-text-muted)] uppercase tracking-wider mb-2">Non-Interactive (No actions/URLs)</h4>
        <x-file-card
            name="read-only-information.txt"
            size="15 KB"
            mime="text/plain"
        />
    </div>
</div>
HTML;

                                    $fileCardActionsCode = <<<'HTML'
<!-- Custom Action Menu Slots -->
<x-file-card
    name="shared_document.docx"
    size="1.8 MB"
    mime="application/vnd.openxmlformats-officedocument.wordprocessingml.document"
>
    <x-slot:actions>
        <x-dropdown.item onclick="alert('Shared!')">
            Share Link
        </x-dropdown.item>
        <x-dropdown.item onclick="alert('Downloaded!')">
            Direct Download
        </x-dropdown.item>
        <x-dropdown.divider />
        <x-dropdown.item class="text-red-600 hover:text-red-700" onclick="alert('Deleted!')">
            Delete File
        </x-dropdown.item>
    </x-slot:actions>
</x-file-card>
HTML;

                                    $fileCardGalleryCode = <<<'HTML'
<div class="grid grid-cols-2 sm:grid-cols-4 gap-6 w-full">
    <!-- Image Card -->
    <x-file-card
        name="mountain.jpg"
        size="3.1 MB"
        mime="image/jpeg"
        thumbnail="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=300&h=300&fit=crop"
        preview="gallery-img"
    />
    <!-- PDF Card -->
    <x-file-card
        name="financial_plan_2026.pdf"
        size="1.4 MB"
        mime="application/pdf"
        preview="gallery-pdf"
    />
    <!-- Zip Card -->
    <x-file-card
        name="assets_v2.zip"
        size="24.8 MB"
        mime="application/zip"
        downloadUrl="#"
    />
    <!-- Docx Card -->
    <x-file-card
        name="project_proposal.docx"
        size="2.1 MB"
        mime="application/vnd.openxmlformats-officedocument.wordprocessingml.document"
        downloadUrl="#"
    />
    <!-- MP4 Video Card -->
    <x-file-card
        name="ad_campaign.mp4"
        size="45.1 MB"
        mime="video/mp4"
        preview="gallery-video"
    />
    <!-- Disabled Card -->
    <x-file-card
        name="restricted_file.key"
        size="0 bytes"
        disabled
    />
    <!-- Loading Card -->
    <x-file-card
        name="fetching..."
        loading
    />
    <!-- Long Filename -->
    <x-file-card
        name="quarterly_report_executive_summary_final_v3_draft_reviewed.xlsx"
        size="1.8 MB"
        mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
        downloadUrl="#"
    />
</div>

<!-- Preview lightboxes -->
<x-file-preview id="gallery-img" name="mountain.jpg" url="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=1200&h=800&fit=crop" mime="image/jpeg" size="3.1 MB" downloadUrl="#" />
<x-file-preview id="gallery-pdf" name="financial_plan_2026.pdf" url="https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf" mime="application/pdf" size="1.4 MB" downloadUrl="#" />
<x-file-preview id="gallery-video" name="ad_campaign.mp4" url="https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4" mime="video/mp4" size="45.1 MB" downloadUrl="#" />
HTML;
                                @endphp

                                <section id="{{ $categories['Data Display']['File Card'] }}" class="js-section scroll-mt-8 mt-16">
                                    <h2 class="text-2xl font-bold border-b border-[color:var(--color-border)] pb-4 mb-8">File Card</h2>
                                    
                                    <div class="space-y-12">
                                        <!-- Tile Grid -->
                                        <x-showcase.preview title="Tile Grid Variant" :code="$fileCardBasicCode" id="preview-file-card-tile">
                                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-6 w-full">
                                                <x-file-card
                                                    name="landscape.jpg"
                                                    size="4.2 MB"
                                                    mime="image/jpeg"
                                                    thumbnail="https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=300&h=300&fit=crop"
                                                    preview="preview-image-tile"
                                                />
                                                <x-file-card
                                                    name="annual_report_2026.pdf"
                                                    size="2.4 MB"
                                                    mime="application/pdf"
                                                    preview="preview-pdf-tile"
                                                />
                                                <x-file-card
                                                    name="presentation.mp4"
                                                    size="18.5 MB"
                                                    mime="video/mp4"
                                                    preview="preview-video-tile"
                                                />
                                                <x-file-card
                                                    name="podcast_episode.mp3"
                                                    size="12.1 MB"
                                                    mime="audio/mpeg"
                                                    preview="preview-audio-tile"
                                                />
                                                <x-file-card
                                                    name="backup_assets.zip"
                                                    size="145.8 MB"
                                                    mime="application/zip"
                                                    downloadUrl="#"
                                                    downloadName="backup_assets.zip"
                                                />
                                                <x-file-card
                                                    name="quarterly_metrics.xlsx"
                                                    size="840 KB"
                                                    mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                                    downloadUrl="#"
                                                    downloadName="quarterly_metrics.xlsx"
                                                />
                                            </div>

                                            <x-file-preview id="preview-image-tile" name="landscape.jpg" url="https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=1200&h=800&fit=crop" mime="image/jpeg" size="4.2 MB" downloadUrl="#" />
                                            <x-file-preview id="preview-pdf-tile" name="annual_report_2026.pdf" url="https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf" mime="application/pdf" size="2.4 MB" downloadUrl="#" />
                                            <x-file-preview id="preview-video-tile" name="presentation.mp4" url="https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4" mime="video/mp4" size="18.5 MB" downloadUrl="#" />
                                            <x-file-preview id="preview-audio-tile" name="podcast_episode.mp3" url="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3" mime="audio/mpeg" size="12.1 MB" downloadUrl="#" />
                                        </x-showcase.preview>

                                        <!-- List Variant -->
                                        <x-showcase.preview title="List Variant" :code="$fileCardListCode" id="preview-file-card-list">
                                            <div class="space-y-3 w-full">
                                                <x-file-card
                                                    name="invoice_1092.pdf"
                                                    size="124 KB"
                                                    mime="application/pdf"
                                                    variant="list"
                                                    preview="preview-list-pdf"
                                                />
                                                <x-file-card
                                                    name="avatar_face.png"
                                                    size="84 KB"
                                                    mime="image/png"
                                                    variant="list"
                                                    thumbnail="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=128&h=128&fit=crop&crop=face"
                                                    preview="preview-list-image"
                                                />
                                                <x-file-card
                                                    name="database_dump.zip"
                                                    size="48.2 MB"
                                                    mime="application/zip"
                                                    variant="list"
                                                    downloadUrl="#"
                                                />
                                            </div>

                                            <x-file-preview id="preview-list-pdf" name="invoice_1092.pdf" url="https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf" mime="application/pdf" size="124 KB" downloadUrl="#" />
                                            <x-file-preview id="preview-list-image" name="avatar_face.png" url="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=800&h=800&fit=crop&crop=face" mime="image/png" size="84 KB" downloadUrl="#" />
                                        </x-showcase.preview>

                                        <!-- Card States -->
                                        <x-showcase.preview title="File Card States" :code="$fileCardStatesCode" id="preview-file-card-states">
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 w-full">
                                                <div>
                                                    <h4 class="text-xs font-semibold text-[color:var(--color-text-muted)] uppercase tracking-wider mb-2">Disabled State</h4>
                                                    <x-file-card
                                                        name="archive_old.zip"
                                                        size="250 MB"
                                                        mime="application/zip"
                                                        disabled
                                                    />
                                                </div>
                                                <div>
                                                    <h4 class="text-xs font-semibold text-[color:var(--color-text-muted)] uppercase tracking-wider mb-2">Loading State</h4>
                                                    <x-file-card
                                                        name="loading_file.mp4"
                                                        loading
                                                    />
                                                </div>
                                                <div>
                                                    <h4 class="text-xs font-semibold text-[color:var(--color-text-muted)] uppercase tracking-wider mb-2">Selectable + Selected</h4>
                                                    <x-file-card
                                                        name="selected_document.docx"
                                                        size="1.2 MB"
                                                        mime="application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                                        selectable
                                                        selected
                                                    />
                                                </div>
                                                <div>
                                                    <h4 class="text-xs font-semibold text-[color:var(--color-text-muted)] uppercase tracking-wider mb-2">Non-Interactive (No Action)</h4>
                                                    <x-file-card
                                                        name="read-only-information.txt"
                                                        size="15 KB"
                                                        mime="text/plain"
                                                    />
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Actions Dropdown -->
                                        <x-showcase.preview title="Custom Actions Menu (Composed Dropdown)" :code="$fileCardActionsCode" id="preview-file-card-actions">
                                            <div class="max-w-sm">
                                                <x-file-card
                                                    name="shared_document.docx"
                                                    size="1.8 MB"
                                                    mime="application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                                >
                                                    <x-slot:actions>
                                                        <x-dropdown.item onclick="alert('Shared!')">
                                                            Share Link
                                                        </x-dropdown.item>
                                                        <x-dropdown.item onclick="alert('Downloaded!')">
                                                            Direct Download
                                                        </x-dropdown.item>
                                                        <x-dropdown.divider />
                                                        <x-dropdown.item class="text-red-600 hover:text-red-700" onclick="alert('Deleted!')">
                                                            Delete File
                                                        </x-dropdown.item>
                                                    </x-slot:actions>
                                                </x-file-card>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Mixed Gallery -->
                                        <x-showcase.preview title="Mixed Gallery (Comprehensive Layout & Code Paths)" :code="$fileCardGalleryCode" id="preview-file-card-gallery">
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 w-full">
                                                <x-file-card
                                                    name="mountain.jpg"
                                                    size="3.1 MB"
                                                    mime="image/jpeg"
                                                    thumbnail="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=300&h=300&fit=crop"
                                                    preview="gallery-img"
                                                />
                                                <x-file-card
                                                    name="financial_plan_2026.pdf"
                                                    size="1.4 MB"
                                                    mime="application/pdf"
                                                    preview="gallery-pdf"
                                                />
                                                <x-file-card
                                                    name="assets_v2.zip"
                                                    size="24.8 MB"
                                                    mime="application/zip"
                                                    downloadUrl="#"
                                                />
                                                <x-file-card
                                                    name="project_proposal.docx"
                                                    size="2.1 MB"
                                                    mime="application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                                    downloadUrl="#"
                                                />
                                                <x-file-card
                                                    name="ad_campaign.mp4"
                                                    size="45.1 MB"
                                                    mime="video/mp4"
                                                    preview="gallery-video"
                                                />
                                                <x-file-card
                                                    name="restricted_file.key"
                                                    size="0 bytes"
                                                    disabled
                                                />
                                                <x-file-card
                                                    name="fetching..."
                                                    loading
                                                />
                                                <x-file-card
                                                    name="quarterly_report_executive_summary_final_v3_draft_reviewed.xlsx"
                                                    size="1.8 MB"
                                                    mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                                    downloadUrl="#"
                                                />
                                                {{-- forcePreviewType and showBadge --}}
                                                <x-file-card
                                                    name="custom_image_stream.bin"
                                                    size="920 KB"
                                                    mime="application/octet-stream"
                                                    forcePreviewType="image"
                                                    thumbnail="https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=300&h=300&fit=crop"
                                                    preview="gallery-forced-img"
                                                    :showBadge="false"
                                                />
                                            </div>

                                            <x-file-preview id="gallery-img" name="mountain.jpg" url="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=1200&h=800&fit=crop" mime="image/jpeg" size="3.1 MB" downloadUrl="#" />
                                            <x-file-preview id="gallery-pdf" name="financial_plan_2026.pdf" url="https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf" mime="application/pdf" size="1.4 MB" downloadUrl="#" />
                                            <x-file-preview id="gallery-video" name="ad_campaign.mp4" url="https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4" mime="video/mp4" size="45.1 MB" downloadUrl="#" />
                                            <x-file-preview id="gallery-forced-img" name="custom_image_stream.bin" url="https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=1200&h=800&fit=crop" mime="application/octet-stream" forcePreviewType="image" size="920 KB" downloadUrl="#" />
                                        </x-showcase.preview>
                                    </div>
                                </section>
                            @endif

                            {{-- File Preview --}}
                            @if(isset($categories['Data Display']['File Preview']))
                                @php
                                    $filePreviewDemoCode = <<<'HTML'
<!-- Button to trigger standard file preview lightbox -->
<button
    type="button"
    onclick="window.openModal('preview-standalone-image')"
    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold bg-[color:var(--color-primary)] text-white rounded-lg hover:bg-[color:var(--color-primary-hover)] active:bg-[color:var(--color-primary-active)] transition-colors focus:outline-none focus:ring-2 focus:ring-[color:var(--color-primary)] focus:ring-offset-2"
>
    <span>Open Standalone Preview</span>
</button>

<x-file-preview
    id="preview-standalone-image"
    name="aurora_borealis.png"
    url="https://images.unsplash.com/photo-1579033461380-adb47c3eb938?w=1200&h=800&fit=crop"
    mime="image/png"
    size="5.8 MB"
    downloadUrl="#"
    downloadName="aurora_borealis.png"
>
    <x-slot:actions>
        <x-button intent="ghost" size="sm" onclick="alert('Shared standalone!')">Share</x-button>
        <x-button intent="secondary" size="sm" onclick="alert('Rotated standalone!')">Rotate</x-button>
    </x-slot:actions>
</x-file-preview>
HTML;
                                @endphp

                                <section id="{{ $categories['Data Display']['File Preview'] }}" class="js-section scroll-mt-8 mt-16">
                                    <h2 class="text-2xl font-bold border-b border-[color:var(--color-border)] pb-4 mb-8">File Preview</h2>
                                    
                                    <div class="space-y-12">
                                        <!-- Standalone Demo -->
                                        <x-showcase.preview title="Standalone Preview Lightbox" :code="$filePreviewDemoCode" id="preview-file-preview-standalone">
                                            <div class="flex items-center gap-4">
                                                <button
                                                    type="button"
                                                    onclick="window.openModal('preview-standalone-image')"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold bg-[color:var(--color-primary)] text-white rounded-lg hover:bg-[color:var(--color-primary-hover)] active:bg-[color:var(--color-primary-active)] transition-colors focus:outline-none focus:ring-2 focus:ring-[color:var(--color-primary)] focus:ring-offset-2"
                                                >
                                                    <span>Open Standalone Preview</span>
                                                </button>

                                                <x-file-preview
                                                    id="preview-standalone-image"
                                                    name="aurora_borealis.png"
                                                    url="https://images.unsplash.com/photo-1579033461380-adb47c3eb938?w=1200&h=800&fit=crop"
                                                    mime="image/png"
                                                    size="5.8 MB"
                                                    downloadUrl="#"
                                                    downloadName="aurora_borealis.png"
                                                >
                                                    <x-slot:actions>
                                                        <x-button intent="ghost" size="sm" onclick="alert('Shared standalone!')">Share</x-button>
                                                        <x-button intent="secondary" size="sm" onclick="alert('Rotated standalone!')">Rotate</x-button>
                                                    </x-slot:actions>
                                                </x-file-preview>
                                            </div>
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

                            {{-- Spinner & Loading Indicators --}}
                            @if(isset($categories['Feedback']['Spinner']))
                                @php
                                    $spinnerBasicCode = <<<'HTML'
<!-- Spinner Customization Grid -->
<x-spinner size="xs" />
<x-spinner size="sm" />
<x-spinner size="md" />
<x-spinner size="lg" />
<x-spinner size="xl" />

<!-- Spinner Intents -->
<x-spinner intent="primary" size="md" />
<x-spinner intent="secondary" size="md" />
<x-spinner intent="success" size="md" />
<x-spinner intent="danger" size="md" />
<x-spinner intent="warning" size="md" />
<x-spinner intent="neutral" size="md" />

<!-- Custom stroke thickness (clamped 1-8) -->
<x-spinner thickness="2" size="md" />
<x-spinner thickness="6" size="md" />

<!-- Accessible screen-reader support -->
<x-spinner srOnlyLabel="Syncing files..." size="md" />
HTML;

                                    $loadingInlineCode = <<<'HTML'
<!-- Inline Loader Component -->
<x-loading.inline />
<x-loading.inline text="Saving changes..." intent="secondary" />
<x-loading.inline size="lg" intent="success">Downloading invoice...</x-loading.inline>
HTML;

                                    $loadingOverlayCode = <<<'HTML'
<!-- Loading Overlay Wrapper Mode -->
<x-loading.overlay show="cardLoading" label="Updating database..." tone="glass" blur="md">
    <div class="p-6 bg-white border border-[color:var(--color-border)] rounded-xl">
        <h4 class="font-bold text-[color:var(--color-text-primary)]">Orders Database</h4>
        <p class="text-xs text-[color:var(--color-text-muted)] mt-2">Try clicking the action button or tabbing inside while loading is active.</p>
        <button @click="alert('Action completed!')" class="mt-4 px-4 py-2 bg-neutral-900 text-white rounded-lg text-xs font-semibold">
            Action Button
        </button>
    </div>
</x-loading.overlay>
HTML;

                                    $loadingFullscreenCode = <<<'HTML'
<!-- Fullscreen Loading Overlay -->
<x-loading.overlay show="fullscreenLoading" label="Initializing workspace..." fullscreen tone="dark" />
HTML;
                                @endphp

                                <section id="{{ $categories['Feedback']['Spinner'] }}" class="js-section scroll-mt-8 mt-16">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Spinner & Loading Indicators</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            A unified vector loader component with standard sizing/color intents, inline indicators, and relative/fixed viewport overlays with dynamic interaction blocking.
                                        </p>
                                    </div>

                                    <div class="space-y-12">
                                        <!-- Basic Spinner Variations -->
                                        <x-showcase.preview title="Spinner Sizing & Thickness Grid" :code="$spinnerBasicCode" id="preview-spinner-basic">
                                            <div class="w-full space-y-6">
                                                <!-- Sizes -->
                                                <div>
                                                    <span class="text-xs font-bold text-neutral-400 block mb-3 uppercase tracking-wider">Sizes</span>
                                                    <div class="flex items-center gap-6">
                                                        <div class="text-center"><x-spinner size="xs" /><span class="text-[10px] text-neutral-400 mt-1 block">xs</span></div>
                                                        <div class="text-center"><x-spinner size="sm" /><span class="text-[10px] text-neutral-400 mt-1 block">sm</span></div>
                                                        <div class="text-center"><x-spinner size="md" /><span class="text-[10px] text-neutral-400 mt-1 block">md</span></div>
                                                        <div class="text-center"><x-spinner size="lg" /><span class="text-[10px] text-neutral-400 mt-1 block">lg</span></div>
                                                        <div class="text-center"><x-spinner size="xl" /><span class="text-[10px] text-neutral-400 mt-1 block">xl</span></div>
                                                    </div>
                                                </div>

                                                <!-- Intents -->
                                                <div>
                                                    <span class="text-xs font-bold text-neutral-400 block mb-3 uppercase tracking-wider">Intents</span>
                                                    <div class="flex flex-wrap items-center gap-6">
                                                        <div class="text-center"><x-spinner intent="primary" size="md" /><span class="text-[10px] text-neutral-400 mt-1 block">primary</span></div>
                                                        <div class="text-center"><x-spinner intent="secondary" size="md" /><span class="text-[10px] text-neutral-400 mt-1 block">secondary</span></div>
                                                        <div class="text-center"><x-spinner intent="success" size="md" /><span class="text-[10px] text-neutral-400 mt-1 block">success</span></div>
                                                        <div class="text-center"><x-spinner intent="danger" size="md" /><span class="text-[10px] text-neutral-400 mt-1 block">danger</span></div>
                                                        <div class="text-center"><x-spinner intent="warning" size="md" /><span class="text-[10px] text-neutral-400 mt-1 block">warning</span></div>
                                                        <div class="text-center"><x-spinner intent="neutral" size="md" /><span class="text-[10px] text-neutral-400 mt-1 block">neutral</span></div>
                                                    </div>
                                                </div>

                                                <!-- Thickness Clamping -->
                                                <div>
                                                    <span class="text-xs font-bold text-neutral-400 block mb-3 uppercase tracking-wider">Custom Thickness (Stroke Width)</span>
                                                    <div class="flex items-center gap-8">
                                                        <div class="text-center"><x-spinner thickness="2" size="md" /><span class="text-[10px] text-neutral-400 mt-1 block">thickness="2"</span></div>
                                                        <div class="text-center"><x-spinner thickness="4" size="md" /><span class="text-[10px] text-neutral-400 mt-1 block">thickness="4" (default)</span></div>
                                                        <div class="text-center"><x-spinner thickness="6" size="md" /><span class="text-[10px] text-neutral-400 mt-1 block">thickness="6"</span></div>
                                                        <div class="text-center"><x-spinner thickness="8" size="md" /><span class="text-[10px] text-neutral-400 mt-1 block">thickness="8"</span></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Inline Loader Previews -->
                                        <x-showcase.preview title="Inline Loaders" :code="$loadingInlineCode" id="preview-loading-inline">
                                            <div class="w-full space-y-4">
                                                <div>
                                                    <span class="text-xs text-neutral-400 mb-1 block">Default state</span>
                                                    <x-loading.inline />
                                                </div>
                                                <div>
                                                    <span class="text-xs text-neutral-400 mb-1 block">Custom text & secondary intent</span>
                                                    <x-loading.inline text="Saving changes..." intent="secondary" />
                                                </div>
                                                <div>
                                                    <span class="text-xs text-neutral-400 mb-1 block">Custom slot, large success size</span>
                                                    <x-loading.inline size="lg" intent="success">Downloading invoice...</x-loading.inline>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Container Overlays (Wrapper Mode) -->
                                        <x-showcase.preview title="Container Overlays (Wrapper Mode)" :code="$loadingOverlayCode" id="preview-loading-overlay">
                                            <div x-data="{ cardLoading: false }" class="w-full max-w-sm space-y-6">
                                                <div class="flex items-center gap-2">
                                                    <input type="checkbox" id="toggle-card-loading" x-model="cardLoading" class="rounded border-neutral-300 text-[color:var(--color-primary-600)] focus:ring-[color:var(--color-primary-500)]">
                                                    <label for="toggle-card-loading" class="text-xs font-semibold text-[color:var(--color-text-secondary)] select-none">Toggle Loading State (Inert Wrapper)</label>
                                                </div>

                                                <x-loading.overlay show="cardLoading" label="Syncing database..." tone="glass" blur="md">
                                                    <div class="p-6 bg-white border border-[color:var(--color-border)] rounded-xl">
                                                        <h4 class="font-bold text-[color:var(--color-text-primary)]">Orders Database</h4>
                                                        <p class="text-xs text-[color:var(--color-text-muted)] mt-2">
                                                            When loading is active, this card automatically receives the <code>inert</code> attribute, preventing tabbing or mouse interactions with the button.
                                                        </p>
                                                        <button @click="alert('Button clicked! (Not blocked)')" class="mt-4 px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white rounded-lg text-xs font-semibold transition active:scale-95">
                                                            Interactive Target Button
                                                        </button>
                                                    </div>
                                                </x-loading.overlay>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Fullscreen Blocker -->
                                        <x-showcase.preview title="Fullscreen Overlay Blocker" :code="$loadingFullscreenCode" id="preview-loading-fullscreen">
                                            <div x-data="{ fullscreenLoading: false }" class="w-full">
                                                <button @click="fullscreenLoading = true; setTimeout(() => fullscreenLoading = false, 3000)" class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] rounded-lg transition active:scale-95 shadow-sm">
                                                    Trigger Fullscreen Overlay (3s)
                                                </button>

                                                <x-loading.overlay show="fullscreenLoading" label="Rebuilding workspace index..." fullscreen tone="dark" />
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Refactored Components Integration -->
                                        <div class="p-6 bg-white border border-[color:var(--color-border)] rounded-xl">
                                            <h3 class="font-bold text-base text-[color:var(--color-text-primary)] mb-2">Refactored Shell Components</h3>
                                            <p class="text-xs text-[color:var(--color-text-muted)] mb-4">
                                                Verify that the newly refactored buttons, dropdowns, and stepper steps consume the modular <code>&lt;x-spinner&gt;</code> without visual regression.
                                            </p>
                                            <div class="flex flex-wrap items-center gap-4">
                                                <x-button intent="primary" :loading="true">Loading Button</x-button>
                                                <x-button intent="secondary" :loading="true">Secondary Spinner</x-button>
                                                
                                                <div class="p-2 border rounded-lg bg-neutral-50 flex items-center gap-2">
                                                    <span class="text-xs text-neutral-400">Stepper Step:</span>
                                                    <x-stepper.step step="1" title="Initial Step" description="Processing..." status="current" :busy="true" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            @endif

                            {{-- Progress Indicators --}}
                            @if(isset($categories['Feedback']['Progress Indicators']))
                                @php
                                    $progressBasicCode = <<<'HTML'
<!-- Determinate Progress Sizes -->
<x-progress value="60" size="sm" showLabel />
<x-progress value="60" size="md" showLabel />
<x-progress value="60" size="lg" showLabel />

<!-- Color Intents -->
<x-progress value="75" intent="primary" />
<x-progress value="75" intent="secondary" />
<x-progress value="75" intent="success" />
<x-progress value="75" intent="danger" />
<x-progress value="75" intent="warning" />
<x-progress value="75" intent="neutral" />

<!-- Rounded Corner Variants -->
<x-progress value="45" rounded="full" />
<x-progress value="45" rounded="md" />
<x-progress value="45" rounded="none" />
HTML;

                                    $progressSpecialCode = <<<'HTML'
<!-- Indeterminate Progress -->
<x-progress />

<!-- Striped & Animated Determinate Tracks -->
<x-progress value="65" striped />
<x-progress value="65" striped animated />
HTML;

                                    $progressLabelCode = <<<'HTML'
<!-- Label Alignments -->
<x-progress value="80" showLabel labelPosition="top" />
<x-progress value="80" showLabel labelPosition="bottom" />
<x-progress value="80" showLabel labelPosition="inline" size="lg" />

<!-- Custom Formatting & Text labels -->
<x-progress value="60" label="6 of 10 completed" showLabel />
<x-progress value="60" label="₹1,200 of ₹2,000 raised" showLabel />
HTML;

                                    $progressInteractiveCode = <<<'HTML'
<!-- Interactive Playground with Alpine.js -->
<div x-data="{ progressVal: 45 }" class="space-y-4">
    <x-progress :xBind="'progressVal'" showLabel />
    
    <div class="flex items-center gap-2">
        <button @click="progressVal = Math.max(0, progressVal - 10)" class="...">-10%</button>
        <button @click="progressVal = Math.min(100, progressVal + 10)" class="...">+10%</button>
        <button @click="progressVal = 0" class="...">Reset</button>
        <button @click="progressVal = 100" class="...">Complete</button>
    </div>
</div>
HTML;
                                @endphp

                                <section id="{{ $categories['Feedback']['Progress Indicators'] }}" class="js-section scroll-mt-8 mt-16">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Progress Indicators</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            Visual feedback loaders showcasing indeterminate loading states and determinate value completion, supporting size layouts, intent palettes, and label positionings.
                                        </p>
                                    </div>

                                    <div class="space-y-12">
                                        <!-- Sizing and Intent Grid -->
                                        <x-showcase.preview title="Sizing, Intents, and Rounded Radii" :code="$progressBasicCode" id="preview-progress-basic">
                                            <div class="w-full max-w-xl space-y-8">
                                                <!-- Sizes -->
                                                <div>
                                                    <span class="text-xs font-bold text-neutral-400 block mb-3 uppercase tracking-wider">Sizes</span>
                                                    <div class="space-y-4">
                                                        <div><span class="text-[10px] text-neutral-400 block mb-1">Small (h-1.5)</span><x-progress value="60" size="sm" showLabel /></div>
                                                        <div><span class="text-[10px] text-neutral-400 block mb-1">Medium (h-2.5, default)</span><x-progress value="60" size="md" showLabel /></div>
                                                        <div><span class="text-[10px] text-neutral-400 block mb-1">Large (h-4)</span><x-progress value="60" size="lg" showLabel /></div>
                                                    </div>
                                                </div>

                                                <!-- Intents -->
                                                <div>
                                                    <span class="text-xs font-bold text-neutral-400 block mb-3 uppercase tracking-wider">Intents</span>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        <div><span class="text-[10px] text-neutral-400 block mb-1">Primary</span><x-progress value="75" intent="primary" /></div>
                                                        <div><span class="text-[10px] text-neutral-400 block mb-1">Secondary</span><x-progress value="75" intent="secondary" /></div>
                                                        <div><span class="text-[10px] text-neutral-400 block mb-1">Success</span><x-progress value="75" intent="success" /></div>
                                                        <div><span class="text-[10px] text-neutral-400 block mb-1">Danger</span><x-progress value="75" intent="danger" /></div>
                                                        <div><span class="text-[10px] text-neutral-400 block mb-1">Warning</span><x-progress value="75" intent="warning" /></div>
                                                        <div><span class="text-[10px] text-neutral-400 block mb-1">Neutral</span><x-progress value="75" intent="neutral" /></div>
                                                    </div>
                                                </div>

                                                <!-- Rounded -->
                                                <div>
                                                    <span class="text-xs font-bold text-neutral-400 block mb-3 uppercase tracking-wider">Rounded Radii</span>
                                                    <div class="space-y-4">
                                                        <div><span class="text-[10px] text-neutral-400 block mb-1">Full Rounded (default)</span><x-progress value="45" rounded="full" /></div>
                                                        <div><span class="text-[10px] text-neutral-400 block mb-1">Medium Rounded</span><x-progress value="45" rounded="md" /></div>
                                                        <div><span class="text-[10px] text-neutral-400 block mb-1">Square / None</span><x-progress value="45" rounded="none" /></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Indeterminate and Striped States -->
                                        <x-showcase.preview title="Indeterminate & Striped States" :code="$progressSpecialCode" id="preview-progress-special">
                                            <div class="w-full max-w-xl space-y-6">
                                                <div>
                                                    <span class="text-xs text-neutral-400 mb-1 block">Indeterminate (35% Sliding Track)</span>
                                                    <x-progress />
                                                </div>
                                                <div>
                                                    <span class="text-xs text-neutral-400 mb-1 block">Static Translucent Stripes</span>
                                                    <x-progress value="65" striped />
                                                </div>
                                                <div>
                                                    <span class="text-xs text-neutral-400 mb-1 block">Animated Stripes (Determinate Only)</span>
                                                    <x-progress value="65" striped animated />
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Label Customizations -->
                                        <x-showcase.preview title="Labels & Positionings" :code="$progressLabelCode" id="preview-progress-labels">
                                            <div class="w-full max-w-xl space-y-6">
                                                <div>
                                                    <span class="text-xs text-neutral-400 mb-1 block">Top Position (default)</span>
                                                    <x-progress value="80" showLabel labelPosition="top" />
                                                </div>
                                                <div>
                                                    <span class="text-xs text-neutral-400 mb-1 block">Bottom Position</span>
                                                    <x-progress value="80" showLabel labelPosition="bottom" />
                                                </div>
                                                <div>
                                                    <span class="text-xs text-neutral-400 mb-1 block">Inline Position (intent contrast: Primary)</span>
                                                    <x-progress value="80" showLabel labelPosition="inline" size="lg" intent="primary" />
                                                </div>
                                                <div>
                                                    <span class="text-xs text-neutral-400 mb-1 block">Inline Position (intent contrast: Warning)</span>
                                                    <x-progress value="80" showLabel labelPosition="inline" size="lg" intent="warning" />
                                                </div>
                                                <div>
                                                    <span class="text-xs text-neutral-400 mb-1 block">Custom Text Label (Precedence)</span>
                                                    <x-progress value="60" label="6 of 10 completed" showLabel />
                                                </div>
                                                <div>
                                                    <span class="text-xs text-neutral-400 mb-1 block">Custom Currency Progress</span>
                                                    <x-progress value="60" label="₹1,200 of ₹2,000 raised" showLabel />
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Interactive Progress Sandbox -->
                                        <x-showcase.preview title="Interactive Progress Sandbox" :code="$progressInteractiveCode" id="preview-progress-interactive">
                                            <div x-data="{ progressVal: 45 }" class="w-full max-w-sm space-y-4 p-4 border border-[color:var(--color-border)] rounded-xl bg-neutral-50/50">
                                                <span class="text-xs font-bold text-neutral-400 block mb-1 uppercase tracking-wider">Dynamic Controller</span>
                                                <x-progress :xBind="'progressVal'" showLabel size="lg" rounded="md" striped animated />
                                                
                                                <div class="flex flex-wrap items-center gap-2 mt-4">
                                                    <button @click="progressVal = Math.max(0, progressVal - 10)" class="px-3 py-1.5 bg-white hover:bg-neutral-50 border rounded-lg text-xs font-semibold shadow-sm transition active:scale-95">
                                                        -10%
                                                    </button>
                                                    <button @click="progressVal = Math.min(100, progressVal + 10)" class="px-3 py-1.5 bg-white hover:bg-neutral-50 border rounded-lg text-xs font-semibold shadow-sm transition active:scale-95">
                                                        +10%
                                                    </button>
                                                    <button @click="progressVal = 0" class="px-3 py-1.5 bg-white hover:bg-neutral-50 border rounded-lg text-xs font-semibold shadow-sm transition active:scale-95">
                                                        Reset
                                                    </button>
                                                    <button @click="progressVal = 100" class="px-3 py-1.5 bg-white hover:bg-neutral-50 border rounded-lg text-xs font-semibold shadow-sm transition active:scale-95 text-emerald-600">
                                                        Complete
                                                    </button>
                                                </div>
                                            </div>
                                        </x-showcase.preview>
                                    </div>
                                </section>
                            @endif

                            {{-- Transition Utilities --}}

                            @if(isset($categories['Feedback']['Transition Utilities']))
                                @php
                                    $motionBasicCode = <<<'HTML'
<!-- Basic Transitions -->
<x-motion type="fade" show="openBasic" class="w-full">
    <div class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-sm text-[color:var(--color-text-primary)]">
        Fade Transition
    </div>
</x-motion>

<x-motion type="slide-up" show="openBasic" class="w-full">
    <div class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-sm text-[color:var(--color-text-primary)]">
        Slide Up Transition
    </div>
</x-motion>

<x-motion type="scale" show="openBasic" class="w-full">
    <div class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-sm text-[color:var(--color-text-primary)]">
        Scale Transition
    </div>
</x-motion>
HTML;

                                    $motionComboCode = <<<'HTML'
<!-- Effect Combination -->
<x-motion type="fade" effect="scale slide-up" show="openCombo" class="w-full">
    <div class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-sm">
        Combined Fade + Scale + Slide Up
    </div>
</x-motion>

<x-motion type="fade" effect="slide-right" :transform="false" show="openCombo" class="w-full">
    <div class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-sm text-neutral-500">
        Transform Disabled (:transform="false") - Fade only
    </div>
</x-motion>
HTML;

                                    $motionStaggerCode = <<<'HTML'
<!-- Staggered List Animations (Using delay props) -->
<div x-show="openStagger" class="space-y-3">
    <x-motion type="fade" effect="slide-up" delay="75" show="openStagger">
        <div class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs">Stagger item 1 (75ms delay)</div>
    </x-motion>
    <x-motion type="fade" effect="slide-up" delay="150" show="openStagger">
        <div class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs">Stagger item 2 (150ms delay)</div>
    </x-motion>
    <x-motion type="fade" effect="slide-up" delay="300" show="openStagger">
        <div class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs">Stagger item 3 (300ms delay)</div>
    </x-motion>
</div>
HTML;

                                    $motionNestedCode = <<<'HTML'
<!-- Nested Transition wrappers -->
<div x-show="openNested" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Backdrop Fades In -->
    <x-motion type="fade" show="openNested" class="fixed inset-0 bg-neutral-900/50 backdrop-blur-xs" @click="openNested = false"></x-motion>

    <!-- Content Card Scales + Slides In -->
    <x-motion type="scale" effect="slide-up" show="openNested" class="relative bg-white border rounded-xl shadow-2xl p-6 w-full max-w-sm">
        <h3 class="font-bold text-lg">Nested Dialog Box</h3>
        <p class="text-sm text-neutral-500 mt-2">The backdrop fades while this modal card scales independently.</p>
        <button @click="openNested = false" class="mt-4 px-4 py-2 bg-neutral-900 text-white rounded-lg text-sm">Close</button>
    </x-motion>
</div>
HTML;

                                    $motionCollapseCode = <<<'HTML'
<!-- Collapse height transition (Vertical grid transition) -->
<x-motion type="collapse" show="openCollapse" class="w-full">
    <div class="p-4 bg-[color:var(--color-neutral-100)] border border-[color:var(--color-border)] rounded-xl">
        This is collapsible dynamic vertical height content.
        CSS Grid rows automatically animate height without layout shifts!
    </div>
</x-motion>
HTML;

                                    $motionTimingCode = <<<'HTML'
<!-- Durations compared side-by-side -->
<x-motion type="fade" effect="slide-up" duration="fast" show="openTiming" class="w-full">
    <div class="p-3 bg-white border text-center rounded-lg shadow-xs">Fast (150ms)</div>
</x-motion>
<x-motion type="fade" effect="slide-up" duration="normal" show="openTiming" class="w-full">
    <div class="p-3 bg-white border text-center rounded-lg shadow-xs">Normal (300ms)</div>
</x-motion>
<x-motion type="fade" effect="slide-up" duration="slow" show="openTiming" class="w-full">
    <div class="p-3 bg-white border text-center rounded-lg shadow-xs">Slow (500ms)</div>
</x-motion>
HTML;

                                    $motionOriginCode = <<<'HTML'
<!-- Origins compared side-by-side -->
<x-motion type="scale" origin="center" show="openOrigins" class="w-full">
    <div class="p-3 bg-white border text-center rounded-lg shadow-xs">origin-center</div>
</x-motion>
<x-motion type="scale" origin="top" show="openOrigins" class="w-full">
    <div class="p-3 bg-white border text-center rounded-lg shadow-xs">origin-top</div>
</x-motion>
<x-motion type="scale" origin="bottom" show="openOrigins" class="w-full">
    <div class="p-3 bg-white border text-center rounded-lg shadow-xs">origin-bottom</div>
</x-motion>
<x-motion type="scale" origin="left" show="openOrigins" class="w-full">
    <div class="p-3 bg-white border text-center rounded-lg shadow-xs">origin-left</div>
</x-motion>
<x-motion type="scale" origin="right" show="openOrigins" class="w-full">
    <div class="p-3 bg-white border text-center rounded-lg shadow-xs">origin-right</div>
</x-motion>
HTML;

                                    $motionUtilitiesCode = <<<'HTML'
<!-- Hover & Active Scale premium CSS utilities -->
<div class="hover-scale active-scale p-6 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-center font-semibold cursor-pointer">
    hover-scale + active-scale
</div>

<div class="hover-lift p-6 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-center font-semibold cursor-pointer">
    hover-lift
</div>

<div class="hover-glow p-6 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-center font-semibold cursor-pointer">
    hover-glow
</div>
HTML;
                                @endphp

                                <section id="{{ $categories['Feedback']['Transition Utilities'] }}" class="js-section scroll-mt-8 mt-16">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Transition & Motion Utilities</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            A declarative motion framework backing Alpine.js entry/exit transitions and premium native CSS decorators conforming to project design system tokens.
                                        </p>
                                    </div>

                                    <div class="space-y-12">
                                        <!-- Basic Transitions Sandbox -->
                                        <x-showcase.preview title="Basic Transition Presets" :code="$motionBasicCode" id="preview-motion-basic">
                                            <div x-data="{ openBasic: true }" class="w-full space-y-6">
                                                <button @click="openBasic = !openBasic" class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] rounded-lg transition active:scale-95">
                                                    Toggle Transitions
                                                </button>
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-28 items-start">
                                                    <div>
                                                        <span class="text-xs font-semibold text-neutral-400 block mb-2">type="fade"</span>
                                                        <x-motion type="fade" show="openBasic" class="w-full">
                                                            <div class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-sm text-center font-medium">Fade Transition</div>
                                                        </x-motion>
                                                    </div>
                                                    <div>
                                                        <span class="text-xs font-semibold text-neutral-400 block mb-2">type="slide-up"</span>
                                                        <x-motion type="slide-up" show="openBasic" class="w-full">
                                                            <div class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-sm text-center font-medium">Slide Up Transition</div>
                                                        </x-motion>
                                                    </div>
                                                    <div>
                                                        <span class="text-xs font-semibold text-neutral-400 block mb-2">type="scale"</span>
                                                        <x-motion type="scale" show="openBasic" class="w-full">
                                                            <div class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-sm text-center font-medium">Scale Transition</div>
                                                        </x-motion>
                                                    </div>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Combined effects & transform flag -->
                                        <x-showcase.preview title="Combined Effects & Transform Control" :code="$motionComboCode" id="preview-motion-combo">
                                            <div x-data="{ openCombo: true }" class="w-full space-y-6">
                                                <button @click="openCombo = !openCombo" class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] rounded-lg transition active:scale-95">
                                                    Toggle Transitions
                                                </button>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 h-28 items-start">
                                                    <div>
                                                        <span class="text-xs font-semibold text-neutral-400 block mb-2">type="fade" effect="scale slide-up"</span>
                                                        <x-motion type="fade" effect="scale slide-up" show="openCombo" class="w-full">
                                                            <div class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-sm text-center font-medium">Fade + Scale + Slide Up</div>
                                                        </x-motion>
                                                    </div>
                                                    <div>
                                                        <span class="text-xs font-semibold text-neutral-400 block mb-2">type="fade" effect="slide-right" :transform="false"</span>
                                                        <x-motion type="fade" effect="slide-right" :transform="false" show="openCombo" class="w-full">
                                                            <div class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-sm text-center text-neutral-400">Transform Disabled (Fade only)</div>
                                                        </x-motion>
                                                    </div>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Collapse -->
                                        <x-showcase.preview title="Accordion Collapse (Dynamic CSS Grid Height)" :code="$motionCollapseCode" id="preview-motion-collapse">
                                            <div x-data="{ openCollapse: false }" class="w-full max-w-lg space-y-4">
                                                <button @click="openCollapse = !openCollapse" class="w-full flex items-center justify-between p-4 bg-white border border-[color:var(--color-border)] rounded-xl font-semibold shadow-xs">
                                                    <span>Collapsible Panel Trigger</span>
                                                    <svg class="w-5 h-5 transition duration-300" :class="openCollapse ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                                <x-motion type="collapse" show="openCollapse" class="w-full">
                                                    <div class="p-4 bg-neutral-50 border border-[color:var(--color-border)] rounded-xl text-sm leading-relaxed text-[color:var(--color-text-secondary)]">
                                                        This is a vertical collapse panel. Because it transitions <code>grid-template-rows</code> from <code>0fr</code> to <code>1fr</code>, it accommodates dynamic inner content heights perfectly without layout shifts or heavy JavaScript calculations.
                                                    </div>
                                                </x-motion>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Staggered Lists -->
                                        <x-showcase.preview title="Staggered List entry (Delay)" :code="$motionStaggerCode" id="preview-motion-stagger">
                                            <div x-data="{ openStagger: false }" class="w-full max-w-md space-y-4">
                                                <button @click="openStagger = !openStagger" class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] rounded-lg transition active:scale-95" x-text="openStagger ? 'Reset List' : 'Simulate Feed Entry'"></button>
                                                <div class="h-44 space-y-3">
                                                    <div x-show="openStagger" class="space-y-3">
                                                        <x-motion type="fade" effect="slide-up" delay="75" show="openStagger">
                                                            <div class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-sm">Notification 1 (Delay: 75ms)</div>
                                                        </x-motion>
                                                        <x-motion type="fade" effect="slide-up" delay="150" show="openStagger">
                                                            <div class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-sm">Notification 2 (Delay: 150ms)</div>
                                                        </x-motion>
                                                        <x-motion type="fade" effect="slide-up" delay="300" show="openStagger">
                                                            <div class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-sm">Notification 3 (Delay: 300ms)</div>
                                                        </x-motion>
                                                    </div>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Nested Transitions -->
                                        <x-showcase.preview title="Nested Component Transitions" :code="$motionNestedCode" id="preview-motion-nested">
                                            <div x-data="{ openNested: false }" class="w-full">
                                                <button @click="openNested = true" class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] rounded-lg transition active:scale-95">
                                                    Trigger Nested Dialog
                                                </button>
                                                <div x-show="openNested" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                                    <x-motion type="fade" show="openNested" class="fixed inset-0 bg-neutral-900/50 backdrop-blur-xs" @click="openNested = false"></x-motion>
                                                    <x-motion type="scale" effect="slide-up" show="openNested" class="relative bg-white border border-[color:var(--color-border)] rounded-2xl shadow-2xl p-6 w-full max-w-sm">
                                                        <h3 class="font-bold text-lg text-[color:var(--color-text-primary)]">Nested Overlay Card</h3>
                                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-2">
                                                            The backdrop panel fades in softly, while this inner content box scales and slides upward independently. Toggling is isolated.
                                                        </p>
                                                        <div class="mt-6 flex justify-end">
                                                            <button @click="openNested = false" class="px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white font-semibold rounded-lg text-sm transition">
                                                                Dismiss Modal
                                                            </button>
                                                        </div>
                                                    </x-motion>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Durations Side by Side -->
                                        <x-showcase.preview title="Timing Comparisons" :code="$motionTimingCode" id="preview-motion-timing">
                                            <div x-data="{ openTiming: true }" class="w-full space-y-6">
                                                <button @click="openTiming = !openTiming" class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] rounded-lg transition active:scale-95">
                                                    Toggle Side-by-Side
                                                </button>
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-24 items-start">
                                                    <div>
                                                        <span class="text-xs font-semibold text-neutral-400 block mb-2">duration="fast" (150ms)</span>
                                                        <x-motion type="fade" effect="slide-up" duration="fast" show="openTiming" class="w-full">
                                                            <div class="p-3 bg-white border text-center rounded-lg shadow-xs font-medium">Fast (150ms)</div>
                                                        </x-motion>
                                                    </div>
                                                    <div>
                                                        <span class="text-xs font-semibold text-neutral-400 block mb-2">duration="normal" (300ms)</span>
                                                        <x-motion type="fade" effect="slide-up" duration="normal" show="openTiming" class="w-full">
                                                            <div class="p-3 bg-white border text-center rounded-lg shadow-xs font-medium">Normal (300ms)</div>
                                                        </x-motion>
                                                    </div>
                                                    <div>
                                                        <span class="text-xs font-semibold text-neutral-400 block mb-2">duration="slow" (500ms)</span>
                                                        <x-motion type="fade" effect="slide-up" duration="slow" show="openTiming" class="w-full">
                                                            <div class="p-3 bg-white border text-center rounded-lg shadow-xs font-medium">Slow (500ms)</div>
                                                        </x-motion>
                                                    </div>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Origins Side by Side -->
                                        <x-showcase.preview title="Origins Comparison" :code="$motionOriginCode" id="preview-motion-origins">
                                            <div x-data="{ openOrigins: true }" class="w-full space-y-6">
                                                <button @click="openOrigins = !openOrigins" class="px-4 py-2 text-sm font-semibold text-white bg-[color:var(--color-primary-600)] hover:bg-[color:var(--color-primary-700)] rounded-lg transition active:scale-95">
                                                    Toggle Scales
                                                </button>
                                                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 h-24 items-start">
                                                    <div>
                                                        <span class="text-xs font-semibold text-neutral-400 block mb-2">origin="center"</span>
                                                        <x-motion type="scale" origin="center" show="openOrigins" class="w-full">
                                                            <div class="p-3 bg-white border text-center rounded-lg shadow-xs text-xs font-medium">Center</div>
                                                        </x-motion>
                                                    </div>
                                                    <div>
                                                        <span class="text-xs font-semibold text-neutral-400 block mb-2">origin="top"</span>
                                                        <x-motion type="scale" origin="top" show="openOrigins" class="w-full">
                                                            <div class="p-3 bg-white border text-center rounded-lg shadow-xs text-xs font-medium">Top</div>
                                                        </x-motion>
                                                    </div>
                                                    <div>
                                                        <span class="text-xs font-semibold text-neutral-400 block mb-2">origin="bottom"</span>
                                                        <x-motion type="scale" origin="bottom" show="openOrigins" class="w-full">
                                                            <div class="p-3 bg-white border text-center rounded-lg shadow-xs text-xs font-medium">Bottom</div>
                                                        </x-motion>
                                                    </div>
                                                    <div>
                                                        <span class="text-xs font-semibold text-neutral-400 block mb-2">origin="left"</span>
                                                        <x-motion type="scale" origin="left" show="openOrigins" class="w-full">
                                                            <div class="p-3 bg-white border text-center rounded-lg shadow-xs text-xs font-medium">Left</div>
                                                        </x-motion>
                                                    </div>
                                                    <div>
                                                        <span class="text-xs font-semibold text-neutral-400 block mb-2">origin="right"</span>
                                                        <x-motion type="scale" origin="right" show="openOrigins" class="w-full">
                                                            <div class="p-3 bg-white border text-center rounded-lg shadow-xs text-xs font-medium">Right</div>
                                                        </x-motion>
                                                    </div>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Premium CSS Decors -->
                                        <x-showcase.preview title="Premium CSS Micro-Animations" :code="$motionUtilitiesCode" id="preview-motion-utilities">
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
                                                <div>
                                                    <span class="text-xs font-semibold text-neutral-400 block mb-2">hover-scale active-scale</span>
                                                    <div class="hover-scale active-scale p-6 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-center font-semibold cursor-pointer">
                                                        Scale Utilities
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-neutral-400 block mb-2">hover-lift</span>
                                                    <div class="hover-lift p-6 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-center font-semibold cursor-pointer">
                                                        Elevation Lift
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-neutral-400 block mb-2">hover-glow</span>
                                                    <div class="hover-glow p-6 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-center font-semibold cursor-pointer">
                                                        Brand Glow
                                                    </div>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        <!-- Recommended Guidelines Map -->
                                        <div class="p-6 bg-white border border-[color:var(--color-border)] rounded-xl">
                                            <h3 class="font-bold text-base text-[color:var(--color-text-primary)] mb-4">Design System Recommendations</h3>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full text-sm divide-y divide-neutral-200">
                                                    <thead>
                                                        <tr class="text-left text-neutral-400 font-medium">
                                                            <th class="pb-3 pr-4">Component Area</th>
                                                            <th class="pb-3 px-4">Recommended Motion Combo</th>
                                                            <th class="pb-3 pl-4">Target Timing Token</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-neutral-100 text-[color:var(--color-text-secondary)]">
                                                        <tr>
                                                            <td class="py-3 pr-4 font-semibold text-[color:var(--color-text-primary)]">Modal dialogs</td>
                                                            <td class="py-3 px-4"><code>type="fade" effect="scale"</code></td>
                                                            <td class="py-3 pl-4"><code>normal</code> (300ms)</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="py-3 pr-4 font-semibold text-[color:var(--color-text-primary)]">Dropdowns / Selects</td>
                                                            <td class="py-3 px-4"><code>type="fade" effect="slide-down" :transform="false"</code> (if scrolling issues)</td>
                                                            <td class="py-3 pl-4"><code>fast</code> (150ms)</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="py-3 pr-4 font-semibold text-[color:var(--color-text-primary)]">Toasts / Alerts</td>
                                                            <td class="py-3 px-4"><code>type="fade" effect="slide-right"</code></td>
                                                            <td class="py-3 pl-4"><code>fast</code> (150ms)</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="py-3 pr-4 font-semibold text-[color:var(--color-text-primary)]">Drawers (Side panels)</td>
                                                            <td class="py-3 px-4"><code>type="slide-left"</code> or <code>type="slide-right"</code></td>
                                                            <td class="py-3 pl-4"><code>normal</code> (300ms)</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="py-3 pr-4 font-semibold text-[color:var(--color-text-primary)]">Accordions / Collapses</td>
                                                            <td class="py-3 px-4"><code>type="collapse"</code></td>
                                                            <td class="py-3 pl-4"><code>normal</code> (300ms)</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="py-3 pr-4 font-semibold text-[color:var(--color-text-primary)]">Tooltips</td>
                                                            <td class="py-3 px-4"><code>type="fade"</code></td>
                                                            <td class="py-3 pl-4"><code>fast</code> (150ms)</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Documentation Table -->
                                        <div class="p-6 bg-[color:var(--color-neutral-50)] border border-[color:var(--color-border)] rounded-xl">
                                            <h3 class="font-bold text-base text-[color:var(--color-text-primary)] mb-2">Browser & Integration Compatibility</h3>
                                            <p class="text-xs text-[color:var(--color-text-muted)] mb-4">
                                                Future enhancements may introduce semantic presets (e.g. <code>preset="modal"</code>), built on top of the existing registry without changing the public API.
                                            </p>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full text-xs text-left divide-y divide-neutral-200">
                                                    <thead>
                                                        <tr class="text-neutral-400 font-semibold uppercase tracking-wider">
                                                            <th class="pb-3 pr-4">Feature</th>
                                                            <th class="pb-3 px-4">Alpine Required</th>
                                                            <th class="pb-3 px-4">Browser Support</th>
                                                            <th class="pb-3 pl-4">Reduced Motion Fallback</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-neutral-100 text-[color:var(--color-text-secondary)]">
                                                        <tr>
                                                            <td class="py-3 pr-4 font-bold">Fade</td>
                                                            <td class="py-3 px-4">Yes</td>
                                                            <td class="py-3 px-4">All modern browsers</td>
                                                            <td class="py-3 pl-4">Immediate visibility change with opacity only</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="py-3 pr-4 font-bold">Slide</td>
                                                            <td class="py-3 px-4">Yes</td>
                                                            <td class="py-3 px-4">All modern browsers</td>
                                                            <td class="py-3 pl-4">Immediate visibility change with opacity only (transforms neutralized)</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="py-3 pr-4 font-bold">Scale</td>
                                                            <td class="py-3 px-4">Yes</td>
                                                            <td class="py-3 px-4">All modern browsers</td>
                                                            <td class="py-3 pl-4">Immediate visibility change with opacity only (transforms neutralized)</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="py-3 pr-4 font-bold">Collapse (Grid)</td>
                                                            <td class="py-3 px-4">No</td>
                                                            <td class="py-3 px-4">Modern browsers supporting Grid transitions</td>
                                                            <td class="py-3 pl-4">Immediate vertical expand/collapse (no dynamic height slide)</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            @endif

                            {{-- Scroll Animations --}}

                            @if(isset($categories['Feedback']['Scroll Animations']))
                                @php
                                    $scrollBasicCode = <<<'HTML'
<!-- Scroll Reveal Animation Types -->
<x-scroll-reveal type="fade">
    <div class="p-4 bg-white rounded-xl border shadow-sm">Fade In</div>
</x-scroll-reveal>

<x-scroll-reveal type="slide-up">
    <div class="p-4 bg-white rounded-xl border shadow-sm">Slide Up</div>
</x-scroll-reveal>

<x-scroll-reveal type="slide-left">
    <div class="p-4 bg-white rounded-xl border shadow-sm">Slide Left</div>
</x-scroll-reveal>

<x-scroll-reveal type="scale-up">
    <div class="p-4 bg-white rounded-xl border shadow-sm">Scale Up</div>
</x-scroll-reveal>
HTML;

                                    $scrollDelayCode = <<<'HTML'
<!-- Speed and Delay Scale -->
<x-scroll-reveal type="slide-up" speed="slow" delay="none">Slow, No Delay</x-scroll-reveal>
<x-scroll-reveal type="slide-up" speed="normal" delay="sm">Normal, 150ms Delay</x-scroll-reveal>
<x-scroll-reveal type="slide-up" speed="fast" delay="md">Fast, 300ms Delay</x-scroll-reveal>
HTML;

                                    $scrollOnceCode = <<<'HTML'
<!-- Repeatable Reveal (once="false") -->
<x-scroll-reveal type="fade" :once="false">
    <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-200">
        This element hides when scrolled out and re-reveals when scrolled back in.
    </div>
</x-scroll-reveal>
HTML;

                                    $scrollNestedCode = <<<'HTML'
<!-- Staggered Nested Card Reveal -->
<x-scroll-reveal as="article" type="slide-up" delay="none" class="bg-white rounded-2xl border shadow-sm overflow-hidden">
    <x-scroll-reveal type="fade" delay="sm">
        <header class="p-5 border-b">
            <h3 class="font-bold text-lg">Order Received</h3>
            <p class="text-xs text-neutral-400 mt-1">July 08, 2026</p>
        </header>
    </x-scroll-reveal>
    <x-scroll-reveal type="slide-up" delay="md" class="p-5 space-y-2">
        <p class="text-sm text-neutral-600">Your order has been confirmed and will be dispatched within 24 hours.</p>
        <p class="text-sm font-semibold text-emerald-600">Estimated delivery: July 11, 2026</p>
    </x-scroll-reveal>
    <x-scroll-reveal type="fade" delay="lg" class="px-5 pb-5">
        <button class="w-full py-2.5 bg-neutral-900 text-white text-sm font-semibold rounded-xl hover:bg-neutral-800 transition active:scale-95">
            Track Order
        </button>
    </x-scroll-reveal>
</x-scroll-reveal>
HTML;
                                @endphp

                                <section id="{{ $categories['Feedback']['Scroll Animations'] }}" class="js-section scroll-mt-8 mt-16">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Scroll Animations</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            Lightweight, Intersection Observer-powered entrance animations. Elements start visible (no-JS safe), then animate in when they enter the viewport. Respects <code>prefers-reduced-motion</code>.
                                        </p>
                                    </div>

                                    <div class="space-y-12">
                                        {{-- Animation Types --}}
                                        <x-showcase.preview title="Animation Types" :code="$scrollBasicCode" id="preview-scroll-types">
                                            <div class="w-full space-y-4 max-w-sm">
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">type="fade"</span>
                                                    <x-scroll-reveal type="fade" class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-sm font-medium text-center">
                                                        Fade In
                                                    </x-scroll-reveal>
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">type="slide-up"</span>
                                                    <x-scroll-reveal type="slide-up" class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-sm font-medium text-center">
                                                        Slide Up
                                                    </x-scroll-reveal>
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">type="slide-down"</span>
                                                    <x-scroll-reveal type="slide-down" class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-sm font-medium text-center">
                                                        Slide Down
                                                    </x-scroll-reveal>
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">type="slide-left"</span>
                                                    <x-scroll-reveal type="slide-left" class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-sm font-medium text-center">
                                                        Slide Left
                                                    </x-scroll-reveal>
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">type="slide-right"</span>
                                                    <x-scroll-reveal type="slide-right" class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-sm font-medium text-center">
                                                        Slide Right
                                                    </x-scroll-reveal>
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">type="scale-up"</span>
                                                    <x-scroll-reveal type="scale-up" class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-sm font-medium text-center">
                                                        Scale Up
                                                    </x-scroll-reveal>
                                                </div>
                                                <div>
                                                    <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-2 block">type="scale-down"</span>
                                                    <x-scroll-reveal type="scale-down" class="p-4 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs text-sm font-medium text-center">
                                                        Scale Down
                                                    </x-scroll-reveal>
                                                </div>
                                            </div>
                                        </x-showcase.preview>

                                        {{-- Speed and Delay Scale --}}
                                        <x-showcase.preview title="Speed & Delay Scales" :code="$scrollDelayCode" id="preview-scroll-delays">
                                            <div class="w-full space-y-3 max-w-sm">
                                                <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mb-3 block uppercase tracking-wider">Delay Stagger (type=slide-up)</span>
                                                <x-scroll-reveal type="slide-up" delay="none" class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl text-xs flex items-center justify-between">
                                                    <span class="font-medium">No Delay</span>
                                                    <code class="text-neutral-400 text-[10px]">delay="none" (0ms)</code>
                                                </x-scroll-reveal>
                                                <x-scroll-reveal type="slide-up" delay="xs" class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl text-xs flex items-center justify-between">
                                                    <span class="font-medium">XS Delay</span>
                                                    <code class="text-neutral-400 text-[10px]">delay="xs" (75ms)</code>
                                                </x-scroll-reveal>
                                                <x-scroll-reveal type="slide-up" delay="sm" class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl text-xs flex items-center justify-between">
                                                    <span class="font-medium">SM Delay</span>
                                                    <code class="text-neutral-400 text-[10px]">delay="sm" (150ms)</code>
                                                </x-scroll-reveal>
                                                <x-scroll-reveal type="slide-up" delay="md" class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl text-xs flex items-center justify-between">
                                                    <span class="font-medium">MD Delay</span>
                                                    <code class="text-neutral-400 text-[10px]">delay="md" (300ms)</code>
                                                </x-scroll-reveal>
                                                <x-scroll-reveal type="slide-up" delay="lg" class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl text-xs flex items-center justify-between">
                                                    <span class="font-medium">LG Delay</span>
                                                    <code class="text-neutral-400 text-[10px]">delay="lg" (500ms)</code>
                                                </x-scroll-reveal>
                                                <x-scroll-reveal type="slide-up" delay="xl" class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl text-xs flex items-center justify-between">
                                                    <span class="font-medium">XL Delay</span>
                                                    <code class="text-neutral-400 text-[10px]">delay="xl" (1000ms)</code>
                                                </x-scroll-reveal>

                                                <span class="text-xs font-semibold text-[color:var(--color-text-muted)] mt-4 mb-3 block uppercase tracking-wider">Speed Scale (type=fade)</span>
                                                <x-scroll-reveal type="fade" speed="fast" class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl text-xs flex items-center justify-between">
                                                    <span class="font-medium">Fast</span>
                                                    <code class="text-neutral-400 text-[10px]">speed="fast" (150ms)</code>
                                                </x-scroll-reveal>
                                                <x-scroll-reveal type="fade" speed="normal" class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl text-xs flex items-center justify-between">
                                                    <span class="font-medium">Normal</span>
                                                    <code class="text-neutral-400 text-[10px]">speed="normal" (300ms)</code>
                                                </x-scroll-reveal>
                                                <x-scroll-reveal type="fade" speed="slow" class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl text-xs flex items-center justify-between">
                                                    <span class="font-medium">Slow</span>
                                                    <code class="text-neutral-400 text-[10px]">speed="slow" (500ms)</code>
                                                </x-scroll-reveal>
                                                <x-scroll-reveal type="fade" speed="800ms" class="p-3 bg-white border border-[color:var(--color-border)] rounded-xl text-xs flex items-center justify-between">
                                                    <span class="font-medium">Custom</span>
                                                    <code class="text-neutral-400 text-[10px]">speed="800ms" (pass-through)</code>
                                                </x-scroll-reveal>
                                            </div>
                                        </x-showcase.preview>

                                        {{-- Repeatable Reveals --}}
                                        <x-showcase.preview title="Repeatable Reveals (once=false)" :code="$scrollOnceCode" id="preview-scroll-repeat">
                                            <div class="w-full max-w-sm space-y-4">
                                                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 font-medium">
                                                    💡 Scroll down past this card and back up to see it re-animate. Requires scrolling within the page.
                                                </div>
                                                <x-scroll-reveal type="slide-up" :once="false" class="p-5 bg-white border border-[color:var(--color-border)] rounded-xl shadow-xs space-y-2">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-10 h-10 rounded-full bg-[color:var(--color-primary-100)] flex items-center justify-center text-[color:var(--color-primary-600)] font-bold text-sm shrink-0">↕</div>
                                                        <div>
                                                            <p class="font-semibold text-sm text-[color:var(--color-text-primary)]">Repeatable Entry</p>
                                                            <p class="text-xs text-[color:var(--color-text-muted)]">once=false — hides on scroll-out, reveals on scroll-in</p>
                                                        </div>
                                                    </div>
                                                </x-scroll-reveal>
                                                <x-scroll-reveal type="fade" :once="true" class="p-5 bg-[color:var(--color-neutral-50)] border border-[color:var(--color-border)] rounded-xl shadow-xs space-y-2">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold text-sm shrink-0">✓</div>
                                                        <div>
                                                            <p class="font-semibold text-sm text-[color:var(--color-text-primary)]">One-Time Entry</p>
                                                            <p class="text-xs text-[color:var(--color-text-muted)]">once=true (default) — observer disconnects after first reveal</p>
                                                        </div>
                                                    </div>
                                                </x-scroll-reveal>
                                            </div>
                                        </x-showcase.preview>

                                        {{-- Staggered Nested Cards --}}
                                        <x-showcase.preview title="Staggered Nested Reveal (Real-world Card)" :code="$scrollNestedCode" id="preview-scroll-nested">
                                            <div class="w-full max-w-sm space-y-4">
                                                <span class="text-xs text-[color:var(--color-text-muted)] block">Header, content, and button reveal in sequence using staggered delays.</span>

                                                <x-scroll-reveal as="article" type="slide-up" delay="none" class="bg-white rounded-2xl border border-[color:var(--color-border)] shadow-sm overflow-hidden">
                                                    <x-scroll-reveal type="fade" delay="sm">
                                                        <header class="p-5 border-b border-[color:var(--color-border)] flex items-center gap-3">
                                                            <div class="w-9 h-9 rounded-full bg-[color:var(--color-primary-100)] flex items-center justify-center shrink-0">
                                                                <svg class="w-4 h-4 text-[color:var(--color-primary-600)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                            </div>
                                                            <div>
                                                                <h3 class="font-bold text-sm text-[color:var(--color-text-primary)]">Order Confirmed</h3>
                                                                <p class="text-[11px] text-[color:var(--color-text-muted)]">July 08, 2026</p>
                                                            </div>
                                                        </header>
                                                    </x-scroll-reveal>

                                                    <x-scroll-reveal type="slide-up" delay="md" class="p-5 space-y-2">
                                                        <p class="text-sm text-[color:var(--color-text-secondary)] leading-relaxed">Your order has been confirmed and will be dispatched within 24 hours.</p>
                                                        <p class="text-sm font-semibold text-[color:var(--color-success-600)]">Estimated delivery: July 11, 2026</p>
                                                    </x-scroll-reveal>

                                                    <x-scroll-reveal type="fade" delay="lg" class="px-5 pb-5">
                                                        <button class="w-full py-2.5 bg-[color:var(--color-neutral-900)] hover:bg-[color:var(--color-neutral-800)] text-white text-sm font-semibold rounded-xl transition active:scale-95">
                                                            Track Order
                                                        </button>
                                                    </x-scroll-reveal>
                                                </x-scroll-reveal>
                                            </div>
                                        </x-showcase.preview>
                                    </div>
                                </section>
                            @endif

                            {{-- Page Transitions --}}

                            @if(isset($categories['Feedback']['Page Transitions']))
                                @php
                                    $pageTransitionCode = <<<'HTML'
<!-- Simulated Page Transitions (Morphing Sandbox) -->
<div x-data="{ 
    activeTab: 'dashboard',
    changeTab(tab) {
        if ('startViewTransition' in document) {
            document.startViewTransition(() => {
                this.activeTab = tab;
            });
        } else {
            // CSS Transition fallback
            const panel = document.querySelector('.simulated-main');
            if (panel) {
                panel.classList.add('ui-transition-fade-out');
                setTimeout(() => {
                    this.activeTab = tab;
                    panel.classList.remove('ui-transition-fade-out');
                    panel.classList.add('ui-transition-fade-in');
                    setTimeout(() => {
                        panel.classList.remove('ui-transition-fade-in');
                    }, 300);
                }, 150);
            } else {
                this.activeTab = tab;
            }
        }
    }
}" class="w-full border rounded-2xl bg-white shadow-sm overflow-hidden flex flex-col md:flex-row h-[320px]">
    <!-- Mock Sidebar -->
    <aside class="w-full md:w-48 bg-neutral-50 border-r p-4 flex flex-col gap-1 shrink-0">
        <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-2 block">Staff Navigation</span>
        <button @click="changeTab('dashboard')" :class="activeTab === 'dashboard' ? 'bg-primary-50 text-primary-600 font-semibold' : 'text-neutral-600 hover:bg-neutral-100'" class="w-full text-left px-3 py-2 rounded-lg text-xs transition">Dashboard</button>
        <button @click="changeTab('orders')" :class="activeTab === 'orders' ? 'bg-primary-50 text-primary-600 font-semibold' : 'text-neutral-600 hover:bg-neutral-100'" class="w-full text-left px-3 py-2 rounded-lg text-xs transition">Orders</button>
        <button @click="changeTab('settings')" :class="activeTab === 'settings' ? 'bg-primary-50 text-primary-600 font-semibold' : 'text-neutral-600 hover:bg-neutral-100'" class="w-full text-left px-3 py-2 rounded-lg text-xs transition">Settings</button>
    </aside>
    <!-- Mock Main Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-12 border-b px-4 flex items-center bg-white shrink-0">
            <span class="text-xs font-semibold text-neutral-500" x-text="activeTab.charAt(0).toUpperCase() + activeTab.slice(1)"></span>
        </header>
        <main class="flex-1 p-5 overflow-y-auto simulated-main">
            <div x-show="activeTab === 'dashboard'" class="space-y-3">
                <h4 class="font-bold text-base">Dashboard Home</h4>
                <p class="text-xs text-neutral-500 leading-relaxed">Welcome back, staff member. View transitions keep your navigation fixed and morph body contents dynamically.</p>
            </div>
            <div x-show="activeTab === 'orders'" x-cloak class="space-y-3">
                <h4 class="font-bold text-base">Recent Orders</h4>
                <p class="text-xs text-neutral-500 leading-relaxed">No orders require action. Native view transitions will slide up or crossfade new elements seamlessly.</p>
            </div>
            <div x-show="activeTab === 'settings'" x-cloak class="space-y-3">
                <h4 class="font-bold text-base">Admin Settings</h4>
                <p class="text-xs text-neutral-500 leading-relaxed">Adjust layout behavior. Prefers-reduced-motion triggers will automatically switch view transitions off instantly.</p>
            </div>
        </main>
    </div>
</div>
HTML;
                                @endphp

                                <section id="{{ $categories['Feedback']['Page Transitions'] }}" class="js-section scroll-mt-8 mt-16">
                                    <div class="border-b border-[color:var(--color-border)] pb-4 mb-6">
                                        <h2 class="text-2xl font-bold text-[color:var(--color-text-primary)]">Page Transitions</h2>
                                        <p class="text-sm text-[color:var(--color-text-muted)] mt-1">
                                            Smooth app-like navigation morphing. Keeps sidebar and topbar fixed while transitioning body content dynamically.
                                        </p>
                                    </div>

                                    <div class="space-y-12">
                                        {{-- Simulated Page Switcher --}}
                                        <x-showcase.preview title="Simulated Page Navigator Navigation" :code="$pageTransitionCode" id="preview-page-transition">
                                            <div x-data="{ 
                                                activeTab: 'dashboard',
                                                changeTab(tab) {
                                                    if ('startViewTransition' in document) {
                                                        document.startViewTransition(() => {
                                                            this.activeTab = tab;
                                                        });
                                                    } else {
                                                        // CSS Transition fallback
                                                        const panel = document.querySelector('.simulated-main');
                                                        if (panel) {
                                                            panel.classList.add('ui-transition-fade-out');
                                                            setTimeout(() => {
                                                                this.activeTab = tab;
                                                                panel.classList.remove('ui-transition-fade-out');
                                                                panel.classList.add('ui-transition-fade-in');
                                                                setTimeout(() => {
                                                                    panel.classList.remove('ui-transition-fade-in');
                                                                }, 300);
                                                            }, 150);
                                                        } else {
                                                            this.activeTab = tab;
                                                        }
                                                    }
                                                }
                                            }" class="w-full border border-[color:var(--color-border)] rounded-2xl bg-white shadow-sm overflow-hidden flex flex-col md:flex-row h-[280px]">
                                                <!-- Mock Sidebar -->
                                                <aside class="w-full md:w-48 bg-neutral-50 border-r border-[color:var(--color-border)] p-4 flex flex-col gap-1 shrink-0">
                                                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-2 block">Staff Navigation</span>
                                                    <button @click="changeTab('dashboard')" :class="activeTab === 'dashboard' ? 'bg-[color:var(--color-primary-50)] text-[color:var(--color-primary-600)] font-semibold' : 'text-[color:var(--color-neutral-600)] hover:bg-neutral-100'" class="w-full text-left px-3 py-2 rounded-lg text-xs transition cursor-pointer">Dashboard</button>
                                                    <button @click="changeTab('orders')" :class="activeTab === 'orders' ? 'bg-[color:var(--color-primary-50)] text-[color:var(--color-primary-600)] font-semibold' : 'text-[color:var(--color-neutral-600)] hover:bg-neutral-100'" class="w-full text-left px-3 py-2 rounded-lg text-xs transition cursor-pointer">Orders</button>
                                                    <button @click="changeTab('settings')" :class="activeTab === 'settings' ? 'bg-[color:var(--color-primary-50)] text-[color:var(--color-primary-600)] font-semibold' : 'text-[color:var(--color-neutral-600)] hover:bg-neutral-100'" class="w-full text-left px-3 py-2 rounded-lg text-xs transition cursor-pointer">Settings</button>
                                                </aside>
                                                <!-- Mock Main Area -->
                                                <div class="flex-1 flex flex-col min-w-0">
                                                    <header class="h-12 border-b border-[color:var(--color-border)] px-4 flex items-center bg-white shrink-0">
                                                        <span class="text-xs font-semibold text-neutral-500" x-text="activeTab.charAt(0).toUpperCase() + activeTab.slice(1)"></span>
                                                    </header>
                                                    <main class="flex-1 p-5 overflow-y-auto simulated-main bg-white">
                                                        <div x-show="activeTab === 'dashboard'" class="space-y-3">
                                                            <h4 class="font-bold text-sm text-[color:var(--color-text-primary)]">Dashboard Home</h4>
                                                            <p class="text-xs text-[color:var(--color-text-muted)] leading-relaxed">Welcome back, staff member. View transitions keep your navigation fixed and morph body contents dynamically.</p>
                                                        </div>
                                                        <div x-show="activeTab === 'orders'" x-cloak class="space-y-3">
                                                            <h4 class="font-bold text-sm text-[color:var(--color-text-primary)]">Recent Orders</h4>
                                                            <p class="text-xs text-[color:var(--color-text-muted)] leading-relaxed">No orders require action. Native view transitions will slide up or crossfade new elements seamlessly.</p>
                                                        </div>
                                                        <div x-show="activeTab === 'settings'" x-cloak class="space-y-3">
                                                            <h4 class="font-bold text-sm text-[color:var(--color-text-primary)]">Admin Settings</h4>
                                                            <p class="text-xs text-[color:var(--color-text-muted)] leading-relaxed">Adjust layout behavior. Prefers-reduced-motion triggers will automatically switch view transitions off instantly.</p>
                                                        </div>
                                                    </main>
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
