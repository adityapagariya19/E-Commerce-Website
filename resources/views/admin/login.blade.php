<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Admin login – JB Optician</title><link rel="stylesheet" href="{{ asset('css/admin.css') }}"></head>
<body><div class="login"><form method="POST" action="{{ route('admin.login.store') }}">@csrf
  <div class="logo">JB <small style="font-size:12px;letter-spacing:.1em">OPTICIAN</small></div>
  @error('email')<div class="flash er" role="alert">{{ $message }}</div>@enderror
  <div class="f"><label for="e">Username or Email Address</label><input id="e" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
  <div class="f"><label for="p">Password</label><input id="p" name="password" type="password" required autocomplete="current-password"></div>
  <label style="font-weight:400"><input type="checkbox" name="remember" value="1"> Remember Me</label><br><button>Log In</button>
  <p style="text-align:center;font-size:11px;color:#9aa;margin:16px 0 0">© {{ date('Y') }} JB optician, All Rights Reserved</p></form></div></body></html>
