<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Support\StoreException;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private InventoryService $inventory)
    {
    }

    /**
     * Change an order's fulfilment status. Admin-initiated changes are checked against config('jbo.status_transitions');
     * system changes (payment, expiry) pass $system = true.
     */
    public function transition(Order $order, string $to, ?string $note = null, ?User $admin = null, bool $system = false): Order
    {
        return DB::transaction(function () use ($order, $to, $note, $admin, $system) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = $order->status;
            if ($from === $to) {
                return $order;
            }
            if (! array_key_exists($to, config('jbo.order_statuses'))) {
                throw new StoreException('Unknown order status.');
            }
            if (! $system && ! in_array($to, config("jbo.status_transitions.{$from}", []), true)) {
                throw new StoreException('Cannot change an order from '.order_status_label($from).' to '.order_status_label($to).'.');
            }
            // A captured online payment must be refunded explicitly so payment state and order state never disagree.
            if (! $system && $to === 'cancelled' && $order->payment_status === 'paid') {
                throw new StoreException('This order has been paid. Issue a refund instead of cancelling it.');
            }

            $updates = ['status' => $to];
            if (in_array($to, ['cancelled', 'failed'], true)) {
                $updates['cancelled_at'] = now();
                $this->inventory->release($order);
                $order->couponUsages()->delete(); // coupon use is returned
            }
            if (in_array($to, ['delivered', 'completed'], true)) {
                $updates['delivered_at'] = now();
                if ($order->payment_method === 'cod' && $order->payment_status === 'cod_pending') {
                    $updates['payment_status'] = 'paid'; // cash collected on delivery
                    $updates['paid_at'] = now();
                }
            }
            if ($to === 'shipped' && ! $order->shipped_at) {
                $updates['shipped_at'] = now();
            }
            $order->update($updates);
            $order->histories()->create(['from_status' => $from, 'to_status' => $to, 'note' => $note, 'admin_id' => $admin?->id]);
            PopularProductsService::forget();
            return $order;
        });
    }

    public function addNote(Order $order, string $note, ?User $admin): void
    {
        $order->histories()->create(['from_status' => $order->status, 'to_status' => $order->status, 'note' => $note, 'admin_id' => $admin?->id]);
    }
}
