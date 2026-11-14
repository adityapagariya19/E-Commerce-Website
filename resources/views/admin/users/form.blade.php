@extends('layouts.admin')
@section('title','Admin user')
@section('content')<h1>{{ $user->exists ? 'Edit admin' : 'Add admin' }}</h1><form class="card" style="max-width:560px" method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" autocomplete="off">@csrf @if($user->exists) @method('PUT') @endif
<div class="f"><label>Name *</label><input type="text" name="name" value="{{ old('name', $user->name) }}" required></div><div class="f"><label>Email *</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required></div>
<div class="f"><label>Role *</label><select name="role"><option value="super_admin" selected>Admin</option></select></div>
<div class="f"><label>Password {{ $user->exists ? '(leave blank to keep)' : '*' }}</label><input type="password" name="password" autocomplete="new-password" @if(!$user->exists) required @endif><div class="hint">Minimum 10 characters with letters and numbers.</div></div>
<label style="font-weight:400"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))> Active</label><br><br><button class="btn">Save</button></form>@endsection
