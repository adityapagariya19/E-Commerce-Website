@extends('layouts.admin')
@section('title','Homepage blocks')
@section('content')<h1>Homepage blocks</h1><p>@foreach(App\Models\HomeBlock::SECTIONS as $k=>$l)<a class="btn sm ghost" href="{{ route('admin.blocks.create', ['section'=>$k]) }}">+ {{ $l }}</a> @endforeach</p>
<div class="warn-box">Blocks marked <b>PLACEHOLDER</b> are seeded stand-ins (image or business claim). Edit &amp; confirm them before launch — especially the trust row text.</div>
<div class="card tablewrap"><table><thead><tr><th></th><th>Section</th><th>Title</th><th>Link</th><th>Order</th><th>Active</th><th></th></tr></thead><tbody>
@foreach($blocks as $b)<tr><td>@if($b->image)<img class="thumb" src="{{ media_url($b->image) }}" alt="">@endif</td><td>{{ App\Models\HomeBlock::SECTIONS[$b->section] ?? $b->section }}</td><td><a href="{{ route('admin.blocks.edit', $b) }}">{{ $b->title ?: '(image tile #'.$b->id.')' }}</a> @if($b->is_placeholder)<span class="pill warn">PLACEHOLDER</span>@endif<div class="hint">{{ $b->subtitle }}</div></td><td>{{ $b->url }}</td><td>{{ $b->position }}</td><td>{{ $b->is_active ? 'yes':'no' }}</td>
<td><form method="POST" action="{{ route('admin.blocks.destroy', $b) }}" onsubmit="return confirm('Delete block?')">@csrf @method('DELETE')<button class="btn sm red">Delete</button></form></td></tr>@endforeach</tbody></table></div>@endsection
