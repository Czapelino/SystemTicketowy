<?php

namespace App\Mail;

use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgentReplyNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public Message $agentMessage
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nowa odpowiedź w zgłoszeniu {$this->ticket->ticket_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.agent-reply',
        );
    }
}
