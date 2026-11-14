@extends('layouts.store')
@section('title', 'Contact Us – '.setting('store_name', 'JB Optician'))
@section('content')
<div class="wrap" style="max-width:820px;padding:40px 20px 60px;margin:0 auto">
  <h1 style="font-size:36px;font-weight:700;text-align:center;margin:0 0 10px;color:var(--ink)">Contact Us</h1>
  <p style="text-align:center;font-size:14px;color:var(--muted);margin:0 0 32px">If you have any questions or need assistance, feel free to reach out to us!</p>

  <div style="background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.04);padding:32px 36px;margin-bottom:50px">
    <form method="POST" action="{{ route('contact.store') }}" novalidate>
      @csrf
      <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <div class="field">
        <label for="c-name" style="font-size:13px;font-weight:600">Name:</label>
        <input id="c-name" name="name" value="{{ old('name') }}" placeholder="Your Name" required>
        @error('name')<div class="errors">{{ $message }}</div>@enderror
      </div>
      <div class="field">
        <label for="c-email" style="font-size:13px;font-weight:600">Email:</label>
        <input id="c-email" type="email" name="email" value="{{ old('email') }}" placeholder="Your Email" required>
        @error('email')<div class="errors">{{ $message }}</div>@enderror
      </div>
      <div class="field">
        <label for="c-msg" style="font-size:13px;font-weight:600">Message:</label>
        <textarea id="c-msg" name="message" rows="5" placeholder="Write your message here..." required>{{ old('message') }}</textarea>
        @error('message')<div class="errors">{{ $message }}</div>@enderror
      </div>
      <button class="btn btn--block btn--rect" style="background:var(--blue);color:#fff;padding:14px 24px;font-size:14px;font-weight:600;border-radius:8px;border:0;cursor:pointer;margin-top:12px" type="submit">Submit Form</button>
    </form>
  </div>

  <div style="text-align:center;margin-top:20px">
    <h2 style="font-size:28px;font-weight:700;margin:0 0 12px;color:var(--ink)">Our Location</h2>
    <p style="font-size:14.5px;color:var(--muted);line-height:1.6;margin:0 auto 24px;max-width:680px">
      {{ setting('store_address', 'Shop 1, Matrutva Maternity & Nursing Hospital, Colony, Ulkanagari Rd, near Omkareshwar Mandir, Garkheda Area, Chhatrapati Sambhajinagar, Maharashtra 431005') }}
      @if(setting('store_phone'))<br><b>Phone:</b> <a href="tel:{{ setting('store_phone') }}" style="color:var(--blue);font-weight:600">{{ setting('store_phone') }}</a>@endif
      @if(setting('store_email')) · <b>Email:</b> {{ setting('store_email') }}@endif
      @if(setting('store_hours'))<br><b>Hours:</b> {{ setting('store_hours') }}@endif
    </p>

    @if(setting('map_embed_url'))
      <iframe class="map" src="{{ setting('map_embed_url') }}" style="width:100%;height:400px;border-radius:12px;border:1px solid var(--line);box-shadow:0 4px 16px rgba(0,0,0,.04)" title="Store location map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
    @else
      <div class="map" style="display:flex;align-items:center;justify-content:center;text-align:center;padding:24px;border-radius:12px;border:1px solid var(--line)">
        <div>
          <p style="margin:0 0 10px;color:var(--muted)">Matrutva Maternity & Nursing Hospital, Ulkanagari Rd, Chhatrapati Sambhajinagar</p>
          <a class="btn btn--sm" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query={{ urlencode(setting('store_address', 'JB Optician Ulkanagari Sambhajinagar')) }}">Open in Google Maps ↗</a>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
