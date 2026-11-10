@extends('layouts.store')
@section('title', 'About Us – '.setting('store_name', 'JB Optician'))
@section('content')
<div class="wrap about prose" style="max-width:880px;padding:30px 20px">
  <h1>About <span>{{ setting('store_name', 'JB Optician') }}</span></h1>
  {!! \Illuminate\Support\Str::markdown((string) ($page->body ?? ''), ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
  <h2 style="font-weight:500">Visit Our Store</h2>
  <p><b>Address:</b> {{ setting('store_address') }}</p>
  @if(setting('store_phone'))<p><b>Phone:</b> {{ setting('store_phone') }}</p>@endif @if(setting('store_hours'))<p><b>Hours:</b> {{ setting('store_hours') }}</p>@endif
</div>
@endsection
