<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard | Okina Craft</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f8fafc;
            --panel: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --line: #cbd5e1;
            --accent: #0f766e;
            --accent-strong: #115e59;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text);
            background:
                linear-gradient(180deg, rgba(15, 118, 110, 0.06), transparent 26%),
                var(--bg);
        }
        header, main {
            width: min(100%, 1100px);
            margin: 0 auto;
            padding: 24px;
        }
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(8px);
        }
        h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        p {
            margin: 8px 0 0;
            color: var(--muted);
            line-height: 1.5;
        }
        .panel {
            margin-top: 24px;
            padding: 24px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--panel);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
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
        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid var(--line);
            border-radius: 999px;
            color: var(--muted);
            background: #f8fafc;
        }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>Okina Craft Admin</h1>
            <p>Protected staff area for backend operations.</p>
        </div>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </header>

    <main>
        <div class="panel">
            <div class="status">Authenticated staff session active</div>
            <p>This placeholder dashboard confirms the admin gate is working. Later tasks will add the real business modules here.</p>
        </div>
    </main>
</body>
</html>
