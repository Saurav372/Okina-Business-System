@props([
    'colspan' => 100,
    'message' => 'No data found'
])

<tr>
    <td colspan="{{ $colspan }}" class="px-[var(--spacing-6)] py-[var(--spacing-12)] text-center text-[color:var(--color-text-muted)]">
        @if ($slot->isEmpty())
            <div class="flex flex-col items-center justify-center space-y-3">
                <div class="p-3 bg-[color:var(--color-surface-secondary)] rounded-full">
                    <x-icons.inbox class="w-6 h-6 text-[color:var(--color-text-muted)] opacity-50" />
                </div>
                <span class="text-[length:var(--text-body)] font-medium">{{ $message }}</span>
            </div>
        @else
            {{ $slot }}
        @endif
    </td>
</tr>
