<?php

namespace Tests\Feature;

use App\Services\PopularProductsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesStore;
use Tests\TestCase;

class PopularProductsTest extends TestCase
{
    use RefreshDatabase, MakesStore;

    private function ranked(bool $demo = false): array
    {
        PopularProductsService::forget();
        $r = app(PopularProductsService::class)->get($demo);
        return $r['items']->where('is_fallback', false)->map(fn ($i) => [$i['product']->id, $i['units']])->values()->all();
    }

    public function test_ranks_by_units_in_rolling_window(): void
    {
        $a = $this->product(); $b = $this->product(); $c = $this->product();
        $this->order([[$a, 1], [$b, 3]]);
        $this->order([[$c, 2]]);
        $this->order([[$a, 1]]);
        $this->order([[$c, 9]], ['placed_at' => now()->subDays(8)]); // outside window
        $this->assertSame([[$b->id, 3], [$a->id, 2], [$c->id, 2]], $this->ranked());
    }

    public function test_tie_break_is_revenue_then_recency_then_id(): void
    {
        $cheap = $this->product(['price' => 100]); $dear = $this->product(['price' => 900]);
        $this->order([[$cheap, 2]]);
        $this->order([[$dear, 2]]);
        $this->assertSame($dear->id, $this->ranked()[0][0]);
    }

    public function test_excludes_cancelled_failed_pending_on_hold_refunded_and_demo(): void
    {
        $p = $this->product();
        foreach (['cancelled', 'failed', 'pending_payment', 'on_hold', 'refunded'] as $s) {
            $this->order([[$p, 5]], ['status' => $s]);
        }
        $this->order([[$p, 4]], ['is_demo' => true]);
        $this->assertSame([], $this->ranked(false));
        $this->assertSame([[$p->id, 4]], $this->ranked(true)); // demo only when explicitly included
    }

    public function test_counts_shipped_completed_and_cod_processing(): void
    {
        $p = $this->product();
        $this->order([[$p, 1]], ['status' => 'shipped']);
        $this->order([[$p, 1]], ['status' => 'completed']);
        $this->order([[$p, 1]], ['status' => 'processing', 'payment_method' => 'cod', 'payment_status' => 'cod_pending']);
        $this->assertSame([[$p->id, 3]], $this->ranked());
    }

    public function test_refunded_quantity_is_subtracted(): void
    {
        $p = $this->product(); $q = $this->product();
        $o = $this->order([[$p, 3]]);
        $o->items()->first()->update(['refunded_qty' => 3]);
        $this->order([[$q, 1]]);
        $this->assertSame([[$q->id, 1]], $this->ranked());
    }

    public function test_variants_roll_up_to_parent_product(): void
    {
        $p = $this->product(['type' => 'variable']);
        $v1 = $p->variants()->create(['label' => 'S', 'price' => 1000, 'stock_qty' => 5]);
        $v2 = $p->variants()->create(['label' => 'L', 'price' => 1000, 'stock_qty' => 5]);
        $o = $this->order([[$p, 1]]);
        $o->items()->first()->update(['variant_id' => $v1->id]);
        $o2 = $this->order([[$p, 2]]);
        $o2->items()->first()->update(['variant_id' => $v2->id]);
        $this->assertSame([[$p->id, 3]], $this->ranked());
    }

    public function test_falls_back_with_label_when_no_sales(): void
    {
        $this->product(['is_featured' => true]);
        PopularProductsService::forget();
        $r = app(PopularProductsService::class)->get(false);
        $this->assertSame('fallback', $r['source']);
        $this->assertTrue($r['items']->every(fn ($i) => $i['is_fallback']));
    }

    public function test_updates_after_new_sale_when_cache_is_cleared_by_status_change(): void
    {
        $p = $this->product();
        $o = $this->order([[$p, 1]], ['status' => 'pending_payment', 'payment_status' => 'unpaid']);
        $this->assertSame([], $this->ranked());
        app(PopularProductsService::class)->get(false); // warm cache
        app(\App\Services\PaymentService::class)->markPaid($o, 'pay_pop', null);
        $r = app(PopularProductsService::class)->get(false);
        $this->assertSame('sales', $r['source']);
        $this->assertSame($p->id, $r['items']->first()['product']->id);
        $this->assertFalse($r['items']->first()['is_fallback']);
    }

    public function test_homepage_renders_section(): void
    {
        $this->product();
        $this->get('/')->assertOk()->assertSee('Popular This Week');
    }
}
