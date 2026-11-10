<?php

namespace Tests\Unit;

use App\Models\Coupon;
use App\Models\Setting;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesStore;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase, MakesStore;

    private function lines(array $pairs)
    {
        return collect($pairs)->map(fn ($p) => ['product' => $p[0]->load('categories'), 'variant' => null, 'qty' => $p[1]]);
    }

    public function test_uses_sale_price_when_valid(): void
    {
        $p = $this->product(['price' => 2000, 'sale_price' => 1500]);
        $q = app(PricingService::class)->quote($this->lines([[$p, 2]]));
        $this->assertSame(3000.0, $q['subtotal']);
        $this->assertSame(3000.0, $q['total']);
    }

    public function test_ignores_sale_price_not_lower_than_regular(): void
    {
        $p = $this->product(['price' => 1000, 'sale_price' => 1200]);
        $this->assertSame(1000.0, app(PricingService::class)->quote($this->lines([[$p, 1]]))['subtotal']);
    }

    public function test_percent_coupon_with_cap(): void
    {
        Coupon::create(['code' => 'TEN', 'type' => 'percent', 'value' => 10, 'max_discount' => 150, 'is_active' => true]);
        $p = $this->product(['price' => 2000]);
        $q = app(PricingService::class)->quote($this->lines([[$p, 1]]), 'ten');
        $this->assertSame(150.0, $q['discount']);
        $this->assertSame(1850.0, $q['total']);
    }

    public function test_fixed_coupon_never_exceeds_subtotal(): void
    {
        Coupon::create(['code' => 'BIG', 'type' => 'fixed', 'value' => 5000, 'is_active' => true]);
        $p = $this->product(['price' => 300]);
        $q = app(PricingService::class)->quote($this->lines([[$p, 1]]), 'BIG');
        $this->assertSame(300.0, $q['discount']);
        $this->assertSame(0.0, $q['total']);
    }

    public function test_coupon_rejections(): void
    {
        $p = $this->product(['price' => 500]);
        Coupon::create(['code' => 'MIN', 'type' => 'fixed', 'value' => 50, 'min_subtotal' => 1000, 'is_active' => true]);
        Coupon::create(['code' => 'OLD', 'type' => 'fixed', 'value' => 50, 'ends_at' => now()->subDay(), 'is_active' => true]);
        Coupon::create(['code' => 'OFF', 'type' => 'fixed', 'value' => 50, 'is_active' => false]);
        $svc = app(PricingService::class);
        foreach (['MIN', 'OLD', 'OFF', 'NOPE'] as $code) {
            $q = $svc->quote($this->lines([[$p, 1]]), $code);
            $this->assertNotNull($q['coupon_error'], $code);
            $this->assertSame(0.0, $q['discount'], $code);
        }
    }

    public function test_coupon_restricted_to_category_only_discounts_matching_items(): void
    {
        $cat = $this->category('Sun');
        $a = $this->product(['price' => 1000]);
        $b = $this->product(['price' => 1000]);
        $a->categories()->attach($cat);
        Coupon::create(['code' => 'CAT', 'type' => 'percent', 'value' => 50, 'category_ids' => [$cat->id], 'is_active' => true]);
        $q = app(PricingService::class)->quote($this->lines([[$a, 1], [$b, 1]]), 'CAT');
        $this->assertSame(500.0, $q['discount']);
    }

    public function test_usage_limit_enforced(): void
    {
        $c = Coupon::create(['code' => 'ONE', 'type' => 'fixed', 'value' => 10, 'usage_limit' => 1, 'is_active' => true]);
        $p = $this->product(['price' => 500]);
        $o = $this->order([[$p, 1]]);
        $c->usages()->create(['order_id' => $o->id, 'email' => 'x@test.test', 'amount' => 10]);
        $this->assertNotNull(app(PricingService::class)->quote($this->lines([[$p, 1]]), 'ONE')['coupon_error']);
    }

    public function test_shipping_threshold_and_tax(): void
    {
        Setting::put('shipping_flat_rate', '99');
        Setting::put('shipping_free_threshold', '2000');
        Setting::put('tax_rate', '18');
        $cheap = $this->product(['price' => 500]);
        $big = $this->product(['price' => 2500]);
        $svc = app(PricingService::class);
        $q1 = $svc->quote($this->lines([[$cheap, 1]]));
        $this->assertSame(99.0, $q1['shipping']);
        $this->assertSame(90.0, $q1['tax']);
        $this->assertSame(689.0, $q1['total']);
        $q2 = $svc->quote($this->lines([[$big, 1]]));
        $this->assertSame(0.0, $q2['shipping']);
    }
}
