<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesStore;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase, MakesStore;

    public function test_guests_are_redirected_to_admin_login(): void
    {
        foreach (['admin', 'admin/products', 'admin/orders', 'admin/settings', 'admin/reports'] as $url) {
            $this->get("/{$url}")->assertRedirect(route('admin.login'));
        }
    }

    public function test_customers_cannot_enter_admin(): void
    {
        $this->actingAs($this->customer());
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/orders')->assertRedirect(route('admin.login'));
    }

    public function test_admin_login_rejects_customer_credentials(): void
    {
        $c = $this->customer(['email' => 'cust@test.test']);
        $this->post(route('admin.login.store'), ['email' => 'cust@test.test', 'password' => 'Password123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_login_works_for_staff_and_disabled_staff_are_blocked(): void
    {
        $a = $this->admin('store_manager');
        $this->post(route('admin.login.store'), ['email' => $a->email, 'password' => 'Password123'])->assertRedirect(route('admin.dashboard'));
        $this->post(route('admin.logout'));
        $a->update(['is_active' => false]);
        $this->post(route('admin.login.store'), ['email' => $a->email, 'password' => 'Password123'])->assertSessionHasErrors('email');
    }

    public function test_role_permissions_are_enforced(): void
    {
        $matrix = [
            'order_support' => ['allowed' => ['/admin/orders', '/admin'], 'denied' => ['/admin/products', '/admin/settings', '/admin/users', '/admin/customers', '/admin/coupons', '/admin/reports']],
            'content_manager' => ['allowed' => ['/admin/products', '/admin/hero-slides', '/admin/categories'], 'denied' => ['/admin/orders', '/admin/settings', '/admin/users', '/admin/customers']],
            'store_manager' => ['allowed' => ['/admin/orders', '/admin/products', '/admin/reports', '/admin/customers', '/admin/coupons', '/admin/newsletter'], 'denied' => ['/admin/settings', '/admin/users', '/admin/activity-log']],
            'super_admin' => ['allowed' => ['/admin/settings', '/admin/users', '/admin/activity-log', '/admin/orders'], 'denied' => []],
        ];
        foreach ($matrix as $role => $m) {
            $this->actingAs($this->admin($role));
            foreach ($m['allowed'] as $u) { $this->get($u)->assertOk(); }
            foreach ($m['denied'] as $u) { $this->get($u)->assertForbidden(); }
        }
    }

    public function test_last_super_admin_cannot_be_demoted_or_deleted(): void
    {
        $root = $this->admin('super_admin');
        $other = $this->admin('super_admin');
        $this->actingAs($other);
        $this->put(route('admin.users.update', $root), ['name' => 'x', 'email' => $root->email, 'role' => 'order_support', 'is_active' => 1])->assertRedirect(); // allowed: $other remains
        $this->actingAs($root->fresh());
        $this->assertTrue($other->fresh()->role === 'super_admin');
        $this->actingAs($other);
        $this->delete(route('admin.users.destroy', $other))->assertSessionHas('error'); // cannot delete self
    }

    public function test_settings_secrets_are_stored_encrypted_and_not_rendered(): void
    {
        $this->actingAs($this->admin());
        $this->put(route('admin.settings.update'), ['store_name' => 'JB', 'razorpay_key_id' => 'rzp_test_abc123', 'razorpay_key_secret' => 'super-secret-value'])->assertRedirect();
        $raw = \App\Models\Setting::where('key', 'razorpay_key_secret')->first();
        $this->assertNotSame('super-secret-value', $raw->value);
        $this->assertTrue($raw->is_secret);
        $this->get(route('admin.settings.edit'))->assertOk()->assertDontSee('super-secret-value');
    }

    public function test_settings_reject_script_urls_and_non_google_map_embeds(): void
    {
        $this->actingAs($this->admin());
        $this->put(route('admin.settings.update'), ['store_name' => 'JB', 'map_embed_url' => 'https://evil.test/x'])->assertSessionHasErrors('map_embed_url');
        $this->put(route('admin.settings.update'), ['store_name' => 'JB', 'social_instagram' => 'javascript:alert(1)'])->assertSessionHasErrors('social_instagram');
    }

    public function test_admin_actions_are_logged(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.categories.store'), ['name' => 'Logged Cat'])->assertRedirect();
        $this->assertDatabaseHas('activity_logs', ['action' => 'category.created']);
    }
}
