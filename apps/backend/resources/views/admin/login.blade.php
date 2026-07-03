<x-layouts.auth title="Admin Login | Okina Craft">
    <div class="p-8">
        <h1 class="text-2xl font-bold text-surface-900 mb-2">Admin Login</h1>
        <p class="text-surface-500 mb-6">Sign in to manage Okina Craft staff operations.</p>

        <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-semibold text-surface-900 mb-1.5">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required
                       class="w-full px-3 py-2 border border-surface-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors bg-white">
                @error('email')
                    <div class="text-danger-600 text-sm mt-1.5">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-surface-900 mb-1.5">Password</label>
                <div class="flex gap-2">
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                           class="flex-1 w-full px-3 py-2 border border-surface-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors bg-white">
                    <button class="px-3 py-2 border border-surface-300 bg-surface-50 hover:bg-surface-100 text-surface-700 rounded-lg font-medium transition-colors" type="button" data-toggle-password>Show</button>
                </div>
                @error('password')
                    <div class="text-danger-600 text-sm mt-1.5">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex items-center justify-between pt-2">
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Sign in
                </button>
                <a class="text-primary-600 hover:text-primary-700 font-semibold text-sm transition-colors" href="{{ route('admin.password.request') }}">
                    Forgot password?
                </a>
            </div>
        </form>

        @if ($errors->isNotEmpty() && ! $errors->has('email') && ! $errors->has('password'))
            <p class="text-danger-600 text-sm mt-4">{{ $errors->first() }}</p>
        @endif
    </div>

    <script>
        const toggle = document.querySelector('[data-toggle-password]');
        const password = document.querySelector('#password');

        toggle?.addEventListener('click', () => {
            const nextType = password.type === 'password' ? 'text' : 'password';
            password.type = nextType;
            toggle.textContent = nextType === 'password' ? 'Show' : 'Hide';
        });
    </script>
</x-layouts.auth>
