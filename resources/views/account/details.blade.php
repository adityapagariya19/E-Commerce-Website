@extends('account.layout')
@section('account')
<form method="POST" action="{{ route('account.details.update') }}" style="max-width:640px">@csrf @method('PUT')
  <div class="field"><label for="d-name">Name *</label><input id="d-name" name="name" value="{{ old('name', auth()->user()->name) }}" required></div>@error('name')<div class="errors">{{ $message }}</div>@enderror
  <div class="field"><label for="d-email">Email address *</label><input id="d-email" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required></div>@error('email')<div class="errors">{{ $message }}</div>@enderror
  <div class="field"><label for="d-phone">Phone</label><input id="d-phone" name="phone" value="{{ old('phone', auth()->user()->phone) }}"></div>
  <fieldset style="border:1px solid var(--line);padding:16px;margin:0 0 16px"><legend>Password change</legend>
    <div class="field"><label>Current password (leave blank to leave unchanged)</label><input type="password" name="current_password" autocomplete="current-password"></div>@error('current_password')<div class="errors">{{ $message }}</div>@enderror
    <div class="field"><label>New password (leave blank to leave unchanged)</label><input type="password" name="new_password" autocomplete="new-password"></div>@error('new_password')<div class="errors">{{ $message }}</div>@enderror
    <div class="field"><label>Confirm new password</label><input type="password" name="new_password_confirmation" autocomplete="new-password"></div></fieldset>
  <button class="btn">Save changes</button></form>
@endsection
