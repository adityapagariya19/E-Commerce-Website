<?php

use Illuminate\Support\Facades\Schedule;

// Cancels stale "pending payment" orders and releases reserved stock.
Schedule::command('jbo:expire-pending-orders')->everyFifteenMinutes();
Schedule::command('jbo:sync-omnisend')->hourly(); // retries failed Omnisend syncs; no-op without an API key
