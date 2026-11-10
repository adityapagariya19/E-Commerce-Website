<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesStore;
use Tests\TestCase;

class CartAndCheckoutTest extends TestCase
{
    use RefreshDatabase, MakesStore;

    public function test_add_to_cart_validates_stock(): void
    {
        $p = $this->product(['stock_qty' => 2]);
        $this->guestCart();
        $this->postJson(route('cart.add'), ['product_id' => $p->id, 'qty' => 2])->assertOk();
        $this->postJson(route('cart.add'), ['product_id' => $p->id, 'qty' => 1])->assertStatus(422);
        $this->assertSame(2, (int) Cart::first()->items()->sum('qty'));
    }

    public function test_cart_ignores_browser_supplied_price(): void
    {
        $p = $this->product(['price' => 1000]);
        $this->guestCart([[$p, 1]]);
        $this->postJson(route('cart.add'), ['product_id' => $p->id, 'qty' => 1, 'price' => 1, 'unit_price' => 1])->assertOk();
        $this->get(route('cart'))->assertSee('2,000.00');
    }

    public function test_variable_product_requires_a_variant(): void
    {
        $p = $this->product(['type' => 'variable', 'price' => 900]);
        $v = $p->variants()->create(['label' => 'M', 'price' => 900, 'stock_qty' => 3, 'is_active' => true]);
        $this->guestCart();
        $this->postJson(route('cart.add'), ['product_id' => $p->id])->assertStatus(422);
        $this->postJson(route('cart.add'), ['product_id' => $p->id, 'variant_id' => $v->id, 'qty' => 5])->assertStatus(422);
        $this->postJson(route('cart.add'), ['product_id' => $p->id, 'variant_id' => $v->id, 'qty' => 3])->assertOk();
    }

    public function test_product_without_price_is_not_purchasable(): void
    {
        $p = $this->product(['price' => null]);
        $this->guestCart();
        $this->postJson(route('cart.add'), ['product_id' => $p->id])->assertStatus(422);
    }

    public function test_guest_cart_merges_into_member_cart_on_login(): void
    {
        $p = $this->product();
        $user = $this->customer(['email' => 'merge@test.test']);
        $mine = Cart::create(['user_id' => $user->id]);
        $mine->items()->create(['product_id' => $p->id, 'qty' => 1]);
        $this->guestCart([[$p, 2]], 'guest-tok');
        $this->post(route('login.store'), ['username' => 'merge@test.test', 'password' => 'Password123'])->assertRedirect();
        $this->assertSame(3, (int) $mine->fresh()->items()->sum('qty'));
        $this->assertNull(Cart::where('token', 'guest-tok')->first());
    }

    public function test_cod_checkout_creates_order_reserves_stock_and_clears_cart(): void
    {
        $p = $this->product(['price' => 800, 'stock_qty' => 5]);
        $cart = $this->guestCart([[$p, 2]]);
        $res = $this->post(route('checkout.store'), $this->checkoutData());
        $order = Order::firstOrFail();
        $res->assertRedirect(route('order.received', ['order' => $order->id, 'key' => $order->order_key]));
        $this->assertSame('processing', $order->status);
        $this->assertSame('cod_pending', $order->payment_status);
        $this->assertEquals(1600, (float) $order->total);
        $this->assertSame(3, $p->fresh()->stock_qty);
        $this->assertSame(0, $cart->items()->count());
    }

    public function test_duplicate_submit_returns_same_order(): void
    {
        $p = $this->product(['stock_qty' => 5]);
        $this->guestCart([[$p, 1]]);
        $data = $this->checkoutData();
        $this->post(route('checkout.store'), $data);
        $this->post(route('checkout.store'), $data);
        $this->assertSame(1, Order::count());
        $this->assertSame(4, $p->fresh()->stock_qty);
    }

    public function test_checkout_blocked_when_stock_changed(): void
    {
        $p = $this->product(['stock_qty' => 5]);
        $this->guestCart([[$p, 3]]);
        $p->update(['stock_qty' => 1]);
        $this->post(route('checkout.store'), $this->checkoutData())->assertSessionHas('error');
        $this->assertSame(0, Order::count());
    }

    public function test_checkout_validation(): void
    {
        $p = $this->product();
        $this->guestCart([[$p, 1]]);
        $this->post(route('checkout.store'), $this->checkoutData(['postcode' => '12', 'terms' => null, 'email' => 'bad']))
            ->assertSessionHasErrors(['postcode', 'terms', 'email']);
        $this->assertSame(0, Order::count());
    }

    public function test_razorpay_order_is_pending_and_never_paid_at_creation(): void
    {
        Http::fake(['api.razorpay.com/v1/orders' => Http::response(['id' => 'order_TEST123'], 200)]);
        $p = $this->product(['price' => 1000, 'stock_qty' => 5]);
        $this->guestCart([[$p, 1]]);
        $res = $this->post(route('checkout.store'), $this->checkoutData(['payment_method' => 'razorpay']));
        $order = Order::firstOrFail();
        $res->assertRedirect(route('checkout.pay', ['order' => $order->id, 'key' => $order->order_key]));
        $this->assertSame('pending_payment', $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('order_TEST123', $order->payments()->first()->provider_order_id);
        $this->assertSame(4, $p->fresh()->stock_qty); // reserved
    }

    public function test_coupon_applied_at_checkout_is_recorded_once(): void
    {
        \App\Models\Coupon::create(['code' => 'SAVE', 'type' => 'fixed', 'value' => 100, 'per_user_limit' => 1, 'is_active' => true]);
        $p = $this->product(['price' => 1000]);
        $cart = $this->guestCart([[$p, 1]]);
        $cart->update(['coupon_code' => 'SAVE']);
        $this->post(route('checkout.store'), $this->checkoutData());
        $o = Order::firstOrFail();
        $this->assertEquals(900, (float) $o->total);
        $this->assertSame(1, $o->couponUsages()->count());
    }
}
