<?php

namespace Tests\Concerns;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;

trait MakesStore
{
    protected function product(array $o = []): Product
    {
        static $n = 0;
        $n++;
        return Product::create($o + [
            'name' => "Test Product {$n}", 'slug' => "test-product-{$n}-".Str::lower(Str::random(4)), 'sku' => "SKU{$n}".Str::random(3),
            'type' => 'simple', 'price' => 1000, 'stock_qty' => 10, 'manage_stock' => true, 'status' => 'published', 'published_at' => now()->subDay(),
        ]);
    }

    protected function admin(string $role = 'super_admin'): User
    {
        return User::create(['name' => 'Admin '.$role, 'email' => Str::random(8).'@test.test', 'password' => 'Password123', 'role' => $role, 'is_active' => true]);
    }

    protected function customer(array $o = []): User
    {
        return User::create($o + ['name' => 'Cust', 'email' => Str::random(8).'@test.test', 'password' => 'Password123', 'role' => 'customer', 'is_active' => true]);
    }

    /** @param array<int, array{0: Product, 1: int}> $items */
    protected function order(array $items, array $o = []): Order
    {
        $total = collect($items)->sum(fn ($i) => ($i[2] ?? $i[0]->price) * $i[1]);
        $addr = ['first_name' => 'T', 'last_name' => 'User', 'line1' => '1 St', 'city' => 'City', 'state' => 'Maharashtra', 'postcode' => '431005', 'country' => 'IN', 'phone' => '9999999999'];
        $order = Order::create($o + [
            'order_number' => Order::nextNumber(), 'order_key' => 'wc_order_'.Str::random(13), 'email' => 'buyer@test.test', 'status' => 'processing',
            'payment_status' => 'paid', 'payment_method' => 'razorpay', 'subtotal' => $total, 'total' => $total, 'billing' => $addr, 'shipping' => $addr,
            'placed_at' => now()->subHour(), 'is_demo' => false,
        ]);
        foreach ($items as $i) {
            $price = $i[2] ?? $i[0]->price;
            $order->items()->create(['product_id' => $i[0]->id, 'name' => $i[0]->name, 'unit_price' => $price, 'qty' => $i[1], 'line_total' => $price * $i[1]]);
        }
        return $order;
    }

    protected function guestCart(array $lines = [], string $token = 'tok-test'): Cart
    {
        $cart = Cart::create(['token' => $token]);
        foreach ($lines as [$product, $qty]) {
            $cart->items()->create(['product_id' => $product->id, 'qty' => $qty]);
        }
        $this->withSession(['cart_token' => $token]);
        return $cart;
    }

    protected function category(string $name): Category
    {
        return Category::create(['name' => $name, 'slug' => Str::slug($name).'-'.Str::lower(Str::random(3)), 'is_active' => true]);
    }

    protected function checkoutData(array $o = []): array
    {
        return $o + [
            'checkout_token' => (string) Str::uuid(), 'email' => 'buyer@test.test', 'first_name' => 'Asha', 'last_name' => 'Patil', 'country' => 'IN',
            'address_1' => '1 Main Road', 'city' => 'Chhatrapati Sambhajinagar', 'state' => 'Maharashtra', 'postcode' => '431005', 'phone' => '9876543210',
            'same_billing' => '1', 'payment_method' => 'cod', 'terms' => '1',
        ];
    }
}
