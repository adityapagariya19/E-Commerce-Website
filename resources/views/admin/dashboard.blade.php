@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<h1>Dashboard</h1>
<form class="toolbar" method="GET"><input type="hidden" name="apply" value="1">
  <div class="f"><label>From</label><input type="date" name="from" value="{{ $from->toDateString() }}"></div><div class="f"><label>To</label><input type="date" name="to" value="{{ $to->toDateString() }}"></div>
  <button class="btn">Apply</button></form>
<div class="grid g4">
  <div class="card stat"><b>{{ money($summary['revenue']) }}</b><span>Revenue (net of refunds)</span></div><div class="card stat"><b>{{ $summary['orders'] }}</b><span>Orders counted as sales</span></div>
  <div class="card stat"><b>{{ $summary['units'] }}</b><span>Units sold</span></div><div class="card stat"><b>{{ money($summary['aov']) }}</b><span>Average order value</span></div></div>
<div class="grid g4"><div class="card stat"><b>{{ money($summary['refunded']) }}</b><span>Refunded</span></div><div class="card stat"><b>{{ $summary['pending_orders'] }}</b><span>Awaiting payment</span></div><div class="card stat"><b>{{ $counts['products'] }}</b><span>Published products</span></div><div class="card stat"><b>{{ $counts['drafts'] }}</b><span>Draft products</span></div></div>
<p class="hint" style="margin:-6px 0 14px">Sale = order status Processing, Shipped or Completed. Revenue = order total − refunds. Orders placed {{ $from->format('M j') }} – {{ $to->format('M j, Y') }}.</p>
<div class="card"><h2>Revenue per day</h2>@php($max = max(1, $series->max('revenue')))<div class="bars" role="img" aria-label="Daily revenue">@foreach($series as $d)<div style="height:{{ max(1, round($d['revenue'] / $max * 100)) }}%" title="{{ $d['date'] }}: {{ money($d['revenue']) }} ({{ $d['orders'] }} orders)"></div>@endforeach</div></div>
<div class="grid g2">
  <div class="card"><h2>Orders by status</h2><table><tbody>@forelse($byStatus as $s => $v)<tr><td>{{ order_status_label($s) }}</td><td>{{ $v['count'] }}</td><td>{{ money($v['total']) }}</td></tr>@empty<tr><td>No orders in range.</td></tr>@endforelse</tbody></table></div>
  <div class="card"><h2>Best sellers ({{ $from->format('M j') }} – {{ $to->format('M j') }})</h2><table><tbody>@forelse($best as $b)<tr><td>{{ $b['name'] }}</td><td>{{ $b['units'] }} units</td><td>{{ money($b['revenue']) }}</td></tr>@empty<tr><td>No sales in range.</td></tr>@endforelse</tbody></table></div>
</div>
<div class="grid g2">
  <div class="card"><h2>Popular This Week <a class="btn sm ghost" href="{{ route('admin.reports.popular') }}">Inspect ranking</a></h2><table><tbody>@foreach($popular['items'] as $r)<tr><td>{{ $r['product']->name }}</td><td>@if($r['is_fallback'])<span class="pill warn">fallback</span>@else{{ $r['units'] }} units @endif</td></tr>@endforeach</tbody></table>
    <p class="hint">Source: {{ $popular['source'] }}</p></div>
  <div class="card"><h2>Low stock</h2><table><tbody>@forelse($lowStock as $p)<tr><td><a href="{{ route('admin.products.edit', $p) }}">{{ $p->name }}</a></td><td>{{ $p->stock_qty }} left</td></tr>@empty<tr><td>Nothing low.</td></tr>@endforelse</tbody></table></div>
</div>
<div class="card"><h2>Recent orders</h2><div class="tablewrap"><table><thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Payment</th><th>Total</th></tr></thead><tbody>
@foreach($recent as $o)<tr><td><a href="{{ route('admin.orders.show', $o) }}">#{{ $o->order_number }}</a></td><td>{{ $o->placed_at?->format('M j, H:i') }}</td><td>{{ $o->statusLabel() }}</td><td>{{ $o->payment_status }}</td><td>{{ money($o->total) }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
