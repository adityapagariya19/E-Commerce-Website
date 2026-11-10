<?php

namespace App\Console\Commands;

use App\Services\OmnisendService;
use Illuminate\Console\Command;

class SyncOmnisend extends Command
{
    protected $signature = 'jbo:sync-omnisend {--limit=200}';
    protected $description = 'Retry newsletter contacts that are not yet synced to Omnisend (max 5 attempts each).';

    public function handle(OmnisendService $omnisend): int
    {
        if (! $omnisend->enabled()) {
            $this->warn('Omnisend is not configured (add the API key in Admin > Settings). Nothing to do.');
            return self::SUCCESS;
        }
        [$ok, $fail] = $omnisend->retryPending((int) $this->option('limit'));
        $this->info("Omnisend: {$ok} synced, {$fail} failed.");
        return self::SUCCESS;
    }
}
