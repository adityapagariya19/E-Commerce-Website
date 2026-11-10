@extends('layouts.admin')
@section('title','Categories')
@section('content')<h1>Categories <a class="btn sm" href="{{ route('admin.categories.create') }}">Add new</a></h1>
<div class="card tablewrap"><table><thead><tr><th></th><th>Name</th><th>Parent</th><th>Products</th><th>Order</th><th>Active</th><th></th></tr></thead><tbody>
@foreach($categories as $c)<tr><td>@if($c->image)<img class="thumb" src="{{ media_url($c->image) }}" alt="">@endif</td><td><a href="{{ route('admin.categories.edit', $c) }}">{{ $c->name }}</a><div class="hint">/{{ $c->slug }}</div></td><td>{{ $c->parent?->name }}</td><td>{{ $c->products_count }}</td><td>{{ $c->position }}</td><td>{!! $c->is_active ? '<span class="pill ok">yes</span>' : '<span class="pill">no</span>' !!}</td>
<td><form method="POST" action="{{ route('admin.categories.destroy', $c) }}" onsubmit="return confirm('Delete category? Products are kept.')">@csrf @method('DELETE')<button class="btn sm red">Delete</button></form></td></tr>@endforeach</tbody></table></div>@endsection
