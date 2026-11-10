<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WelcomeCustomer extends Mailable
{
    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to '.setting('store_name', 'JB Optician'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.welcome');
    }
}
