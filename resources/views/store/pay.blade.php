@extends('layouts.store')
@section('title', 'Pay for order #'.$order->order_number)
@section('content')
<div class="wrap" style="max-width:640px;text-align:center;padding:50px 20px">
  <h1 class="page-title">Order #{{ $order->order_number }}</h1>
  <p>Total: <b>{{ money($order->total) }}</b> · Status: {{ $order->statusLabel() }}</p>
  @if($mode === 'test')<p><span class="note-pill">Razorpay TEST mode — no real money is charged</span></p>@endif
  @if($error)<div class="flash flash--err">{{ $error }}</div>@endif
  @if($order->status !== 'pending_payment')
    <p>This order is {{ strtolower($order->statusLabel()) }}.</p><a class="btn" href="{{ route('shop') }}">Continue shopping</a>
  @elseif($checkout)
    <p id="pay-msg" role="status">Thank you for your order, please click the button below to pay with Razorpay.</p>
    <div style="display:flex;gap:12px;justify-content:center;margin:20px 0"><button class="btn" id="pay-now" type="button">Pay Now</button>
      <form method="POST" action="{{ route('checkout.cancel', ['order' => $order->id, 'key' => $order->order_key]) }}">@csrf<button class="btn btn--grey">Cancel</button></form></div>
    <form id="verify-form" method="POST" action="{{ route('checkout.verify', ['order' => $order->id]) }}" hidden>@csrf<input type="hidden" name="key" value="{{ $order->order_key }}"><input name="razorpay_payment_id"><input name="razorpay_order_id"><input name="razorpay_signature"></form>
    <p style="font-size:12px;color:var(--muted)">Your order is only marked paid after our server verifies the payment with Razorpay.</p>
  @endif
</div>
@endsection
@push('scripts')
@if($checkout)
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
(function(){
  const cfg = @json($checkout); const csrf = document.querySelector('meta[name=csrf-token]').content;
  const form = document.getElementById('verify-form');
  function open(){
    const rz = new Razorpay({ key: cfg.key, amount: cfg.amount, currency: cfg.currency, name: cfg.name, description: cfg.description, order_id: cfg.order_id, prefill: cfg.prefill,
      handler: function(r){ form.razorpay_payment_id.value = r.razorpay_payment_id; form.razorpay_order_id.value = r.razorpay_order_id; form.razorpay_signature.value = r.razorpay_signature; form.submit(); },
      modal: { ondismiss: function(){ document.getElementById('pay-msg').textContent = 'Payment was not completed. You can try again or cancel the order.'; } } });
    rz.on('payment.failed', function(resp){ fetch(@json(route('checkout.failed', ['order' => $order->id])), { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Content-Type':'application/json','Accept':'application/json'}, body: JSON.stringify({ key: @json($order->order_key), reason: (resp.error && resp.error.description) || '' }) });
      document.getElementById('pay-msg').textContent = 'Payment failed: ' + ((resp.error && resp.error.description) || 'please try again') + '.'; });
    rz.open();
  }
  document.getElementById('pay-now').addEventListener('click', open); window.addEventListener('load', function(){ setTimeout(open, 400); });
})();
</script>
@endif
@endpush
