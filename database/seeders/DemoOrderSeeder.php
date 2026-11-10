<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DEMO ORDERS — every row has is_demo = 1 and a *@example.test e-mail. They exist so the dashboard, reports and
 * "Popular This Week" can be exercised locally. They are NOT customer purchases. Stock is not touched.
 * Skipped automatically in production.
 */
class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction() || Order::where('is_demo', true)->exists()) {
            return;
        }
        mt_srand(42);
        $products = Product::published()->where('type', 'simple')->whereNotNull('price')->get();
        if ($products->isEmpty()) {
            return;
        }
        $statuses = ['completed', 'completed', 'processing', 'processing', 'shipped', 'cancelled', 'failed', 'pending_payment'];
        $addr = ['first_name' => 'Demo', 'last_name' => 'Customer', 'company' => null, 'line1' => '1 Demo Street', 'line2' => null, 'city' => 'Chhatrapati Sambhajinagar', 'state' => 'Maharashtra', 'postcode' => '431005', 'country' => 'IN', 'phone' => '9999999999'];

        for ($n = 0; $n < 28; $n++) {
            $status = $statuses[mt_rand(0, count($statuses) - 1)];
            $when = now()->subDays(mt_rand(0, 13))->subMinutes(mt_rand(0, 1300));
            $cod = mt_rand(0, 1) === 1;
            $picked = $products->shuffle()->take(mt_rand(1, 3));
            $subtotal = 0;
            $lines = [];
            foreach ($picked as $p) {
                $unit = $p->pricing()['current'];
                $qty = mt_rand(1, 2);
                $subtotal += $unit * $qty;
                $lines[] = [$p, $unit, $qty];
            }
            $paid = in_array($status, ['completed', 'shipped', 'processing'], true) && ! $cod || $status === 'completed';
            $order = Order::create([
                'order_number' => Order::nextNumber(), 'order_key' => 'wc_order_'.Str::random(13), 'email' => "demo-buyer-{$n}@example.test", 'phone' => '9999999999',
                'status' => $status, 'payment_status' => $paid ? 'paid' : ($cod && $status !== 'failed' ? 'cod_pending' : ($status === 'failed' ? 'failed' : 'unpaid')),
                'payment_method' => $cod ? 'cod' : 'razorpay', 'subtotal' => $subtotal, 'discount_total' => 0, 'shipping_total' => 0, 'tax_total' => 0, 'total' => $subtotal,
                'billing' => $addr, 'shipping' => $addr, 'is_demo' => true, 'stock_reserved' => false, 'placed_at' => $when, 'paid_at' => $paid ? $when : null,
                'customer_note' => 'DEMO ORDER — not a real purchase',
            ]);
            foreach ($lines as [$p, $unit, $qty]) {
                $order->items()->create(['product_id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'unit_price' => $unit, 'qty' => $qty, 'line_total' => $unit * $qty, 'image' => $p->images->first()?->path]);
            }
            $order->histories()->create(['from_status' => null, 'to_status' => $status, 'note' => 'DEMO order seeded.']);
        }
    }
}
