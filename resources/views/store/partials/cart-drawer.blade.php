<div class="drawer__body">
@forelse($summary['cart_lines'] as $l)
  <div class="cart__item" style="grid-template-columns:60px 1fr auto">
    <img src="{{ $l['product']->image_url }}" alt="" style="width:60px;height:60px">
    <div><a href="{{ route('product', $l['product']) }}">{{ $l['product']->name }}</a>@if($l['variant'])<div class="price">{{ $l['variant']->label }}</div>@endif<div class="price">{{ $l['qty'] }} × {{ money($l['product']->pricing($l['variant'])['current']) }}</div>@if($l['problem'])<div class="problem">{{ $l['problem'] }}</div>@endif</div>
    <button class="link" data-drawer-remove="{{ route('cart.remove', $l['item']->id) }}" aria-label="Remove {{ $l['product']->name }}">Remove</button>
  </div>
@empty
  <p style="text-align:center;color:var(--muted);margin-top:40px">No products in the cart.</p>
@endforelse
</div>
<div class="drawer__foot">
  <div style="display:flex;justify-content:space-between;margin-bottom:12px"><span>Subtotal:</span><b>{{ money($summary['subtotal']) }}</b></div>
  <a class="btn btn--block btn--ghost" href="{{ route('cart') }}" style="margin-bottom:8px">View cart</a>
  @if($summary['cart_lines']->isNotEmpty() && ! $summary['has_problems'])<a class="btn btn--block" href="{{ route('checkout') }}">Checkout</a>@endif
</div>
