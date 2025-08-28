<?php
namespace App\Services;

class EmailService
{
    public function send(string $to, string $subject, string $body, array $attachments = []): void
    {
        // TODO: send email via SMTP
    }
}
