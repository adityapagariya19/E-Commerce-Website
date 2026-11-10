@extends('layouts.admin')
@section('title', 'Products')
@section('content')
<h1>Products <a class="btn sm" href="{{ route('admin.products.create') }}">Add new</a></h1>
<form class="toolbar" method="GET"><div class="f"><label>Search</label><input type="search" name="q" value="{{ request('q') }}" placeholder="Name or SKU"></div>
  <div class="f"><label>Status</label><select name="status"><option value="">All</option>@foreach(['published','draft','archived'] as $s)<option @selected(request('status')===$s)>{{ $s }}</option>@endforeach</select></div>
  <div class="f"><label>Category</label><select name="category"><option value="">All</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category')==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
  <div class="f"><label>Sort</label><select name="sort">@foreach(['created_at'=>'Date added','name'=>'Name','price'=>'Price','stock_qty'=>'Stock'] as $k=>$l)<option value="{{ $k }}" @selected(request('sort')===$k)>{{ $l }}</option>@endforeach</select></div>
  <div class="f"><label>Order</label><select name="dir"><option value="desc" @selected(request('dir')!=='asc')>Desc</option><option value="asc" @selected(request('dir')==='asc')>Asc</option></select></div><button class="btn">Filter</button></form>
<div class="card tablewrap"><table><thead><tr><th></th><th>Name</th><th>SKU</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($products as $p)<tr><td><img class="thumb" src="{{ $p->image_url }}" alt=""></td><td><a href="{{ route('admin.products.edit', $p) }}">{{ $p->name }}</a>@if($p->is_featured) <span class="pill">featured</span>@endif @if($p->is_new_arrival) <span class="pill ok">new</span>@endif<div class="hint">{{ $p->categories->pluck('name')->implode(', ') }}</div></td>
<td>{{ $p->sku }}</td><td>@if($p->price !== null){{ $p->sale_price ? money($p->sale_price).' (was '.money($p->price).')' : money($p->price) }}@else<span class="pill warn">no price</span>@endif</td>
<td>{{ $p->type === 'variable' ? $p->variants->sum('stock_qty').' (variants)' : ($p->manage_stock ? $p->stock_qty : '∞') }}</td><td><span class="pill {{ $p->status==='published'?'ok':'' }}">{{ $p->status }}</span></td>
<td><form method="POST" action="{{ route('admin.products.destroy', $p) }}" onsubmit="return confirm('Delete this product? Products with orders are archived instead.')">@csrf @method('DELETE')<button class="btn sm red">Delete</button></form></td></tr>@empty<tr><td colspan="7">No products found.</td></tr>@endforelse</tbody></table>{{ $products->links() }}</div>
@endsection
