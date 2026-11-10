@extends('layouts.admin')
@section('title', $product->exists ? 'Edit product' : 'Add product')
@section('content')
@php($a = $product->specs ?? [])
<h1>{{ $product->exists ? 'Edit: '.$product->name : 'Add new product' }}</h1>
<form method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">@csrf @if($product->exists) @method('PUT') @endif
<div class="grid g2" style="grid-template-columns:2fr 1fr;align-items:start">
<div>
  <div class="card"><div class="f"><label>Product name *</label><input type="text" name="name" value="{{ old('name', $product->name) }}" required></div>
    <div class="grid g2"><div class="f"><label>Slug</label><input type="text" name="slug" value="{{ old('slug', $product->slug) }}"><div class="hint">Leave blank to generate.</div></div><div class="f"><label>SKU</label><input type="text" name="sku" value="{{ old('sku', $product->sku) }}"></div></div>
    <div class="f"><label>Short description</label><textarea name="short_description" style="min-height:60px">{{ old('short_description', $product->short_description) }}</textarea></div>
    <div class="f"><label>Description</label><textarea name="description" style="min-height:200px">{{ old('description', $product->description) }}</textarea><div class="hint">Plain text; line breaks are kept.</div></div></div>
  <div class="card"><h2>Pricing &amp; stock</h2>
    <div class="f"><label>Product type</label><select name="type" id="ptype"><option value="simple" @selected(old('type', $product->type)==='simple')>Simple</option><option value="variable" @selected(old('type', $product->type)==='variable')>Variable (variants)</option></select></div>
    <div class="grid g3"><div class="f"><label>Regular price (₹)</label><input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}"><div class="hint">Empty = shown as “Read more”, not purchasable. For variable products the lowest variant price is used.</div></div>
      <div class="f"><label>Sale price (₹)</label><input type="number" step="0.01" min="0" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}"></div>
      <div class="f"><label>Stock quantity</label><input type="number" min="0" name="stock_qty" value="{{ old('stock_qty', $product->stock_qty) }}"></div></div>
    <label style="font-weight:400"><input type="checkbox" name="manage_stock" value="1" @checked(old('manage_stock', $product->manage_stock))> Track stock quantity (unchecked = unlimited)</label></div>
  <div class="card"><h2>Variants</h2><p class="hint">Used when type is “Variable”. Each variant needs a label and price.</p>
    <div class="tablewrap"><table><thead><tr><th>Label</th><th>SKU</th><th>Price</th><th>Sale</th><th>Stock</th><th>Active</th></tr></thead><tbody>
    @php($vs = old('variants', $product->variants->map(fn($v) => $v->only(['id','label','sku','price','sale_price','stock_qty','is_active']))->all()))
    @for($i = 0; $i < max(count($vs) + 2, 3); $i++)@php($v = $vs[$i] ?? [])
      <tr><td><input type="hidden" name="variants[{{ $i }}][id]" value="{{ $v['id'] ?? '' }}"><input type="text" name="variants[{{ $i }}][label]" value="{{ $v['label'] ?? '' }}"></td><td><input type="text" name="variants[{{ $i }}][sku]" value="{{ $v['sku'] ?? '' }}"></td>
      <td><input type="number" step="0.01" name="variants[{{ $i }}][price]" value="{{ $v['price'] ?? '' }}"></td><td><input type="number" step="0.01" name="variants[{{ $i }}][sale_price]" value="{{ $v['sale_price'] ?? '' }}"></td>
      <td><input type="number" name="variants[{{ $i }}][stock_qty]" value="{{ $v['stock_qty'] ?? 0 }}"></td><td><input type="checkbox" name="variants[{{ $i }}][is_active]" value="1" @checked($v ? ($v['is_active'] ?? false) : true)></td></tr>@endfor
    </tbody></table></div><div class="hint">Remove a variant by clearing its label.</div></div>
  <div class="card"><h2>Images</h2>
    @if($product->exists)<div class="tablewrap"><table><tbody>@foreach($product->images as $img)<tr><td><img class="thumb lg" src="{{ $img->url }}" alt=""></td><td><div class="f"><label>Alt text</label><input type="text" name="image_alt[{{ $img->id }}]" value="{{ $img->alt }}"></div></td><td style="width:90px"><div class="f"><label>Position</label><input type="number" min="0" name="image_pos[{{ $img->id }}]" value="{{ $img->position }}"></div></td>
      <td><button class="btn sm red" form="del-img-{{ $img->id }}" onclick="return confirm('Remove image?')">Remove</button></td></tr>@endforeach</tbody></table></div>@endif
    <div class="f"><label>Upload images (JPG, PNG, WebP · max {{ config('jbo.upload.max_kb')/1024 }} MB each)</label><input type="file" name="new_images[]" accept="image/jpeg,image/png,image/webp" multiple><div class="hint">Position 0 is the main image. New images are appended; alt text defaults to the product name.</div></div></div>
</div>
<div>
  <div class="card"><h2>Publish</h2><div class="f"><label>Status</label><select name="status">@foreach(['draft','published','archived'] as $s)<option value="{{ $s }}" @selected(old('status', $product->status)===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
    <label style="font-weight:400"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))> Featured</label><br>
    <label style="font-weight:400"><input type="checkbox" name="is_new_arrival" value="1" @checked(old('is_new_arrival', $product->is_new_arrival))> Show in New Arrivals</label><br>
    <label style="font-weight:400"><input type="checkbox" name="tryon_enabled" value="1" @checked(old('tryon_enabled', $product->tryon_enabled))> Enable on Try On page</label>
    <div class="f" style="margin-top:8px"><label>AR Try-On project URL (MyWebAR)</label><input type="url" name="tryon_url" value="{{ old('tryon_url', $product->tryon_url) }}" placeholder="https://mywebar.com/p/…"><div class="hint">Paste the product's MyWebAR project/share link. Blank = simple webcam overlay using the product photo.</div></div><br>
    <button class="btn">{{ $product->exists ? 'Update' : 'Create' }}</button> @if($product->exists && $product->status==='published')<a class="btn ghost" href="{{ route('product', $product) }}" target="_blank" rel="noopener">View</a>@endif</div>
  <div class="card"><h2>Organise</h2><div class="f"><label>Brand</label><select name="brand_id"><option value="">—</option>@foreach($brands as $b)<option value="{{ $b->id }}" @selected(old('brand_id', $product->brand_id)==$b->id)>{{ $b->name }}</option>@endforeach</select></div>
    <label>Categories</label><div style="max-height:180px;overflow:auto;border:1px solid var(--line);border-radius:7px;padding:8px">@php($sel = old('category_ids', $product->categories->pluck('id')->all()))@foreach($categories as $c)<label style="font-weight:400"><input type="checkbox" name="category_ids[]" value="{{ $c->id }}" @checked(in_array($c->id, $sel))> {{ $c->name }}</label>@endforeach</div>
    <div class="f" style="margin-top:12px"><label>Tags (comma separated)</label><input type="text" name="tags" value="{{ old('tags', implode(', ', $product->tags ?? [])) }}"></div></div>
  <div class="card"><h2>Frame attributes</h2>@foreach(['gender'=>'Gender','frame_shape'=>'Frame shape','frame_color'=>'Frame colour','size'=>'Size','material'=>'Material'] as $k=>$l)<div class="f"><label>{{ $l }}</label><input type="text" name="attr[{{ $k }}]" value="{{ old("attr.$k", $a[$k] ?? '') }}"></div>@endforeach</div>
  <div class="card"><h2>SEO</h2><div class="f"><label>Meta title</label><input type="text" name="meta_title" value="{{ old('meta_title', $product->meta_title) }}"></div><div class="f"><label>Meta description</label><textarea name="meta_description" style="min-height:60px">{{ old('meta_description', $product->meta_description) }}</textarea></div></div>
</div></div></form>
@if($product->exists)@foreach($product->images as $img)<form id="del-img-{{ $img->id }}" method="POST" action="{{ route('admin.products.images.destroy', [$product, $img]) }}">@csrf @method('DELETE')</form>@endforeach @endif
@endsection
