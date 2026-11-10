@extends('layouts.store')
@section('title', $page->title.' – '.setting('store_name', 'JB Optician'))
@section('content')
<div class="wrap prose" style="max-width:880px;padding:30px 20px"><h1 style="font-weight:500">{{ $page->title }}</h1>{!! \Illuminate\Support\Str::markdown((string) $page->body, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
@endsection
