@props([
    'colspan' => 100,
    'title' => 'No data found',
    'description' => 'There are no records to display at the moment.'
])

<tr>
    <td colspan="{{ $colspan }}" class="px-[var(--spacing-6)] py-16 text-center">
        <div class="flex flex-col items-center justify-center space-y-4">
            <div class="flex items-center justify-center w-14 h-14 rounded-full bg-[color:var(--color-surface-secondary)] border border-[color:var(--color-border)]">
                @if (isset($icon))
                    {{ $icon }}
                @else
                    <x-icons.inbox class="w-6 h-6 text-[color:var(--color-text-muted)]" />
                @endif
            </div>
            
            <div class="space-y-1">
                <h3 class="text-base font-bold text-[color:var(--color-text-primary)]">{{ $title }}</h3>
                <p class="text-sm text-[color:var(--color-text-muted)]">{{ $description }}</p>
            </div>
            
            @if (isset($action))
                <div class="pt-2">
                    {{ $action }}
                </div>
            @endif
        </div>
    </td>
</tr>
