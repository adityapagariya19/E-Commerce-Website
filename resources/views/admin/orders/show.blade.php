@extends('layouts.admin')
@section('title','Order #'.$order->order_number)
@section('content')
<h1>Order #{{ $order->order_number }}</h1>
<div class="grid g2" style="grid-template-columns:2fr 1fr;align-items:start"><div>
  <div class="card"><h2>Items</h2><div class="tablewrap"><table><thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Refunded</th><th>Total</th></tr></thead><tbody>
    @foreach($order->items as $i)<tr><td>{{ $i->name }}@if($i->variant_label) ({{ $i->variant_label }})@endif<div class="hint">{{ $i->sku }}</div></td><td>{{ money($i->unit_price) }}</td><td>{{ $i->qty }}</td><td>{{ $i->refunded_qty }}</td><td>{{ money($i->line_total) }}</td></tr>@endforeach
    <tr><td colspan="4" align="right">Subtotal</td><td>{{ money($order->subtotal) }}</td></tr>@if($order->discount_total>0)<tr><td colspan="4" align="right">Discount ({{ $order->coupon_code }})</td><td>−{{ money($order->discount_total) }}</td></tr>@endif
    <tr><td colspan="4" align="right">Shipping</td><td>{{ money($order->shipping_total) }}</td></tr>@if($order->tax_total>0)<tr><td colspan="4" align="right">Tax</td><td>{{ money($order->tax_total) }}</td></tr>@endif
    <tr><td colspan="4" align="right"><b>Total</b></td><td><b>{{ money($order->total) }}</b></td></tr>@if($order->refunded_total>0)<tr><td colspan="4" align="right">Refunded</td><td>−{{ money($order->refunded_total) }}</td></tr>@endif</tbody></table></div></div>
  <div class="grid g2"><div class="card"><h2>Billing</h2>@php($b = $order->billing){{ $b['first_name'] ?? '' }} {{ $b['last_name'] ?? '' }}<br>{{ $b['line1'] ?? '' }} {{ $b['line2'] ?? '' }}<br>{{ $b['city'] ?? '' }} {{ $b['postcode'] ?? '' }}, {{ $b['state'] ?? '' }}<br>{{ $order->email }} · {{ $order->phone }}</div>
    <div class="card"><h2>Shipping</h2>@php($s = $order->shipping){{ $s['first_name'] ?? '' }} {{ $s['last_name'] ?? '' }}<br>{{ $s['line1'] ?? '' }} {{ $s['line2'] ?? '' }}<br>{{ $s['city'] ?? '' }} {{ $s['postcode'] ?? '' }}, {{ $s['state'] ?? '' }}@if($order->customer_note)<p class="hint">Customer note: {{ $order->customer_note }}</p>@endif</div></div>
  <div class="card"><h2>Payments</h2><div class="tablewrap"><table><thead><tr><th>Provider</th><th>Status</th><th>Amount</th><th>Provider IDs</th><th>Note</th></tr></thead><tbody>@foreach($order->payments as $p)<tr><td>{{ $p->provider }}</td><td>{{ $p->status }}</td><td>{{ money($p->amount) }}</td><td class="hint">{{ $p->provider_order_id }}<br>{{ $p->provider_payment_id }}</td><td>{{ $p->failure_reason }}</td></tr>@endforeach</tbody></table></div></div>
  @if(in_array($order->payment_status, ['paid','partially_refunded']))
  <div class="card"><h2>Refund</h2><form method="POST" action="{{ route('admin.orders.refund', $order) }}" onsubmit="return confirm('Record this refund? For Razorpay orders the refund is sent to Razorpay.')">@csrf
    <div class="tablewrap"><table><thead><tr><th>Item</th><th>Refundable qty</th><th>Refund qty</th></tr></thead><tbody>@foreach($order->items as $i)<tr><td>{{ $i->name }}</td><td>{{ $i->refundableQty() }}</td><td style="width:100px"><input type="number" min="0" max="{{ $i->refundableQty() }}" name="qty[{{ $i->id }}]" value="0"></td></tr>@endforeach</tbody></table></div>
    <div class="grid g2"><div class="f"><label>Amount (₹) — blank = value of selected items</label><input type="number" step="0.01" name="amount"></div><div class="f"><label>Reason</label><input type="text" name="reason"></div></div>
    <label style="font-weight:400"><input type="checkbox" name="restock" value="1"> Return refunded items to stock</label><br><br><button class="btn red">Refund</button>
    @if($order->payment_method==='cod')<p class="hint">COD: hand the money back manually; this records it.</p>@endif</form>
    @if($order->refunds->isNotEmpty())<table><tbody>@foreach($order->refunds as $r)<tr><td>{{ $r->created_at->format('M j H:i') }}</td><td>{{ money($r->amount) }}</td><td>{{ $r->status }}</td><td>{{ $r->reason }}</td><td class="hint">{{ $r->admin?->name }}</td></tr>@endforeach</tbody></table>@endif</div>@endif
</div><div>
  <div class="card"><h2>Status</h2><p><span class="pill">{{ $order->statusLabel() }}</span> <span class="pill {{ $order->payment_status==='paid'?'ok':'warn' }}">{{ $order->payment_status }}</span><br><span class="hint">{{ $order->paymentMethodLabel() }}</span></p>
    @if($next)<form method="POST" action="{{ route('admin.orders.status', $order) }}">@csrf<div class="f"><label>Change status to</label><select name="status">@foreach($next as $n)<option value="{{ $n }}">{{ order_status_label($n) }}</option>@endforeach</select></div><div class="f"><label>Note (optional)</label><input type="text" name="note"></div><button class="btn">Update status</button></form>
    @else<p class="hint">No manual status changes available from this state.</p>@endif
    @if($order->payment_status==='paid')<p class="hint">Paid orders can't be cancelled directly — refund them instead so payment and order state stay consistent.</p>@endif</div>
  <div class="card"><h2>Courier &amp; tracking</h2><form method="POST" action="{{ route('admin.orders.tracking', $order) }}">@csrf
    <div class="f"><label>Courier name</label><input type="text" name="courier_name" value="{{ old('courier_name', $order->courier_name) }}"></div>
    <div class="f"><label>Tracking number</label><input type="text" name="tracking_number" value="{{ old('tracking_number', $order->tracking_number) }}"></div>
    <div class="f"><label>Tracking URL (https://…)</label><input type="url" name="tracking_url" value="{{ old('tracking_url', $order->tracking_url) }}"></div>
    <button class="btn sm">Save tracking</button><p class="hint">Shown to the customer on their order page. Leave blank until you have real details — nothing is auto-generated.</p></form></div>
  <div class="card"><h2>Notes &amp; history</h2><form method="POST" action="{{ route('admin.orders.note', $order) }}" style="margin-bottom:12px">@csrf<textarea name="note" style="min-height:60px" required></textarea><button class="btn sm" style="margin-top:6px">Add note</button></form>
    @foreach($order->histories as $h)<div style="border-top:1px solid var(--line);padding:8px 0;font-size:12.5px"><b>{{ order_status_label($h->to_status) }}</b> · {{ $h->created_at->format('M j, H:i') }} @if($h->admin)· {{ $h->admin->name }}@endif<br>{{ $h->note }}</div>@endforeach</div>
  @if($order->user)<div class="card"><h2>Customer</h2><a href="{{ route('admin.customers.show', $order->user) }}">{{ $order->user->name }}</a></div>@endif
</div></div>@endsection
