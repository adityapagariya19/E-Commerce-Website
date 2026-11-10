<div style="overflow-x:auto"><table class="table"><thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th><th>Actions</th></tr></thead><tbody>
@foreach($orders as $o)<tr><td>#{{ $o->order_number }}</td><td>{{ $o->placed_at?->format('F j, Y') }}</td><td>{{ $o->statusLabel() }}</td><td>{{ money($o->total) }}</td><td><a class="btn btn--sm" href="{{ route('account.order', $o) }}">View</a></td></tr>@endforeach
</tbody></table></div>
