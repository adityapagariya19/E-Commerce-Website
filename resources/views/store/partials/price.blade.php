@php($pr = $product->listingPricing())
@if($pr['current'] !== null)
  <span class="price">
    @if($product->isVariable())From @endif
    @if($pr['sale'])<del>{{ money($pr['regular']) }}</del> <ins>{{ money($pr['sale']) }}</ins>@else{{ money($pr['regular']) }}@endif
  </span>
@endif
