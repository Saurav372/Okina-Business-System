@props([
    'as' => 'button',
    'href' => null,
    'external' => false,
    'busy' => false,
    'confirm' => null,
    'truncate' => true,
    'variant' => 'default',
    'disabled' => false
])

@php
    $isDisabled = $disabled || $busy;
    $isLink = $as === 'link';
    $isSubmit = $as === 'submit';

    $variantClasses = match($variant) {
        'danger' => 'text-[color:var(--color-danger-600,#dc2626)] hover:bg-[color:var(--color-danger-50,#fef2f2)] focus:bg-[color:var(--color-danger-50,#fef2f2)]',
        'success' => 'text-[color:var(--color-success-600,#059669)] hover:bg-[color:var(--color-success-50,#ecfdf5)] focus:bg-[color:var(--color-success-50,#ecfdf5)]',
        default => 'text-[color:var(--color-text-secondary,#4b5563)] hover:text-[color:var(--color-text-primary,#111827)] hover:bg-[color:var(--color-surface-secondary,#f9fafb)] focus:bg-[color:var(--color-surface-secondary,#f9fafb)] focus:text-[color:var(--color-text-primary,#111827)]'
    };

    $baseClasses = 'flex items-center justify-between w-full px-4 py-2 text-sm font-medium transition-colors text-left outline-none border-none cursor-pointer';
    $stateClasses = $isDisabled ? 'opacity-50 cursor-not-allowed select-none' : '';
    $truncateClasses = $truncate ? 'truncate' : 'whitespace-normal';
@endphp

@if($isLink)
    <a
        href="{{ $isDisabled ? '#' : $href }}"
        @if($external && !$isDisabled)
            target="_blank"
            rel="noopener noreferrer"
        @endif
        role="menuitem"
        tabindex="-1"
        data-dropdown-item
        aria-disabled="{{ $isDisabled ? 'true' : 'false' }}"
        @if($busy) aria-busy="true" @endif
        @click="
            if ({{ $isDisabled ? 'true' : 'false' }}) {
                $event.preventDefault();
                return;
            }
            @if($confirm)
                const confirmed = $dispatch('dropdown-confirm', { message: '{{ $confirm }}', target: $el });
                if (!confirmed) {
                    $event.preventDefault();
                    return;
                }
            @endif
            if (closeOnClick) open = false;
        "
        {{ $attributes->merge(['class' => "{$baseClasses} {$variantClasses} {$stateClasses}"]) }}
    >
        <span class="flex items-center gap-2 {{ $truncateClasses }}">
            @if($busy)
                @if(isset($busyIcon))
                    {{ $busyIcon }}
                @else
                    <x-spinner size="sm" />
                @endif
            @elseif(isset($icon))
                <span class="flex-shrink-0 text-[color:var(--color-text-muted,#9ca3af)]">{{ $icon }}</span>
            @endif
            <span class="bc-label">{{ $slot }}</span>
        </span>
        @if(isset($shortcut))
            <span class="text-xs text-[color:var(--color-text-muted,#9ca3af)] ml-4 font-normal">{{ $shortcut }}</span>
        @endif
    </a>
@else
    <button
        type="{{ $isSubmit ? 'submit' : 'button' }}"
        @if($isDisabled) disabled aria-disabled="true" @endif
        @if($busy) aria-busy="true" @endif
        role="menuitem"
        tabindex="-1"
        data-dropdown-item
        @click="
            if ({{ $isDisabled ? 'true' : 'false' }}) {
                $event.preventDefault();
                return;
            }
            @if($confirm)
                const confirmed = $dispatch('dropdown-confirm', { message: '{{ $confirm }}', target: $el });
                if (!confirmed) {
                    $event.preventDefault();
                    return;
                }
            @endif
            if (closeOnClick) open = false;
        "
        {{ $attributes->merge(['class' => "{$baseClasses} {$variantClasses} {$stateClasses}"]) }}
    >
        <span class="flex items-center gap-2 {{ $truncateClasses }}">
            @if($busy)
                @if(isset($busyIcon))
                    {{ $busyIcon }}
                @else
                    <x-spinner size="sm" />
                @endif
            @elseif(isset($icon))
                <span class="flex-shrink-0 text-[color:var(--color-text-muted,#9ca3af)]">{{ $icon }}</span>
            @endif
            <span class="bc-label">{{ $slot }}</span>
        </span>
        @if(isset($shortcut))
            <span class="text-xs text-[color:var(--color-text-muted,#9ca3af)] ml-4 font-normal">{{ $shortcut }}</span>
        @endif
    </button>
@endif
