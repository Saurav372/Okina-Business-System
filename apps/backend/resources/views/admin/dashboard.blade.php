<x-layouts.admin title="Admin Dashboard | Okina Craft">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-surface-900">Okina Craft Admin</h1>
            <p class="text-surface-500 mt-1">Protected staff area for backend operations.</p>
        </div>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                Logout
            </button>
        </form>
    </div>

    <div class="bg-surface-0 border border-surface-200 rounded-xl p-6 shadow-sm">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-surface-50 border border-surface-200 text-sm text-surface-600 mb-4">
            <svg class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            Authenticated staff session active
        </div>
        <p class="text-surface-700 leading-relaxed">This placeholder dashboard confirms the admin gate is working. Later tasks will add the real business modules here.</p>
    </div>
</x-layouts.admin>
