<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class DatabaseTransport implements TransportInterface
{
    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        if (! $message instanceof Email) {
            return null;
        }

        $envelope ??= $message->getEnvelope();

        DB::table('mail_messages')->insert([
            'from_address' => $envelope->getSender()->getAddress(),
            'from_name' => $envelope->getSender()->getName(),
            'to_address' => collect($envelope->getRecipients())->map(fn ($r) => $r->getAddress())->implode(', '),
            'subject' => $message->getSubject(),
            'body' => $message->getHtmlBody() ?? $message->getBody(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return new SentMessage($message, $envelope);
    }

    public function __toString(): string
    {
        return 'database';
    }
}
