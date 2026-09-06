@extends('customer.auth-layout')

@section('title', 'Choose a new password')

@section('content')
    <p class="eyebrow">Secure account recovery</p>
    <h1>Choose a new password.</h1>
    <p class="intro">Use a password you do not reuse on another account.</p>

    @if ($errors->any())
        <div class="error-summary" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('customer.password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label class="field"><span>Email address</span><input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"></label>
        <label class="field"><span>New password</span><input type="password" name="password" required autocomplete="new-password"><small>Use at least 8 characters.</small></label>
        <label class="field"><span>Confirm new password</span><input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        <button class="submit" type="submit">Save new password →</button>
    </form>
@endsection
