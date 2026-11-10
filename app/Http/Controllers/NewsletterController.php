<?php

namespace App\Http\Controllers;

use App\Services\NewsletterService;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request, NewsletterService $newsletter)
    {
        $d = $request->validate(['email' => ['required', 'email', 'max:150'], 'phone' => ['nullable', 'regex:/^[0-9+\-\s]{10,15}$/'], 'website' => ['nullable', 'max:0'], 'consent' => ['accepted']], ['consent.accepted' => 'Please tick the box to confirm you want to receive our emails.']);
        $newsletter->subscribe($d['email'], $d['phone'] ?? null, $request->input('source', 'footer'), $request->ip());
        $msg = setting('newsletter_success_message', 'Thanks for subscribing!');
        return $request->expectsJson() ? response()->json(['ok' => true, 'message' => $msg]) : back()->with('newsletter_success', $msg);
    }

    public function unsubscribe(string $token, NewsletterService $newsletter)
    {
        $ok = $newsletter->unsubscribe($token);
        return redirect()->route('home')->with($ok ? 'success' : 'error', $ok ? 'You have been unsubscribed.' : 'That unsubscribe link is not valid.');
    }
}
