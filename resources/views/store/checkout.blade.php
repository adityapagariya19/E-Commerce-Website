@extends('layouts.store')
@section('title', 'Checkout – '.setting('store_name', 'JB Optician'))
@section('content')
@php($u = auth()->user())
<div class="wrap" style="padding-bottom:40px">
  <ol class="steps" style="padding:0"><li class="on">Billing Details</li><li>Delivery</li><li>Payment</li></ol>
  @if($errors->any())<div class="flash flash--err" role="alert" style="margin-left:0;margin-right:0">Please correct the highlighted fields.</div>@endif
  <form method="POST" action="{{ route('checkout.store') }}" class="checkout" novalidate>
    @csrf<input type="hidden" name="checkout_token" value="{{ $token }}">
    <div>
      <h3 style="font-weight:600;margin:0 0 2px;font-size:15px;display:flex;justify-content:space-between">Contact information @guest<a href="#" data-open="#login-modal" style="font-size:12px;font-weight:400">Log in</a>@endguest</h3>
      <p style="font-size:12px;color:var(--muted);margin:0 0 10px">We'll use this email to send you details and updates about your order.</p>
      <div class="field"><input name="email" type="email" placeholder="Email address" value="{{ old('email', $u->email ?? '') }}" required aria-label="Email address">@error('email')<div class="errors">{{ $message }}</div>@enderror</div>
      @guest<label style="font-size:12px;display:block;margin-bottom:6px"><input type="checkbox" name="create_account" value="1" @checked(old('create_account'))> Create an account with {{ setting('store_name', 'JB Optician') }}</label>@endguest

      <h3 style="font-weight:600;margin:26px 0 2px;font-size:15px">Shipping address</h3>
      <p style="font-size:12px;color:var(--muted);margin:0 0 10px">Enter the address where you want your order delivered.</p>
      <div class="field"><select name="country" aria-label="Country"><option value="IN">India</option></select></div>
      <div class="two">
        <div class="field"><input name="first_name" placeholder="First name" value="{{ old('first_name', $default->first_name ?? '') }}" aria-label="First name" required>@error('first_name')<div class="errors">{{ $message }}</div>@enderror</div>
        <div class="field"><input name="last_name" placeholder="Last name" value="{{ old('last_name', $default->last_name ?? '') }}" aria-label="Last name" required>@error('last_name')<div class="errors">{{ $message }}</div>@enderror</div>
      </div>
      <div class="field"><input name="company" placeholder="Company (optional)" value="{{ old('company') }}" aria-label="Company"></div>
      <div class="field"><input name="address_1" placeholder="Address" value="{{ old('address_1', $default->line1 ?? '') }}" aria-label="Address" required>@error('address_1')<div class="errors">{{ $message }}</div>@enderror</div>
      <div class="field"><input name="address_2" placeholder="Apartment, suite, etc. (optional)" value="{{ old('address_2', $default->line2 ?? '') }}" aria-label="Address line 2"></div>
      <div class="two">
        <div class="field"><input name="city" placeholder="City" value="{{ old('city', $default->city ?? '') }}" aria-label="City" required>@error('city')<div class="errors">{{ $message }}</div>@enderror</div>
        <div class="field"><select name="state" aria-label="State">@foreach($states as $s)<option @selected(old('state', $default->state ?? 'Maharashtra') === $s)>{{ $s }}</option>@endforeach</select>@error('state')<div class="errors">{{ $message }}</div>@enderror</div>
      </div>
      <div class="two">
        <div class="field"><input name="postcode" placeholder="PIN Code" value="{{ old('postcode', $default->postcode ?? '') }}" aria-label="PIN code" inputmode="numeric" required>@error('postcode')<div class="errors">{{ $message }}</div>@enderror</div>
        <div class="field"><input name="phone" placeholder="Phone" value="{{ old('phone', $default->phone ?? $u->phone ?? '') }}" aria-label="Phone" inputmode="tel" required>@error('phone')<div class="errors">{{ $message }}</div>@enderror</div>
      </div>
      <label style="font-size:12px;display:block;margin:8px 0"><input type="checkbox" id="same_billing" name="same_billing" value="1" @checked(old('same_billing', '1'))> Use same address for billing</label>
      <div id="billing-fields" hidden>
        <div class="two"><div class="field"><input name="b_first_name" placeholder="Billing first name" value="{{ old('b_first_name') }}"></div><div class="field"><input name="b_last_name" placeholder="Billing last name" value="{{ old('b_last_name') }}"></div></div>
        <div class="field"><input name="b_address_1" placeholder="Billing address" value="{{ old('b_address_1') }}"></div>
        <div class="two"><div class="field"><input name="b_city" placeholder="City" value="{{ old('b_city') }}"></div><div class="field"><select name="b_state">@foreach($states as $s)<option @selected(old('b_state','Maharashtra') === $s)>{{ $s }}</option>@endforeach</select></div></div>
        <div class="field"><input name="b_postcode" placeholder="PIN Code" value="{{ old('b_postcode') }}"></div>
        @if($errors->hasAny(['b_first_name','b_last_name','b_address_1','b_city','b_postcode']))<div class="errors">Please complete the billing address.</div>@endif
      </div>

      <h3 style="font-weight:600;margin:26px 0 10px;font-size:15px">Shipping options</h3>
      <div class="choice on" style="justify-content:space-between"><label style="display:flex;gap:10px"><input type="radio" checked disabled> {{ $summary['shipping'] > 0 ? 'Flat rate' : 'Free shipping' }}</label><span>{{ $summary['shipping'] > 0 ? money($summary['shipping']) : 'FREE' }}</span></div>

      <h3 style="font-weight:600;margin:26px 0 10px;font-size:15px">Payment options</h3>
      <label class="choice {{ old('payment_method', 'cod') === 'cod' ? 'on' : '' }}"><input type="radio" name="payment_method" value="cod" @checked(old('payment_method', 'cod') === 'cod')><span>Cash on delivery<p>Pay with cash upon delivery.</p></span></label>
      @if($razorpayReady)<label class="choice {{ old('payment_method') === 'razorpay' ? 'on' : '' }}"><input type="radio" name="payment_method" value="razorpay" @checked(old('payment_method') === 'razorpay')><span>Pay by Razorpay<p>Pay securely by Credit or Debit card or Internet Banking through Razorpay.</p></span></label>
      @else<div class="choice" style="opacity:.55"><input type="radio" disabled><span>Pay by Razorpay<p>Not configured on this store yet.</p></span></div>@endif
      @error('payment_method')<div class="errors">{{ $message }}</div>@enderror

      <label style="font-size:12px;display:block;margin:16px 0 6px"><input type="checkbox" onclick="document.getElementById('note').hidden=!this.checked"> Add a note to your order</label>
      <div class="field" id="note" hidden><textarea name="customer_note" maxlength="500">{{ old('customer_note') }}</textarea></div>
      <hr style="border:0;border-top:1px solid var(--line);margin:22px 0">
      <label style="font-size:12px;display:block"><input type="checkbox" name="terms" value="1" @checked(old('terms'))> By proceeding with your purchase you agree to our <a href="{{ route('page', 'terms-and-conditions') }}" style="text-decoration:underline">Terms and Conditions</a> and <a href="{{ route('page', 'privacy-policy') }}" style="text-decoration:underline">Privacy Policy</a></label>
      @error('terms')<div class="errors">{{ $message }}</div>@enderror
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:22px;gap:14px"><a href="{{ route('cart') }}" style="font-size:12px">← Return to Cart</a><button class="btn btn--rect" style="flex:1;max-width:300px" type="submit">Place Order</button></div>
    </div>
    <aside><div class="panel">
      <div style="font-size:13px;margin-bottom:10px">Order summary</div>
      @foreach($summary['cart_lines'] as $l)@php($p = $l['product']->pricing($l['variant']))
        <div style="display:grid;grid-template-columns:44px 1fr auto;gap:10px;font-size:12px;margin-bottom:14px"><img src="{{ $l['product']->image_url }}" alt="" style="width:44px;height:44px;object-fit:cover"><div>{{ $l['qty'] }} × {{ $l['product']->name }}@if($l['variant']) ({{ $l['variant']->label }})@endif</div><b>{{ money($p['current'] * $l['qty']) }}</b></div>
      @endforeach
      <div class="totals" style="margin-top:10px">
        <div class="r"><span>Subtotal</span><span>{{ money($summary['subtotal']) }}</span></div>
        @if($summary['discount'] > 0)<div class="r"><span>Discount ({{ $summary['coupon']?->code }})</span><span>−{{ money($summary['discount']) }}</span></div>@endif
        <div class="r"><span>Delivery</span><span>{{ $summary['shipping'] > 0 ? money($summary['shipping']) : 'FREE' }}</span></div>
        @if($summary['tax'] > 0)<div class="r"><span>Tax</span><span>{{ money($summary['tax']) }}</span></div>@endif
        <div class="r t"><span>Total</span><span>{{ money($summary['total']) }}</span></div>
      </div>
      <p style="font-size:11px;color:var(--muted);margin:8px 0 0">Totals are recalculated securely on our server when you place the order.</p>
    </div></aside>
  </form>
</div>
@endsection
