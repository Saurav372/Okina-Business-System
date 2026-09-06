<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Account</title>
</head>
<body>
    <main>
        <h1>Customer Account</h1>
        <p>Welcome, {{ $account->customer->name }}.</p>

        <form method="POST" action="{{ route('customer.logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </main>
</body>
</html>
