@extends('layouts.store')
@section('content')
<div class="wrap" style="max-width:520px;padding:40px 20px"><h1 class="page-title">Lost your password?</h1>
  <p style="font-size:13px">Enter your email address and we will send you a link to choose a new one.</p>
  <form method="POST" action="{{ route('password.email') }}">@csrf<div class="field"><label for="fe">Email address</label><input id="fe" type="email" name="email" value="{{ old('email') }}" required></div>@error('email')<div class="errors">{{ $message }}</div>@enderror<button class="btn">Reset password</button></form></div>
@endsection
