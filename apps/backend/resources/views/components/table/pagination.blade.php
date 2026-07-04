@props(['paginator'])

@php
    $isLengthAware = $paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    $hasPages = $paginator->hasPages();
    $total = $isLengthAware ? $paginator->total() : null;
    $firstItem = $paginator->firstItem() ?? 0;
    $lastItem = $paginator->lastItem() ?? 0;
    
    $elements = [];
    if ($isLengthAware) {
        $window = \Illuminate\Pagination\UrlWindow::make($paginator);
        $elements = array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]);
    }
@endphp

@if ($total === 0 || ($total === null && $firstItem === 0))
    <nav aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between border-t border-[color:var(--color-border)] px-[var(--spacing-4)] py-[var(--spacing-3)] sm:px-[var(--spacing-6)] bg-[color:var(--color-surface)] rounded-b-[var(--radius-lg)]">
        <p class="text-[length:var(--text-sm)] text-[color:var(--color-text-muted)]">
            {{ __('No results found') }}
        </p>
    </nav>
@else
    <nav aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between border-t border-[color:var(--color-border)] px-[var(--spacing-4)] py-[var(--spacing-3)] sm:px-[var(--spacing-6)] bg-[color:var(--color-surface)] rounded-b-[var(--radius-lg)]">
        
        <!-- Mobile View -->
        <div class="flex flex-1 items-center justify-between sm:hidden">
            <div class="text-[length:var(--text-sm)] text-[color:var(--color-text-muted)] mr-4 whitespace-nowrap">
                @if ($isLengthAware)
                    {{ __('Showing :first-:last', ['first' => $firstItem, 'last' => $lastItem]) }}
                @endif
            </div>
            
            <div class="flex flex-1 justify-end gap-2">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" class="relative inline-flex items-center rounded-md border border-[color:var(--color-border)] bg-[color:var(--color-surface-secondary)] px-4 py-2 text-sm font-medium text-[color:var(--color-text-muted)] opacity-50 cursor-not-allowed">
                        {{ __('Previous') }}
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center rounded-md border border-[color:var(--color-border)] bg-[color:var(--color-surface)] px-4 py-2 text-sm font-medium text-[color:var(--color-text-primary)] hover:bg-[color:var(--color-surface-hover)] transition-colors focus:outline-none focus:ring-[length:var(--focus-ring-width)] focus:ring-[color:var(--focus-ring-color)]">
                        {{ __('Previous') }}
                    </a>
                @endif

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center rounded-md border border-[color:var(--color-border)] bg-[color:var(--color-surface)] px-4 py-2 text-sm font-medium text-[color:var(--color-text-primary)] hover:bg-[color:var(--color-surface-hover)] transition-colors focus:outline-none focus:ring-[length:var(--focus-ring-width)] focus:ring-[color:var(--focus-ring-color)]">
                        {{ __('Next') }}
                    </a>
                @else
                    <span aria-disabled="true" class="relative inline-flex items-center rounded-md border border-[color:var(--color-border)] bg-[color:var(--color-surface-secondary)] px-4 py-2 text-sm font-medium text-[color:var(--color-text-muted)] opacity-50 cursor-not-allowed">
                        {{ __('Next') }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Desktop View -->
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-[length:var(--text-sm)] text-[color:var(--color-text-muted)]">
                    @if ($isLengthAware)
                        {!! __('Showing :first to :last of :total results', [
                            'first' => '<span class="font-medium text-[color:var(--color-text-primary)]">' . $firstItem . '</span>',
                            'last' => '<span class="font-medium text-[color:var(--color-text-primary)]">' . $lastItem . '</span>',
                            'total' => '<span class="font-medium text-[color:var(--color-text-primary)]">' . $total . '</span>'
                        ]) !!}
                    @else
                        {!! __('Showing :first to :last results', [
                            'first' => '<span class="font-medium text-[color:var(--color-text-primary)]">' . $firstItem . '</span>',
                            'last' => '<span class="font-medium text-[color:var(--color-text-primary)]">' . $lastItem . '</span>'
                        ]) !!}
                    @endif
                </p>
            </div>
            
            @if ($hasPages)
                <div>
                    <div class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                        {{-- Previous Page Link --}}
                        @if ($paginator->onFirstPage())
                            <span aria-disabled="true" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-[color:var(--color-text-muted)] border border-[color:var(--color-border)] bg-[color:var(--color-surface-secondary)] opacity-50 cursor-not-allowed">
                                <span class="sr-only">{{ __('Previous') }}</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        @else
                            <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-[color:var(--color-text-primary)] border border-[color:var(--color-border)] bg-[color:var(--color-surface)] hover:bg-[color:var(--color-surface-hover)] transition-colors focus:z-20 focus:outline-none focus:ring-[length:var(--focus-ring-width)] focus:ring-[color:var(--focus-ring-color)]">
                                <span class="sr-only">{{ __('Previous') }}</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        @endif

                        {{-- Pagination Elements --}}
                        @if (!empty($elements))
                            @foreach ($elements as $element)
                                {{-- "Three Dots" Separator --}}
                                @if (is_string($element))
                                    <span aria-hidden="true" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-[color:var(--color-text-muted)] border border-[color:var(--color-border)] bg-[color:var(--color-surface)]">{{ $element }}</span>
                                @endif

                                {{-- Array Of Links --}}
                                @if (is_array($element))
                                    @foreach ($element as $page => $url)
                                        @if ($page == $paginator->currentPage())
                                            <span aria-current="page" class="relative z-10 inline-flex items-center px-4 py-2 text-sm font-semibold text-[color:var(--color-primary-inverse)] bg-[color:var(--color-primary)] border border-[color:var(--color-primary)] focus:z-20">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-[color:var(--color-text-primary)] border border-[color:var(--color-border)] bg-[color:var(--color-surface)] hover:bg-[color:var(--color-surface-hover)] transition-colors focus:z-20 focus:outline-none focus:ring-[length:var(--focus-ring-width)] focus:ring-[color:var(--focus-ring-color)]">{{ $page }}</a>
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach
                        @endif

                        {{-- Next Page Link --}}
                        @if ($paginator->hasMorePages())
                            <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-[color:var(--color-text-primary)] border border-[color:var(--color-border)] bg-[color:var(--color-surface)] hover:bg-[color:var(--color-surface-hover)] transition-colors focus:z-20 focus:outline-none focus:ring-[length:var(--focus-ring-width)] focus:ring-[color:var(--focus-ring-color)]">
                                <span class="sr-only">{{ __('Next') }}</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        @else
                            <span aria-disabled="true" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-[color:var(--color-text-muted)] border border-[color:var(--color-border)] bg-[color:var(--color-surface-secondary)] opacity-50 cursor-not-allowed">
                                <span class="sr-only">{{ __('Next') }}</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </nav>
@endif
