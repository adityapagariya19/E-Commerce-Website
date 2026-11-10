<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Safety net for databases created from an earlier build where the JSON column was called `attributes`
// (that name collides with Eloquent's own $attributes property). No-op on fresh/up-to-date databases.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'attributes') && ! Schema::hasColumn('products', 'specs')) {
            Schema::table('products', fn (Blueprint $t) => $t->renameColumn('attributes', 'specs'));
        }
    }

    public function down(): void
    {
        // intentionally empty
    }
};
