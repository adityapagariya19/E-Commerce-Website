@extends('layouts.admin')
@section('title','Hero slide')
@section('content')<h1>{{ $slide->exists ? 'Edit slide' : 'Add slide' }}</h1>
<form class="card" style="max-width:760px" method="POST" enctype="multipart/form-data" action="{{ $slide->exists ? route('admin.hero-slides.update', $slide) : route('admin.hero-slides.store') }}">@csrf @if($slide->exists) @method('PUT') @endif
<div class="f"><label>Eyebrow text</label><input type="text" name="eyebrow" value="{{ old('eyebrow', $slide->eyebrow) }}"></div><div class="f"><label>Small caps line</label><input type="text" name="kicker" value="{{ old('kicker', $slide->kicker) }}"></div>
<div class="f"><label>Heading *</label><input type="text" name="heading" value="{{ old('heading', $slide->heading) }}" required></div>
<div class="grid g2"><div class="f"><label>Button label</label><input type="text" name="cta_label" value="{{ old('cta_label', $slide->cta_label) }}"></div><div class="f"><label>Button URL (/path or https://…)</label><input type="text" name="cta_url" value="{{ old('cta_url', $slide->cta_url) }}"></div></div>
<div class="f"><label>Image {{ $slide->exists ? '(upload to replace)' : '*' }} — wide, about 1600×640</label>@if($slide->image)<img class="thumb lg" src="{{ media_url($slide->image) }}" alt=""><br>@endif<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
<div class="f"><label>Mobile image (optional, portrait)</label>@if($slide->mobile_image)<img class="thumb lg" src="{{ media_url($slide->mobile_image) }}" alt=""><br>@endif<input type="file" name="mobile_image" accept="image/jpeg,image/png,image/webp"></div>
<div class="grid g2"><div class="f"><label>Alt text</label><input type="text" name="alt" value="{{ old('alt', $slide->alt) }}"></div><div class="f"><label>Order</label><input type="number" min="0" name="position" value="{{ old('position', $slide->position) }}"></div></div>
<label style="font-weight:400"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $slide->is_active))> Active</label><br><br><button class="btn">Save</button></form>@endsection
