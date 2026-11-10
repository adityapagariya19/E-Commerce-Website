<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\NewsletterSubscriber;
use App\Services\OmnisendService;
use App\Support\Slug;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function index(Request $request, OmnisendService $omnisend)
    {
        $subs = NewsletterSubscriber::when($request->filled('q'), fn ($q) => $q->where('email', 'like', '%'.addcslashes($request->query('q'), '%_\\').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->latest()->paginate(25)->withQueryString();
        return view('admin.newsletter.index', [
            'subs' => $subs, 'omnisend' => $omnisend->enabled(),
            'stats' => ['total' => NewsletterSubscriber::count(), 'subscribed' => NewsletterSubscriber::where('status', 'subscribed')->count(), 'with_phone' => NewsletterSubscriber::whereNotNull('phone')->count()],
        ]);
    }

    /** Works with no external service: plain CSV straight from the database. */
    public function export(Request $request)
    {
        ActivityLog::record('newsletter.export');
        $status = $request->query('status');
        return response()->streamDownload(function () use ($status) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'phone', 'status', 'source', 'subscribed_at', 'unsubscribed_at']);
            NewsletterSubscriber::when($status, fn ($q) => $q->where('status', $status))->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [Slug::csv($r->email), Slug::csv($r->phone), $r->status, $r->source, $r->subscribed_at, $r->unsubscribed_at]);
                }
            });
            fclose($out);
        }, 'newsletter-subscribers-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function toggle(NewsletterSubscriber $subscriber)
    {
        $on = $subscriber->status !== 'subscribed';
        $subscriber->update(['status' => $on ? 'subscribed' : 'unsubscribed', 'subscribed_at' => $on ? now() : $subscriber->subscribed_at, 'unsubscribed_at' => $on ? null : now()]);
        return back()->with('success', 'Subscriber '.($on ? 'resubscribed' : 'unsubscribed').'.');
    }

    public function syncOmnisend(OmnisendService $omnisend)
    {
        if (! $omnisend->enabled()) {
            return back()->with('error', 'Omnisend is not configured. Add an API key in Settings (optional).');
        }
        [$ok, $fail] = $omnisend->retryPending(200);
        return back()->with($fail ? 'error' : 'success', "Omnisend sync finished: {$ok} sent, {$fail} failed.");
    }
}
