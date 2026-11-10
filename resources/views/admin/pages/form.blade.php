@extends('layouts.admin')
@section('title','Page')
@section('content')<h1>{{ $page->exists ? 'Edit page' : 'Add page' }}</h1><form class="card" style="max-width:860px" method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}">@csrf @if($page->exists) @method('PUT') @endif
<div class="grid g2"><div class="f"><label>Title *</label><input type="text" name="title" value="{{ old('title', $page->title) }}" required></div><div class="f"><label>Slug</label><input type="text" name="slug" value="{{ old('slug', $page->slug) }}" @if($page->slug==='about-us') readonly @endif></div></div>
<div class="f"><label>Body (Markdown; raw HTML is stripped)</label><textarea name="body" style="min-height:320px">{{ old('body', $page->body) }}</textarea></div>
<label style="font-weight:400"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $page->is_active))> Published</label><br><br><button class="btn">Save</button></form>@endsection
