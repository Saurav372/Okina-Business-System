<!DOCTYPE html>
<html lang="en" class="h-full bg-neutral-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Account Activation | Okina Business System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased font-sans text-neutral-800 bg-neutral-950 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-neutral-800 border border-neutral-700 shadow-sm mb-4">
            <x-icons.lucide name="lucide-shield-check" class="w-6 h-6 text-red-500" />
        </div>
        <h2 class="text-2xl font-black tracking-tight text-white">Staff Account Activation</h2>
        <p class="text-xs text-neutral-400 mt-1">Set up your secure password to access Okina Business System.</p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-neutral-900 py-8 px-6 shadow-2xl rounded-2xl border border-neutral-800 sm:px-10">
            @if(! $isValid)
                <div class="rounded-xl bg-rose-950/50 border border-rose-800/80 p-4 text-center space-y-3">
                    <x-icons.lucide name="lucide-alert-triangle" class="w-6 h-6 text-rose-400 mx-auto" />
                    <p class="text-xs font-semibold text-rose-200">{{ $errorMessage }}</p>
                    <a href="{{ route('login') }}" class="inline-block text-xs font-bold text-neutral-300 hover:text-white underline pt-2">Return to Login</a>
                </div>
            @else
                <div class="mb-6 p-3 rounded-xl bg-neutral-800/80 border border-neutral-700/80 text-xs text-neutral-300 space-y-1">
                    <div class="flex justify-between">
                        <span class="text-neutral-400">Account:</span>
                        <span class="font-bold text-white">{{ $user->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-400">Email:</span>
                        <span class="font-mono text-neutral-200">{{ $user->email }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('staff.invitation.update', $token) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-neutral-300 mb-1.5">New Password</label>
                        <input type="password" name="password" id="password" required autofocus placeholder="Minimum 8 characters with numbers" class="w-full text-xs rounded-xl border-neutral-700 bg-neutral-950 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 py-2.5 px-3">
                        @error('password')
                            <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-neutral-300 mb-1.5">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required placeholder="Re-enter your password" class="w-full text-xs rounded-xl border-neutral-700 bg-neutral-950 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 py-2.5 px-3">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-2.5 px-4 bg-red-600 hover:bg-red-500 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                            Activate Account &amp; Access Dashboard
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</body>
</html>
