@extends('layouts.store')
@section('content')
<div class="wrap" style="max-width:520px;padding:40px 20px"><h1 class="page-title">Set a new password</h1>
  <form method="POST" action="{{ route('password.update') }}">@csrf<input type="hidden" name="token" value="{{ $token }}">
    <div class="field"><label for="re">Email address</label><input id="re" type="email" name="email" value="{{ old('email', $email) }}" required></div>@error('email')<div class="errors">{{ $message }}</div>@enderror
    <div class="field"><label for="rp">New password</label><input id="rp" type="password" name="password" required autocomplete="new-password"></div>@error('password')<div class="errors">{{ $message }}</div>@enderror
    <div class="field"><label for="rp2">Confirm password</label><input id="rp2" type="password" name="password_confirmation" required autocomplete="new-password"></div>
    <p style="font-size:12px;color:var(--muted)">At least 8 characters, with letters and numbers.</p><button class="btn">Save password</button></form></div>
@endsection
