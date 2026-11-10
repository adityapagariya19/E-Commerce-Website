@extends('layouts.admin')
@section('title','Brand')
@section('content')<h1>{{ $brand->exists ? 'Edit brand' : 'Add brand' }}</h1>
<form class="card" style="max-width:560px" method="POST" enctype="multipart/form-data" action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}">@csrf @if($brand->exists) @method('PUT') @endif
<div class="f"><label>Name *</label><input type="text" name="name" value="{{ old('name', $brand->name) }}" required></div><div class="f"><label>Slug</label><input type="text" name="slug" value="{{ old('slug', $brand->slug) }}"></div>
<div class="f"><label>Logo</label>@if($brand->logo)<img class="thumb lg" src="{{ media_url($brand->logo) }}" alt=""><br>@endif<input type="file" name="logo" accept="image/jpeg,image/png,image/webp"></div>
<label style="font-weight:400"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $brand->is_active))> Active</label><br><br><button class="btn">Save</button></form>@endsection
