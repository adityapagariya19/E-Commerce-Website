@extends('layouts.store')
@section('title', ($product->meta_title ?: $product->name).' – '.setting('store_name', 'JB Optician'))
@section('description', $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->short_description), 150))
@section('content')
@php($pr = $product->pricing())
@php($avg = $product->rating_average)
<div class="wrap">
  <div class="crumbs"><div><a href="{{ route('home') }}">Home</a> / @if($c = $product->categories->first())<a href="{{ route('category', $c) }}">{{ $c->name }}</a> / @endif {{ $product->name }}</div>
    <div>@if($prev)<a href="{{ route('product', $prev) }}">‹ PREVIOUS</a>@endif @if($prev && $next) | @endif @if($next)<a href="{{ route('product', $next) }}">NEXT ›</a>@endif</div></div>
  <div class="pdp">
    <div class="gallery" data-gallery>
      <div class="gallery__thumbs">@foreach($product->images as $img)<button type="button" data-src="{{ $img->url }}" aria-current="{{ $loop->first ? 'true' : 'false' }}" aria-label="Show image {{ $loop->iteration }}"><img src="{{ $img->url }}" alt=""></button>@endforeach</div>
      <div class="gallery__main">@if($product->discount_percent)<span class="card__badge">-{{ $product->discount_percent }}%</span>@endif{!! picture($product->images->first()?->path, $product->images->first()?->alt ?? $product->name, null, ['fetchpriority' => 'high']) !!}</div>
    </div>
    <div>
      <h1>{{ $product->name }}</h1>
      @if($avg)<div class="stars" aria-label="Rated {{ $avg }} out of 5">{{ str_repeat('★', (int) round($avg)) }}<i>{{ str_repeat('★', 5 - (int) round($avg)) }}</i> <span class="price">({{ $product->approvedReviews->count() }} review(s))</span></div>@endif
      <div id="variant-price">@include('store.partials.price', ['product' => $product])</div>
      @if($product->short_description)<p style="color:var(--muted);font-size:13px">{{ $product->short_description }}</p>@endif

      @if($product->isPurchasable())
      <form method="POST" action="{{ route('cart.add') }}" data-ajax-cart>
        @csrf<input type="hidden" name="product_id" value="{{ $product->id }}">
        @if($product->isVariable())
          <div class="field" style="max-width:320px"><label for="variant-select">Choose an option</label>
            <select id="variant-select" name="variant_id" required><option value="">Select…</option>
              @foreach($product->variants as $v)@php($vp = $product->pricing($v))
                <option value="{{ $v->id }}" @disabled($product->manage_stock && $v->stock_qty < 1)
                  data-html="{{ $vp['sale'] ? '<span class="price"><del>'.money($vp['regular']).'</del> <ins>'.money($vp['sale']).'</ins></span>' : '<span class="price">'.money($vp['regular']).'</span>' }}">{{ $v->label }}{{ $product->manage_stock && $v->stock_qty < 1 ? ' — out of stock' : '' }}</option>@endforeach
            </select></div>
        @endif
        @if($product->inStock())
        <div class="buyrow">
          <div class="qty" data-qty><button type="button" data-step="-" aria-label="Decrease">−</button><input type="number" name="qty" value="1" min="1" max="99" aria-label="Quantity"><button type="button" data-step="+" aria-label="Increase">+</button></div>
          <button class="btn" type="submit">Add to cart</button>
          <button class="btn btn--ghost" type="submit" name="buy_now" value="1">Buy now</button>
        </div>
        @else<p class="oos" style="font-size:14px">Out of stock</p>@endif
      </form>
      @else
        <p style="color:var(--muted)">This product is not available for online purchase. <a href="{{ route('contact') }}" style="color:var(--blue)">Contact us</a> for details.</p>
      @endif
      @if($product->inStock() && $product->manage_stock && ! $product->isVariable() && $product->stock_qty <= config('jbo.low_stock_threshold'))<p class="oos">Only {{ $product->stock_qty }} left in stock</p>@endif
      @if($product->sku)<p style="font-size:12px;color:var(--muted);margin:6px 0">SKU: {{ $product->sku }}</p>@endif
      <p style="font-size:12px;color:var(--muted)">Categories: @foreach($product->categories as $c)<a href="{{ route('category', $c) }}">{{ $c->name }}</a>{{ $loop->last ? '' : ', ' }}@endforeach</p>
      @php($attrs = $product->specs ?? [])
      @if($attrs)<p style="font-size:12px;color:var(--muted)">@foreach($attrs as $k => $v){{ ucwords(str_replace('_',' ',$k)) }}: {{ $v }}{{ $loop->last ? '' : ' · ' }}@endforeach</p>@endif
      <fieldset class="safe"><legend>GUARANTEED SAFE CHECKOUT</legend>
        @if(app(\App\Services\RazorpayService::class)->configured())<span>Razorpay</span><span>UPI</span><span>Cards</span><span>Netbanking</span>@endif<span>Cash on delivery</span>
      </fieldset>
    </div>
  </div>

  <div class="tabs" role="tablist"><button role="tab" aria-selected="true" data-tab="desc">Description</button><button role="tab" aria-selected="false" data-tab="rev">Reviews ({{ $product->approvedReviews->count() }})</button></div>
  <div class="tabpanel prose" data-panel="desc">{!! nl2br(e($product->description ?: 'No description yet.')) !!}</div>
  <div class="tabpanel" data-panel="rev" hidden>
    @forelse($product->approvedReviews()->latest()->get() as $r)<div style="border-bottom:1px solid var(--line);padding:12px 0"><span class="stars">{{ str_repeat('★', $r->rating) }}<i>{{ str_repeat('★', 5 - $r->rating) }}</i></span> <b>{{ $r->name }}</b><p style="margin:4px 0">{{ $r->body }}</p></div>
    @empty<p>There are no reviews yet.</p>@endforelse
    <h3 style="font-weight:500">Be the first to review “{{ $product->name }}”</h3>
    <p style="font-size:12px">Your email address will not be published. Required fields are marked *</p>
    <form method="POST" action="{{ route('product.review', $product) }}" novalidate>@csrf<input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <div class="field"><label>Your rating *</label><div class="rate">@for($i=5;$i>=1;$i--)<input type="radio" id="rt{{ $i }}" name="rating" value="{{ $i }}" @checked(old('rating')==$i)><label for="rt{{ $i }}" title="{{ $i }} stars">★</label>@endfor</div>@error('rating')<div class="errors">{{ $message }}</div>@enderror</div>
      <div class="field"><label for="rv-body">Your review *</label><textarea id="rv-body" name="body" required>{{ old('body') }}</textarea>@error('body')<div class="errors">{{ $message }}</div>@enderror</div>
      <div class="two"><div class="field"><label for="rv-name">Name *</label><input id="rv-name" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required></div><div class="field"><label for="rv-email">Email *</label><input id="rv-email" type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" required></div></div>
      <button class="btn" type="submit">Submit</button>
    </form>
  </div>

  @if($related->isNotEmpty())<section class="section"><h2 class="section__title" style="font-size:14px;font-weight:400">Related products</h2><div class="grid grid--4">@foreach($related as $product)@include('store.partials.product-card', ['product' => $product])@endforeach</div></section>@endif
</div>
@endsection
