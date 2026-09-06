@props(['title' => null])

<x-layouts.app :title="$title">
    <div class="min-h-screen bg-surface-50 flex flex-col">
        <!-- Portal Top Navigation -->
        <header class="h-16 bg-surface-0 border-b border-surface-200 flex items-center px-4 md:px-8">
            <!-- Navigation Content Goes Here -->
        </header>

        <!-- Portal Main Content -->
        <main class="flex-1 w-full max-w-7xl mx-auto p-4 md:p-8">
            {{ $slot }}
        </main>
    </div>
</x-layouts.app>
