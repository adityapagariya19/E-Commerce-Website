<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;

class ExpirePendingOrders extends Command
{
    protected $signature = 'jbo:expire-pending-orders';
    protected $description = 'Cancel unpaid online-payment orders older than the configured window and release their stock.';

    public function handle(OrderService $orders): int
    {
        $cutoff = now()->subMinutes((int) config('jbo.pending_payment_expiry_minutes'));
        $n = 0;
        Order::where('status', 'pending_payment')->where('payment_method', 'razorpay')->where('placed_at', '<', $cutoff)->each(function ($o) use ($orders, &$n) {
            $orders->transition($o, 'cancelled', 'Expired: payment not completed in time.', null, true);
            $n++;
        });
        $this->info("Expired {$n} order(s).");
        return self::SUCCESS;
    }
}
