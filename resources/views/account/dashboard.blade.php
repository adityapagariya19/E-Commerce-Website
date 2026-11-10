@extends('account.layout')
@section('account')
<p>Hello <b>{{ auth()->user()->name }}</b> (not {{ auth()->user()->name }}? <form method="POST" action="{{ route('logout') }}" style="display:inline">@csrf<button class="link">Log out</button></form>)</p>
<p>From your account dashboard you can view your <a href="{{ route('account.orders') }}" style="color:var(--blue)">recent orders</a>, manage your <a href="{{ route('account.addresses') }}" style="color:var(--blue)">addresses</a>, and edit your <a href="{{ route('account.details') }}" style="color:var(--blue)">password and account details</a>.</p>
@if($recent->isNotEmpty())<h3 style="font-weight:500">Recent orders</h3>@include('account.orders-table', ['orders' => $recent])@endif
@endsection
