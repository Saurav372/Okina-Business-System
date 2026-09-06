@props([
    'name' => '',
    'size' => null,
    'mime' => null,
    'url' => null,
    'thumbnail' => null,
    'preview' => null,
    'downloadUrl' => null,
    'downloadName' => null,
    'forcePreviewType' => null,
    'variant' => 'tile', // tile or list
    'selectable' => false,
    'selected' => false,
    'showActions' => true,
    'showBadge' => true,
    'disabled' => false,
    'loading' => false,
])

@php
    use App\Support\Ui\UiFileType;

    if (!$loading) {
        $fileType = UiFileType::resolve($mime, $forcePreviewType);
        $previewType = $fileType['previewType'];
        $displayName = filled($name) ? $name : 'Untitled file';
        $showThumbnail = $thumbnail && $previewType === 'image';

        // Extension-preserving truncation
        $ext  = pathinfo($displayName, PATHINFO_EXTENSION);
        $stem = $ext ? substr($displayName, 0, -(strlen($ext) + 1)) : $displayName;

        // Action resolution
        if ($disabled) {
            $action       = 'none';
            $actionTarget = '';
        } elseif (filled($preview)) {
            $action       = 'preview';
            $actionTarget = $preview;
        } elseif (filled($url)) {
            $action       = 'url';
            $actionTarget = $url;
        } else {
            $action       = 'none';
            $actionTarget = '';
        }

        $isInteractive = $action !== 'none';
        $cursorClass   = $isInteractive ? 'cursor-pointer' : 'cursor-default';

        $ariaLabel = match($action) {
            'preview' => 'Preview ' . $displayName,
            'url'     => 'Open ' . $displayName,
            default   => $displayName,
        };

        // Shared classes
        $cardClasses = [
            'relative flex border rounded-[var(--radius-lg)] bg-white dark:bg-[color:var(--color-neutral-900)] transition-all duration-200 outline-none select-none group border-[color:var(--color-border)] hover:border-[color:var(--color-border-hover)] hover:shadow-sm',
            'opacity-50 pointer-events-none' => $disabled,
            'flex-col items-center p-4 gap-3 text-center' => $variant === 'tile',
            'items-center p-3 gap-3 text-left w-full' => $variant === 'list',
        ];
    }
@endphp

@if ($loading)
    @if ($variant === 'tile')
        <div class="border border-[color:var(--color-border)] rounded-[var(--radius-lg)] p-4 flex flex-col items-center gap-3 w-full bg-white dark:bg-[color:var(--color-neutral-900)]">
            <x-skeleton variant="block" height="56" width="56" class="rounded-[var(--radius-md)]" />
            <x-skeleton variant="line" width="70%" />
            <x-skeleton variant="line" width="40%" />
        </div>
    @else
        <div class="border border-[color:var(--color-border)] rounded-[var(--radius-lg)] p-3 flex items-center gap-3 w-full bg-white dark:bg-[color:var(--color-neutral-900)]">
            <x-skeleton variant="circle" width="32" height="32" />
            <div class="flex-1 space-y-1.5">
                <x-skeleton variant="line" width="40%" />
            </div>
            <x-skeleton variant="line" width="60px" class="shrink-0" />
            <x-skeleton variant="line" width="40px" class="shrink-0" />
        </div>
    @endif
@else
    <div class="relative w-full group">
        {{-- Primary activation element --}}
        @if ($action === 'preview')
            <button
                type="button"
                x-data="{ action: 'preview', target: '{{ $actionTarget }}', triggerAction() { this.$dispatch('open-overlay', { id: this.target }); } }"
                x-on:click="triggerAction()"
                x-on:keydown.enter.prevent="triggerAction()"
                x-on:keydown.space.prevent="triggerAction()"
                @class([
                    ...$cardClasses,
                    'focus-visible:ring-2 focus-visible:ring-[color:var(--color-primary)] focus-visible:ring-offset-2',
                ])
                aria-label="{{ $ariaLabel }}"
                data-file-card
                data-file-type="{{ $fileType['label'] }}"
                data-variant="{{ $variant }}"
                data-preview-type="{{ $previewType }}"
                aria-disabled="{{ $disabled ? 'true' : 'false' }}"
            >
                @include('components.file-card-content')
            </button>
        @elseif ($action === 'url')
            <a
                href="{{ $actionTarget }}"
                target="_blank"
                rel="noopener noreferrer"
                @class([
                    ...$cardClasses,
                    'focus-visible:ring-2 focus-visible:ring-[color:var(--color-primary)] focus-visible:ring-offset-2',
                ])
                aria-label="{{ $ariaLabel }}"
                data-file-card
                data-file-type="{{ $fileType['label'] }}"
                data-variant="{{ $variant }}"
                data-preview-type="{{ $previewType }}"
                aria-disabled="{{ $disabled ? 'true' : 'false' }}"
            >
                @include('components.file-card-content')
            </a>
        @else
            <div
                @class($cardClasses)
                aria-label="{{ $ariaLabel }}"
                data-file-card
                data-file-type="{{ $fileType['label'] }}"
                data-variant="{{ $variant }}"
                data-preview-type="{{ $previewType }}"
                aria-disabled="true"
            >
                @include('components.file-card-content')
            </div>
        @endif

        {{-- Selectable Checkbox (rendered outside button/link, absolute positioning) --}}
        @if ($selectable && !$disabled)
            <div class="absolute top-2.5 left-2.5 z-10 md:opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity duration-200" x-on:click.stop x-on:keydown.stop>
                <input
                    type="checkbox"
                    {{ $selected ? 'checked' : '' }}
                    class="w-4 h-4 rounded border-[color:var(--color-border)] text-[color:var(--color-primary)] focus:ring-[color:var(--color-primary)] cursor-pointer"
                    aria-label="Select {{ $displayName }}"
                />
            </div>
        @endif

        {{-- Actions menu: sibling, stops propagation --}}
        @if ($showActions && isset($actions) && !$disabled)
            <div
                class="absolute top-2.5 right-2.5 z-10 md:opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity duration-200"
                x-on:click.stop
                x-on:keydown.stop
            >
                <x-dropdown align="end">
                    <x-dropdown.trigger>
                        <button
                            type="button"
                            class="p-1 rounded-lg text-[color:var(--color-text-muted)] hover:bg-[color:var(--color-surface-secondary)] hover:text-[color:var(--color-text-primary)] transition-colors focus:outline-none focus:ring-2 focus:ring-[color:var(--color-primary)]"
                            aria-label="Actions for {{ $displayName }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                            </svg>
                        </button>
                    </x-dropdown.trigger>
                    <x-dropdown.content>
                        {{ $actions }}
                    </x-dropdown.content>
                </x-dropdown>
            </div>
        @endif
    </div>
@endif
