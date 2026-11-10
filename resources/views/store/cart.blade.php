@extends('layouts.store')
@section('title', 'Cart – '.setting('store_name', 'JB Optician'))
@section('content')
<div class="wrap">
  <h1 class="page-title">Cart</h1>
  @if($summary['cart_lines']->isEmpty())
    <p style="text-align:center;padding:60px 0">Your cart is currently empty. <a class="btn" href="{{ route('shop') }}" style="margin-left:10px">Return to shop</a></p>
  @else
  <div class="cart">
    <div>
      <div style="display:flex;justify-content:space-between;font-size:11px;letter-spacing:.06em;border-bottom:1px solid var(--line);padding-bottom:8px"><span>PRODUCT</span><span>TOTAL</span></div>
      @foreach($summary['cart_lines'] as $l)@php($p = $l['product']->pricing($l['variant']))
      <div class="cart__item">
        <a href="{{ route('product', $l['product']) }}"><img src="{{ $l['product']->image_url }}" alt=""></a>
        <div>
          <a href="{{ route('product', $l['product']) }}">{{ $l['product']->name }}</a>@if($l['variant'])<div class="price">{{ $l['variant']->label }}</div>@endif
          <div class="price">@if($p['sale'])<del>{{ money($p['regular']) }}</del> @endif{{ money($p['current']) }}</div>
          @if($p['sale'])<div class="save">SAVE {{ money(($p['regular'] - $p['sale']) * $l['qty']) }}</div>@endif
          @if($l['problem'])<div class="problem">{{ $l['problem'] }}</div>@endif
          <form method="POST" action="{{ route('cart.update', $l['item']->id) }}" style="margin:8px 0">@csrf @method('PATCH')
            <div class="qty" data-qty><button type="button" data-step="-" aria-label="Decrease">−</button><input type="number" name="qty" value="{{ $l['qty'] }}" min="0" max="99" data-autosubmit aria-label="Quantity"><button type="button" data-step="+" aria-label="Increase">+</button></div></form>
          <form method="POST" action="{{ route('cart.remove', $l['item']->id) }}">@csrf @method('DELETE')<button class="link">Remove item</button></form>
        </div>
        <div>{{ money($p['current'] * $l['qty']) }}</div>
      </div>
      @endforeach
    </div>
    <aside>
      <div style="font-size:11px;letter-spacing:.06em;border-bottom:1px solid var(--line);padding-bottom:8px;margin-bottom:6px">CART TOTALS</div>
      @if($summary['coupon'])
        <div class="totals"><div class="r"><span>Coupon: {{ $summary['coupon']->code }}</span><form method="POST" action="{{ route('cart.coupon.remove') }}">@csrf @method('DELETE')<button class="link">Remove</button></form></div></div>
      @else
        <details class="totals" @if(session('error')) open @endif><summary class="r" style="cursor:pointer;list-style:none">Add a coupon <span>⌵</span></summary>
          <form method="POST" action="{{ route('cart.coupon') }}" style="display:flex;gap:8px;padding:10px 0">@csrf<input class="input" name="coupon_code" placeholder="Coupon code" aria-label="Coupon code" required><button class="btn btn--sm">Apply</button></form></details>
      @endif
      <div class="totals">
        <div class="r"><span>Subtotal</span><span>{{ money($summary['subtotal']) }}</span></div>
        @if($summary['discount'] > 0)<div class="r"><span>Discount</span><span>−{{ money($summary['discount']) }}</span></div>@endif
        <div class="r"><span>Delivery</span><span>{{ $summary['shipping'] > 0 ? money($summary['shipping']) : 'FREE' }}</span></div>
        @if($summary['tax'] > 0)<div class="r"><span>Tax</span><span>{{ money($summary['tax']) }}</span></div>@endif
        <div class="r t"><span>Total</span><span>{{ money($summary['total']) }}</span></div>
      </div>
      <p style="text-align:center">@if($summary['has_problems'])<span class="problem">Fix the highlighted items to continue.</span>@else<a class="btn btn--block" href="{{ route('checkout') }}">Proceed to Checkout</a>@endif</p>
    </aside>
  </div>
  @endif
</div>
@endsection
