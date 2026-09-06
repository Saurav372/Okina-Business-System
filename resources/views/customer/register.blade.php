@extends('customer.auth-layout')

@section('title', 'Create account')

@section('content')
    <p class="eyebrow">Create your private workspace</p>
    <h1>Make an account.</h1>
    <p class="intro">Save artwork securely, reuse delivery details, and follow every order.</p>

    @if ($errors->any())
        <div class="error-summary" role="alert" aria-live="polite">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('customer.register.store') }}">
        @csrf
        <label class="field"><span>Your name</span><input type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"></label>
        <label class="field"><span>Email address</span><input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
        <label class="field"><span>Password</span><input type="password" name="password" required autocomplete="new-password"><small>Use at least 8 characters.</small></label>
        <label class="field"><span>Confirm password</span><input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        <button class="submit" type="submit">Create my account →</button>
    </form>
    <p class="switch">Already have an account? <a href="{{ route('customer.login') }}">Sign in</a></p>
@endsection
