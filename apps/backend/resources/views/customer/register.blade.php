<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Customer Account</title>
</head>
<body>
    <main>
        <h1>Create Customer Account</h1>

        @if ($errors->any())
            <div>
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('customer.register.store') }}">
            @csrf

            <label>
                Name
                <input type="text" name="name" value="{{ old('name') }}" required autofocus>
            </label>

            <label>
                Email address
                <input type="email" name="email" value="{{ old('email') }}" required>
            </label>

            <label>
                Password
                <input type="password" name="password" required>
            </label>

            <label>
                Confirm password
                <input type="password" name="password_confirmation" required>
            </label>

            <button type="submit">Create account</button>
        </form>

        <p><a href="{{ route('customer.login') }}">Already have an account?</a></p>
    </main>
</body>
</html>
