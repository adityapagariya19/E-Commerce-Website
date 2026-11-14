@extends('layouts.admin')
@section('title','Homepage blocks')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:10px">
  <h1>Homepage blocks</h1>
  <div style="display:flex;gap:6px;flex-wrap:wrap">
    @foreach(App\Models\HomeBlock::SECTIONS as $k=>$l)
      <a class="btn sm ghost" href="{{ route('admin.blocks.create', ['section'=>$k]) }}">+ {{ $l }}</a>
    @endforeach
  </div>
</div>
<div class="card tablewrap">
  <table>
    <thead>
      <tr>
        <th style="width:70px">Image</th>
        <th>Section</th>
        <th>Title / Subtitle</th>
        <th>Target URL</th>
        <th style="width:70px">Order</th>
        <th style="width:90px">Status</th>
        <th style="width:140px;text-align:right">Actions</th>
      </tr>
    </thead>
    <tbody>
    @forelse($blocks as $b)
      <tr>
        <td>
          @if($b->image)
            <a href="{{ route('admin.blocks.edit', $b) }}">
              <img class="thumb" src="{{ media_url($b->image) }}" alt="">
            </a>
          @else
            <span style="color:var(--muted)">—</span>
          @endif
        </td>
        <td><span class="pill">{{ App\Models\HomeBlock::SECTIONS[$b->section] ?? $b->section }}</span></td>
        <td>
          <a href="{{ route('admin.blocks.edit', $b) }}" style="font-weight:600">{{ $b->title ?: '(tile #'.$b->id.')' }}</a>
          @if($b->subtitle)<div class="hint">{{ $b->subtitle }}</div>@endif
        </td>
        <td><span style="font-size:12px;color:var(--muted)">{{ $b->url ?: '—' }}</span></td>
        <td><strong>{{ $b->position }}</strong></td>
        <td>{!! $b->is_active ? '<span class="pill ok">Active</span>' : '<span class="pill">Inactive</span>' !!}</td>
        <td style="text-align:right">
          <div style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end">
            <a class="btn sm" href="{{ route('admin.blocks.edit', $b) }}">Edit</a>
            <form method="POST" action="{{ route('admin.blocks.destroy', $b) }}" onsubmit="return confirm('Delete block?')">
              @csrf @method('DELETE')
              <button class="btn sm red" type="submit">Delete</button>
            </form>
          </div>
        </td>
      </tr>
    @empty
      <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--muted)">No homepage blocks configured.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
