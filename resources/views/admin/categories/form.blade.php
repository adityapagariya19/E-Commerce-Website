@extends('layouts.admin')
@section('title','Category')
@section('content')<h1>{{ $category->exists ? 'Edit category' : 'Add category' }}</h1>
<form class="card" style="max-width:640px" method="POST" enctype="multipart/form-data" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">@csrf @if($category->exists) @method('PUT') @endif
<div class="f"><label>Name *</label><input type="text" name="name" value="{{ old('name', $category->name) }}" required></div><div class="f"><label>Slug</label><input type="text" name="slug" value="{{ old('slug', $category->slug) }}"></div>
<div class="f"><label>Parent</label><select name="parent_id"><option value="">— none —</option>@foreach($parents as $p)<option value="{{ $p->id }}" @selected(old('parent_id', $category->parent_id)==$p->id)>{{ $p->name }}</option>@endforeach</select></div>
<div class="f"><label>Description</label><textarea name="description">{{ old('description', $category->description) }}</textarea></div>
<div class="f"><label>Image</label>@if($category->image)<img class="thumb lg" src="{{ media_url($category->image) }}" alt=""><br>@endif<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
<div class="f"><label>Display order</label><input type="number" min="0" name="position" value="{{ old('position', $category->position ?? 0) }}"></div>
<label style="font-weight:400"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))> Visible</label><br><br><button class="btn">Save</button> <a href="{{ route('admin.categories.index') }}">Cancel</a></form>@endsection
