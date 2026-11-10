<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\RazorpayService;
use App\Support\StoreException;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments, private RazorpayService $razorpay)
    {
    }

    /** Guests prove ownership with the unguessable order key, members by being the owner. */
    public static function authorizeOrder(Request $request, Order $order): void
    {
        $key = (string) $request->input('key', $request->query('key', ''));
        $owner = $request->user() && $order->user_id === $request->user()->id;
        abort_unless($owner || ($key !== '' && hash_equals($order->order_key, $key)), 404);
    }

    public function show(Request $request, Order $order)
    {
        self::authorizeOrder($request, $order);
        if ($order->payment_status === 'paid' || $order->payment_method !== 'razorpay') {
            return redirect()->route('order.received', ['order' => $order->id, 'key' => $order->order_key]);
        }
        $checkout = null;
        $error = null;
        if ($order->isPayable()) {
            try {
                $payment = $this->payments->createRazorpayOrder($order);
                $checkout = [
                    'key' => $this->razorpay->keyId(), 'amount' => to_paise($order->total), 'currency' => $order->currency, 'order_id' => $payment->provider_order_id,
                    'name' => setting('store_name', 'JB Optician'), 'description' => 'Order #'.$order->order_number,
                    'prefill' => ['name' => trim(($order->billing['first_name'] ?? '').' '.($order->billing['last_name'] ?? '')), 'email' => $order->email, 'contact' => $order->phone],
                ];
            } catch (StoreException $e) {
                $error = $e->getMessage();
            }
        }
        return view('store.pay', ['order' => $order, 'checkout' => $checkout, 'error' => $error, 'mode' => $this->razorpay->mode()]);
    }

    public function verify(Request $request, Order $order)
    {
        self::authorizeOrder($request, $order);
        $d = $request->validate(['razorpay_payment_id' => ['required', 'string', 'max:100'], 'razorpay_order_id' => ['required', 'string', 'max:100'], 'razorpay_signature' => ['required', 'string', 'max:200']]);
        try {
            $this->payments->verifyCheckoutCallback($order, $d['razorpay_order_id'], $d['razorpay_payment_id'], $d['razorpay_signature']);
        } catch (StoreException $e) {
            return redirect()->route('checkout.pay', ['order' => $order->id, 'key' => $order->order_key])->with('error', $e->getMessage());
        }
        return redirect()->route('order.received', ['order' => $order->id, 'key' => $order->order_key]);
    }

    public function failed(Request $request, Order $order)
    {
        self::authorizeOrder($request, $order);
        $this->payments->recordFailure($order, null, mb_substr((string) $request->input('reason'), 0, 200));
        return response()->json(['ok' => true]);
    }

    public function cancel(Request $request, Order $order, OrderService $orders)
    {
        self::authorizeOrder($request, $order);
        if ($order->status === 'pending_payment' && $order->payment_status !== 'paid') {
            $orders->transition($order, 'cancelled', 'Cancelled by customer before payment.', null, true);
        }
        return redirect()->route('cart')->with('success', 'Your order was cancelled. Your items are no longer reserved.');
    }

    public function webhook(Request $request)
    {
        $result = $this->payments->handleWebhook($request->getContent(), $request->header('X-Razorpay-Signature'), $request->header('X-Razorpay-Event-Id'));
        return $result === 'invalid' ? response('invalid signature', 400) : response()->json(['status' => $result]);
    }
}
