@props(['title' => null])

<x-layouts.app :title="$title">
    <div class="min-h-screen bg-surface-0 flex flex-col">
        <!-- Public Marketing Header -->
        <header class="w-full bg-surface-0 border-b border-surface-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <!-- Logo & Nav Links -->
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1">
            {{ $slot }}
        </main>

        <!-- Public Footer -->
        <footer class="bg-surface-50 border-t border-surface-200 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Footer Links -->
            </div>
        </footer>
    </div>
</x-layouts.app>
