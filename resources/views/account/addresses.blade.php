@extends('account.layout')
@section('account')
<p style="font-size:13px">The following addresses can be used on the checkout page.</p>
<div class="addr">@forelse($addresses as $a)<div style="font-style:normal"><h3 style="font-weight:500;margin:0 0 8px;text-transform:capitalize">{{ $a->type }} address</h3>{{ $a->first_name }} {{ $a->last_name }}<br>{{ $a->line1 }}@if($a->line2), {{ $a->line2 }}@endif<br>{{ $a->city }}, {{ $a->state }} {{ $a->postcode }}
  <form method="POST" action="{{ route('account.addresses.destroy', $a) }}" onsubmit="return confirm('Delete this address?')">@csrf @method('DELETE')<button class="link">Delete</button></form></div>@empty<p>You have no saved addresses.</p>@endforelse</div>
<h3 style="font-weight:500;margin-top:30px">Add an address</h3>
<form method="POST" action="{{ route('account.addresses.store') }}" style="max-width:560px">@csrf
  <div class="field"><label>Type</label><select name="type"><option value="shipping">Shipping</option><option value="billing">Billing</option></select></div>
  <div class="two"><div class="field"><label>First name</label><input name="first_name" value="{{ old('first_name') }}" required></div><div class="field"><label>Last name</label><input name="last_name" value="{{ old('last_name') }}" required></div></div>
  <div class="field"><label>Address</label><input name="line1" value="{{ old('line1') }}" required></div><div class="field"><label>Apartment, suite (optional)</label><input name="line2" value="{{ old('line2') }}"></div>
  <div class="two"><div class="field"><label>City</label><input name="city" value="{{ old('city') }}" required></div><div class="field"><label>State</label><select name="state">@foreach($states as $s)<option @selected(old('state','Maharashtra')===$s)>{{ $s }}</option>@endforeach</select></div></div>
  <div class="two"><div class="field"><label>PIN code</label><input name="postcode" value="{{ old('postcode') }}" required></div><div class="field"><label>Phone</label><input name="phone" value="{{ old('phone') }}"></div></div>
  @if($errors->any())<div class="errors">{{ $errors->first() }}</div>@endif<button class="btn">Save address</button></form>
@endsection
