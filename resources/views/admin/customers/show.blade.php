@extends('layouts.admin')
@section('title',$user->name)
@section('content')<h1>{{ $user->name }}</h1><div class="grid g2"><div class="card"><p>{{ $user->email }}<br>{{ $user->phone }}<br>Joined {{ $user->created_at->format('M j, Y') }} · Last login {{ $user->last_login_at?->diffForHumans() ?? 'never' }}</p>
<form method="POST" action="{{ route('admin.customers.toggle', $user) }}">@csrf<button class="btn {{ $user->is_active ? 'red' : '' }}">{{ $user->is_active ? 'Disable account' : 'Enable account' }}</button></form></div>
<div class="card"><h2>Addresses</h2>@forelse($user->addresses as $a)<p>{{ $a->type }}: {{ $a->line1 }}, {{ $a->city }} {{ $a->postcode }}</p>@empty<p class="hint">None saved.</p>@endforelse</div></div>
<div class="card"><h2>Orders</h2><table><tbody>@foreach($orders as $o)<tr><td><a href="{{ route('admin.orders.show', $o) }}">#{{ $o->order_number }}</a></td><td>{{ $o->placed_at?->format('M j, Y') }}</td><td>{{ $o->statusLabel() }}</td><td>{{ money($o->total) }}</td></tr>@endforeach</tbody></table></div>@endsection
