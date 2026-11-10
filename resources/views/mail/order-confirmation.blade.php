<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;color:#222;max-width:600px">
<h2>Thank you for your order, {{ $order->billing['first_name'] ?? '' }}!</h2>
<p>Order <b>#{{ $order->order_number }}</b> placed {{ $order->placed_at?->format('F j, Y') }}. Payment: {{ $order->paymentMethodLabel() }}.</p>
<table width="100%" cellpadding="6" style="border-collapse:collapse">@foreach($order->items as $i)<tr style="border-bottom:1px solid #eee"><td>{{ $i->name }} × {{ $i->qty }}</td><td align="right">{{ money($i->line_total) }}</td></tr>@endforeach
<tr><td><b>Total</b></td><td align="right"><b>{{ money($order->total) }}</b></td></tr></table>
<p>View your order: <a href="{{ route('order.received', ['order' => $order->id, 'key' => $order->order_key]) }}">order details</a></p>
<p style="color:#888;font-size:12px">{{ setting('store_name', 'JB Optician') }}</p></body></html>
