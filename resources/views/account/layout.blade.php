@extends('layouts.store')
@section('title', 'My account – '.setting('store_name', 'JB Optician'))
@section('content')
<div class="wrap" style="padding:30px 20px 50px"><h1 class="page-title">My account</h1>
  <div style="display:grid;grid-template-columns:230px 1fr;gap:40px" class="account-grid">
    <nav aria-label="Account" style="font-size:14px;border-right:1px solid var(--line)">
      @foreach(['account.dashboard' => ['Dashboard','⌂'], 'account.orders' => ['Orders','☰'], 'account.downloads' => ['Downloads','↓'], 'account.addresses' => ['Addresses','⌖'], 'account.details' => ['Account details','☺']] as $r => [$l,$i])
        <a href="{{ route($r) }}" style="display:block;padding:14px 8px;{{ request()->routeIs($r.'*') ? 'font-weight:600;color:var(--blue)' : '' }}">{{ $i }} &nbsp;{{ $l }}</a>@endforeach
      <form method="POST" action="{{ route('logout') }}">@csrf<button class="link" style="padding:14px 8px;font-size:14px;text-decoration:none;color:inherit">⎋ &nbsp;Log out</button></form>
    </nav>
    <div>@yield('account')</div>
  </div></div>
<style>@media(max-width:800px){.account-grid{grid-template-columns:1fr!important;gap:10px!important}}</style>
@endsection
