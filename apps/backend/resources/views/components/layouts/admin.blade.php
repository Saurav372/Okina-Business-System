@props(['title' => null])

<x-layouts.app :title="$title">
    <div class="min-h-screen bg-surface-50 flex flex-col md:flex-row">
        <!-- Sidebar (Hidden on mobile by default, fixed on desktop) -->
        <aside class="w-full md:w-64 bg-surface-0 border-r border-surface-200 hidden md:flex flex-col">
            <!-- Sidebar Content Goes Here (U1.1 / U1.3 Navigation) -->
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Header -->
            <header class="h-16 bg-surface-0 border-b border-surface-200 flex items-center px-4 md:px-6">
                <!-- Header Content Goes Here -->
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-4 md:p-6 overflow-y-auto">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.app>
