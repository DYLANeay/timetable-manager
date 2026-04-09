<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $employee,
        public readonly string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre accès Horaires Socar',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.employee-invitation',
            with: [
                'employee' => $this->employee,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => config('app.frontend_url', 'http://localhost:5173') . '/login',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
