<?php

namespace App\Services;

use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Support\StoreException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Payment state machine. An order becomes "paid" ONLY via markPaid(), which is reached through
 * (a) a verified checkout signature or (b) a verified webhook. Visiting a success URL never marks anything paid.
 */
class PaymentService
{
    public function __construct(private RazorpayService $razorpay, private InventoryService $inventory)
    {
    }

    public function createRazorpayOrder(Order $order): Payment
    {
        // Reuse the "created" payment row opened at checkout instead of adding a second one.
        $payment = $order->payments()->where('provider', 'razorpay')->where('status', 'created')->latest('id')->first();
        if ($payment && $payment->provider_order_id && to_paise($payment->amount) === to_paise($order->total)) {
            return $payment;
        }
        $res = $this->razorpay->createOrder(to_paise($order->total), 'JB'.$order->order_number, [
            'order_id' => (string) $order->id, 'order_number' => (string) $order->order_number,
        ]);
        $attrs = ['provider_order_id' => $res['id'], 'amount' => $order->total, 'currency' => $order->currency, 'status' => 'created'];
        if ($payment) {
            $payment->update($attrs);
            return $payment;
        }
        return $order->payments()->create(['provider' => 'razorpay'] + $attrs);
    }

    /** Customer returned from Razorpay Checkout with ids + signature. */
    public function verifyCheckoutCallback(Order $order, string $razorpayOrderId, string $paymentId, string $signature): bool
    {
        $payment = $order->payments()->where('provider', 'razorpay')->where('provider_order_id', $razorpayOrderId)->first();
        if (! $payment) {
            throw new StoreException('This payment does not belong to this order.');
        }
        if (! $this->razorpay->verifyPaymentSignature($razorpayOrderId, $paymentId, $signature)) {
            logger()->warning('Razorpay signature mismatch', ['order' => $order->id]);
            throw new StoreException('Payment verification failed. If money was deducted it will be reconciled automatically; please contact us.');
        }
        return $this->markPaid($order, $paymentId, null);
    }

    /** Idempotent. Returns true only the first time the order transitions to paid. */
    public function markPaid(Order $order, string $providerPaymentId, ?string $method, ?int $amountPaise = null): bool
    {
        if ($amountPaise !== null && $amountPaise !== to_paise($order->total)) {
            logger()->error('Razorpay amount mismatch', ['order' => $order->id, 'expected' => to_paise($order->total), 'got' => $amountPaise]);
            throw new StoreException('Payment amount does not match the order total.');
        }

        $newlyPaid = DB::transaction(function () use ($order, $providerPaymentId, $method) {
            $o = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (in_array($o->payment_status, ['paid', 'partially_refunded', 'refunded'], true)) {
                return false;
            }
            $payment = $o->payments()->where('provider', 'razorpay')->latest('id')->first();
            $alreadyUsed = Payment::where('provider_payment_id', $providerPaymentId)->where('order_id', '!=', $o->id)->exists();
            if ($alreadyUsed) {
                throw new StoreException('This payment is already attached to another order.');
            }
            $attrs = ['provider_payment_id' => $providerPaymentId, 'status' => 'captured', 'method' => $method, 'failure_reason' => null];
            $payment ? $payment->update($attrs) : $o->payments()->create($attrs + ['provider' => 'razorpay', 'amount' => $o->total, 'currency' => $o->currency]);

            $from = $o->status;
            $note = 'Payment received via Razorpay ('.$providerPaymentId.').';
            $status = 'processing';
            if (in_array($from, ['cancelled', 'failed'], true)) {
                try {
                    $this->inventory->reserve($o); // payment arrived after expiry: try to re-take the stock
                } catch (StoreException $e) {
                    $status = 'on_hold';
                    $note .= ' Paid after cancellation and stock is no longer available — review and refund or fulfil manually.';
                }
            }
            $o->update(['status' => $status, 'payment_status' => 'paid', 'paid_at' => now(), 'cancelled_at' => null]);
            $o->histories()->create(['from_status' => $from, 'to_status' => $status, 'note' => $note]);
            return true;
        });

        if ($newlyPaid) {
            PopularProductsService::forget();
            try {
                Mail::to($order->email)->send(new OrderConfirmation($order->fresh('items')));
            } catch (\Throwable $e) {
                logger()->warning('Order email failed: '.$e->getMessage());
            }
        }
        return $newlyPaid;
    }

    /** A failed attempt does NOT cancel the order — the customer may retry from the Pay page. */
    public function recordFailure(Order $order, ?string $providerPaymentId, ?string $reason): void
    {
        if (in_array($order->payment_status, ['paid', 'partially_refunded', 'refunded'], true)) {
            return;
        }
        $payment = $order->payments()->where('provider', 'razorpay')->latest('id')->first();
        $attrs = ['status' => 'failed', 'failure_reason' => $reason ? mb_substr($reason, 0, 250) : null];
        if ($providerPaymentId && ! Payment::where('provider_payment_id', $providerPaymentId)->exists()) {
            $attrs['provider_payment_id'] = $providerPaymentId;
        }
        $payment ? $payment->update($attrs) : null;
        $order->update(['payment_status' => 'failed']);
        $order->histories()->create(['from_status' => $order->status, 'to_status' => $order->status, 'note' => 'Payment attempt failed'.($reason ? ': '.$reason : '.')]);
    }

    /** @return 'invalid'|'duplicate'|'processed'|'ignored'|'rejected' */
    public function handleWebhook(string $rawBody, ?string $signature, ?string $eventId): string
    {
        if (! $this->razorpay->verifyWebhookSignature($rawBody, $signature)) {
            return 'invalid';
        }
        $payload = json_decode($rawBody, true);
        if (! is_array($payload) || empty($payload['event'])) {
            return 'ignored';
        }
        $eventId = $eventId ?: 'body:'.hash('sha256', $rawBody);

        try {
            $event = PaymentEvent::create([
                'provider' => 'razorpay', 'event_id' => $eventId, 'event_type' => $payload['event'],
                'payload' => RazorpayService::sanitize($payload),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            $event = PaymentEvent::where('event_id', $eventId)->first();
            if ($event?->processed_at) {
                return 'duplicate';
            }
        }

        $payment = $payload['payload']['payment']['entity'] ?? [];
        $razorpayOrderId = $payment['order_id'] ?? ($payload['payload']['order']['entity']['id'] ?? null);
        $local = $razorpayOrderId ? Payment::where('provider_order_id', $razorpayOrderId)->first() : null;
        $order = $local?->order;

        $result = 'ignored';
        if ($order) {
            try {
            switch ($payload['event']) {
                case 'payment.captured':
                case 'order.paid':
                    if (! empty($payment['id'])) {
                        $this->markPaid($order, $payment['id'], $payment['method'] ?? null, isset($payment['amount']) ? (int) $payment['amount'] : null);
                        $result = 'processed';
                    }
                    break;
                case 'payment.failed':
                    $this->recordFailure($order, $payment['id'] ?? null, $payment['error_description'] ?? null);
                    $result = 'processed';
                    break;
                case 'refund.processed':
                    $rid = $payload['payload']['refund']['entity']['id'] ?? null;
                    $order->refunds()->where('provider_refund_id', $rid)->update(['status' => 'processed']);
                    $result = 'processed';
                    break;
            }
            } catch (StoreException $e) {
                // Permanent problem (e.g. amount mismatch): acknowledge so Razorpay stops retrying, but never mark paid.
                logger()->error('Razorpay webhook rejected: '.$e->getMessage(), ['event' => $payload['event']]);
                $result = 'rejected';
            }
        }
        $event?->update(['processed_at' => now()]);
        return $result;
    }
}
