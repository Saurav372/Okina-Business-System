<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | Okina Craft Admin</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: Arial, Helvetica, sans-serif; background: #f6f3ee; color: #1f2937; }
        main { width: min(100%, 420px); padding: 32px; background: #fff; border: 1px solid #d6d3cd; border-radius: 12px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08); }
        h1 { margin: 0 0 8px; font-size: 1.6rem; }
        p { margin: 0 0 24px; color: #6b7280; line-height: 1.5; }
        label { display: block; margin-bottom: 6px; font-weight: 700; }
        input { width: 100%; box-sizing: border-box; padding: 12px 14px; border: 1px solid #d6d3cd; border-radius: 10px; font: inherit; }
        button { margin-top: 18px; border: 0; border-radius: 10px; padding: 12px 16px; background: #0f766e; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .status { margin-bottom: 16px; color: #115e59; font-weight: 700; }
        .error { margin-top: 10px; color: #b91c1c; }
        a { display: inline-block; margin-left: 12px; color: #115e59; font-weight: 700; text-decoration: none; }
    </style>
</head>
<body>
    <main>
        <h1>Forgot Password</h1>
        <p>Enter your dashboard email address.</p>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.password.email') }}">
            @csrf
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
            <button type="submit">Send reset link</button>
            <a href="{{ route('login') }}">Back to login</a>
        </form>
    </main>
</body>
</html>
