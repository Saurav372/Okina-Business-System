<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Login</title>
</head>
<body>
    <main>
        <h1>Customer Login</h1>

        @if ($errors->any())
            <div>
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('customer.login.store') }}">
            @csrf

            <label>
                Email address
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>

            <label>
                Password
                <input type="password" name="password" required>
            </label>

            <button type="submit">Login</button>
        </form>

        <p><a href="{{ route('customer.register') }}">Create customer account</a></p>
    </main>
</body>
</html>
