<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesStore;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase, MakesStore;

    private function pendingOrder(): Order
    {
        $p = $this->product(['price' => 1000, 'stock_qty' => 5]);
        $o = $this->order([[$p, 1]], ['status' => 'pending_payment', 'payment_status' => 'unpaid', 'stock_reserved' => true]);
        $o->payments()->create(['provider' => 'razorpay', 'provider_order_id' => 'order_ABC', 'status' => 'created', 'amount' => 1000]);
        return $o;
    }

    private function webhook(string $event, array $payment, ?string $eventId = 'evt_1', ?string $secret = 'unit_webhook_secret')
    {
        $body = json_encode(['event' => $event, 'payload' => ['payment' => ['entity' => $payment]]]);
        $sig = hash_hmac('sha256', $body, $secret ?? 'x');
        return $this->call('POST', '/webhooks/razorpay', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_RAZORPAY_SIGNATURE' => $sig, 'HTTP_X_RAZORPAY_EVENT_ID' => $eventId], $body);
    }

    public function test_success_url_alone_never_marks_paid(): void
    {
        $o = $this->pendingOrder();
        $this->get(route('order.received', ['order' => $o->id, 'key' => $o->order_key]))->assertOk();
        $this->assertSame('unpaid', $o->fresh()->payment_status);
    }

    public function test_valid_signature_marks_order_paid(): void
    {
        $o = $this->pendingOrder();
        $sig = hash_hmac('sha256', 'order_ABC|pay_111', 'unit_secret');
        $this->post(route('checkout.verify', $o), ['key' => $o->order_key, 'razorpay_order_id' => 'order_ABC', 'razorpay_payment_id' => 'pay_111', 'razorpay_signature' => $sig])->assertRedirect();
        $o->refresh();
        $this->assertSame('paid', $o->payment_status);
        $this->assertSame('processing', $o->status);
        $this->assertNotNull($o->paid_at);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $o = $this->pendingOrder();
        $this->post(route('checkout.verify', $o), ['key' => $o->order_key, 'razorpay_order_id' => 'order_ABC', 'razorpay_payment_id' => 'pay_111', 'razorpay_signature' => 'forged'])->assertSessionHas('error');
        $this->assertSame('unpaid', $o->fresh()->payment_status);
        $this->assertSame('pending_payment', $o->fresh()->status);
    }

    public function test_cannot_verify_someone_elses_order_without_key(): void
    {
        $o = $this->pendingOrder();
        $this->post(route('checkout.verify', $o), ['razorpay_order_id' => 'order_ABC', 'razorpay_payment_id' => 'pay_1', 'razorpay_signature' => 'x'])->assertNotFound();
    }

    public function test_webhook_with_bad_signature_is_400(): void
    {
        $this->pendingOrder();
        $this->webhook('payment.captured', ['id' => 'pay_1', 'order_id' => 'order_ABC', 'amount' => 100000], 'evt_x', 'wrong-secret')->assertStatus(400);
    }

    public function test_captured_webhook_marks_paid_and_duplicate_is_idempotent(): void
    {
        $o = $this->pendingOrder();
        $payload = ['id' => 'pay_222', 'order_id' => 'order_ABC', 'amount' => 100000, 'method' => 'upi'];
        $this->webhook('payment.captured', $payload, 'evt_dup')->assertOk()->assertJson(['status' => 'processed']);
        $this->webhook('payment.captured', $payload, 'evt_dup')->assertOk()->assertJson(['status' => 'duplicate']);
        $this->webhook('order.paid', $payload, 'evt_other')->assertOk(); // different event id, same payment
        $this->assertSame('paid', $o->fresh()->payment_status);
        $this->assertSame(1, $o->payments()->where('status', 'captured')->count());
        $this->assertSame(2, PaymentEvent::count());
        $this->assertSame(1, $o->histories()->where('to_status', 'processing')->count());
    }

    public function test_webhook_amount_mismatch_does_not_mark_paid(): void
    {
        $o = $this->pendingOrder();
        $this->webhook('payment.captured', ['id' => 'pay_9', 'order_id' => 'order_ABC', 'amount' => 100])->assertOk()->assertJson(['status' => 'rejected']);
        $this->assertSame('unpaid', $o->fresh()->payment_status);
    }

    public function test_failed_payment_keeps_order_retryable(): void
    {
        $o = $this->pendingOrder();
        $this->webhook('payment.failed', ['id' => 'pay_f', 'order_id' => 'order_ABC', 'error_description' => 'Card declined'], 'evt_f')->assertOk();
        $o->refresh();
        $this->assertSame('failed', $o->payment_status);
        $this->assertSame('pending_payment', $o->status);
        // a later successful payment still works
        $this->webhook('payment.captured', ['id' => 'pay_ok', 'order_id' => 'order_ABC', 'amount' => 100000], 'evt_ok')->assertOk();
        $this->assertSame('paid', $o->fresh()->payment_status);
    }

    public function test_payment_after_expiry_reserves_stock_again_and_processes(): void
    {
        $o = $this->pendingOrder();
        app(\App\Services\OrderService::class)->transition($o, 'cancelled', 'expired', null, true);
        $item = $o->items()->first();
        $this->assertSame(6, $item->product->fresh()->stock_qty); // released
        $this->webhook('payment.captured', ['id' => 'pay_late', 'order_id' => 'order_ABC', 'amount' => 100000], 'evt_late')->assertOk();
        $o->refresh();
        $this->assertSame('processing', $o->status);
        $this->assertSame(5, $item->product->fresh()->stock_qty);
    }

    public function test_payment_after_expiry_with_no_stock_goes_on_hold(): void
    {
        $o = $this->pendingOrder();
        app(\App\Services\OrderService::class)->transition($o, 'cancelled', 'expired', null, true);
        $o->items()->first()->product->update(['stock_qty' => 0]);
        $this->webhook('payment.captured', ['id' => 'pay_late2', 'order_id' => 'order_ABC', 'amount' => 100000], 'evt_late2')->assertOk();
        $this->assertSame('on_hold', $o->fresh()->status);
        $this->assertSame('paid', $o->fresh()->payment_status);
    }
}
