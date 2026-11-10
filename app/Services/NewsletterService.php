<?php

namespace App\Services;

use App\Models\NewsletterSubscriber;
use Illuminate\Support\Str;

class NewsletterService
{
    public function __construct(private OmnisendService $omnisend)
    {
    }

    /** @return array{0: NewsletterSubscriber, 1: bool} subscriber and whether this was a new/re-activated signup */
    public function subscribe(string $email, ?string $phone = null, string $source = 'footer', ?string $ip = null): array
    {
        $email = strtolower(trim($email));
        $sub = NewsletterSubscriber::firstOrNew(['email' => $email]);
        $isNew = ! $sub->exists || $sub->status !== 'subscribed';
        if (! $sub->exists) {
            $sub->token = Str::random(32);
            $sub->source = $source;
        }
        $sub->fill(['status' => 'subscribed', 'phone' => $phone ?: $sub->phone, 'unsubscribed_at' => null]);
        if ($isNew) {
            $sub->subscribed_at = now();
            $sub->consent_at = now(); // explicit opt-in checkbox is required by the controller
            $sub->consent_ip = $ip;
            $sub->sync_status = 'pending';
        }
        $sub->save();
        $this->omnisend->sync($sub); // optional; failure is swallowed
        return [$sub, $isNew];
    }

    public function unsubscribe(string $token): bool
    {
        $sub = NewsletterSubscriber::where('token', $token)->first();
        if (! $sub) return false;
        $sub->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);
        $this->omnisend->sync($sub);
        return true;
    }
}
