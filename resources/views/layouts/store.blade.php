<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', setting('store_name', 'JB Optician').' – '.setting('store_tagline', 'Your vision is our mission'))</title>
<meta name="description" content="@yield('description', 'JB Optician – eyeglasses, sunglasses, lenses and accessories.')">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/store.css') }}">
@stack('head')
@php
  $ga = setting('ga4_measurement_id', config('services.ga4.measurement_id'));
@endphp
@if($ga)
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga }}"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','{{ $ga }}');</script>
@endif
</head>
<body data-open-login="{{ session('login_error') || $errors->has('username') || $errors->register->any() ? '1' : '0' }}">
@php
  $ticks = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) setting('announcements', '')))));
@endphp
@if($ticks)
<div class="ticker" aria-label="Announcements"><div class="ticker__track">@foreach(array_merge($ticks, $ticks, $ticks) as $t)<span>{{ $t }}</span>@endforeach</div></div>
@endif

<header class="site-header">
  <div class="wrap">
    <a href="{{ route('home') }}" class="logo" aria-label="{{ setting('store_name', 'JB Optician') }} home">
      {{-- Logo is an inline stand-in; replace with the supplied logo file when available. --}}
      <span class="logo__mark">JB</span>
      <span class="logo__word"><svg viewBox="0 0 70 26" aria-hidden="true"><circle cx="20" cy="13" r="3" fill="#1a47b8"/><path d="M2 13 Q20 -2 38 13 Q20 28 2 13Z" fill="none" stroke="#1a47b8" stroke-width="2"/><path d="M40 6 Q58 2 68 13 Q58 24 40 20" fill="none" stroke="#1a47b8" stroke-width="2"/></svg><b>OPTICIAN</b><small>YOUR VISION OUR MISSION</small></span>
    </a>
    <nav class="nav" id="main-nav" aria-label="Primary">
      <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-active' : '' }}">Home</a>
      <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'is-active' : '' }}">About Us</a>
      <div class="has-sub"><a href="{{ route('shop') }}">Shop ⌵</a>
        <div class="sub">@foreach($navCategories as $c)<a href="{{ route('category', $c) }}">{{ $c->name }}</a>@endforeach</div></div>
      <a href="{{ route('shop') }}" class="{{ request()->routeIs('shop') ? 'is-active' : '' }}">All Products</a>
      <a href="{{ route('tryon') }}" class="{{ request()->routeIs('tryon') ? 'is-active' : '' }}">Try On 👓</a>
      <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'is-active' : '' }}">Contact Us</a>
    </nav>
    <div class="icons">
      <button class="icon-btn" data-open="#search-ov" aria-label="Search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg></button>
      @auth
        <a class="icon-btn" href="{{ route('account.dashboard') }}" aria-label="My account"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-5 15-5 16 0"/></svg></a>
      @else
        <button class="icon-btn" data-open="#login-modal" aria-label="Log in or register"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-5 15-5 16 0"/></svg></button>
      @endauth
      <button class="icon-btn" data-open="#cart-drawer" aria-label="Cart"><svg viewBox="0 0 24 24"><path d="M3 4h3l2.5 11h10L21 7H7"/><circle cx="9" cy="19" r="1.4"/><circle cx="18" cy="19" r="1.4"/></svg><span class="badge" data-cart-count @if(!$cartCount) hidden @endif>{{ $cartCount }}</span></button>
      <button class="icon-btn burger" aria-label="Menu" aria-controls="main-nav" aria-expanded="false"><svg viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button>
    </div>
  </div>
</header>

@if(session('success'))<div class="flash flash--ok" role="status">{{ session('success') }}</div>@endif
@if(session('error'))<div class="flash flash--err" role="alert">{{ session('error') }}</div>@endif

<main id="content">@yield('content')</main>

<footer class="footer">
  <div class="wrap">
    <div class="footer__cols">
      <div class="footer__brand"><b>{{ setting('store_name', 'JB Optician') }}</b><small>{{ setting('store_tagline', 'Your vision is our mission') }}</small>
        <div class="footer__social">
          @if(setting('social_facebook'))<a href="{{ setting('social_facebook') }}" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#111"/><path d="M13 20v-7h2.4l.4-3H13V8.5c0-.9.3-1.5 1.5-1.5H16V4.3A20 20 0 0 0 13.8 4C11.7 4 10.3 5.3 10.3 7.6V10H8v3h2.3v7z" fill="#fff"/></svg></a>@endif
          @if(setting('social_youtube'))<a href="{{ setting('social_youtube') }}" target="_blank" rel="noopener" aria-label="YouTube"><svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="4" fill="#111"/><path d="M10 9l5 3-5 3z" fill="#fff"/></svg></a>@endif
        </div></div>
      <div><h5>Follow Us</h5><ul>
        <li><a href="{{ setting('social_instagram') ?: 'https://instagram.com/' }}" target="_blank" rel="noopener">Instagram</a></li>
        <li><a href="{{ setting('social_facebook') ?: 'https://facebook.com/' }}" target="_blank" rel="noopener">Facebook</a></li>
        <li><a href="{{ setting('social_youtube') ?: 'https://youtube.com/' }}" target="_blank" rel="noopener">Youtube</a></li>
      </ul></div>
      <div><h5>Support</h5><ul>
        <li><a href="{{ route('contact') }}">Contact Us</a></li><li><a href="{{ route('help') }}">Help Center</a></li><li><a href="{{ route('page', 'privacy-policy') }}">Privacy Policy</a></li>
      </ul></div>
      <div><h5>Newsletter</h5>
        <div class="news">
          @if(session('newsletter_success'))
            <div class="news__ok">@php
              $nlParts = array_pad(explode('! ', session('newsletter_success'), 2), 2, null);
              $a = $nlParts[0]; $b = $nlParts[1];
            @endphp{{ $a }}{{ $b ? '!' : '' }}@if($b)<span>{{ $b }}</span>@endif</div>
          @else
          <h3>DON'T<br>MISS OUT</h3>
          <p>Subscribe to get exclusive deals sent directly to your inbox.</p>
          <form method="POST" action="{{ route('newsletter.subscribe') }}" data-newsletter novalidate>
            @csrf<input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
            <label class="sr-only" for="nl-email">Email</label>
            <input type="email" id="nl-email" name="email" placeholder="Enter your email" required value="{{ old('email') }}">
            @error('email')<div class="errors">{{ $message }}</div>@enderror
            <label style="display:flex;gap:8px;font-size:11px;margin:0 0 10px;align-items:flex-start"><input type="checkbox" name="consent" value="1" required style="margin-top:2px"><span>I agree to receive marketing emails from {{ setting('store_name', 'JB Optician') }}. Unsubscribe any time.</span></label>
            @error('consent')<div class="errors">{{ $message }}</div>@enderror
            <button type="submit">SUBSCRIBE</button>
          </form>
          @endif
        </div></div>
    </div>
  </div>
  <div class="footer__copy">© {{ date('Y') }} {{ setting('store_name', 'JB Optician') }} <a href="{{ route('page', 'privacy-policy') }}">Privacy Policy</a> All rights reserved.</div>
</footer>

@include('store.partials.login-modal')
<aside class="drawer" id="cart-drawer" aria-label="Shopping cart" aria-hidden="true"><div class="drawer__head"><span>SHOPPING CART <span class="badge" style="position:static;display:inline-flex" data-cart-count @if(!$cartCount) hidden @endif>{{ $cartCount }}</span></span><button class="icon-btn" data-close aria-label="Close cart">✕</button></div><div class="drawer__content" style="display:contents"></div></aside>
<div class="search-ov" id="search-ov" aria-hidden="true"><div class="wrap"><form action="{{ route('shop') }}" method="GET" role="search"><input id="search-input" name="q" type="search" placeholder="Search products…" autocomplete="off" aria-label="Search products"><button class="btn">Search</button><button type="button" class="icon-btn" data-close aria-label="Close">✕</button></form><div class="suggest" id="suggest"></div></div></div>

<button class="fab fab--top" id="to-top" aria-label="Back to top">↑</button>
@php
  $bot = setting('chatbase_bot_id', config('services.chatbase.bot_id'));
@endphp
@if($bot)
  {{-- Chatbase embed as used by the reference site. Snippet format from memory: verify against your Chatbase dashboard. --}}
  <script>window.embeddedChatbotConfig={chatbotId:@json($bot),domain:"www.chatbase.co"}</script>
  <script src="https://www.chatbase.co/embed.min.js" chatbotId="{{ $bot }}" domain="www.chatbase.co" defer></script>
@else
  <button class="fab fab--chat" id="chat-open" aria-label="Open chat"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6"><path d="M4 5h16v11H9l-5 4z"/></svg></button>
  <div class="chat" id="chat" role="dialog" aria-label="Chat"><div class="chat__head"><span>{{ setting('store_name', 'JB Optician') }}</span><button data-chat-close style="background:none;border:0;color:#fff;cursor:pointer" aria-label="Close chat">✕</button></div><div class="chat__body" aria-live="polite"></div><form><input name="message" maxlength="300" placeholder="Message…" aria-label="Message" autocomplete="off"><button aria-label="Send">➤</button></form><div class="chat__foot">Help Centre search — not an AI assistant</div></div>
@endif
<script src="{{ asset('js/store.js') }}" defer></script>
@stack('scripts')
</body>
</html>
