@props([
    'icon' => true,
])

<div {{ $attributes->class(['border border-[color:var(--color-border)] rounded-xl p-6 bg-white dark:bg-[color:var(--color-neutral-900)] shadow-sm flex items-start justify-between gap-4']) }} aria-hidden="true" role="presentation">
    <div class="space-y-3 flex-1">
        <x-skeleton variant="line" class="w-1/3 h-3.5" />
        <x-skeleton variant="block" class="w-3/5 h-8 rounded-md" />
        <x-skeleton variant="line" class="w-1/2 h-3" />
    </div>
    @if ($icon)
        <x-skeleton variant="block" class="w-10 h-10 rounded-lg shrink-0" />
    @endif
</div>
