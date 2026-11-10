@extends('account.layout')
@section('account')
@if($orders->isEmpty())<p>No order has been made yet. <a class="btn btn--sm" href="{{ route('shop') }}">Browse products</a></p>@else @include('account.orders-table')<div style="margin-top:16px">{{ $orders->links() }}</div>@endif
@endsection
