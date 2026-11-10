@extends('layouts.admin')
@section('title','Activity log')
@section('content')<h1>Activity log</h1><form class="toolbar" method="GET"><div class="f"><label>Action contains</label><input type="search" name="q" value="{{ request('q') }}"></div><button class="btn">Filter</button></form>
<div class="card tablewrap"><table><thead><tr><th>When</th><th>User</th><th>Action</th><th>Subject</th><th>Details</th><th>IP</th></tr></thead><tbody>@foreach($logs as $l)<tr><td>{{ $l->created_at->format('M j, H:i:s') }}</td><td>{{ $l->user?->name ?? '–' }}</td><td>{{ $l->action }}</td><td>{{ $l->subject_type }} {{ $l->subject_id }}</td><td class="hint">{{ $l->meta ? json_encode($l->meta) : '' }}</td><td>{{ $l->ip }}</td></tr>@endforeach</tbody></table>{{ $logs->links() }}</div>@endsection
