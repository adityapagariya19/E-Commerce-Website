@extends('account.layout')
@section('account')
<p>Order <b>#{{ $order->order_number }}</b> was placed on <b>{{ $order->placed_at?->format('F j, Y') }}</b> and is currently <b>{{ $order->statusLabel() }}</b>.</p>
<table class="table"><thead><tr><th>Product</th><th style="text-align:right">Total</th></tr></thead><tbody>
@foreach($order->items as $i)<tr><td>{{ $i->name }}@if($i->variant_label) ({{ $i->variant_label }})@endif × {{ $i->qty }}@if($i->refunded_qty)<div class="problem">{{ $i->refunded_qty }} refunded</div>@endif</td><td style="text-align:right">{{ money($i->line_total) }}</td></tr>@endforeach
<tr><td>Subtotal</td><td style="text-align:right">{{ money($order->subtotal) }}</td></tr>@if($order->discount_total > 0)<tr><td>Discount</td><td style="text-align:right">−{{ money($order->discount_total) }}</td></tr>@endif
<tr><td>Shipping</td><td style="text-align:right">{{ $order->shipping_total > 0 ? money($order->shipping_total) : 'Free shipping' }}</td></tr>
@if($order->refunded_total > 0)<tr><td>Refunded</td><td style="text-align:right">−{{ money($order->refunded_total) }}</td></tr>@endif
<tr><td><b>Total</b> ({{ $order->paymentMethodLabel() }})</td><td style="text-align:right"><b>{{ money($order->total) }}</b></td></tr></tbody></table>
<div class="addr" style="margin-top:20px">@foreach(['billing' => 'Billing address', 'shipping' => 'Shipping address'] as $k => $label)@php($a = $order->{$k})<div><h3 style="font-style:normal;font-weight:500;margin:0 0 8px">{{ $label }}</h3>{{ $a['first_name'] ?? '' }} {{ $a['last_name'] ?? '' }}<br>{{ $a['line1'] ?? '' }}<br>{{ $a['city'] ?? '' }} {{ $a['postcode'] ?? '' }}<br>{{ $a['state'] ?? '' }}</div>@endforeach</div>
@php($steps = config('jbo.tracker_steps'))
@php($cur = $order->status === 'completed' ? 'delivered' : $order->status)
@php($idx = array_search($cur, $steps, true))
@if($idx !== false)
<ol class="steps" style="padding:0;gap:22px;flex-wrap:wrap;margin:26px 0">@foreach($steps as $i => $s)<li class="{{ $i <= $idx ? 'on' : '' }}">{{ order_status_label($s) }}</li>@endforeach</ol>
@elseif(in_array($order->status, ['cancelled','failed','refunded','on_hold','pending_payment']))<p class="problem" style="font-size:14px">Status: {{ $order->statusLabel() }}</p>@endif
@if($order->hasTracking())<div class="panel" style="margin:16px 0;font-size:13px"><b>Shipment tracking</b><br>@if($order->courier_name)Courier: {{ $order->courier_name }}<br>@endif @if($order->tracking_number)Tracking number: {{ $order->tracking_number }}<br>@endif @if($order->tracking_url)<a href="{{ $order->tracking_url }}" target="_blank" rel="noopener noreferrer" style="color:var(--blue);text-decoration:underline">Track this shipment ↗</a>@endif</div>@endif
<p style="margin-top:14px"><a class="btn btn--sm btn--ghost" href="{{ route('account.order.receipt', $order) }}" target="_blank">View receipt</a> <span class="price">Payment: {{ ucwords(str_replace('_', ' ', $order->payment_status)) }}</span></p>
@if($order->isPayable())<p style="margin-top:20px"><a class="btn" href="{{ route('checkout.pay', ['order' => $order->id]) }}">Pay now</a></p>@endif
@if($order->customerCanCancel())<form method="POST" action="{{ route('account.order.cancel', $order) }}" onsubmit="return confirm('Cancel this order?')" style="margin-top:10px">@csrf<button class="btn btn--grey btn--sm">Cancel order</button></form>@endif
<h3 style="font-weight:500;margin-top:30px">Order updates</h3><ul style="font-size:13px;color:#555">@foreach($order->histories as $h)<li>{{ $h->created_at->format('M j, Y H:i') }} — {{ order_status_label($h->to_status) }}</li>@endforeach</ul>
@endsection
