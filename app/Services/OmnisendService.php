<?php

namespace App\Services;

use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Http;

/**
 * OPTIONAL connector. The native newsletter list works without it.
 * Request shape follows Omnisend's public v3 "contacts" API from memory — UNVERIFIED against a live account.
 */
class OmnisendService
{
    public function enabled(): bool
    {
        return (bool) $this->key();
    }

    private function key(): ?string
    {
        return setting('omnisend_api_key', config('services.omnisend.api_key')) ?: null;
    }

    /** Pushes one contact. Records status/attempts/error locally; never throws (a failed sync must not break signup). */
    public function sync(NewsletterSubscriber $s): bool
    {
        if (! $this->enabled()) {
            $s->update(['sync_status' => 'skipped', 'sync_error' => 'Omnisend API key not configured']);
            return false;
        }
        $s->increment('sync_attempts');
        try {
            $channels = ['email' => ['status' => $s->status === 'subscribed' ? 'subscribed' : 'unsubscribed', 'statusDate' => ($s->consent_at ?? now())->toIso8601String()]];
            $body = ['identifiers' => [['type' => 'email', 'id' => $s->email, 'channels' => $channels]], 'tags' => ['jb-optician-site']];
            $res = Http::baseUrl(config('services.omnisend.base_url'))->withHeaders(['X-API-KEY' => $this->key()])->acceptJson()->timeout(10)->post('/contacts', $body);
            // 409 = contact already exists in Omnisend → treat as in sync (no duplicate is created)
            $ok = $res->successful() || $res->status() === 409;
            $s->update($ok
                ? ['synced_omnisend' => true, 'sync_status' => 'synced', 'sync_error' => null, 'synced_at' => now()]
                : ['synced_omnisend' => false, 'sync_status' => 'failed', 'sync_error' => 'HTTP '.$res->status().' '.mb_substr((string) ($res->json('error') ?? $res->json('message') ?? ''), 0, 150)]);
            if (! $ok) {
                logger()->warning('Omnisend sync failed', ['status' => $res->status()]);
            }
            return $ok;
        } catch (\Throwable $e) {
            $s->update(['synced_omnisend' => false, 'sync_status' => 'failed', 'sync_error' => mb_substr($e->getMessage(), 0, 200)]);
            logger()->warning('Omnisend sync error: '.$e->getMessage());
            return false;
        }
    }

    /** Retry pending/failed contacts (max 5 attempts each). @return array{0:int,1:int} [sent, failed] */
    public function retryPending(int $limit = 200): array
    {
        $ok = $fail = 0;
        NewsletterSubscriber::whereIn('sync_status', ['pending', 'failed', 'skipped'])->where('sync_attempts', '<', 5)->orderBy('id')->limit($limit)->get()
            ->each(function ($s) use (&$ok, &$fail) { $this->sync($s) ? $ok++ : $fail++; });
        return [$ok, $fail];
    }
}
