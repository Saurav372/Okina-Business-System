@props([
    'title',
    'id' => null,
    'code' => null,
    'defaultViewport' => 'desktop',
    'defaultTheme' => 'light',
])

@php
    $viewports = config('ui-showcase.viewports');
    $initialViewportClass = $viewports[$defaultViewport] ?? $viewports['desktop'];
@endphp

<div class="mb-12 showcase-component" id="{{ $id ?? Str::slug($title) }}">
    <div class="flex flex-col border border-[color:var(--color-border)] rounded-2xl overflow-hidden bg-white shadow-sm">
        
        <!-- Toolbar -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-[color:var(--color-border)] bg-[color:var(--color-neutral-50)] flex-wrap gap-4">
            <h3 class="text-sm font-semibold text-[color:var(--color-neutral-700)]">{{ $title }}</h3>
            
            <div class="flex items-center gap-3 sm:gap-4">
                
                <!-- Theme Toggles -->
                <div class="flex items-center gap-1 bg-[color:var(--color-neutral-200)] p-1 rounded-lg">
                    <button type="button" 
                        class="js-theme-btn p-1.5 rounded-md text-[color:var(--color-neutral-500)] hover:text-[color:var(--color-neutral-700)] aria-pressed:bg-white aria-pressed:text-[color:var(--color-neutral-900)] aria-pressed:shadow-sm transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-primary)]" 
                        data-theme="light" 
                        aria-pressed="{{ $defaultTheme === 'light' ? 'true' : 'false' }}" 
                        aria-label="Light mode">
                        <!-- Sun Icon -->
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </button>
                    <button type="button" 
                        class="js-theme-btn p-1.5 rounded-md text-[color:var(--color-neutral-500)] hover:text-[color:var(--color-neutral-700)] aria-pressed:bg-white aria-pressed:text-[color:var(--color-neutral-900)] aria-pressed:shadow-sm transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-primary)]" 
                        data-theme="dark" 
                        aria-pressed="{{ $defaultTheme === 'dark' ? 'true' : 'false' }}" 
                        aria-label="Dark mode">
                        <!-- Moon Icon -->
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                </div>

                <!-- Viewport Toggles -->
                <div class="flex items-center gap-1 bg-[color:var(--color-neutral-200)] p-1 rounded-lg">
                    <button type="button" 
                        class="js-viewport-btn p-1.5 rounded-md text-[color:var(--color-neutral-500)] hover:text-[color:var(--color-neutral-700)] aria-pressed:bg-white aria-pressed:text-[color:var(--color-neutral-900)] aria-pressed:shadow-sm transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-primary)]" 
                        data-viewport="mobile" 
                        data-width="{{ $viewports['mobile'] }}"
                        aria-pressed="{{ $defaultViewport === 'mobile' ? 'true' : 'false' }}" 
                        aria-label="Mobile viewport">
                        <!-- Mobile Icon -->
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </button>
                    <button type="button" 
                        class="js-viewport-btn p-1.5 rounded-md text-[color:var(--color-neutral-500)] hover:text-[color:var(--color-neutral-700)] aria-pressed:bg-white aria-pressed:text-[color:var(--color-neutral-900)] aria-pressed:shadow-sm transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-primary)]" 
                        data-viewport="tablet" 
                        data-width="{{ $viewports['tablet'] }}"
                        aria-pressed="{{ $defaultViewport === 'tablet' ? 'true' : 'false' }}" 
                        aria-label="Tablet viewport">
                        <!-- Tablet Icon -->
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </button>
                    <button type="button" 
                        class="js-viewport-btn p-1.5 rounded-md text-[color:var(--color-neutral-500)] hover:text-[color:var(--color-neutral-700)] aria-pressed:bg-white aria-pressed:text-[color:var(--color-neutral-900)] aria-pressed:shadow-sm transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-primary)]" 
                        data-viewport="desktop" 
                        data-width="max-w-none"
                        aria-pressed="{{ $defaultViewport === 'desktop' ? 'true' : 'false' }}" 
                        aria-label="Desktop viewport">
                        <!-- Desktop Icon -->
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </button>
                </div>
                
                @if($code)
                <!-- Copy Code Button -->
                <button type="button" 
                    class="js-copy-btn flex items-center justify-center gap-1.5 px-3 py-1.5 text-sm font-medium text-[color:var(--color-neutral-600)] bg-[color:var(--color-surface)] border border-[color:var(--color-border)] hover:bg-[color:var(--color-neutral-100)] hover:text-[color:var(--color-text-primary)] rounded-lg transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-primary)] shadow-[var(--shadow-xs)] w-[110px]" 
                >
                    <script type="text/plain" class="js-code-template">{!! $code !!}</script>
                    <!-- Copy Icon -->
                    <svg class="w-4 h-4 js-copy-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    <!-- Check Icon (Hidden by default) -->
                    <svg class="w-4 h-4 js-check-icon hidden text-[color:var(--color-success)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                    
                    <span class="js-copy-text">Copy Code</span>
                </button>
                @endif
            </div>
        </div>

        <!-- Preview Isolation Area -->
        <div class="bg-[image:repeating-linear-gradient(45deg,#f4f6f8_25%,transparent_25%,transparent_75%,#f4f6f8_75%,#f4f6f8),repeating-linear-gradient(45deg,#f4f6f8_25%,#ffffff_25%,#ffffff_75%,#f4f6f8_75%,#f4f6f8)] bg-[length:20px_20px] bg-[position:0_0,10px_10px] p-6 sm:p-10 flex justify-center items-start overflow-hidden min-h-[300px]">
            <div class="js-preview-wrapper w-full transition-all duration-300 ease-out flex justify-center {{ $initialViewportClass }} {{ $defaultTheme === 'dark' ? 'dark' : '' }}">
                <div class="w-full text-left dark:bg-[color:var(--color-neutral-900)] dark:text-white rounded-xl transition-colors">
                    {{ $slot }}
                </div>
            </div>
        </div>
        
    </div>
</div>
