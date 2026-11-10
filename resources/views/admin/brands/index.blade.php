@extends('layouts.admin')
@section('title','Brands')
@section('content')<h1>Brands <a class="btn sm" href="{{ route('admin.brands.create') }}">Add new</a></h1>
<div class="card tablewrap"><table><thead><tr><th>Name</th><th>Products</th><th>Active</th><th></th></tr></thead><tbody>@foreach($brands as $b)<tr><td><a href="{{ route('admin.brands.edit', $b) }}">{{ $b->name }}</a></td><td>{{ $b->products_count }}</td><td>{{ $b->is_active ? 'yes' : 'no' }}</td><td><form method="POST" action="{{ route('admin.brands.destroy', $b) }}" onsubmit="return confirm('Delete brand?')">@csrf @method('DELETE')<button class="btn sm red">Delete</button></form></td></tr>@endforeach</tbody></table></div>@endsection
