@extends('layouts.store')
@section('title', 'Try On – '.setting('store_name', 'JB Optician'))
@section('content')
<div class="wrap" style="max-width:860px;margin:0 auto;padding-bottom:50px">
  <h1 class="page-title" style="margin-top:30px">Try On</h1>
  <p style="text-align:center;font-size:13px;color:var(--muted);margin-bottom:30px">Virtual try-on uses your device camera. Allow camera access when your browser asks.</p>
  @forelse($products as $p)
  <div class="tryon-row">
    <a href="{{ route('product', $p) }}">{!! picture($p->images->first()?->path, $p->name, 'thumb', ['loading' => 'lazy']) !!}</a>
    <button class="btn" type="button" data-tryon-btn data-tryon="{{ $p->image_url }}" data-tryon-url="{{ $p->tryon_url }}" data-name="{{ $p->name }}" data-open="#tryon-overlay">Try Now</button>
    <a class="btn" href="{{ route('product', $p) }}">Buy Now</a>
  </div>
  @empty<p style="text-align:center;padding:50px">No frames are enabled for try-on yet.</p>@endforelse
</div>
<div class="overlay" id="tryon-overlay" role="dialog" aria-modal="true" aria-label="Virtual try-on" aria-hidden="true">
  <div class="modal" style="max-width:760px"><button class="modal__close" data-close aria-label="Close">×</button>
    <h2 id="tryon-title" style="font-size:16px;font-weight:500;margin:0 28px 10px 0"></h2>
    <iframe id="tryon-iframe" title="AR try-on" hidden allow="camera; microphone; xr-spatial-tracking; accelerometer; gyroscope; fullscreen" referrerpolicy="strict-origin-when-cross-origin" style="width:100%;height:min(70vh,560px);border:0;border-radius:10px;background:#000"></iframe>
    <div class="tryon-stage" hidden><video autoplay playsinline muted></video><img class="tryon-frame" alt="Frame overlay" draggable="false" src=""></div>
    <div class="tryon-ctl" hidden><label>Size <input type="range" id="tryon-size" min="20" max="80" value="46"></label></div>
    <p id="tryon-msg" style="text-align:center;font-size:12px;color:var(--muted)"></p>
    @if($products->count() > 1)<div style="display:flex;gap:8px;overflow:auto;padding:6px 0" aria-label="Switch frame">@foreach($products as $p)<button type="button" class="btn btn--sm btn--ghost" data-tryon-btn data-tryon="{{ $p->image_url }}" data-tryon-url="{{ $p->tryon_url }}" data-name="{{ $p->name }}" style="white-space:nowrap">{{ \Illuminate\Support\Str::limit($p->name, 22) }}</button>@endforeach</div>@endif
    <p style="text-align:center"><a class="btn btn--sm" id="tryon-buy" href="{{ route('shop') }}">Back to shopping</a></p></div>
</div>
@endsection
