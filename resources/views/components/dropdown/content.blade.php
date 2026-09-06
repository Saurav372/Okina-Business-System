@props([
    'width' => 'md',
    'maxHeight' => 'none'
])

@php
    $widthClasses = match($width) {
        'sm' => 'w-[var(--dropdown-width-sm,10rem)]',
        'md' => 'w-[var(--dropdown-width-md,14rem)]',
        'lg' => 'w-[var(--dropdown-width-lg,20rem)]',
        'fit' => '',
        default => 'w-auto'
    };

    $maxHeightClasses = match($maxHeight) {
        'sm' => 'max-h-[200px] overflow-y-auto',
        'md' => 'max-h-[300px] overflow-y-auto',
        'lg' => 'max-h-[420px] overflow-y-auto',
        default => ''
    };
@endphp

<style>
    .ui-dropdown-menu {
        --z-dropdown: 50;
        z-index: var(--z-dropdown);
        position: absolute;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05), 0 0 0 1px rgba(0, 0, 0, 0.05);
    }
    
    @media (prefers-reduced-motion: reduce) {
        .ui-dropdown-transition {
            transition: opacity 80ms linear !important;
            transform: none !important;
        }
    }
</style>

<div
    x-show="open"
    x-ref="menu"
    data-dropdown-menu
    data-width-fit="{{ $width === 'fit' ? 'true' : 'false' }}"
    role="menu"
    x-transition:enter="ui-dropdown-transition transition ease-out duration-120 transform"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="ui-dropdown-transition transition ease-in duration-75 transform"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    @keydown.down.prevent="focusNext()"
    @keydown.up.prevent="focusPrev()"
    @keydown.home.prevent="focusFirst()"
    @keydown.end.prevent="focusLast()"
    @keydown.tab="open = false"
    @keydown="if ($event.key.length === 1 && $event.key.match(/[a-zA-Z]/)) { $event.preventDefault(); focusChar($event.key); }"
    {{ $attributes->merge(['class' => "ui-dropdown-menu bg-[color:var(--color-surface,white)] border border-[color:var(--color-border,#e5e7eb)] rounded-xl py-1 focus:outline-none {$widthClasses} {$maxHeightClasses}"]) }}
    style="display: none;"
>
    {{ $slot }}
</div>
