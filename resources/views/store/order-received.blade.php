@extends('layouts.store')
@section('title', 'Order received – '.setting('store_name', 'JB Optician'))
@section('content')
<div class="wrap" style="max-width:860px;padding-bottom:40px">
  @php($paid = in_array($order->payment_status, ['paid','partially_refunded','refunded']))
  @if($order->payment_method === 'cod')<div class="success-box">Thank you. Your order has been received and will be paid in cash on delivery.</div>
  @elseif($paid)<div class="success-box">Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be processing your order soon.</div>
  @else<div class="success-box" style="color:#8a5a00;border-color:#d9a400">Your order is awaiting payment confirmation.</div>
    @if($order->isPayable())<p style="text-align:center"><a class="btn" href="{{ route('checkout.pay', ['order' => $order->id, 'key' => $order->order_key]) }}">Pay Now</a></p>@endif @endif
  <div class="meta"><div>Order number:<b>{{ $order->order_number }}</b></div><div>Date:<b>{{ $order->placed_at?->format('F j, Y') }}</b></div><div>Total:<b>{{ money($order->total) }}</b></div><div>Payment method:<b>{{ $order->paymentMethodLabel() }}</b></div></div>
  <h2 style="text-align:center;font-size:15px;letter-spacing:.06em">ORDER DETAILS</h2>
  <table class="table"><thead><tr><th>PRODUCT</th><th style="text-align:right">TOTAL</th></tr></thead><tbody>
    @foreach($order->items as $i)<tr><td>{{ $i->name }}@if($i->variant_label) ({{ $i->variant_label }})@endif × {{ $i->qty }}</td><td style="text-align:right">{{ money($i->line_total) }}</td></tr>@endforeach
    <tr><td><b>Subtotal:</b></td><td style="text-align:right">{{ money($order->subtotal) }}</td></tr>
    @if($order->discount_total > 0)<tr><td><b>Discount:</b></td><td style="text-align:right">−{{ money($order->discount_total) }}</td></tr>@endif
    <tr><td><b>Shipping:</b></td><td style="text-align:right">{{ $order->shipping_total > 0 ? money($order->shipping_total) : 'Free shipping' }}</td></tr>
    @if($order->tax_total > 0)<tr><td><b>Tax:</b></td><td style="text-align:right">{{ money($order->tax_total) }}</td></tr>@endif
    <tr><td><b>Payment method:</b></td><td style="text-align:right">{{ $order->paymentMethodLabel() }}</td></tr>
    <tr><td><b>TOTAL:</b></td><td style="text-align:right"><b>{{ money($order->total) }}</b></td></tr></tbody></table>
  <div class="addr" style="margin-top:26px">
    @foreach(['billing' => 'Billing address', 'shipping' => 'Shipping address'] as $k => $label)@php($a = $order->{$k})<div><h3 style="font-style:normal;font-weight:500;margin:0 0 8px">{{ $label }}</h3>{{ $a['first_name'] ?? '' }} {{ $a['last_name'] ?? '' }}<br>{{ $a['line1'] ?? '' }}@if(!empty($a['line2']))<br>{{ $a['line2'] }}@endif<br>{{ $a['city'] ?? '' }} {{ $a['postcode'] ?? '' }}<br>{{ $a['state'] ?? '' }}</div>@endforeach
  </div>
</div>
@endsection
