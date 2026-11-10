<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('description')->nullable();
            $t->string('type', 10)->default('percent'); // percent | fixed
            $t->decimal('value', 10, 2);
            $t->decimal('min_subtotal', 10, 2)->nullable();
            $t->decimal('max_discount', 10, 2)->nullable();
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->unsignedInteger('usage_limit')->nullable();
            $t->unsignedInteger('per_user_limit')->nullable();
            $t->json('product_ids')->nullable();   // restrict to these products (null = all)
            $t->json('category_ids')->nullable();  // restrict to these categories (null = all)
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('carts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('token', 64)->nullable()->unique();
            $t->string('coupon_code', 50)->nullable();
            $t->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $t->unsignedInteger('qty')->default(1);
            $t->timestamps();
            $t->index(['cart_id', 'product_id', 'variant_id']);
        });

        Schema::create('addresses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type', 10)->default('shipping'); // billing | shipping
            $t->string('first_name');
            $t->string('last_name');
            $t->string('company')->nullable();
            $t->string('line1');
            $t->string('line2')->nullable();
            $t->string('city');
            $t->string('state');
            $t->string('postcode', 12);
            $t->string('country', 2)->default('IN');
            $t->string('phone', 30)->nullable();
            $t->timestamps();
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('order_number')->unique();
            $t->string('order_key', 40)->unique();            // lets guests view their own confirmation
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('checkout_token', 64)->nullable()->unique(); // duplicate-submit guard
            $t->string('email');
            $t->string('phone', 30)->nullable();
            $t->string('status', 20)->default('pending_payment');
            $t->string('payment_status', 20)->default('unpaid'); // unpaid|cod_pending|paid|failed|partially_refunded|refunded
            $t->string('payment_method', 20);                    // cod | razorpay
            $t->decimal('subtotal', 12, 2);
            $t->decimal('discount_total', 12, 2)->default(0);
            $t->decimal('shipping_total', 12, 2)->default(0);
            $t->decimal('tax_total', 12, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->decimal('refunded_total', 12, 2)->default(0);
            $t->string('currency', 3)->default('INR');
            $t->string('coupon_code', 50)->nullable();
            $t->string('shipping_method')->nullable();
            $t->json('billing');
            $t->json('shipping');
            $t->text('customer_note')->nullable();
            $t->boolean('stock_reserved')->default(false);
            $t->boolean('is_demo')->default(false);
            $t->timestamp('placed_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'placed_at']);
            $t->index('is_demo');
            $t->index('email');
        });

        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $t->string('name');                 // snapshots: survive product edits/deletes
            $t->string('sku', 80)->nullable();
            $t->string('variant_label')->nullable();
            $t->string('image')->nullable();
            $t->decimal('unit_price', 10, 2);
            $t->unsignedInteger('qty');
            $t->decimal('line_total', 12, 2);
            $t->unsignedInteger('refunded_qty')->default(0);
            $t->timestamps();
            $t->index('product_id');
        });

        Schema::create('order_status_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('from_status', 20)->nullable();
            $t->string('to_status', 20);
            $t->text('note')->nullable();
            $t->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('coupon_usages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('email');
            $t->decimal('amount', 10, 2);
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('provider', 20);                        // razorpay | cod
            $t->string('provider_order_id')->nullable()->index();
            $t->string('provider_payment_id')->nullable()->unique();
            $t->string('status', 20)->default('created');       // created|authorized|captured|failed|refunded
            $t->decimal('amount', 12, 2);
            $t->string('currency', 3)->default('INR');
            $t->string('method')->nullable();
            $t->string('failure_reason')->nullable();
            $t->timestamps();
        });

        Schema::create('payment_events', function (Blueprint $t) {
            $t->id();
            $t->string('provider', 20);
            $t->string('event_id')->unique();                  // idempotency key
            $t->string('event_type');
            $t->json('payload')->nullable();                    // card/vpa data is never stored: see RazorpayService::sanitize
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('refunds', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $t->decimal('amount', 12, 2);
            $t->string('reason')->nullable();
            $t->boolean('restock')->default(false);
            $t->json('items')->nullable();                      // [{order_item_id, qty}]
            $t->string('provider_refund_id')->nullable();
            $t->string('status', 20)->default('recorded');      // recorded | processed | failed
            $t->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['refunds', 'payment_events', 'payments', 'coupon_usages', 'order_status_histories', 'order_items', 'orders', 'addresses', 'cart_items', 'carts', 'coupons'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
