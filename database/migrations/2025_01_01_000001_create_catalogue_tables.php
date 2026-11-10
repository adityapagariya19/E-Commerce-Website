<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->string('image')->nullable();
            $t->unsignedInteger('position')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('brands', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('logo')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('sku', 80)->nullable()->unique();
            $t->string('type', 20)->default('simple'); // simple | variable
            $t->text('short_description')->nullable();
            $t->longText('description')->nullable();
            $t->decimal('price', 10, 2)->nullable();       // null => "Read more" card, not purchasable (as in reference)
            $t->decimal('sale_price', 10, 2)->nullable();
            $t->integer('stock_qty')->default(0);
            $t->boolean('manage_stock')->default(true);
            $t->string('status', 20)->default('draft');   // draft | published | archived
            $t->boolean('is_featured')->default(false);
            $t->boolean('is_new_arrival')->default(false); // admin-managed New Arrivals flag
            $t->boolean('tryon_enabled')->default(false);  // shown on Try On page
            $t->json('specs')->nullable();                 // frame_shape, frame_color, size, gender, material
            $t->json('tags')->nullable();
            $t->string('meta_title')->nullable();
            $t->string('meta_description', 300)->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'published_at']);
            $t->index('is_new_arrival');
        });

        Schema::create('product_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->string('alt')->nullable();
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
        });

        Schema::create('category_product', function (Blueprint $t) {
            $t->foreignId('category_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->primary(['category_id', 'product_id']);
        });

        Schema::create('product_variants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('sku', 80)->nullable()->unique();
            $t->string('label');                 // e.g. "Black / Medium"
            $t->json('options')->nullable();     // {"colour":"Black","size":"Medium"}
            $t->decimal('price', 10, 2)->nullable();      // null => inherit product price
            $t->decimal('sale_price', 10, 2)->nullable();
            $t->integer('stock_qty')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('email');
            $t->unsignedTinyInteger('rating');
            $t->text('body');
            $t->string('status', 20)->default('pending'); // pending | approved | rejected
            $t->timestamps();
            $t->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['reviews', 'product_variants', 'category_product', 'product_images', 'products', 'brands', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
