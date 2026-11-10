<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use App\Support\StoreException;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(private RazorpayService $razorpay, private InventoryService $inventory, private OrderService $orders)
    {
    }

    /**
     * @param array<int,int> $qtyByItem order_item_id => qty being refunded
     * @param ?float $amount  null => sum of refunded lines' unit prices
     */
    public function refund(Order $order, array $qtyByItem, ?float $amount, bool $restock, ?string $reason, User $admin): Refund
    {
        if (! in_array($order->payment_status, ['paid', 'partially_refunded'], true)) {
            throw new StoreException('Only paid orders can be refunded.');
        }

        $refund = DB::transaction(function () use ($order, $qtyByItem, $amount, $restock, $reason, $admin) {
            $o = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $items = $o->items()->get()->keyBy('id');
            $sum = 0;
            $clean = [];
            foreach ($qtyByItem as $itemId => $qty) {
                $qty = (int) $qty;
                if ($qty <= 0) continue;
                $item = $items->get((int) $itemId);
                if (! $item || $qty > $item->refundableQty()) {
                    throw new StoreException('Refund quantity exceeds what was purchased.');
                }
                $sum += to_paise($item->unit_price) * $qty;
                $clean[$item->id] = $qty;
            }
            $amountP = $amount !== null ? to_paise($amount) : $sum;
            $remaining = to_paise($o->total) - to_paise($o->refunded_total);
            if ($amountP <= 0 || $amountP > $remaining) {
                throw new StoreException('Refund amount must be between 0.01 and '.money(from_paise($remaining)).'.');
            }

            $payment = $o->payments()->where('status', 'captured')->latest('id')->first();
            $providerRefundId = null;
            $status = 'recorded';
            if ($o->payment_method === 'razorpay') {
                if (! $payment?->provider_payment_id) {
                    throw new StoreException('No captured Razorpay payment found for this order.');
                }
                $res = $this->razorpay->refund($payment->provider_payment_id, $amountP, ['order_number' => (string) $o->order_number]);
                $providerRefundId = $res['id'] ?? null;
                $status = 'processed';
            } // COD: cash is returned manually; the refund is recorded for accounting.

            foreach ($clean as $itemId => $qty) {
                $items[$itemId]->increment('refunded_qty', $qty);
            }
            $refund = $o->refunds()->create([
                'payment_id' => $payment?->id, 'amount' => from_paise($amountP), 'reason' => $reason, 'restock' => $restock,
                'items' => collect($clean)->map(fn ($q, $id) => ['order_item_id' => $id, 'qty' => $q])->values()->all(),
                'provider_refund_id' => $providerRefundId, 'status' => $status, 'admin_id' => $admin->id,
            ]);

            $newRefunded = to_paise($o->refunded_total) + $amountP;
            $full = $newRefunded >= to_paise($o->total);
            $o->update(['refunded_total' => from_paise($newRefunded), 'payment_status' => $full ? 'refunded' : 'partially_refunded']);
            if ($restock && $clean) {
                $this->inventory->release($o, $clean);
            }
            $o->histories()->create(['from_status' => $o->status, 'to_status' => $o->status, 'admin_id' => $admin->id,
                'note' => 'Refund of '.money(from_paise($amountP)).' recorded'.($reason ? ': '.$reason : '.')]);
            return $refund;
        });

        $fresh = $order->fresh();
        if ($fresh->payment_status === 'refunded') {
            $this->orders->transition($fresh, 'refunded', 'Order fully refunded.', $admin, true);
        }
        PopularProductsService::forget();
        return $refund;
    }
}
