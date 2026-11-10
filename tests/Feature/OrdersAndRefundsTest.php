<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\PopularProductsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesStore;
use Tests\TestCase;

class OrdersAndRefundsTest extends TestCase
{
    use RefreshDatabase, MakesStore;

    public function test_admin_cannot_cancel_paid_order_or_make_illegal_transitions(): void
    {
        $p = $this->product();
        $o = $this->order([[$p, 1]]); // paid + processing
        $this->actingAs($this->admin());
        $this->post(route('admin.orders.status', $o), ['status' => 'cancelled'])->assertSessionHas('error');
        $this->post(route('admin.orders.status', $o), ['status' => 'pending_payment'])->assertSessionHas('error');
        $this->post(route('admin.orders.status', $o), ['status' => 'shipped', 'note' => 'Handed to courier'])->assertSessionHas('success');
        $this->assertSame('shipped', $o->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $o->id, 'to_status' => 'shipped', 'note' => 'Handed to courier']);
    }

    public function test_cod_order_completed_becomes_paid(): void
    {
        $p = $this->product();
        $o = $this->order([[$p, 1]], ['payment_method' => 'cod', 'payment_status' => 'cod_pending']);
        $this->actingAs($this->admin());
        $this->post(route('admin.orders.status', $o), ['status' => 'completed']);
        $this->assertSame('paid', $o->fresh()->payment_status);
    }

    public function test_cancelling_unpaid_cod_order_releases_stock_and_coupon(): void
    {
        $p = $this->product(['stock_qty' => 3]);
        $o = $this->order([[$p, 2]], ['payment_method' => 'cod', 'payment_status' => 'cod_pending', 'stock_reserved' => true]);
        $c = \App\Models\Coupon::create(['code' => 'X', 'type' => 'fixed', 'value' => 1, 'is_active' => true]);
        $o->couponUsages()->create(['coupon_id' => $c->id, 'email' => 'a@a.a', 'amount' => 1]);
        $this->actingAs($this->admin())->post(route('admin.orders.status', $o), ['status' => 'cancelled'])->assertSessionHas('success');
        $this->assertSame(5, $p->fresh()->stock_qty);
        $this->assertSame(0, $o->couponUsages()->count());
        $this->post(route('admin.orders.status', $o), ['status' => 'cancelled']); // idempotent: no double release
        $this->assertSame(5, $p->fresh()->stock_qty);
    }

    public function test_partial_razorpay_refund_updates_totals_popular_ranking_and_stock(): void
    {
        Http::fake(['api.razorpay.com/v1/payments/*/refund' => Http::response(['id' => 'rfnd_1'], 200)]);
        $p = $this->product(['price' => 1000, 'stock_qty' => 5]);
        $o = $this->order([[$p, 3]]);
        $o->payments()->create(['provider' => 'razorpay', 'provider_payment_id' => 'pay_R', 'status' => 'captured', 'amount' => 3000]);
        $item = $o->items()->first();
        $this->actingAs($this->admin())->post(route('admin.orders.refund', $o), ['qty' => [$item->id => 1], 'restock' => 1, 'reason' => 'Damaged'])->assertSessionHas('success');
        $o->refresh();
        $this->assertEquals(1000, (float) $o->refunded_total);
        $this->assertSame('partially_refunded', $o->payment_status);
        $this->assertSame(1, $item->fresh()->refunded_qty);
        $this->assertSame(6, $p->fresh()->stock_qty);
        PopularProductsService::forget();
        $this->assertSame(2, app(PopularProductsService::class)->get(false)['items']->first()['units']);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/payments/pay_R/refund') && $r['amount'] === 100000);
    }

    public function test_full_refund_marks_order_refunded_and_rejects_over_refund(): void
    {
        Http::fake(['api.razorpay.com/*' => Http::response(['id' => 'rfnd_2'], 200)]);
        $p = $this->product(['price' => 500]);
        $o = $this->order([[$p, 2]]);
        $o->payments()->create(['provider' => 'razorpay', 'provider_payment_id' => 'pay_F', 'status' => 'captured', 'amount' => 1000]);
        $this->actingAs($this->admin());
        $this->post(route('admin.orders.refund', $o), ['amount' => 5000])->assertSessionHas('error');
        $this->post(route('admin.orders.refund', $o), ['amount' => 1000])->assertSessionHas('success');
        $this->assertSame('refunded', $o->fresh()->status);
        $this->assertSame('refunded', $o->fresh()->payment_status);
        $this->post(route('admin.orders.refund', $o), ['amount' => 1])->assertSessionHas('error');
    }

    public function test_expire_command_cancels_stale_pending_orders_only(): void
    {
        $p = $this->product(['stock_qty' => 5]);
        $stale = $this->order([[$p, 1]], ['status' => 'pending_payment', 'payment_status' => 'unpaid', 'stock_reserved' => true, 'placed_at' => now()->subHours(3)]);
        $fresh = $this->order([[$p, 1]], ['status' => 'pending_payment', 'payment_status' => 'unpaid', 'stock_reserved' => true, 'placed_at' => now()->subMinutes(5)]);
        $this->artisan('jbo:expire-pending-orders')->assertSuccessful();
        $this->assertSame('cancelled', $stale->fresh()->status);
        $this->assertSame('pending_payment', $fresh->fresh()->status);
        $this->assertSame(6, $p->fresh()->stock_qty);
    }

    public function test_reports_and_exports_use_database_and_exclude_demo(): void
    {
        $p = $this->product(['price' => 1000]);
        $this->order([[$p, 2]]);
        $this->order([[$p, 5]], ['is_demo' => true]);
        $this->actingAs($this->admin('store_manager'));
        $this->get(route('admin.reports.index', ['apply' => 1, 'from' => now()->subDay()->toDateString(), 'to' => now()->toDateString()]))->assertOk()->assertSee('2,000.00')->assertDontSee('7,000.00');
        $csv = $this->get(route('admin.reports.export', ['type' => 'products', 'apply' => 1, 'demo' => 0]))->assertOk()->streamedContent();
        $this->assertStringContainsString($p->name, $csv);
        $this->assertStringContainsString(',2,', $csv);
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.reports.popular'))->assertOk()->assertSee('Rules in force');
    }
}
