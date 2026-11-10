<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesStore;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase, MakesStore;

    public function test_customer_can_only_view_own_orders(): void
    {
        $p = $this->product();
        $alice = $this->customer(); $bob = $this->customer();
        $mine = $this->order([[$p, 1]], ['user_id' => $alice->id]);
        $theirs = $this->order([[$p, 1]], ['user_id' => $bob->id]);
        $this->actingAs($alice);
        $this->get(route('account.order', $mine))->assertOk();
        $this->get(route('account.order', $theirs))->assertNotFound();
        $this->post(route('account.order.cancel', $theirs))->assertNotFound();
        $this->get(route('account.orders'))->assertOk()->assertSee((string) $mine->order_number)->assertDontSee((string) $theirs->order_number);
    }

    public function test_order_confirmation_requires_owner_or_key(): void
    {
        $p = $this->product();
        $o = $this->order([[$p, 1]], ['user_id' => $this->customer()->id]);
        $this->get(route('order.received', $o))->assertNotFound();
        $this->get(route('order.received', ['order' => $o->id, 'key' => 'wrong']))->assertNotFound();
        $this->get(route('order.received', ['order' => $o->id, 'key' => $o->order_key]))->assertOk()->assertSee((string) $o->order_number);
    }

    public function test_account_pages_require_login(): void
    {
        foreach (['', '/orders', '/addresses', '/details', '/downloads'] as $u) { $this->get('/my-account'.$u)->assertRedirect(route('login')); }
    }

    public function test_login_and_inactive_block(): void
    {
        $u = $this->customer(['email' => 'new@test.test']);
        $this->post(route('login.store'), ['username' => 'new@test.test', 'password' => 'wrong'])->assertSessionHas('login_error');
        $u->update(['is_active' => false]);
        $this->post(route('login.store'), ['username' => 'new@test.test', 'password' => 'Password123'])->assertSessionHas('login_error');
        $u->update(['is_active' => true]);
        $this->post(route('login.store'), ['username' => 'new@test.test', 'password' => 'Password123'])->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($u);
    }

    public function test_customer_cancels_only_eligible_orders_and_stock_is_released(): void
    {
        $p = $this->product(['stock_qty' => 4]);
        $u = $this->customer();
        $o = $this->order([[$p, 2]], ['user_id' => $u->id, 'status' => 'pending_payment', 'payment_status' => 'unpaid', 'stock_reserved' => true]);
        $this->actingAs($u)->post(route('account.order.cancel', $o))->assertSessionHas('success');
        $this->assertSame('cancelled', $o->fresh()->status);
        $this->assertSame(6, $p->fresh()->stock_qty);
        $paid = $this->order([[$p, 1]], ['user_id' => $u->id]);
        $this->post(route('account.order.cancel', $paid))->assertSessionHas('error');
        $this->assertSame('processing', $paid->fresh()->status);
    }

    public function test_address_book_is_private(): void
    {
        $a = $this->customer(); $b = $this->customer();
        $addr = $a->addresses()->create(['type' => 'shipping', 'first_name' => 'A', 'last_name' => 'B', 'line1' => 'x', 'city' => 'c', 'state' => 'Maharashtra', 'postcode' => '431005', 'country' => 'IN']);
        $this->actingAs($b)->delete(route('account.addresses.destroy', $addr))->assertNotFound();
        $this->assertDatabaseHas('addresses', ['id' => $addr->id]);
    }

    public function test_password_change_requires_current_password(): void
    {
        $u = $this->customer();
        $this->actingAs($u)->put(route('account.details.update'), ['name' => 'N', 'email' => $u->email, 'current_password' => 'nope', 'new_password' => 'NewPassw0rd', 'new_password_confirmation' => 'NewPassw0rd'])->assertSessionHasErrors('current_password');
        $this->put(route('account.details.update'), ['name' => 'N', 'email' => $u->email, 'current_password' => 'Password123', 'new_password' => 'NewPassw0rd', 'new_password_confirmation' => 'NewPassw0rd'])->assertSessionHas('success');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPassw0rd', $u->fresh()->password));
    }
}
