@props([
    'items' => 3,
    'divided' => true,
])

<div {{ $attributes->class(['w-full']) }} aria-hidden="true" role="presentation">
    <div class="space-y-4">
        @for ($i = 0; $i < $items; $i++)
            <div class="flex items-center gap-3 {{ $divided && $i > 0 ? 'pt-4 border-t border-[color:var(--color-border)]' : '' }}">
                <x-skeleton.avatar class="w-10 h-10" />
                <div class="flex-1 space-y-2">
                    <x-skeleton variant="line" class="w-1/3 h-3.5" />
                    <x-skeleton variant="line" class="w-2/3 h-3" />
                </div>
                <div class="shrink-0">
                    <x-skeleton variant="block" class="w-16 h-8 rounded-lg" />
                </div>
            </div>
        @endfor
    </div>
</div>
