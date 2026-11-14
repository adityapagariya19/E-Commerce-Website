@extends('layouts.admin')
@section('title','Orders')
@section('content')<h1>Orders</h1>
<form class="toolbar" method="GET"><div class="f"><label>Search</label><input type="search" name="q" value="{{ request('q') }}" placeholder="#, email, name"></div>
<div class="f"><label>Status</label><select name="status"><option value="">All</option>@foreach(config('jbo.order_statuses') as $k=>$l)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $l }}</option>@endforeach</select></div>
<div class="f"><label>Payment</label><select name="payment_status"><option value="">All</option>@foreach(['unpaid','cod_pending','paid','failed','partially_refunded','refunded'] as $s)<option @selected(request('payment_status')===$s)>{{ $s }}</option>@endforeach</select></div>
<div class="f"><label>From</label><input type="date" name="from" value="{{ request('from') }}"></div><div class="f"><label>To</label><input type="date" name="to" value="{{ request('to') }}"></div>
<button class="btn">Filter</button></form>
<div class="card tablewrap"><table><thead><tr><th>Order #</th><th>Date</th><th>Customer</th><th>Status</th><th>Payment</th><th>Total</th><th style="width:100px;text-align:right">Actions</th></tr></thead><tbody>
@forelse($orders as $o)<tr><td><a href="{{ route('admin.orders.show', $o) }}" style="font-weight:600">#{{ $o->order_number }}</a></td><td>{{ $o->placed_at?->format('M j, Y H:i') }}</td><td>{{ ($o->billing['first_name'] ?? '').' '.($o->billing['last_name'] ?? '') }}<div class="hint">{{ $o->email }}</div></td><td><span class="pill">{{ $o->statusLabel() }}</span></td><td>{{ $o->payment_method }} · {{ $o->payment_status }}</td><td><strong>{{ money($o->total) }}</strong></td><td style="text-align:right"><a class="btn sm" href="{{ route('admin.orders.show', $o) }}">View</a></td></tr>@empty<tr><td colspan="7">No orders.</td></tr>@endforelse</tbody></table>{{ $orders->links() }}</div>@endsection
