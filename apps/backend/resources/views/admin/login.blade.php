<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login | Okina Craft</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f6f3ee;
            --panel: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --line: #d6d3cd;
            --accent: #0f766e;
            --accent-strong: #115e59;
            --danger: #b91c1c;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Arial, Helvetica, sans-serif;
            background:
                linear-gradient(135deg, rgba(15, 118, 110, 0.08), transparent 40%),
                linear-gradient(225deg, rgba(180, 83, 9, 0.08), transparent 36%),
                var(--bg);
            color: var(--text);
        }
        main {
            width: min(100%, 420px);
            padding: 32px;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 12px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
        }
        h1 {
            margin: 0 0 8px;
            font-size: 1.75rem;
        }
        p {
            margin: 0 0 24px;
            color: var(--muted);
            line-height: 1.5;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.95rem;
            font-weight: 700;
        }
        .field {
            margin-bottom: 16px;
        }
        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            font: inherit;
            color: inherit;
            background: #fff;
        }
        input:focus {
            outline: 2px solid rgba(15, 118, 110, 0.22);
            border-color: var(--accent);
        }
        .password-row {
            display: flex;
            gap: 8px;
        }
        .password-row input {
            flex: 1;
        }
        .secondary-button {
            border: 1px solid var(--line);
            background: #f8fafc;
            color: var(--text);
            white-space: nowrap;
        }
        .secondary-button:hover {
            background: #eef2f7;
        }
        .error {
            margin: 12px 0 0;
            color: var(--danger);
            font-size: 0.95rem;
        }
        .actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 24px;
        }
        button {
            appearance: none;
            border: 0;
            border-radius: 10px;
            padding: 12px 16px;
            background: var(--accent);
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
        button:hover {
            background: var(--accent-strong);
        }
        .hint {
            font-size: 0.9rem;
            color: var(--muted);
        }
        .link {
            color: var(--accent-strong);
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <main>
        <h1>Admin Login</h1>
        <p>Sign in to manage Okina Craft staff operations.</p>

        <form method="POST" action="{{ route('admin.login') }}">
            @csrf

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="password-row">
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    <button class="secondary-button" type="button" data-toggle-password>Show</button>
                </div>
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="actions">
                <button type="submit">Sign in</button>
                <a class="link" href="{{ route('admin.password.request') }}">Forgot password?</a>
            </div>
        </form>

        @if ($errors->isNotEmpty() && ! $errors->has('email') && ! $errors->has('password'))
            <p class="error">{{ $errors->first() }}</p>
        @endif
    </main>
    <script>
        const toggle = document.querySelector('[data-toggle-password]');
        const password = document.querySelector('#password');

        toggle?.addEventListener('click', () => {
            const nextType = password.type === 'password' ? 'text' : 'password';
            password.type = nextType;
            toggle.textContent = nextType === 'password' ? 'Show' : 'Hide';
        });
    </script>
</body>
</html>
