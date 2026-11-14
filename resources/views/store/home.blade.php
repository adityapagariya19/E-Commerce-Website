@extends('layouts.store')
@section('content')
@if($slides->isNotEmpty())
<section class="hero" data-hero aria-roledescription="carousel" aria-label="Featured">
  <div class="hero__track">
    @foreach($slides as $s)
    <div class="hero__slide {{ $loop->first ? 'is-active' : '' }}" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ $loop->count }}">
      <picture>
        @if($s->mobile_image)@if($w = media_webp($s->mobile_image))<source media="(max-width:700px)" type="image/webp" srcset="{{ $w }}">@endif<source media="(max-width:700px)" srcset="{{ media_url($s->mobile_image) }}">@endif
        @if($w = media_webp($s->image))<source type="image/webp" srcset="{{ $w }}">@endif
        <img class="bg" src="{{ media_url($s->image) }}" alt="{{ $s->alt }}" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif>
      </picture>
      <div class="hero__copy">
        @if($s->eyebrow)<p class="hero__eyebrow">{{ $s->eyebrow }}</p>@endif
        @if($s->kicker)<p class="hero__kicker">{{ $s->kicker }}</p>@endif
        <h1 class="hero__title">{!! nl2br(e(str_replace(' a online', "\na online", $s->heading))) !!}</h1>
        @if($s->cta_label)<a class="btn" href="{{ $s->cta_url ?: route('shop') }}">{{ $s->cta_label }}</a>@endif
      </div>
    </div>
    @endforeach
  </div>
</section>
@endif

<section class="section">
  <div class="wrap">
    <p class="section__eyebrow">PASSION FOR FASHION</p>
    <h2 class="section__title">Shop By Categories</h2>
    @if($collage->isNotEmpty())
    @php($t = fn ($i) => $collage->get($i))
    <div class="collage--ref">
      @if($t(0))<a class="big" href="{{ $t(0)->url ?: route('shop') }}"><img src="{{ media_url($t(0)->image) }}" alt="{{ $t(0)->alt ?? $t(0)->title }}"></a>@endif
      <div class="collage__stack">
        @if($t(1))<a href="{{ $t(1)->url ?: route('shop') }}"><img src="{{ media_url($t(1)->image) }}" alt="{{ $t(1)->alt ?? $t(1)->title }}"></a>@endif
        <div class="collage__pair">
          @foreach([2,3] as $k)@if($t($k))<a href="{{ $t($k)->url ?: route('shop') }}"><img src="{{ media_url($t($k)->image) }}" alt="{{ $t($k)->alt ?? $t($k)->title }}"></a>@endif @endforeach
        </div>
      </div>
    </div>
    @endif
  </div>
</section>

<section class="section">
  <div class="wrap">
    <p class="section__eyebrow">LET'S SHOP</p>
    <h2 class="section__title">New Arrivals</h2>
    <div class="row-carousel" data-row-carousel>
      <button class="row-carousel__btn row-carousel__btn--prev" aria-label="Previous products">‹</button>
      <div class="row-carousel__track">@foreach($newArrivals as $product)@include('store.partials.product-card', ['product' => $product])@endforeach</div>
      <button class="row-carousel__btn row-carousel__btn--next" aria-label="Next products">›</button>
    </div>
  </div>
</section>

@if($promo)
<section class="wrap" style="padding-top:20px;padding-bottom:0">
  <div class="promo">
    <img src="{{ media_url($promo->image) }}" alt="{{ $promo->alt }}" loading="lazy">
    <div><h2>{{ $promo->title }}</h2>@if($promo->subtitle)<p>{{ $promo->subtitle }}</p>@endif @if($promo->cta_label)<a class="btn" href="{{ $promo->url ?: route('shop') }}">{{ $promo->cta_label }}</a>@endif</div>
  </div>
  @if($trust->isNotEmpty())
  <div class="trust">
    @foreach($trust as $b)<div class="trust__item"><span class="trust__ico">@include('store.partials.icon', ['name' => $b->icon])</span><div><h4>{{ $b->title }}</h4><p>{{ $b->subtitle }}</p></div></div>@endforeach
  </div>
  @endif
</section>
@endif

<section class="section">
  <div class="wrap">
    <p class="section__eyebrow">TRENDING NOW</p>
    <h2 class="section__title">Popular This Week</h2>
    <div class="grid grid--4">@foreach($popular['items'] as $row)@include('store.partials.product-card', ['product' => $row['product']])@endforeach</div>
  </div>
</section>

@if($social->isNotEmpty())
<section class="section">
  <div class="wrap">
    <p class="section__eyebrow">STAY UP-TO-DATE</p>
    <h2 class="section__title">Follow Us On Instagram</h2>
    <div class="social">@foreach($social as $b)
      @if($b->url)<a href="{{ $b->url }}" target="_blank" rel="noopener"><img src="{{ media_url($b->image) }}" alt="{{ $b->alt }}" loading="lazy"></a>@else<span><img src="{{ media_url($b->image) }}" alt="{{ $b->alt }}" loading="lazy"></span>@endif
    @endforeach</div>
    {{-- Static tiles managed in Admin › Homepage blocks. This is NOT a live Instagram feed. --}}
  </div>
</section>
@endif
@endsection
