@extends('layouts.store')
@section('title', 'About Us – '.setting('store_name', 'JB Optician'))
@section('content')
<div class="wrap about prose" style="max-width:var(--wrap);margin:0 auto;padding:40px 24px 60px">
  <h1 style="font-size:36px;font-weight:700;margin:0 0 24px;color:var(--ink)">About <span style="color:var(--blue)">{{ setting('store_name', 'JB Optician') }}</span></h1>
  
  <div style="font-size:15.5px;line-height:1.8;color:#333;max-width:960px">
    {!! \Illuminate\Support\Str::markdown((string) ($page->body ?? "Welcome to **JB Optician**, where your vision is our mission. We specialize in high-quality eyewear, advanced lenses, and professional eye care services to meet your needs.\n\nOur store offers a wide variety of stylish frames, premium lenses, and modern contact lenses for all age groups. Our experienced staff and latest technology guarantee precision and comfort in every product.\n\nCustomer satisfaction is our top priority, and we strive to provide the best service in the industry."), ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
  </div>

  <div style="margin-top:40px;border-top:1px solid var(--line);padding-top:32px">
    <h2 style="font-size:26px;font-weight:700;margin:0 0 14px;color:var(--ink)">Visit Our Store</h2>
    <p style="font-size:15px;line-height:1.7;color:#333;margin:0 0 20px;max-width:860px">
      <b>Address:</b> {{ setting('store_address', 'Shop 1, Matrutva Maternity & Nursing Hospital, Colony, Ulkanagari Rd, near Omkareshwar Mandir, Garkheda Area, Chhatrapati Sambhajinagar, Maharashtra 431005') }}
    </p>

    @if(setting('map_embed_url'))
      <iframe class="map" src="{{ setting('map_embed_url') }}" style="width:100%;height:420px;border-radius:12px;border:1px solid var(--line);box-shadow:0 4px 18px rgba(0,0,0,.04);display:block;margin:24px 0" title="JB Optician Store Location" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
    @else
      <div class="map" style="display:flex;align-items:center;justify-content:center;text-align:center;padding:28px;border-radius:12px;border:1px solid var(--line);margin:24px 0">
        <div>
          <p style="margin:0 0 10px;color:var(--muted)">Matrutva Maternity & Nursing Hospital, Ulkanagari Rd, Chhatrapati Sambhajinagar</p>
          <a class="btn btn--sm" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query={{ urlencode(setting('store_address', 'JB Optician Ulkanagari Sambhajinagar')) }}">Open in Google Maps ↗</a>
        </div>
      </div>
    @endif

    <div style="margin-top:20px;background:#f9f9fb;border:1px solid var(--line);border-radius:10px;padding:18px 24px;display:inline-flex;flex-direction:column;gap:4px">
      <p style="margin:0;font-size:15px"><b>Contact Us:</b> <a href="tel:{{ setting('store_phone', '+919762759028') }}" style="color:var(--blue);font-weight:600">{{ setting('store_phone', '+91 9762759028') }}</a></p>
      <p style="margin:0;color:var(--muted);font-size:13.5px">For appointments or frame inquiries, visit our store or reach out directly.</p>
    </div>
  </div>
</div>
@endsection
