<div class="overlay" id="login-modal" role="dialog" aria-modal="true" aria-label="Log in or register" aria-hidden="true">
  <div class="modal">
    <button class="modal__close" data-close aria-label="Close">×</button>
    <div class="auth">
      <section>
        <h3>Login</h3>
        @if(session('login_error'))<div class="errors" role="alert">{{ session('login_error') }}</div>@endif
        <form method="POST" action="{{ route('login.store') }}">
          @csrf<input type="hidden" name="redirect" value="{{ request()->routeIs('login') ? request('redirect') : request()->getRequestUri() }}">
          <div class="field"><label for="m-user">Username or email address <span style="color:#c00">*</span></label><input id="m-user" name="username" value="{{ old('username') }}" autocomplete="username" required></div>
          <div class="field"><label for="m-pass">Password <span style="color:#c00">*</span></label><input id="m-pass" type="password" name="password" autocomplete="current-password" required></div>
          <label style="font-size:13px"><input type="checkbox" name="remember" value="1"> Remember me</label>
          <p><button class="btn" type="submit">Log in</button></p>
          <a href="{{ route('password.request') }}" style="font-size:13px">Lost your password?</a>
        </form>
      </section>
      <section>
        <h3>Register</h3>
        <form method="POST" action="{{ route('register') }}">
          @csrf<input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
          <div class="field"><label for="m-name">Name <span style="color:#c00">*</span></label><input id="m-name" name="name" value="{{ old('name') }}" autocomplete="name" required></div>
          <div class="field"><label for="m-email">Email address <span style="color:#c00">*</span></label><input id="m-email" type="email" name="email" required value="{{ old('email') }}" autocomplete="email"></div>
          <div class="field"><label for="m-pw">Password <span style="color:#c00">*</span></label><input id="m-pw" type="password" name="password" autocomplete="new-password" required minlength="8"></div>
          <div class="field"><label for="m-pw2">Confirm password <span style="color:#c00">*</span></label><input id="m-pw2" type="password" name="password_confirmation" autocomplete="new-password" required></div>
          @if($errors->register->any())<div class="errors" role="alert">@foreach($errors->register->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
          <input type="hidden" name="redirect" value="{{ request()->routeIs('login') ? request('redirect') : request()->getRequestUri() }}">
          <p style="font-size:12px;color:var(--muted)">At least 8 characters, with letters and numbers.</p>
          <p>Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our <a href="{{ route('page', 'privacy-policy') }}"><b>privacy policy</b></a>.</p>
          <button class="btn" type="submit">Register</button>
        </form>
      </section>
    </div>
  </div>
</div>
