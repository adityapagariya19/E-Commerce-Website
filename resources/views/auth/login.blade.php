@extends('layouts.store')
@section('title', 'My account – '.setting('store_name', 'JB Optician'))
@section('content')
<div class="wrap" style="padding:30px 20px"><h1 class="page-title">My account</h1>
  @if(session('login_error'))<div class="flash flash--err" style="margin-left:0;margin-right:0">{{ session('login_error') }}</div>@endif
  <div class="auth" style="max-width:820px;margin:0 auto">
    <section><h3>Login</h3><form method="POST" action="{{ route('login.store') }}">@csrf<input type="hidden" name="redirect" value="{{ request('redirect') }}">
      <div class="field"><label for="l-user">Username or email address *</label><input id="l-user" name="username" value="{{ old('username') }}" required autocomplete="username"></div>
      <div class="field"><label for="l-pass">Password *</label><input id="l-pass" type="password" name="password" required autocomplete="current-password"></div>
      <label style="font-size:13px"><input type="checkbox" name="remember" value="1"> Remember me</label><p><button class="btn">Log in</button></p><a href="{{ route('password.request') }}" style="font-size:13px">Lost your password?</a></form></section>
    <section><h3>Register</h3><form method="POST" action="{{ route('register') }}">@csrf<input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <div class="field"><label for="r-name">Name <span style="color:#c00">*</span></label><input id="r-name" name="name" value="{{ old('name') }}" autocomplete="name" required></div>
          <div class="field"><label for="r-email">Email address <span style="color:#c00">*</span></label><input id="r-email" type="email" name="email" required value="{{ old('email') }}" autocomplete="email"></div>
          <div class="field"><label for="r-pw">Password <span style="color:#c00">*</span></label><input id="r-pw" type="password" name="password" autocomplete="new-password" required minlength="8"></div>
          <div class="field"><label for="r-pw2">Confirm password <span style="color:#c00">*</span></label><input id="r-pw2" type="password" name="password_confirmation" autocomplete="new-password" required></div>
          @if($errors->register->any())<div class="errors" role="alert">@foreach($errors->register->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
          <input type="hidden" name="redirect" value="{{ request()->routeIs('login') ? request('redirect') : request()->getRequestUri() }}">
      <p style="font-size:12px;color:var(--muted)">At least 8 characters, with letters and numbers.</p><button class="btn">Register</button></form></section>
  </div></div>
@endsection
