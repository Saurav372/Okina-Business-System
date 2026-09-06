<x-layouts.admin title="My Profile">
<div class="py-6 space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="border-b border-neutral-200 dark:border-neutral-700 pb-4">
        <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">My Profile</h1>
        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Manage your account credentials, contact information, and system access status.</p>
    </div>

    @if(session('status'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-200 text-sm rounded-lg flex items-center justify-between">
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Profile Overview Card -->
    <div class="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm flex flex-col sm:flex-row items-start sm:items-center gap-6">
        <div class="w-16 h-16 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xl shadow-md">
            {{ $user->initials() }}
        </div>
        <div class="space-y-1 flex-1">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-bold text-neutral-900 dark:text-white">{{ $user->name }}</h2>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200 uppercase tracking-wider">
                    {{ $primaryRole }}
                </span>
            </div>
            <p class="text-sm text-neutral-500 dark:text-neutral-400 font-mono">{{ $user->email }}</p>
            <div class="pt-2 flex flex-wrap gap-4 text-xs text-neutral-500 dark:text-neutral-400 border-t border-neutral-100 dark:border-neutral-700 mt-2">
                <div><span class="font-medium text-neutral-700 dark:text-neutral-300">Status:</span> <span class="capitalize text-emerald-600 dark:text-emerald-400 font-semibold">{{ $user->status }}</span></div>
                <div><span class="font-medium text-neutral-700 dark:text-neutral-300">Last Login:</span> {{ $user->last_login_at?->format('d M Y, h:i A') ?? 'Never' }}</div>
                <div><span class="font-medium text-neutral-700 dark:text-neutral-300">Last Login IP:</span> <span class="font-mono">{{ $user->last_login_ip ?? 'N/A' }}</span></div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Form Card -->
    <div class="bg-white dark:bg-neutral-800 rounded-xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm space-y-6">
        <h3 class="text-lg font-bold text-neutral-900 dark:text-white border-b border-neutral-100 dark:border-neutral-700 pb-3">Personal Details</h3>
        
        <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 uppercase tracking-wider mb-1">Full Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                @error('name', 'profile')
                    <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-1">Email Address <span class="normal-case font-normal">(Read-only)</span></label>
                <input type="email" id="email" value="{{ $user->email }}" disabled class="w-full text-sm rounded-lg border-neutral-200 dark:border-neutral-700 bg-neutral-100 dark:bg-neutral-900 text-neutral-500 cursor-not-allowed">
            </div>

            <div>
                <label for="phone" class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 uppercase tracking-wider mb-1">Phone Number</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" placeholder="+91 98765 43210" class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                @error('phone', 'profile')
                    <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-sm transition-colors">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
</x-layouts.admin>
