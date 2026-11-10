<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex,nofollow"><title>@yield('title', 'Admin') – JB Optician</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="{{ asset('css/admin.css') }}">
<script>try{var t=localStorage.getItem('jbo-admin-theme');if(t)document.documentElement.dataset.theme=t}catch(e){}</script></head>
<body><div class="shell">
@php($u = auth()->user())
<aside class="side"><a class="brand" href="{{ route('admin.dashboard') }}">JB Optician</a>
  @if($u->hasPermission('dashboard'))<a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'on' : '' }}">Dashboard</a>@endif
  @if($u->hasPermission('products'))<h6>Catalogue</h6><a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'on' : '' }}">Products</a><a href="{{ route('admin.reviews.index') }}" class="{{ request()->routeIs('admin.reviews.*') ? 'on' : '' }}">Reviews</a>@endif
  @if($u->hasPermission('catalog'))<a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'on' : '' }}">Categories</a><a href="{{ route('admin.brands.index') }}" class="{{ request()->routeIs('admin.brands.*') ? 'on' : '' }}">Brands</a>@endif
  @if($u->hasPermission('orders') || $u->hasPermission('customers') || $u->hasPermission('coupons'))<h6>Sales</h6>@endif
  @if($u->hasPermission('orders'))<a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'on' : '' }}">Orders</a>@endif
  @if($u->hasPermission('customers'))<a href="{{ route('admin.customers.index') }}" class="{{ request()->routeIs('admin.customers.*') ? 'on' : '' }}">Customers</a>@endif
  @if($u->hasPermission('coupons'))<a href="{{ route('admin.coupons.index') }}" class="{{ request()->routeIs('admin.coupons.*') ? 'on' : '' }}">Coupons</a>@endif
  @if($u->hasPermission('content'))<h6>Content</h6><a href="{{ route('admin.hero-slides.index') }}" class="{{ request()->routeIs('admin.hero-slides.*') ? 'on' : '' }}">Hero slides</a><a href="{{ route('admin.blocks.index') }}" class="{{ request()->routeIs('admin.blocks.*') ? 'on' : '' }}">Homepage blocks</a><a href="{{ route('admin.pages.index') }}" class="{{ request()->routeIs('admin.pages.*') ? 'on' : '' }}">Pages</a><a href="{{ route('admin.faq-topics.index') }}" class="{{ request()->routeIs('admin.faq-topics.*') ? 'on' : '' }}">Help topics</a><a href="{{ route('admin.faqs.index') }}" class="{{ request()->routeIs('admin.faqs.*') ? 'on' : '' }}">Help questions</a>@endif
  @if($u->hasPermission('newsletter') || $u->hasPermission('messages') || $u->hasPermission('reports'))<h6>Marketing &amp; insight</h6>@endif
  @if($u->hasPermission('newsletter'))<a href="{{ route('admin.newsletter.index') }}" class="{{ request()->routeIs('admin.newsletter.*') ? 'on' : '' }}">Newsletter</a>@endif
  @if($u->hasPermission('messages'))<a href="{{ route('admin.messages.index') }}" class="{{ request()->routeIs('admin.messages.*') ? 'on' : '' }}">Messages</a>@endif
  @if($u->hasPermission('reports'))<a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.index') ? 'on' : '' }}">Reports</a><a href="{{ route('admin.reports.popular') }}" class="{{ request()->routeIs('admin.reports.popular') ? 'on' : '' }}">Popular This Week</a>@endif
  @if($u->hasPermission('settings') || $u->hasPermission('admin_users') || $u->hasPermission('activity_log'))<h6>System</h6>@endif
  @if($u->hasPermission('settings'))<a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'on' : '' }}">Settings</a>@endif
  @if($u->hasPermission('admin_users'))<a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'on' : '' }}">Admin users</a>@endif
  @if($u->hasPermission('activity_log'))<a href="{{ route('admin.activity.index') }}" class="{{ request()->routeIs('admin.activity.*') ? 'on' : '' }}">Activity log</a>@endif
  <h6>Site</h6><a href="{{ route('home') }}" target="_blank" rel="noopener">View storefront ↗</a>
</aside>
<div><div class="top"><span>{{ $u->name }} · <span class="pill">{{ $u->roleLabel() }}</span></span>
  <span><button class="themebtn" type="button" onclick="var d=document.documentElement;var n=d.dataset.theme==='dark'?'light':'dark';d.dataset.theme=n;try{localStorage.setItem('jbo-admin-theme',n)}catch(e){}">◐ Theme</button>
  <form method="POST" action="{{ route('admin.logout') }}" style="display:inline">@csrf<button class="btn sm grey">Log out</button></form></span></div>
<div class="main">
  @if(session('success'))<div class="flash ok" role="status">{{ session('success') }}</div>@endif @if(session('error'))<div class="flash er" role="alert">{{ session('error') }}</div>@endif
  @if($errors->any())<div class="flash er" role="alert"><b>Please fix the following:</b><ul style="margin:6px 0 0 18px;padding:0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
  @yield('content')
</div></div></div></body></html>
