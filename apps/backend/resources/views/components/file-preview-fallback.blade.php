@props([
    'accent' => 'neutral',
    'icon' => 'file',
    'displayName' => 'Untitled file',
    'size' => null,
    'resolvedMimeLabel' => null,
    'downloadUrl' => null,
    'downloadName' => null,
    'desc' => 'No preview is available for this file type.',
])

<div class="flex flex-col items-center gap-4 text-center p-8 bg-[color:var(--color-neutral-50)] dark:bg-[color:var(--color-neutral-800)] border border-[color:var(--color-border)] rounded-xl w-full max-w-sm shadow-sm m-auto">
    <div class="p-4 rounded-full bg-{{ $accent }}-100 text-{{ $accent }}-600 dark:bg-{{ $accent }}-950/30 dark:text-{{ $accent }}-400">
        @include('components.file-card-icons', ['icon' => $icon, 'class' => 'w-8 h-8'])
    </div>
    
    <div class="min-w-0 w-full">
        <h3 class="text-sm font-bold text-[color:var(--color-text-primary)] truncate">{{ $displayName }}</h3>
        @if ($size || $resolvedMimeLabel)
            <p class="text-xs text-[color:var(--color-text-muted)] mt-1">
                {{ $size }}{{ $size && $resolvedMimeLabel ? ' · ' : '' }}{{ $resolvedMimeLabel }}
            </p>
        @endif
    </div>
    
    <p class="text-xs text-[color:var(--color-text-muted)]">{{ $desc }}</p>
    
    @if ($downloadUrl)
        <a
            href="{{ $downloadUrl }}"
            download="{{ $downloadName }}"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-[color:var(--color-primary)] text-white rounded-lg hover:bg-[color:var(--color-primary-hover)] transition-colors focus:outline-none focus:ring-2 focus:ring-[color:var(--color-primary)]"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <span>Download File</span>
        </a>
    @endif
</div>
