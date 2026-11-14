<?php

namespace App\Http\Controllers;

use App\Mail\ContactReceived;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show()
    {
        return view('store.contact');
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'max:150'],
            'message' => ['required', 'string', 'min:5', 'max:3000'], 'website' => ['nullable', 'max:0'], // honeypot
        ]);
        $m = ContactMessage::create(['name' => $d['name'], 'email' => $d['email'], 'message' => $d['message'], 'ip' => $request->ip(), 'source' => 'contact']);
        $this->notify($m);
        return redirect()->route('contact')->with('success', 'Thank you — your message has been sent. We will get back to you soon.');
    }

    public static function notify(ContactMessage $m): void
    {
        $to = trim((string) setting('contact_recipient', setting('store_email', 'adityapagariya19@gmail.com')));
        if ($to === '') {
            $to = 'adityapagariya19@gmail.com';
        }
        try {
            Mail::to($to)->send(new ContactReceived($m));
            logger()->info("Contact form message notification sent to: {$to}");
        } catch (\Throwable $e) {
            logger()->warning('Contact notification failed: '.$e->getMessage());
        }
    }
}
