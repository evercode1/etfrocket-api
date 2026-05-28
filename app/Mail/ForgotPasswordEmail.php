<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $token;

    public string $email;

    /**
     * Create a new message instance.
     */
    public function __construct(string $token, string $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(

            subject: 'Etf Rocket Password Reset Request',

        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(

            markdown: 'mail.forgot-password.forgot-password-request',

            with: [

                'url' => env('FRONTEND_URL')

                    .'/auth/reset-password/'

                    .$this->token

                    .'?email='

                    .urlencode($this->email),

            ],

        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
