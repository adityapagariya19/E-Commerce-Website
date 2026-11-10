<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $t) {
            $t->string('sync_status', 20)->default('pending')->after('synced_omnisend'); // pending | synced | failed | skipped
            $t->string('sync_error', 255)->nullable()->after('sync_status');
            $t->unsignedSmallInteger('sync_attempts')->default(0)->after('sync_error');
            $t->timestamp('synced_at')->nullable()->after('sync_attempts');
            $t->timestamp('consent_at')->nullable()->after('synced_at');
            $t->string('consent_ip', 45)->nullable()->after('consent_at');
            $t->index('sync_status');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $t) {
            $t->dropIndex(['sync_status']);
            $t->dropColumn(['sync_status', 'sync_error', 'sync_attempts', 'synced_at', 'consent_at', 'consent_ip']);
        });
    }
};
