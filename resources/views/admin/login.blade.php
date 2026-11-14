<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Admin login – JB Optician</title><link rel="stylesheet" href="{{ asset('css/admin.css') }}"></head>
<body><div class="login"><form method="POST" action="{{ route('admin.login.store') }}">@csrf
  <div class="logo" style="display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:20px">
    <span style="font-family:Georgia,serif;font-size:34px;font-weight:700;color:#fff;line-height:1">JB</span>
    <div style="display:flex;flex-direction:column;align-items:flex-start;gap:2px">
      <svg viewBox="0 0 70 26" style="width:48px;height:18px" aria-hidden="true">
        <circle cx="20" cy="13" r="3" fill="#3b82f6"/>
        <path d="M2 13 Q20 -2 38 13 Q20 28 2 13Z" fill="none" stroke="#3b82f6" stroke-width="2.2"/>
        <path d="M40 6 Q58 2 68 13 Q58 24 40 20" fill="none" stroke="#3b82f6" stroke-width="2.2"/>
      </svg>
      <b style="font-size:12px;letter-spacing:.08em;color:#fff;font-weight:700;line-height:1">OPTICIAN</b>
      <small style="font-size:7px;letter-spacing:.05em;color:rgba(255,255,255,.7);line-height:1;margin-top:2px">YOUR VISION OUR MISSION</small>
    </div>
  </div>
  @error('email')<div class="flash er" role="alert">{{ $message }}</div>@enderror
  <div class="f"><label for="e">Username or Email Address</label><input id="e" name="email" type="text" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
  <div class="f"><label for="p">Password</label><input id="p" name="password" type="password" required autocomplete="current-password"></div>
  <label style="font-weight:400"><input type="checkbox" name="remember" value="1"> Remember Me</label><br><button>Log In</button>
  <p style="text-align:center;font-size:11px;color:#9aa;margin:16px 0 0">© {{ date('Y') }} JB optician, All Rights Reserved</p></form></div></body></html>
