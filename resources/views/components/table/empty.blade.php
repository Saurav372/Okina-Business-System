@props([
    'colspan' => 100,
    'title' => 'No data found',
    'description' => 'There are no records to display at the moment.'
])

<tr>
    <td colspan="{{ $colspan }}" class="px-[var(--spacing-6)]">
        <x-empty-state 
            :title="$title" 
            :description="$description"
            size="sm"
        >
            @if (isset($icon))
                <x-slot:icon>
                    {{ $icon }}
                </x-slot:icon>
            @endif

            @if (isset($action))
                <x-slot:actions>
                    {{ $action }}
                </x-slot:actions>
            @endif
        </x-empty-state>
    </td>
</tr>
