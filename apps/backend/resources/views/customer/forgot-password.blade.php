@extends('customer.auth-layout')

@section('title', 'Reset password')

@section('content')
    <p class="eyebrow">Account recovery</p>
    <h1>Reset your password.</h1>
    <p class="intro">Enter your account email. If it is eligible, we’ll send a short-lived reset link.</p>

    @if ($errors->any())
        <div class="error-summary" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    @if (session('status'))<div class="success-summary" role="status">{{ session('status') }}</div>@endif

    <form class="auth-form" method="POST" action="{{ route('customer.password.email') }}">
        @csrf
        <label class="field"><span>Email address</span><input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"></label>
        <button class="submit" type="submit">Send reset link →</button>
    </form>
    <p class="switch"><a href="{{ route('customer.login') }}">← Back to sign in</a></p>
@endsection
