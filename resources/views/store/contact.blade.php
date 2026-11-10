@extends('layouts.store')
@section('title', 'Contact Us – '.setting('store_name', 'JB Optician'))
@section('content')
<div class="band" style="margin-top:20px"><div class="wrap">
  <h1 class="page-title" style="margin-top:0">Contact Us</h1>
  <p style="text-align:center;font-size:13px">If you have any questions or need assistance, feel free to reach out to us!</p>
  <form class="card-form" method="POST" action="{{ route('contact.store') }}" novalidate>@csrf<input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
    <div class="field"><label for="c-name">Name:</label><input id="c-name" name="name" value="{{ old('name') }}" required>@error('name')<div class="errors">{{ $message }}</div>@enderror</div>
    <div class="field"><label for="c-email">Email:</label><input id="c-email" type="email" name="email" value="{{ old('email') }}" required>@error('email')<div class="errors">{{ $message }}</div>@enderror</div>
    <div class="field"><label for="c-msg">Message:</label><textarea id="c-msg" name="message" required>{{ old('message') }}</textarea>@error('message')<div class="errors">{{ $message }}</div>@enderror</div>
    <button class="btn btn--block btn--rect" style="background:#0d6efd" type="submit">Submit Form</button>
  </form></div></div>
<div class="band" style="padding-bottom:0"><div class="wrap"><h2 class="page-title" style="font-size:26px;margin-top:0">Our Location</h2>
  <p style="text-align:center;font-size:13px">{{ setting('store_address') }}@if(setting('store_phone')) · {{ setting('store_phone') }}@endif @if(setting('store_email')) · {{ setting('store_email') }}@endif @if(setting('store_hours')) · {{ setting('store_hours') }}@endif</p></div>
  @if(setting('map_embed_url'))<iframe class="map" src="{{ setting('map_embed_url') }}" title="Store location map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
  @else<div class="map" style="display:flex;align-items:center;justify-content:center;text-align:center;padding:20px"><div>Map embed not configured (Admin › Settings › Map embed URL).<br><a class="btn btn--sm" style="margin-top:12px" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query={{ urlencode(setting('store_address', 'JB Optician')) }}">Open in Google Maps</a></div></div>@endif
</div>
@endsection
