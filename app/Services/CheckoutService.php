<?php

namespace App\Services;

use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\StoreException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        private CartService $carts,
        private PricingService $pricing,
        private InventoryService $inventory,
        private PaymentService $payments,
    ) {
    }

    /**
     * Turn the current cart into an order. Prices, stock and coupon are re-validated on the server inside a transaction.
     * $data: email, phone, billing[], shipping[], payment_method (cod|razorpay), customer_note, checkout_token
     */
    public function place(array $data, ?User $user): Order
    {
        $cart = $this->carts->cart(false);
        $token = $data['checkout_token'] ?? null;

        if ($token && ($existing = Order::where('checkout_token', $token)->first())) {
            return $existing; // double-click / refresh: return the order that already exists
        }
        if (! $cart || $cart->items()->count() === 0) {
            throw new StoreException('Your cart is empty.');
        }
        if (($data['payment_method'] ?? '') === 'razorpay' && ! app(RazorpayService::class)->configured()) {
            throw new StoreException('Online payment is not available right now. Please choose Cash on delivery.');
        }

        $order = DB::transaction(function () use ($cart, $data, $user, $token) {
            // Lock products so two checkouts cannot oversell the same stock.
            $lineModels = $cart->items()->get();
            Product::whereIn('id', $lineModels->pluck('product_id'))->lockForUpdate()->get();
            ProductVariant::whereIn('id', $lineModels->pluck('variant_id')->filter())->lockForUpdate()->get();

            $lines = $this->carts->lines($cart);
            foreach ($lines as $l) {
                if ($l['problem']) {
                    throw new StoreException($l['product']->name.': '.$l['problem'].'. Please update your cart.');
                }
            }
            $quote = $this->pricing->quote($lines, $cart->coupon_code, $user, $data['email']);
            if ($cart->coupon_code && $quote['coupon_error']) {
                throw new StoreException($quote['coupon_error']);
            }

            $cod = $data['payment_method'] === 'cod';
            $order = Order::create([
                'order_number' => Order::nextNumber(),
                'order_key' => 'wc_order_'.Str::random(13),
                'user_id' => $user?->id,
                'checkout_token' => $token,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => $cod ? 'processing' : 'pending_payment',
                'payment_status' => $cod ? 'cod_pending' : 'unpaid',
                'payment_method' => $data['payment_method'],
                'subtotal' => $quote['subtotal'], 'discount_total' => $quote['discount'], 'shipping_total' => $quote['shipping'],
                'tax_total' => $quote['tax'], 'total' => $quote['total'],
                'currency' => config('jbo.currency'),
                'coupon_code' => $quote['coupon']?->code,
                'shipping_method' => $quote['shipping_paise'] > 0 ? 'Flat rate' : 'Free shipping',
                'billing' => $data['billing'], 'shipping' => $data['shipping'],
                'customer_note' => $data['customer_note'] ?? null,
                'is_demo' => false,
                'placed_at' => now(),
            ]);

            foreach ($quote['lines'] as $l) {
                $order->items()->create([
                    'product_id' => $l['product']->id,
                    'variant_id' => $l['variant']?->id,
                    'name' => $l['product']->name,
                    'sku' => $l['variant']?->sku ?? $l['product']->sku,
                    'variant_label' => $l['variant']?->label,
                    'image' => $l['product']->images->first()?->path,
                    'unit_price' => from_paise($l['unit_paise']),
                    'qty' => $l['qty'],
                    'line_total' => from_paise($l['line_paise']),
                ]);
            }

            $this->inventory->reserve($order); // throws StoreException if stock vanished

            if ($quote['coupon']) {
                $order->couponUsages()->create([
                    'coupon_id' => $quote['coupon']->id, 'user_id' => $user?->id, 'email' => $data['email'], 'amount' => $quote['discount'],
                ]);
            }
            $order->payments()->create([
                'provider' => $cod ? 'cod' : 'razorpay', 'status' => 'created', 'amount' => $order->total, 'currency' => $order->currency,
            ]);
            $order->histories()->create(['from_status' => null, 'to_status' => $order->status, 'note' => $cod ? 'Order placed — cash on delivery.' : 'Order placed — awaiting online payment.']);

            $this->carts->clear($cart);
            return $order;
        });

        PopularProductsService::forget();

        if ($order->payment_method === 'cod') {
            try {
                Mail::to($order->email)->send(new OrderConfirmation($order->load('items')));
            } catch (\Throwable $e) {
                logger()->warning('Order email failed: '.$e->getMessage());
            }
        } else {
            try {
                $this->payments->createRazorpayOrder($order);
            } catch (StoreException $e) {
                // Order exists; the customer can retry from the Pay page. Nothing is lost.
                logger()->warning('Gateway order creation failed for order '.$order->id);
            }
        }
        return $order;
    }
}
