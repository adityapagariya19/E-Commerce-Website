<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactReceived extends Mailable
{
    public function __construct(public ContactMessage $contactMessage)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New website message from '.$this->contactMessage->name, replyTo: [$this->contactMessage->email]);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contact-received');
    }
}
