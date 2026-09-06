@props([
    'count' => 1,
    'layout' => 'vertical', // vertical, horizontal
])

@if ($count > 1)
    <div {{ $attributes->merge(['class' => 'grid gap-6']) }}>
        @for ($i = 0; $i < $count; $i++)
            <div class="border border-[color:var(--color-border)] rounded-xl p-4 bg-white dark:bg-[color:var(--color-neutral-900)] shadow-sm flex gap-4 {{ $layout === 'vertical' ? 'flex-col' : 'flex-row items-center' }}" aria-hidden="true" role="presentation">
                @if ($layout === 'vertical')
                    <!-- Vertical Card Content -->
                    <x-skeleton.image aspect="video" class="rounded-lg" />
                    
                    <div class="flex items-start gap-3 mt-1">
                        <x-skeleton.avatar class="w-9 h-9" />
                        <div class="space-y-2 flex-1">
                            <x-skeleton variant="line" class="w-3/4 h-3.5" />
                            <x-skeleton variant="line" class="w-1/2 h-3" />
                        </div>
                    </div>
                    
                    <div class="mt-2">
                        <x-skeleton.text :rows="2" />
                    </div>
                    
                    <div class="flex justify-between items-center mt-3 pt-3 border-t border-[color:var(--color-border)]">
                        <x-skeleton variant="line" class="w-1/4 h-3" />
                        <x-skeleton variant="block" class="w-16 h-7 rounded" />
                    </div>
                @else
                    <!-- Horizontal Card Content -->
                    <x-skeleton.image aspect="square" class="w-24 h-24 rounded-lg shrink-0" />
                    
                    <div class="flex-1 space-y-3">
                        <div class="space-y-2">
                            <x-skeleton variant="line" class="w-2/3 h-4" />
                            <x-skeleton variant="line" class="w-1/3 h-3" />
                        </div>
                        <x-skeleton.text :rows="2" />
                    </div>
                @endif
            </div>
        @endfor
    </div>
@else
    <div {{ $attributes->merge(['class' => 'border border-[color:var(--color-border)] rounded-xl p-4 bg-white dark:bg-[color:var(--color-neutral-900)] shadow-sm flex gap-4 ' . ($layout === 'vertical' ? 'flex-col' : 'flex-row items-center')]) }} aria-hidden="true" role="presentation">
        @if ($layout === 'vertical')
            <!-- Vertical Card Content -->
            <x-skeleton.image aspect="video" class="rounded-lg" />
            
            <div class="flex items-start gap-3 mt-1">
                <x-skeleton.avatar class="w-9 h-9" />
                <div class="space-y-2 flex-1">
                    <x-skeleton variant="line" class="w-3/4 h-3.5" />
                    <x-skeleton variant="line" class="w-1/2 h-3" />
                </div>
            </div>
            
            <div class="mt-2">
                <x-skeleton.text :rows="2" />
            </div>
            
            <div class="flex justify-between items-center mt-3 pt-3 border-t border-[color:var(--color-border)]">
                <x-skeleton variant="line" class="w-1/4 h-3" />
                <x-skeleton variant="block" class="w-16 h-7 rounded" />
            </div>
        @else
            <!-- Horizontal Card Content -->
            <x-skeleton.image aspect="square" class="w-24 h-24 rounded-lg shrink-0" />
            
            <div class="flex-1 space-y-3">
                <div class="space-y-2">
                    <x-skeleton variant="line" class="w-2/3 h-4" />
                    <x-skeleton variant="line" class="w-1/3 h-3" />
                </div>
                <x-skeleton.text :rows="2" />
            </div>
        @endif
    </div>
@endif
