@props([
    'id',
    'name' => null,
    'url' => null,
    'mime' => null,
    'size' => null,
    'mimeLabel' => null,
    'downloadUrl' => null,
    'downloadName' => null,
    'forcePreviewType' => null,
    'pdfTimeout' => 3000,
])

@php
    use App\Support\Ui\UiFileType;

    $fileType = UiFileType::resolve($mime, $forcePreviewType);
    $previewType = $fileType['previewType'];
    $displayName = filled($name) ? $name : 'Untitled file';
    $resolvedMimeLabel = $mimeLabel ?? $fileType['label'];

    $titleId = $id . '-title';
    $metaId  = $id . '-meta';
@endphp

<div
    data-file-preview
    data-viewer="{{ $previewType }}"
>
    <x-modal :id="$id" size="full" :persistent="false">
        <x-slot:header>
            <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-[color:var(--color-border,#e5e7eb)] bg-white dark:bg-[color:var(--color-neutral-900)] w-full">
                {{-- Close and Title --}}
                <div class="flex items-center gap-4 min-w-0">
                    <button
                        type="button"
                        @click="closeModal()"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold text-[color:var(--color-text-secondary)] hover:text-[color:var(--color-text-primary)] hover:bg-[color:var(--color-surface-secondary)] transition-colors focus:outline-none focus:ring-2 focus:ring-[color:var(--color-primary)] shrink-0"
                        aria-label="Close preview"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span>Close</span>
                    </button>
                    
                    <div class="min-w-0">
                        <h2 id="{{ $titleId }}" class="text-sm font-semibold text-[color:var(--color-text-primary)] truncate">
                            {{ $displayName }}
                        </h2>
                        @if ($size || $resolvedMimeLabel)
                            <p id="{{ $metaId }}" class="text-xs text-[color:var(--color-text-muted)] mt-0.5">
                                {{ $size }}{{ $size && $resolvedMimeLabel ? ' · ' : '' }}{{ $resolvedMimeLabel }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Actions and Download --}}
                <div class="flex items-center gap-3 shrink-0">
                    @if (isset($actions))
                        <div class="flex items-center gap-2">
                            {{ $actions }}
                        </div>
                    @endif

                    @if ($downloadUrl)
                        <a
                            href="{{ $downloadUrl }}"
                            download="{{ $downloadName }}"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold bg-[color:var(--color-primary)] text-white hover:bg-[color:var(--color-primary-hover)] active:bg-[color:var(--color-primary-active)] transition-colors focus:outline-none focus:ring-2 focus:ring-[color:var(--color-primary)] focus:ring-offset-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span>Download</span>
                        </a>
                    @endif
                </div>
            </div>
        </x-slot:header>

        {{-- Preview Body --}}
        <div 
            class="flex items-center justify-center min-h-[50vh] max-h-[80vh] w-full"
            x-data="{
                init() {
                    this.$watch('open', value => {
                        if (value) {
                            this.$dispatch('file-preview-opened', { id: '{{ $id }}', previewType: '{{ $previewType }}' });
                        } else {
                            this.$dispatch('file-preview-closed', { id: '{{ $id }}' });
                        }
                    });
                }
            }"
        >
            @if ($previewType === 'image')
                <div x-data="{ imgLoaded: false, imgError: false }" class="relative max-h-[80vh] max-w-full flex items-center justify-center">
                    {{-- Loading shimmer --}}
                    <div x-show="!imgLoaded && !imgError" class="absolute inset-0 motion-safe:animate-pulse bg-[color:var(--color-neutral-200)] dark:bg-[color:var(--color-neutral-800)] rounded-lg min-h-[300px] min-w-[300px]" aria-hidden="true"></div>
                    
                    <img
                        src="{{ $url }}"
                        x-ref="img"
                        x-init="if ($refs.img.complete) imgLoaded = true"
                        x-show="!imgError"
                        x-on:load="imgLoaded = true"
                        x-on:error="imgError = true"
                        x-transition:enter="transition-opacity duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="object-contain max-h-[80vh] max-w-full rounded-lg shadow-md"
                        alt=""
                    />
                    
                    {{-- Error state --}}
                    <template x-if="imgError">
                        @include('components.file-preview-fallback', ['accent' => $fileType['accent'], 'icon' => $fileType['icon'], 'displayName' => $displayName, 'size' => $size, 'resolvedMimeLabel' => $resolvedMimeLabel, 'downloadUrl' => $downloadUrl, 'downloadName' => $downloadName])
                    </template>
                </div>

            @elseif ($previewType === 'video')
                <video controls class="max-h-[80vh] max-w-full rounded-lg shadow-md bg-black">
                    <source src="{{ $url }}" type="{{ $mime }}">
                    Your browser does not support the video tag.
                </video>

            @elseif ($previewType === 'audio')
                <div class="flex flex-col items-center gap-4 p-8 bg-[color:var(--color-neutral-50)] dark:bg-[color:var(--color-neutral-800)] border border-[color:var(--color-border)] rounded-xl w-full max-w-md shadow-sm">
                    <div class="p-4 rounded-full bg-pink-100 text-pink-600 dark:bg-pink-950/30 dark:text-pink-400">
                        @include('components.file-card-icons', ['icon' => 'audio', 'class' => 'w-8 h-8'])
                    </div>
                    <div class="text-center min-w-0 w-full">
                        <h3 class="text-sm font-bold text-[color:var(--color-text-primary)] truncate">{{ $displayName }}</h3>
                        @if ($size)<p class="text-xs text-[color:var(--color-text-muted)] mt-0.5">{{ $size }}</p>@endif
                    </div>
                    <audio controls class="w-full mt-2">
                        <source src="{{ $url }}" type="{{ $mime }}">
                        Your browser does not support the audio element.
                    </audio>
                </div>

            @elseif ($previewType === 'pdf')
                <div x-data="{ pdfFailed: false }" 
                     x-init="
                        {{ $pdfTimeout > 0
                            ? 'setTimeout(() => { try { if (!$refs.pdfFrame.contentDocument?.body?.innerHTML) pdfFailed = true } catch { pdfFailed = true } }, ' . $pdfTimeout . ')'
                            : '/* pdf timeout disabled */'
                        }}
                     "
                     class="w-full h-[70vh] relative flex items-center justify-center"
                >
                    <iframe
                        x-ref="pdfFrame"
                        x-show="!pdfFailed"
                        src="{{ $url }}#toolbar=0"
                        class="w-full h-full border border-[color:var(--color-border)] rounded-lg"
                        title="{{ $displayName }}"
                    ></iframe>
                    
                    <div x-show="pdfFailed" class="absolute inset-0 flex items-center justify-center">
                        @include('components.file-preview-fallback', ['accent' => 'red', 'icon' => 'pdf', 'displayName' => $displayName, 'size' => $size, 'resolvedMimeLabel' => 'PDF', 'downloadUrl' => $downloadUrl, 'downloadName' => $downloadName, 'desc' => 'Direct PDF preview is blocked or not supported by your browser.'])
                    </div>
                </div>

            @else
                {{-- download fallback --}}
                @include('components.file-preview-fallback', ['accent' => $fileType['accent'], 'icon' => $fileType['icon'], 'displayName' => $displayName, 'size' => $size, 'resolvedMimeLabel' => $resolvedMimeLabel, 'downloadUrl' => $downloadUrl, 'downloadName' => $downloadName])
            @endif
        </div>
    </x-modal>
</div>
