@extends('customer.auth-layout')

@section('title', 'Sign in')

@section('content')
    <p class="eyebrow">Welcome back</p>
    <h1>Sign in.</h1>
    <p class="intro">Continue to your artwork, addresses, and order progress.</p>

    @if ($errors->any())
        <div class="error-summary" role="alert" aria-live="polite">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
    @if (session('status'))<div class="success-summary" role="status">{{ session('status') }}</div>@endif

    <form class="auth-form" method="POST" action="{{ route('customer.login.store') }}">
        @csrf
        <label class="field"><span>Email address</span><input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"></label>
        <label class="field"><span class="field-line">Password <a href="{{ route('customer.password.request') }}">Forgot password?</a></span><input type="password" name="password" required autocomplete="current-password"></label>
        <button class="submit" type="submit">Sign in securely →</button>
    </form>
    <p class="switch">New to Okina Craft? <a href="{{ route('customer.register') }}">Create an account</a></p>
@endsection
