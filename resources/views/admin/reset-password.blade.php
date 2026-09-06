<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password | Okina Craft Admin</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: Arial, Helvetica, sans-serif; background: #f6f3ee; color: #1f2937; }
        main { width: min(100%, 440px); padding: 32px; background: #fff; border: 1px solid #d6d3cd; border-radius: 12px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08); }
        h1 { margin: 0 0 8px; font-size: 1.6rem; }
        p { margin: 0 0 24px; color: #6b7280; line-height: 1.5; }
        label { display: block; margin-bottom: 6px; font-weight: 700; }
        .field { margin-bottom: 16px; }
        input { width: 100%; box-sizing: border-box; padding: 12px 14px; border: 1px solid #d6d3cd; border-radius: 10px; font: inherit; }
        button { border: 0; border-radius: 10px; padding: 12px 16px; background: #0f766e; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .error { margin-top: 10px; color: #b91c1c; }
    </style>
</head>
<body>
    <main>
        <h1>Reset Password</h1>
        <p>Create a new password for your dashboard account.</p>

        <form method="POST" action="{{ route('admin.password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required>
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="password">New password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required>
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            </div>

            <button type="submit">Reset password</button>
        </form>
    </main>
</body>
</html>
