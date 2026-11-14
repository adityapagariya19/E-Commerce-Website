@extends('layouts.store')
@section('title', 'Contact Us – '.setting('store_name', 'JB Optician'))
@section('content')
<div class="wrap" style="padding:40px 20px 48px">
  <div style="text-align:center;max-width:760px;margin:0 auto 40px">
    <p style="font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--blue);font-weight:700;margin:0 0 8px">GET IN TOUCH</p>
    <h1 style="font-size:clamp(28px, 3.8vw, 40px);font-weight:700;margin:0 0 12px;color:var(--ink)">Contact Us</h1>
    <p style="font-size:15px;color:var(--muted);line-height:1.6;margin:0">Have questions about our eyewear collections, prescription lenses, or your recent order? We are here to assist you.</p>
  </div>

  {{-- 2-Column Symmetrical 50/50 Split (1fr 1fr) --}}
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:36px;align-items:stretch;margin-bottom:48px" class="contact-grid">
    {{-- Left: Store Details Card (50% Width) --}}
    <div style="background:#fff;border:1px solid var(--line);border-radius:14px;padding:32px;box-shadow:0 4px 18px rgba(0,0,0,.03);display:flex;flex-direction:column;justify-content:space-between">
      <div>
        <h2 style="font-size:20px;font-weight:700;color:var(--ink);margin:0 0 6px">Store Information</h2>
        <p style="font-size:13.5px;color:var(--muted);margin:0 0 24px">Visit our optical showroom or get in touch directly.</p>

        <div style="display:flex;flex-direction:column;gap:20px">
          <div style="display:flex;gap:14px;align-items:flex-start">
            <div style="width:38px;height:38px;border-radius:8px;background:#eef4ff;color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">📍</div>
            <div>
              <b style="font-size:13px;color:var(--ink);display:block;margin-bottom:2px">Showroom Address</b>
              <p style="font-size:13.5px;color:#444;line-height:1.5;margin:0">
                {{ setting('store_address', 'Shop 1, Matrutva Maternity & Nursing Hospital, Colony, Ulkanagari Rd, near Omkareshwar Mandir, Garkheda Area, Chhatrapati Sambhajinagar, Maharashtra 431005') }}
              </p>
            </div>
          </div>

          <div style="display:flex;gap:14px;align-items:flex-start">
            <div style="width:38px;height:38px;border-radius:8px;background:#eef4ff;color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">📞</div>
            <div>
              <b style="font-size:13px;color:var(--ink);display:block;margin-bottom:2px">Phone Support</b>
              <p style="font-size:13.5px;color:#444;margin:0">
                <a href="tel:{{ setting('store_phone', '+919876543210') }}" style="color:var(--blue);font-weight:600">{{ setting('store_phone', '+91 9876543210') }}</a>
              </p>
            </div>
          </div>

          <div style="display:flex;gap:14px;align-items:flex-start">
            <div style="width:38px;height:38px;border-radius:8px;background:#eef4ff;color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">✉️</div>
            <div>
              <b style="font-size:13px;color:var(--ink);display:block;margin-bottom:2px">Email Support</b>
              <p style="font-size:13.5px;color:#444;margin:0">
                <a href="mailto:{{ setting('store_email', 'adityapagariya19@gmail.com') }}" style="color:var(--blue);font-weight:600">{{ setting('store_email', 'adityapagariya19@gmail.com') }}</a>
              </p>
            </div>
          </div>

          <div style="display:flex;gap:14px;align-items:flex-start">
            <div style="width:38px;height:38px;border-radius:8px;background:#eef4ff;color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">⏰</div>
            <div>
              <b style="font-size:13px;color:var(--ink);display:block;margin-bottom:2px">Working Hours</b>
              <p style="font-size:13px;color:#444;line-height:1.5;margin:0">
                Mon – Sat: 10:00 AM – 9:00 PM<br>Sunday: 10:00 AM – 8:00 PM
              </p>
            </div>
          </div>
        </div>
      </div>

      <div style="margin-top:24px">
        <a class="btn btn--rect btn--block" href="tel:{{ setting('store_phone', '+919876543210') }}" style="background:var(--blue);color:#fff;text-align:center;padding:13px;border-radius:8px;font-weight:600;display:block">📞 Call Now: {{ setting('store_phone', '+91 9876543210') }}</a>
      </div>
    </div>

    {{-- Right: Contact Form Card (50% Width) --}}
    <div style="background:#fff;border:1px solid var(--line);border-radius:14px;padding:32px;box-shadow:0 4px 18px rgba(0,0,0,.03);display:flex;flex-direction:column;justify-content:space-between">
      <div>
        <h2 style="font-size:20px;font-weight:700;color:var(--ink);margin:0 0 6px">Send Us a Message</h2>
        <p style="font-size:13.5px;color:var(--muted);margin:0 0 20px">Fill out the form below and we will respond as soon as possible.</p>

        <form method="POST" action="{{ route('contact.store') }}" novalidate id="contact-form">
          @csrf
          <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
          <div class="field" style="margin-bottom:16px">
            <label for="c-name" style="font-size:13px;font-weight:600;display:block;margin-bottom:6px">Name *</label>
            <input id="c-name" name="name" value="{{ old('name') }}" placeholder="Your Full Name" required style="width:100%;padding:11px 14px;border:1px solid var(--line);border-radius:8px;font-size:14px">
            @error('name')<div class="errors" style="color:#e02424;font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
          </div>
          <div class="field" style="margin-bottom:16px">
            <label for="c-email" style="font-size:13px;font-weight:600;display:block;margin-bottom:6px">Email Address *</label>
            <input id="c-email" type="email" name="email" value="{{ old('email') }}" placeholder="yourname@example.com" required style="width:100%;padding:11px 14px;border:1px solid var(--line);border-radius:8px;font-size:14px">
            @error('email')<div class="errors" style="color:#e02424;font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
          </div>
          <div class="field" style="margin-bottom:20px">
            <label for="c-msg" style="font-size:13px;font-weight:600;display:block;margin-bottom:6px">Message *</label>
            <textarea id="c-msg" name="message" rows="4" placeholder="How can we help you?" required style="width:100%;padding:11px 14px;border:1px solid var(--line);border-radius:8px;font-size:14px;resize:vertical">{{ old('message') }}</textarea>
            @error('message')<div class="errors" style="color:#e02424;font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
          </div>
          <button class="btn btn--block btn--rect" style="background:var(--blue);color:#fff;padding:13px;font-size:14px;font-weight:600;border-radius:8px;border:0;cursor:pointer;width:100%" type="submit">Submit Message</button>
        </form>
      </div>
    </div>
  </div>

  {{-- Map Section --}}
  <div style="background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden;box-shadow:0 4px 18px rgba(0,0,0,.03)">
    <div style="padding:18px 24px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
      <h3 style="font-size:16px;font-weight:600;margin:0;color:var(--ink)">Find Us on Google Maps</h3>
      <a class="btn btn--sm btn--ghost" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode('JB Optician, Chhatrapati Sambhajinagar') }}">Open in Google Maps ↗</a>
    </div>
    @if(setting('map_embed_url'))
      <iframe class="map" src="{{ setting('map_embed_url') }}" style="width:100%;height:380px;border:0;display:block" title="Store location map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
    @else
      <div style="display:flex;align-items:center;justify-content:center;height:380px;background:#f0f2f7;padding:24px;text-align:center">
        <div>
          <p style="font-size:14px;color:var(--muted);margin:0 0 10px">Shop 1, Matrutva Maternity &amp; Nursing Hospital, Ulkanagari Rd</p>
          <a class="btn btn--sm" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode('JB Optician, Chhatrapati Sambhajinagar') }}">Open in Google Maps ↗</a>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
