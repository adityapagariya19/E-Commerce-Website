<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Additive only: safe to run on an existing database.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->string('courier_name', 100)->nullable()->after('shipping_method');
            $t->string('tracking_number', 100)->nullable()->after('courier_name');
            $t->string('tracking_url', 500)->nullable()->after('tracking_number');
            $t->timestamp('shipped_at')->nullable()->after('paid_at');
            $t->timestamp('delivered_at')->nullable()->after('shipped_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['courier_name', 'tracking_number', 'tracking_url', 'shipped_at', 'delivered_at']));
    }
};
