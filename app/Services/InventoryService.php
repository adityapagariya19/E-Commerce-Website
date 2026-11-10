<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\StoreException;

/** Stock is reserved (decremented) when an order is created and released when it is cancelled/failed. Idempotent via orders.stock_reserved. */
class InventoryService
{
    /** Must be called inside a DB transaction. */
    public function reserve(Order $order): void
    {
        if ($order->stock_reserved) {
            return;
        }
        foreach ($order->items()->get() as $item) {
            if (! $item->product_id) {
                continue;
            }
            $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
            if (! $product || ! $product->manage_stock) {
                continue;
            }
            if ($item->variant_id) {
                $variant = ProductVariant::whereKey($item->variant_id)->lockForUpdate()->first();
                if (! $variant || $variant->stock_qty < $item->qty) {
                    throw new StoreException("Sorry, \"{$item->name}\" no longer has enough stock.");
                }
                $variant->decrement('stock_qty', $item->qty);
            } else {
                if ($product->stock_qty < $item->qty) {
                    throw new StoreException("Sorry, \"{$item->name}\" no longer has enough stock.");
                }
                $product->decrement('stock_qty', $item->qty);
            }
        }
        $order->update(['stock_reserved' => true]);
    }

    public function release(Order $order, ?array $onlyQty = null): void
    {
        if (! $order->stock_reserved && $onlyQty === null) {
            return;
        }
        foreach ($order->items()->get() as $item) {
            $qty = $onlyQty[$item->id] ?? ($onlyQty === null ? $item->qty - $item->refunded_qty : 0);
            if ($qty <= 0 || ! $item->product_id) {
                continue;
            }
            $product = Product::find($item->product_id);
            if (! $product || ! $product->manage_stock) {
                continue;
            }
            $item->variant_id ? ProductVariant::whereKey($item->variant_id)->increment('stock_qty', $qty) : $product->increment('stock_qty', $qty);
        }
        if ($onlyQty === null) {
            $order->update(['stock_reserved' => false]);
        }
    }
}
