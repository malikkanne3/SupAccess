<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NotificationMail extends Mailable
{
    public function __construct(
        public string $nom,
        public string $texte,
        public ?string $lien = null,
        public string $objet = 'SupAccess : nouvelle notification',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->objet);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notification');
    }
}
