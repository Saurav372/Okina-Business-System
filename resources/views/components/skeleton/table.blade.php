@props([
    'rows' => 5,
    'columns' => 4,
    'header' => true,
])

<div {{ $attributes->class(['w-full border border-[color:var(--color-border)] rounded-xl overflow-hidden bg-white dark:bg-[color:var(--color-neutral-900)] shadow-sm']) }} aria-hidden="true" role="presentation">
    @if ($header)
        <!-- Dynamic Header skeleton -->
        <div class="bg-[color:var(--color-surface-secondary)] border-b border-[color:var(--color-border)] px-6 py-4 flex gap-4">
            @for ($c = 0; $c < $columns; $c++)
                <div class="flex-1">
                    <x-skeleton variant="line" class="w-1/2 h-3.5" />
                </div>
            @endfor
        </div>
    @endif
    
    <!-- Dynamic Rows skeleton -->
    <div class="divide-y divide-[color:var(--color-border)]">
        @for ($r = 0; $r < $rows; $r++)
            <div class="px-6 py-4 flex items-center gap-4">
                @for ($c = 0; $c < $columns; $c++)
                    <div class="flex-1">
                        @if ($c === 0)
                            <!-- First column displays primary identifier with a small circle + line -->
                            <div class="flex items-center gap-2.5">
                                <x-skeleton.avatar class="w-5.5 h-5.5" />
                                <x-skeleton variant="line" class="w-2/3 h-3" />
                            </div>
                        @elseif ($c === $columns - 1)
                            <!-- Last column displays an action button placeholder -->
                            <div class="flex justify-end">
                                <x-skeleton variant="block" class="w-14 h-7 rounded" />
                            </div>
                        @else
                            <!-- Middle columns display standard textual content -->
                            <x-skeleton variant="line" class="w-3/5 h-3" />
                        @endif
                    </div>
                @endfor
            </div>
        @endfor
    </div>
</div>
