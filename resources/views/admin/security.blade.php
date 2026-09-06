<x-layouts.admin title="Security Settings">
<div class="py-6 space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="border-b border-neutral-200 dark:border-neutral-700 pb-4">
        <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Security Settings</h1>
        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Manage password credentials, active session tokens, and security audit settings.</p>
    </div>

    @if(session('status'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-200 text-sm rounded-lg flex items-center justify-between">
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Change Password Card -->
    <div class="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm space-y-6">
        <div class="border-b border-neutral-100 dark:border-neutral-700 pb-3">
            <h3 class="text-lg font-bold text-neutral-900 dark:text-white">Update Password</h3>
            <p class="text-xs text-neutral-500 dark:text-neutral-400">Ensure your account uses a strong, unique password (minimum 8 characters, uppercase, lowercase, numbers).</p>
        </div>

        <form method="POST" action="{{ route('admin.security.password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 uppercase tracking-wider mb-1">Current Password</label>
                <input type="password" name="current_password" id="current_password" required class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                @error('current_password', 'password')
                    <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 uppercase tracking-wider mb-1">New Password</label>
                <input type="password" name="password" id="password" required class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                @error('password', 'password')
                    <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 uppercase tracking-wider mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-sm transition-colors">
                    Update Password
                </button>
            </div>
        </form>
    </div>

    <!-- Active Sessions Card -->
    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-neutral-900 dark:text-white">Active Browser Sessions</h3>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">View and revoke active logged-in sessions across your devices.</p>
            </div>
            @if(count($sessions) > 1)
                <form method="POST" action="{{ route('admin.security.sessions.revoke_others') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors">
                        Revoke Other Sessions
                    </button>
                </form>
            @endif
        </div>

        <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
            @forelse($sessions as $session)
                <div class="p-6 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-neutral-100 dark:bg-neutral-700 rounded-lg text-neutral-600 dark:text-neutral-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 002-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-neutral-900 dark:text-white flex items-center gap-2">
                                <span>{{ $session->browser }} on {{ $session->platform }}</span>
                                @if($session->isCurrent)
                                    <span class="px-2 py-0.5 text-xs bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 font-semibold rounded-full">This Device</span>
                                @endif
                            </div>
                            <div class="text-xs text-neutral-500 dark:text-neutral-400 font-mono mt-0.5">
                                {{ $session->ipAddress }} • Last active {{ $session->lastActiveLabel }}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-sm text-neutral-500 dark:text-neutral-400">
                    No active sessions found.
                </div>
            @endforelse
        </div>
    </div>
</div>
</x-layouts.admin>
