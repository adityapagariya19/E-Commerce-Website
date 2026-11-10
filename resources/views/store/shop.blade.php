@extends('layouts.store')
@section('title', ($category ? $category->name : 'Shop').' – '.setting('store_name', 'JB Optician'))
@section('content')
<div class="wrap">
  @if($category)<h1 class="page-title">{{ $category->name }}</h1>@endif
  <div class="shop">
    <aside class="side" aria-label="Filters">
      <form class="side-search" action="{{ $category ? route('category', $category) : route('shop') }}" method="GET" role="search">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search products…" aria-label="Search products"><button class="icon-btn" aria-label="Search"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="1.6"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg></button>
      </form>
      <h4>Categories</h4>
      <ul>@foreach($categories as $c)
        <li class="{{ $category && $category->id === $c->id ? 'is-active' : '' }}"><a href="{{ route('category', $c) }}">{{ $c->name }}</a><span>({{ $counts[$c->id] ?? 0 }})</span></li>
        @foreach($c->children as $ch)<li style="padding-left:12px" class="{{ $category && $category->id === $ch->id ? 'is-active' : '' }}"><a href="{{ route('category', $ch) }}">— {{ $ch->name }}</a><span>({{ $counts[$ch->id] ?? 0 }})</span></li>@endforeach
      @endforeach</ul>
      <h4>Filter By</h4>
      <form action="{{ $category ? route('category', $category) : route('shop') }}" method="GET">
        @foreach(['q','orderby','tag'] as $keep)@if(request($keep))<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endif @endforeach
        <div class="range"><input type="range" id="rng-min" min="{{ $priceMin }}" max="{{ $priceMax }}" value="{{ (int) request('min_price', $priceMin) }}" aria-label="Minimum price"><input type="range" id="rng-max" min="{{ $priceMin }}" max="{{ $priceMax }}" value="{{ (int) request('max_price', $priceMax) }}" aria-label="Maximum price"></div>
        <input type="hidden" name="min_price" id="f-min"><input type="hidden" name="max_price" id="f-max">
        <div style="display:flex;align-items:center;gap:12px;font-size:12px;margin-bottom:22px"><button class="btn btn--sm" type="submit">Filter</button><span>Price: <span id="rng-label">₹{{ $priceMin }} — ₹{{ $priceMax }}</span></span></div>
        @foreach($attrOptions as $key => $opts)
          <div class="field"><label for="a-{{ $key }}" style="font-weight:500">{{ ucwords(str_replace('_',' ',$key)) }}</label>
            <select id="a-{{ $key }}" name="{{ $key }}" data-autosubmit><option value="">Any</option>@foreach($opts as $o)<option value="{{ $o }}" @selected(request($key) === $o)>{{ $o }}</option>@endforeach</select></div>
        @endforeach
      </form>
      @if($tags->isNotEmpty())<h4>Product Tags</h4><div class="tagcloud">@foreach($tags as $t)<a href="{{ request()->fullUrlWithQuery(['tag' => $t, 'page' => null]) }}">{{ $t }}</a>@endforeach</div>@endif
    </aside>
    <section>
      <div class="shop__bar">
        <span id="showing">{{ $showing }}</span>
        <form method="GET" action="{{ $category ? route('category', $category) : route('shop') }}">
          @foreach(request()->except(['orderby','page']) as $k => $v)@if(is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
          <label class="sr-only" for="orderby">Sort</label>
          <select class="select" id="orderby" name="orderby" data-autosubmit>
            @foreach(['default' => 'Default sorting', 'popularity' => 'Sort by popularity', 'rating' => 'Sort by average rating', 'date' => 'Sort by latest', 'price' => 'Sort by price: low to high', 'price-desc' => 'Sort by price: high to low'] as $v => $l)<option value="{{ $v }}" @selected($orderby === $v)>{{ $l }}</option>@endforeach
          </select>
        </form>
      </div>
      <div class="grid grid--3" id="product-grid">@include('store.partials.product-grid', ['products' => $products])</div>
      @if($products->hasMorePages())
        <div class="more"><button class="btn" id="view-more" data-next="{{ $products->nextPageUrl() }}">View more</button>
        <noscript><a class="btn" href="{{ $products->nextPageUrl() }}">View more</a></noscript></div>
      @endif
    </section>
  </div>
</div>
@endsection
