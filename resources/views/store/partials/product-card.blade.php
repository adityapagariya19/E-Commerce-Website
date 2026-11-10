@php($canBuy = $product->isPurchasable() && ! $product->isVariable() && $product->inStock())
<article class="card">
  <a href="{{ route('product', $product) }}" class="card__img" tabindex="-1" aria-hidden="true">
    @if($product->discount_percent)<span class="card__badge">-{{ $product->discount_percent }}%</span>@endif
    {!! picture($product->images->first()?->path, $product->images->first()?->alt ?? $product->name, 'thumb', ['loading' => 'lazy', 'width' => 400, 'height' => 400]) !!}
  </a>
  <a class="card__title" href="{{ route('product', $product) }}">{{ $product->name }}</a>
  @include('store.partials.price', ['product' => $product])
  <div>
  @if($canBuy)
    <form method="POST" action="{{ route('cart.add') }}" data-ajax-cart style="display:inline">@csrf<input type="hidden" name="product_id" value="{{ $product->id }}"><button class="card__cta" type="submit"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 4h3l2.5 11h10L21 7H7"/><circle cx="9" cy="19" r="1.4"/><circle cx="18" cy="19" r="1.4"/></svg> Add to cart</button></form>
  @else
    <a class="card__cta" href="{{ route('product', $product) }}">🛒 Read more</a>
    @if($product->isPurchasable() && ! $product->inStock())<div class="oos">Out of stock</div>@endif
  @endif
  </div>
</article>
