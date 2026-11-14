@extends('layouts.admin')
@section('title','Hero slides')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
  <h1>Hero slides</h1>
  <a class="btn" href="{{ route('admin.hero-slides.create') }}">+ Add New Slide</a>
</div>
<div class="card tablewrap">
  <table>
    <thead>
      <tr>
        <th style="width:130px">Thumbnail</th>
        <th>Heading & Subtitle</th>
        <th>CTA Button</th>
        <th style="width:70px">Order</th>
        <th style="width:90px">Status</th>
        <th style="width:140px;text-align:right">Actions</th>
      </tr>
    </thead>
    <tbody>
    @forelse($slides as $s)
      <tr>
        <td>
          <a href="{{ route('admin.hero-slides.edit', $s) }}" target="_self" title="Click to edit slide">
            <img class="thumb lg" src="{{ media_url($s->image) }}" alt="{{ $s->alt ?: $s->heading }}">
          </a>
        </td>
        <td>
          <a href="{{ route('admin.hero-slides.edit', $s) }}" target="_self" style="font-weight:600;font-size:14px">{{ $s->heading }}</a>
          @if($s->eyebrow)<div class="hint">{{ $s->eyebrow }}</div>@endif
          @if($s->subheading)<div class="hint" style="color:var(--muted)">{{ $s->subheading }}</div>@endif
        </td>
        <td>
          @if($s->cta_label)
            <span class="pill">{{ $s->cta_label }}</span> <span style="font-size:12px;color:var(--muted)">→ {{ $s->cta_url }}</span>
          @else
            <span style="color:var(--muted)">—</span>
          @endif
        </td>
        <td><strong>{{ $s->position }}</strong></td>
        <td>{!! $s->is_active ? '<span class="pill ok">Active</span>' : '<span class="pill">Inactive</span>' !!}</td>
        <td style="text-align:right">
          <div style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end">
            <a class="btn sm" href="{{ route('admin.hero-slides.edit', $s) }}" target="_self">Edit</a>
            <form method="POST" action="{{ route('admin.hero-slides.destroy', $s) }}" onsubmit="return confirm('Are you sure you want to delete this slide?')">
              @csrf @method('DELETE')
              <button class="btn sm red" type="submit">Delete</button>
            </form>
          </div>
        </td>
      </tr>
    @empty
      <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--muted)">No hero slides configured. Click "+ Add New Slide" above.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
