<?php

namespace App\Services;

use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * "Popular This Week" — see docs/popular-this-week.md for the full rule set.
 *  - Window: orders placed in the last 7 days (rolling).
 *  - Counted statuses: processing, shipped, completed. (pending_payment, on_hold, cancelled, failed, refunded are excluded.)
 *  - Refunds: units = qty - refunded_qty per line, so partial refunds reduce the count; fully refunded orders drop out.
 *  - Variants roll up to their parent product.
 *  - Ranking: units DESC, revenue DESC, most recent sale DESC, product id ASC (deterministic tie-break).
 *  - Demo orders (orders.is_demo = 1) count only when jbo.popular.include_demo is enabled.
 *  - If fewer than N products have sales, the remainder is filled with featured/newest products flagged is_fallback.
 */
class PopularProductsService
{
    public const CACHE_KEY = 'popular_this_week';

    public function get(?bool $includeDemo = null): array
    {
        $includeDemo ??= (bool) config('jbo.popular.include_demo');
        $key = self::CACHE_KEY.($includeDemo ? '.demo' : '.real');
        return Cache::remember($key, now()->addMinutes(config('jbo.popular.cache_minutes')), fn () => $this->compute($includeDemo));
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY.'.demo');
        Cache::forget(self::CACHE_KEY.'.real');
    }

    /** Raw ranking rows for any window (used by the admin "inspect ranking" page and reports). */
    public function ranking(CarbonInterface $from, ?CarbonInterface $to = null, bool $includeDemo = false, ?int $limit = null): Collection
    {
        $q = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereNotNull('oi.product_id')
            ->whereIn('o.status', config('jbo.popular.counted_statuses'))
            ->where('o.placed_at', '>=', $from)
            ->when($to, fn ($q) => $q->where('o.placed_at', '<=', $to))
            ->when(! $includeDemo, fn ($q) => $q->where('o.is_demo', false))
            ->selectRaw('oi.product_id, SUM(oi.qty - oi.refunded_qty) as units, SUM((oi.qty - oi.refunded_qty) * oi.unit_price) as revenue, MAX(o.placed_at) as last_sold, COUNT(DISTINCT o.id) as orders_count')
            ->groupBy('oi.product_id')
            ->havingRaw('SUM(oi.qty - oi.refunded_qty) > 0')
            ->orderByDesc('units')->orderByDesc('revenue')->orderByDesc('last_sold')->orderBy('oi.product_id');
        if ($limit) {
            $q->limit($limit);
        }
        return $q->get();
    }

    private function compute(bool $includeDemo): array
    {
        $limit = (int) config('jbo.popular.limit');
        $from = now()->subDays((int) config('jbo.popular.window_days'));
        $rows = $this->ranking($from, null, $includeDemo, $limit * 3);

        $products = Product::published()->with(['images', 'variants', 'categories'])->whereIn('id', $rows->pluck('product_id'))->get()->keyBy('id');
        $items = collect();
        foreach ($rows as $r) {
            if ($p = $products->get($r->product_id)) {
                $items->push(['product' => $p, 'units' => (int) $r->units, 'revenue' => (float) $r->revenue, 'is_fallback' => false]);
            }
            if ($items->count() >= $limit) {
                break;
            }
        }

        $fallbackUsed = false;
        if ($items->count() < $limit) {
            $fill = Product::published()->with(['images', 'variants', 'categories'])
                ->whereNotIn('id', $items->pluck('product.id'))
                ->orderByDesc('is_featured')->orderByDesc('published_at')->orderByDesc('id')
                ->limit($limit - $items->count())->get();
            $fallbackUsed = $fill->isNotEmpty();
            foreach ($fill as $p) {
                $items->push(['product' => $p, 'units' => 0, 'revenue' => 0.0, 'is_fallback' => true]);
            }
        }

        $source = $fallbackUsed ? ($items->where('is_fallback', false)->isEmpty() ? 'fallback' : 'mixed') : ($includeDemo ? 'sales_incl_demo' : 'sales');
        return ['items' => $items, 'source' => $source, 'from' => $from, 'include_demo' => $includeDemo];
    }
}
