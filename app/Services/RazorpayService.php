<?php

namespace App\Services;

use App\Support\StoreException;
use Illuminate\Support\Facades\Http;

/**
 * Thin server-side Razorpay client (REST via Laravel HTTP, no SDK dependency).
 * Credentials: Admin > Settings (secret stored encrypted) overrides .env values.
 * NOTE: endpoints/field names follow Razorpay's public docs from memory and are UNVERIFIED against a live sandbox here.
 */
class RazorpayService
{
    public function keyId(): ?string { return setting('razorpay_key_id', config('services.razorpay.key_id')) ?: null; }
    private function keySecret(): ?string { return setting('razorpay_key_secret', config('services.razorpay.key_secret')) ?: null; }
    private function webhookSecret(): ?string { return setting('razorpay_webhook_secret', config('services.razorpay.webhook_secret')) ?: null; }

    public function configured(): bool { return $this->keyId() && $this->keySecret(); }
    public function mode(): string { return str_starts_with((string) $this->keyId(), 'rzp_test_') ? 'test' : ($this->keyId() ? 'live' : 'unconfigured'); }

    public function createOrder(int $amountPaise, string $receipt, array $notes = []): array
    {
        $res = $this->client()->post('/orders', [
            'amount' => $amountPaise, 'currency' => config('jbo.currency'), 'receipt' => $receipt, 'notes' => $notes,
        ]);
        if (! $res->successful()) {
            logger()->error('Razorpay order creation failed', ['status' => $res->status(), 'error' => $res->json('error.description')]);
            throw new StoreException('The payment gateway could not start the payment. Please try again.');
        }
        return $res->json();
    }

    public function refund(string $paymentId, int $amountPaise, array $notes = []): array
    {
        $res = $this->client()->post("/payments/{$paymentId}/refund", ['amount' => $amountPaise, 'notes' => $notes]);
        if (! $res->successful()) {
            logger()->error('Razorpay refund failed', ['status' => $res->status(), 'error' => $res->json('error.description')]);
            throw new StoreException('Razorpay rejected the refund: '.($res->json('error.description') ?? 'unknown error'));
        }
        return $res->json();
    }

    /** Checkout callback signature: HMAC_SHA256(order_id|payment_id, key_secret). */
    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $secret = $this->keySecret();
        return $secret && hash_equals(hash_hmac('sha256', $orderId.'|'.$paymentId, $secret), $signature);
    }

    /** Webhook signature: HMAC_SHA256(raw body, webhook_secret) from the X-Razorpay-Signature header. */
    public function verifyWebhookSignature(string $rawBody, ?string $signature): bool
    {
        $secret = $this->webhookSecret();
        return $secret && $signature && hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }

    /** Strip anything card/VPA/PII-like from payloads before they are stored. */
    public static function sanitize(array $payload): array
    {
        $entity = fn ($e) => collect($e ?? [])->only(['id', 'order_id', 'status', 'method', 'amount', 'currency', 'error_code', 'error_description', 'notes', 'payment_id'])->all();
        return [
            'event' => $payload['event'] ?? null,
            'payment' => $entity($payload['payload']['payment']['entity'] ?? null),
            'order' => $entity($payload['payload']['order']['entity'] ?? null),
            'refund' => $entity($payload['payload']['refund']['entity'] ?? null),
        ];
    }

    private function client()
    {
        if (! $this->configured()) {
            throw new StoreException('Online payment is not configured on this store.');
        }
        return Http::baseUrl(config('services.razorpay.base_url'))->withBasicAuth($this->keyId(), $this->keySecret())->acceptJson()->timeout(20);
    }
}
