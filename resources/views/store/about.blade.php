@extends('layouts.store')
@section('title', 'About Us – '.setting('store_name', 'JB Optician'))
@section('content')
<div class="wrap" style="padding:40px 20px 48px">
  {{-- Header Banner --}}
  <div style="text-align:center;max-width:780px;margin:0 auto 40px">
    <p style="font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--blue);font-weight:700;margin:0 0 8px">EXCELLENCE IN EYECARE &amp; EYEWEAR</p>
    <h1 style="font-size:clamp(28px, 3.8vw, 40px);font-weight:700;margin:0 0 14px;color:var(--ink);line-height:1.2">
      About <span style="color:var(--blue)">{{ setting('store_name', 'JB Optician') }}</span>
    </h1>
    <p style="font-size:15px;color:var(--muted);line-height:1.6;margin:0">
      Your vision is our mission. Dedicated to delivering precision lenses, iconic designer frames, and professional optical care to empower your perspective.
    </p>
  </div>

  {{-- 2-Column Story & Values Grid: Symmetrical 50/50 Split (1fr 1fr) --}}
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:36px;align-items:stretch;margin-bottom:48px" class="about-grid">
    {{-- Left: Story & Stats Card --}}
    <div style="background:#fff;border:1px solid var(--line);border-radius:14px;padding:32px;box-shadow:0 4px 18px rgba(0,0,0,.03);display:flex;flex-direction:column;justify-content:space-between">
      <div>
        <h2 style="font-size:22px;font-weight:700;color:var(--ink);margin:0 0 16px;line-height:1.3">Our Commitment to Quality Vision</h2>
        <div style="font-size:14.5px;line-height:1.8;color:#444">
          <p style="margin:0 0 14px">Welcome to <b>JB Optician</b>, where your vision is our mission. We specialize in high-quality eyewear, advanced lenses, and professional eye care services to meet your needs.</p>
          <p style="margin:0 0 14px">Our store offers a wide variety of stylish frames, premium lenses, and modern contact lenses for all age groups. Our experienced staff and latest technology guarantee precision and comfort in every product.</p>
          <p style="margin:0">Customer satisfaction is our top priority, and we strive to provide the best service in the industry with personalized consultations and computerized lens testing.</p>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:12px;margin-top:24px">
        <div style="background:#f8f9fc;border:1px solid #e5e9f2;border-radius:10px;padding:16px 8px;text-align:center">
          <b style="display:block;font-size:24px;color:var(--blue);font-weight:700">500+</b>
          <span style="font-size:11.5px;color:var(--muted);font-weight:600;display:block;margin-top:2px">Eyewear Styles</span>
        </div>
        <div style="background:#f8f9fc;border:1px solid #e5e9f2;border-radius:10px;padding:16px 8px;text-align:center">
          <b style="display:block;font-size:24px;color:var(--blue);font-weight:700">100%</b>
          <span style="font-size:11.5px;color:var(--muted);font-weight:600;display:block;margin-top:2px">Authentic Lenses</span>
        </div>
        <div style="background:#f8f9fc;border:1px solid #e5e9f2;border-radius:10px;padding:16px 8px;text-align:center">
          <b style="display:block;font-size:24px;color:var(--blue);font-weight:700">4.9★</b>
          <span style="font-size:11.5px;color:var(--muted);font-weight:600;display:block;margin-top:2px">Customer Rating</span>
        </div>
      </div>
    </div>

    {{-- Right: Highlight Feature Cards (Equal 50% Column) --}}
    <div style="display:flex;flex-direction:column;justify-content:space-between;gap:16px">
      <div style="background:#fff;border:1px solid var(--line);border-radius:14px;padding:24px 22px;box-shadow:0 4px 18px rgba(0,0,0,.03);display:flex;gap:16px;align-items:flex-start;flex:1">
        <div style="width:44px;height:44px;border-radius:10px;background:#eef4ff;color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">👓</div>
        <div>
          <h3 style="font-size:16px;font-weight:600;margin:0 0 6px;color:var(--ink)">Curated Eyewear Styles</h3>
          <p style="font-size:13.5px;color:var(--muted);margin:0;line-height:1.6">Handpicked international &amp; trending Indian frames crafted with durable, ultra-lightweight materials.</p>
        </div>
      </div>

      <div style="background:#fff;border:1px solid var(--line);border-radius:14px;padding:24px 22px;box-shadow:0 4px 18px rgba(0,0,0,.03);display:flex;gap:16px;align-items:flex-start;flex:1">
        <div style="width:44px;height:44px;border-radius:10px;background:#eef4ff;color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">🔬</div>
        <div>
          <h3 style="font-size:16px;font-weight:600;margin:0 0 6px;color:var(--ink)">Advanced Lens Coatings</h3>
          <p style="font-size:13.5px;color:var(--muted);margin:0;line-height:1.6">Anti-reflective, blue-cut screen protection, UV400 filters, and custom progressive focal lenses.</p>
        </div>
      </div>

      <div style="background:#fff;border:1px solid var(--line);border-radius:14px;padding:24px 22px;box-shadow:0 4px 18px rgba(0,0,0,.03);display:flex;gap:16px;align-items:flex-start;flex:1">
        <div style="width:44px;height:44px;border-radius:10px;background:#eef4ff;color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">👨‍⚕️</div>
        <div>
          <h3 style="font-size:16px;font-weight:600;margin:0 0 6px;color:var(--ink)">Personalized Eye Consultation</h3>
          <p style="font-size:13.5px;color:var(--muted);margin:0;line-height:1.6">Expert opticians to help you select the exact frame shape and lens specifications for your facial structure.</p>
        </div>
      </div>
    </div>
  </div>

  {{-- Visit Our Store & Location: Symmetrical 50/50 Split (1fr 1fr) --}}
  <div style="background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.04);overflow:hidden">
    <div style="padding:24px 30px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px">
      <div>
        <h2 style="font-size:20px;font-weight:700;margin:0 0 4px;color:var(--ink)">Visit Our Retail Store</h2>
        <p style="font-size:13.5px;color:var(--muted);margin:0">Experience our frames in person and receive on-the-spot eye testing.</p>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a class="btn btn--sm" href="tel:{{ setting('store_phone', '+919876543210') }}" style="background:var(--blue);color:#fff">📞 Call {{ setting('store_phone', '+91 9876543210') }}</a>
        <a class="btn btn--sm btn--ghost" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode('JB Optician, Chhatrapati Sambhajinagar') }}">📍 Get Directions ↗</a>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;align-items:stretch" class="store-grid">
      <div style="padding:32px 30px;background:#fafafc;display:flex;flex-direction:column;justify-content:center;gap:22px;border-right:1px solid var(--line)">
        <div>
          <span style="font-size:11px;font-weight:700;letter-spacing:.08em;color:var(--blue);text-transform:uppercase">OUR ADDRESS</span>
          <p style="font-size:14px;line-height:1.6;color:#333;margin:6px 0 0">
            {{ setting('store_address', 'Shop 1, Matrutva Maternity & Nursing Hospital, Colony, Ulkanagari Rd, near Omkareshwar Mandir, Garkheda Area, Chhatrapati Sambhajinagar, Maharashtra 431005') }}
          </p>
        </div>

        <div>
          <span style="font-size:11px;font-weight:700;letter-spacing:.08em;color:var(--blue);text-transform:uppercase">DIRECT CONTACT</span>
          <p style="font-size:14px;color:#333;margin:6px 0 0">
            <b>Phone:</b> <a href="tel:{{ setting('store_phone', '+919876543210') }}" style="color:var(--blue);font-weight:600">{{ setting('store_phone', '+91 9876543210') }}</a>
          </p>
        </div>

        <div>
          <span style="font-size:11px;font-weight:700;letter-spacing:.08em;color:var(--blue);text-transform:uppercase">STORE HOURS</span>
          <p style="font-size:13.5px;color:#444;margin:6px 0 0;line-height:1.5">
            Mon – Sat: 10:00 AM – 9:00 PM<br>
            Sunday: 10:00 AM – 8:00 PM
          </p>
        </div>
      </div>

      <div style="min-height:380px;height:100%">
        @if(setting('map_embed_url'))
          <iframe class="map" src="{{ setting('map_embed_url') }}" style="width:100%;height:100%;min-height:380px;border:0;display:block" title="Store location map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        @else
          <div style="display:flex;align-items:center;justify-content:center;height:100%;min-height:380px;background:#f0f2f7;padding:24px;text-align:center">
            <div>
              <p style="font-size:14px;color:var(--muted);margin:0 0 12px">Shop 1, Matrutva Maternity &amp; Nursing Hospital, Ulkanagari Rd</p>
              <a class="btn btn--sm" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode('JB Optician, Chhatrapati Sambhajinagar') }}">Open in Google Maps ↗</a>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
