@props(['title' => null])

<x-layouts.app :title="$title">
    <div class="min-h-screen bg-surface-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-surface-0 rounded-xl shadow-lg border border-surface-200 overflow-hidden">
            {{ $slot }}
        </div>
    </div>
</x-layouts.app>
