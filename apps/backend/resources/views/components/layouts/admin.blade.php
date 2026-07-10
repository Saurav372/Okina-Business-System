@props(['title' => null])

<x-layouts.app :title="$title">
    <div x-data="pageNavigator" class="min-h-screen bg-[color:var(--color-surface-page)] flex flex-col relative overflow-hidden">
        <div 
            x-data="{
                sidebarCollapsed: false,
                mobileSidebarOpen: false,
                activeDropdown: null,
                init() {
                    try {
                        this.sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                    } catch (e) {
                        this.sidebarCollapsed = false;
                    }
                },
                toggleSidebar() {
                    this.sidebarCollapsed = !this.sidebarCollapsed;
                    try {
                        localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
                    } catch (e) {}
                },
                closeDropdowns() {
                    this.activeDropdown = null;
                }
            }"
            @keydown.escape.window="closeDropdowns(); mobileSidebarOpen = false"
            class="flex-1 flex flex-col md:flex-row min-h-0"
        >
        <!-- Mobile Sidebar Overlay (Drawer Backdrop) -->
        <div 
            x-show="mobileSidebarOpen" 
            x-transition:enter="transition-opacity ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileSidebarOpen = false"
            class="fixed inset-0 bg-black/50 z-40 md:hidden"
            aria-hidden="true"
        ></div>

        <!-- Sidebar (Desktop and Mobile Drawer) -->
        <aside 
            :class="{
                'translate-x-0': mobileSidebarOpen,
                '-translate-x-full': !mobileSidebarOpen,
                'md:translate-x-0': true,
                'md:w-64': !sidebarCollapsed,
                'md:w-20': sidebarCollapsed
            }"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-[color:var(--color-surface-sidebar)] text-white flex flex-col transition-all duration-300 ease-in-out md:static shrink-0 border-r border-[color:var(--color-ink-800)] layout-sidebar"
        >
            <!-- Sidebar Header -->
            <div class="h-16 flex items-center justify-between px-6 border-b border-[color:var(--color-ink-800)] shrink-0">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 font-bold tracking-tight text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)] rounded-lg">
                    <span class="p-1.5 bg-[color:var(--color-brand-500)] text-white rounded-lg">
                        <x-icons.lucide name="lucide-building" class="w-5 h-5" />
                    </span>
                    <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" class="text-sm font-bold uppercase tracking-wider text-[color:var(--color-text-inverse)]">{{ config('branding.short_name', 'Okina') }}</span>
                </a>
                
                <!-- Close Button (Mobile Only) -->
                <button 
                    @click="mobileSidebarOpen = false"
                    class="p-1.5 -mr-1.5 rounded-lg text-neutral-400 hover:text-white hover:bg-white/5 md:hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]"
                    aria-label="Close sidebar menu"
                    :aria-expanded="mobileSidebarOpen ? 'true' : 'false'"
                >
                    <x-icons.lucide name="lucide-x" class="w-5 h-5" />
                </button>
            </div>

            <!-- Sidebar Navigation Links -->
            <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-6 scrollbar-thin">
                @foreach($navigation as $group)
                    <div class="space-y-2">
                        <!-- Group Header -->
                        <h3 
                            x-show="!sidebarCollapsed" 
                            class="px-3 text-[10px] font-bold uppercase tracking-widest text-[color:var(--color-ink-400)]"
                        >
                            {{ $group->group }}
                        </h3>
                        <div x-show="sidebarCollapsed" class="h-px bg-[color:var(--color-ink-800)] my-3"></div>

                        <!-- Group Items -->
                        <ul class="space-y-1">
                            @foreach($group->items as $item)
                                @php
                                    $isActive = false;
                                    foreach ($item->active as $pattern) {
                                        if (request()->routeIs($pattern)) {
                                            $isActive = true;
                                            break;
                                        }
                                    }
                                @endphp
                                <li class="relative group/nav-item">
                                    <a 
                                        href="{{ Route::has($item->route) ? route($item->route) : '#' }}" 
                                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition duration-[var(--motion-fast)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)] focus-visible:ring-offset-2 focus-visible:ring-offset-[color:var(--color-surface-sidebar)]
                                            {{ $isActive 
                                                ? 'bg-[color:var(--color-brand-500)] text-white shadow-md' 
                                                : 'text-neutral-300 hover:text-white hover:bg-white/5' }}"
                                        @if($isActive) aria-current="page" @endif
                                        :title="sidebarCollapsed ? '{{ $item->label }}' : ''"
                                    >
                                        <span class="shrink-0">
                                            <x-icons.lucide name="{{ $item->icon }}" class="w-5 h-5" />
                                        </span>
                                        <span 
                                            x-show="!sidebarCollapsed"
                                            class="transition-opacity duration-200 whitespace-nowrap"
                                        >
                                            {{ $item->label }}
                                        </span>

                                        <!-- Badge rendering -->
                                        @if($item->badge)
                                            <span 
                                                x-show="!sidebarCollapsed"
                                                class="ml-auto inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold rounded-full
                                                    {{ $item->badge['variant'] === 'danger' ? 'bg-rose-500 text-white' : 'bg-neutral-600 text-neutral-200' }}"
                                            >
                                                {{ $item->badge['value'] }}
                                            </span>
                                        @endif
                                    </a>

                                    <!-- Collapsed Sidebar Tooltip Hover -->
                                    <div 
                                        x-show="sidebarCollapsed"
                                        class="absolute left-full top-1/2 -translate-y-1/2 ml-3 bg-neutral-900 text-white text-xs font-semibold px-2.5 py-1.5 rounded-lg opacity-0 pointer-events-none group-hover/nav-item:opacity-100 transition-opacity duration-150 shadow-md whitespace-nowrap z-50 border border-neutral-800"
                                    >
                                        {{ $item->label }}
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>

            <!-- Sidebar Footer (Organization details / switch placeholder) -->
            <div class="p-4 border-t border-[color:var(--color-ink-800)] bg-[color:var(--color-ink-950)]/50 shrink-0">
                <div class="flex items-center gap-3">
                    <span class="p-2 bg-[color:var(--color-ink-800)] text-neutral-300 rounded-xl">
                        <x-icons.lucide name="lucide-building" class="w-4 h-4" />
                    </span>
                    <div x-show="!sidebarCollapsed" class="min-w-0">
                        <p class="text-xs font-bold text-white truncate">Okina Craft Admin</p>
                        <p class="text-[10px] text-neutral-400 truncate">Default Sandbox</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0 relative">
            <!-- Top Header Navbar -->
            <header class="h-16 bg-white border-b border-[color:var(--color-border)] flex items-center justify-between px-4 md:px-6 layout-header shrink-0">
                <!-- Left Header Actions -->
                <div class="flex items-center gap-4">
                    <!-- Collapse Button (Desktop) / Hamburger Trigger (Mobile) -->
                    <button 
                        @click="window.innerWidth < 768 ? mobileSidebarOpen = !mobileSidebarOpen : toggleSidebar()"
                        class="p-2 -ml-2 rounded-xl text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]"
                        aria-label="Toggle sidebar navigation menu"
                        aria-controls="admin-sidebar"
                    >
                        <x-icons.lucide name="lucide-menu" class="w-5 h-5" />
                    </button>

                    <!-- Search / Command Palette Box -->
                    <div class="hidden sm:block relative w-64 md:w-80">
                        <button 
                            @click="window.toast({ message: 'Command Palette is not implemented in sandbox.', type: 'info' })"
                            class="w-full flex items-center justify-between px-3 py-1.5 text-xs text-neutral-400 border border-[color:var(--color-border)] rounded-xl bg-neutral-50 hover:bg-neutral-100 transition-colors cursor-pointer text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]"
                        >
                            <span class="flex items-center gap-2">
                                <x-icons.lucide name="lucide-search" class="w-4 h-4" />
                                Search modules & resources...
                            </span>
                            <kbd class="hidden md:inline-flex items-center gap-0.5 px-1.5 py-0.5 border border-neutral-300 bg-white rounded font-mono text-[9px] text-neutral-500 shadow-xs">
                                <span class="text-[10px]">⌘</span>K
                            </kbd>
                        </button>
                    </div>
                </div>

                <!-- Right Header Actions Widgets -->
                <div class="flex items-center gap-3">
                    <!-- Quick Actions menu trigger -->
                    <div class="relative" x-data="{ open: false }">
                        <button 
                            @click="open = !open"
                            @click.away="open = false"
                            class="p-2 text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 rounded-xl transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)] flex items-center gap-1"
                            :aria-expanded="open ? 'true' : 'false'"
                            aria-label="Quick Actions"
                        >
                            <x-icons.lucide name="lucide-plus" class="w-5 h-5" />
                            <x-icons.lucide name="lucide-chevron-down" class="w-3.5 h-3.5" />
                        </button>
                        <!-- Quick Actions dropdown overlay -->
                        <div 
                            x-show="open"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-56 bg-white border border-[color:var(--color-border)] rounded-2xl shadow-lg py-2 z-50"
                        >
                            <div class="px-4 py-1.5 border-b border-[color:var(--color-border)] mb-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Quick Actions</span>
                            </div>
                            <a href="{{ route('admin.sales_orders.create') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 hover:text-[color:var(--color-brand-600)]">
                                <span class="w-2 h-2 rounded-full bg-[color:var(--color-brand-500)]"></span>
                                New Sales Order
                            </a>
                            <a href="{{ route('admin.expenses.index') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 hover:text-neutral-900 border-t border-[color:var(--color-border)] mt-1 pt-2">
                                <span class="w-2 h-2 rounded-full bg-neutral-400"></span>
                                Add Expense Record
                            </a>
                        </div>
                    </div>

                    <!-- Notification Bell Widget -->
                    <button 
                        @click="window.toast({ message: 'No new notifications.', type: 'info' })"
                        class="p-2 text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 rounded-xl transition-colors relative focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]"
                        aria-label="View system notifications"
                    >
                        <x-icons.lucide name="lucide-bell" class="w-5 h-5" />
                    </button>

                    <!-- Help Trigger Placeholder -->
                    <button 
                        @click="window.toast({ message: 'Help documentation is located under resources directory.', type: 'info' })"
                        class="p-2 text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 rounded-xl transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]"
                        aria-label="Help Documentation"
                    >
                        <x-icons.lucide name="lucide-help-circle" class="w-5 h-5" />
                    </button>

                    <!-- Divider -->
                    <div class="h-6 w-px bg-[color:var(--color-border)] mx-1"></div>

                    <!-- User Initials Avatar dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button 
                            @click="open = !open"
                            @click.away="open = false"
                            class="flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)] focus-visible:ring-offset-2 rounded-full cursor-pointer"
                            :aria-expanded="open ? 'true' : 'false'"
                            aria-label="User account details"
                        >
                            <x-avatar :name="auth()->user()->name ?? 'Staff User'" size="sm" ring="sm" />
                        </button>
                        <!-- Account profile popover dropdown -->
                        <div 
                            x-show="open"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-64 bg-white border border-[color:var(--color-border)] rounded-2xl shadow-lg py-2 z-50"
                        >
                            <!-- User profile summary card -->
                            <div class="px-4 py-3 border-b border-[color:var(--color-border)] mb-2 flex items-center gap-3">
                                <x-avatar :name="auth()->user()?->name ?? 'Staff User'" size="sm" />
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-neutral-800 truncate">{{ auth()->user()?->name ?? 'Staff User' }}</h4>
                                    <p class="text-[10px] text-neutral-500 truncate">{{ auth()->user()?->email ?? 'staff@okina.io' }}</p>
                                </div>
                            </div>
                            
                            <!-- Role badge details -->
                            <div class="px-4 py-1.5 flex flex-wrap gap-1.5">
                                @if(auth()->user() && auth()->user()->roles)
                                    @foreach(auth()->user()->roles as $role)
                                        <span class="inline-flex items-center px-2 py-0.5 text-[9px] font-bold bg-neutral-100 text-neutral-700 rounded-md uppercase tracking-wider">
                                            {{ $role->slug }}
                                        </span>
                                    @endforeach
                                @endif
                            </div>

                            <hr class="border-[color:var(--color-border)] my-2">

                            <a href="{{ route('admin.profile') }}" class="flex items-center gap-2 px-4 py-2.5 text-xs text-neutral-700 hover:bg-neutral-50 font-medium">My Profile</a>
                            <a href="{{ route('admin.security') }}" class="flex items-center gap-2 px-4 py-2.5 text-xs text-neutral-700 hover:bg-neutral-50 font-medium">Security Settings</a>
                            
                            <hr class="border-[color:var(--color-border)] my-2">

                            <!-- Logout trigger form -->
                            <form method="POST" action="{{ route('admin.logout') }}" class="w-full">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-xs text-rose-600 hover:bg-rose-50 font-semibold cursor-pointer">
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dynamic Slot Context Layout -->
            <div class="flex-1 overflow-y-auto flex flex-col p-4 md:p-6 lg:p-8">
                <!-- Breadcrumbs & Slots Header -->
                @if(isset($header))
                    <div class="mb-6">
                        <!-- Simple dynamic breadcrumb fallback if not configured on controller page -->
                        <div class="flex items-center gap-1.5 text-xs text-[color:var(--color-text-muted)] mb-2 font-medium">
                            <a href="{{ route('admin.dashboard') }}" class="hover:text-[color:var(--color-text-body)]">Dashboard</a>
                            <span>&gt;</span>
                            <span class="text-[color:var(--color-text-body)] font-semibold">{{ $title ?? 'Administration' }}</span>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h1 class="text-2xl font-bold tracking-tight text-[color:var(--color-text-heading)]">{{ $title ?? 'Workspace Window' }}</h1>
                            </div>
                            <div class="flex items-center gap-2.5 shrink-0">
                                {{ $header }}
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mb-6">
                        <div class="flex items-center gap-1.5 text-xs text-[color:var(--color-text-muted)] mb-2 font-medium">
                            <a href="{{ route('admin.dashboard') }}" class="hover:text-[color:var(--color-text-body)]">Dashboard</a>
                            <span>&gt;</span>
                            <span class="text-[color:var(--color-text-body)] font-semibold">{{ $title ?? 'Administration' }}</span>
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-[color:var(--color-text-heading)]">{{ $title ?? 'Workspace Window' }}</h1>
                    </div>
                @endif

                <!-- Content Slot Main Panel -->
                <main class="flex-1 relative layout-main">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>
</x-layouts.app>
