<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every figure comes from orders/order_items. Definitions (also shown in the admin UI):
 *  - Sale / revenue order: status in processing, shipped, completed. Revenue = order total - refunded_total.
 *  - Demo orders (is_demo) are excluded unless $includeDemo is true, and the UI labels them.
 */
class ReportService
{
    public function counted(): array { return config('jbo.popular.counted_statuses'); }

    private function base(CarbonInterface $from, CarbonInterface $to, bool $includeDemo)
    {
        return Order::query()->whereBetween('placed_at', [$from, $to])->when(! $includeDemo, fn ($q) => $q->where('is_demo', false));
    }

    public function summary(CarbonInterface $from, CarbonInterface $to, bool $includeDemo): array
    {
        $sales = $this->base($from, $to, $includeDemo)->whereIn('status', $this->counted());
        $revenue = (float) (clone $sales)->sum(DB::raw('total - refunded_total'));
        $orders = (clone $sales)->count();
        $units = (int) DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereBetween('o.placed_at', [$from, $to])->whereIn('o.status', $this->counted())
            ->when(! $includeDemo, fn ($q) => $q->where('o.is_demo', false))->sum(DB::raw('oi.qty - oi.refunded_qty'));
        return [
            'revenue' => $revenue, 'orders' => $orders, 'units' => $units,
            'aov' => $orders ? round($revenue / $orders, 2) : 0.0,
            'refunded' => (float) $this->base($from, $to, $includeDemo)->sum('refunded_total'),
            'pending_orders' => $this->base($from, $to, $includeDemo)->where('status', 'pending_payment')->count(),
        ];
    }

    public function ordersByStatus(CarbonInterface $from, CarbonInterface $to, bool $includeDemo): Collection
    {
        return $this->base($from, $to, $includeDemo)->select('status', DB::raw('COUNT(*) as c'), DB::raw('SUM(total) as t'))->groupBy('status')->get()
            ->mapWithKeys(fn ($r) => [$r->status => ['count' => (int) $r->c, 'total' => (float) $r->t]]);
    }

    public function salesSeries(CarbonInterface $from, CarbonInterface $to, bool $includeDemo): Collection
    {
        $rows = $this->base($from, $to, $includeDemo)->whereIn('status', $this->counted())
            ->select(DB::raw('DATE(placed_at) as d'), DB::raw('COUNT(*) as orders'), DB::raw('SUM(total - refunded_total) as revenue'))
            ->groupBy('d')->orderBy('d')->get()->keyBy('d');
        $out = collect();
        for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
            $k = $d->toDateString();
            $out->push(['date' => $k, 'orders' => (int) ($rows[$k]->orders ?? 0), 'revenue' => (float) ($rows[$k]->revenue ?? 0)]);
        }
        return $out;
    }

    public function productSales(CarbonInterface $from, CarbonInterface $to, bool $includeDemo, ?int $limit = null): Collection
    {
        $rows = app(PopularProductsService::class)->ranking($from, $to, $includeDemo, $limit);
        $names = Product::whereIn('id', $rows->pluck('product_id'))->pluck('name', 'id');
        return $rows->map(fn ($r) => ['product_id' => $r->product_id, 'name' => $names[$r->product_id] ?? '(deleted product)', 'units' => (int) $r->units,
            'revenue' => (float) $r->revenue, 'orders' => (int) $r->orders_count, 'last_sold' => $r->last_sold]);
    }

    public function inventory(): Collection
    {
        $rows = collect();
        foreach (Product::with('variants')->where('status', '!=', 'archived')->orderBy('name')->get() as $p) {
            if ($p->isVariable()) {
                foreach ($p->variants as $v) {
                    $rows->push(['product' => $p->name, 'sku' => $v->sku, 'label' => $v->label, 'stock' => $v->stock_qty, 'managed' => $p->manage_stock]);
                }
            } else {
                $rows->push(['product' => $p->name, 'sku' => $p->sku, 'label' => null, 'stock' => $p->stock_qty, 'managed' => $p->manage_stock]);
            }
        }
        return $rows;
    }
}
