<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    public function send(string $to, string $subject, string $body, array $attachments = []): void
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = env('SMTP_HOST', 'localhost');
            $mail->Port = (int)env('SMTP_PORT', '587');
            $mail->Username = env('SMTP_USER', '');
            $mail->Password = env('SMTP_PASS', '');
            $mail->SMTPAuth = $mail->Username !== '';

            // Encryption selection: env SMTP_ENCRYPTION (ssl|tls|none); if not set, infer from port
            $enc = strtolower((string)env('SMTP_ENCRYPTION', ''));
            if ($enc === '') { $enc = ($mail->Port === 465) ? 'ssl' : 'tls'; }
            if ($enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($enc === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = false;
            }

            // From defaults to SMTP_USER if MAIL_FROM is missing
            $from = env('MAIL_FROM') ?: env('SMTP_USER', 'no-reply@example.com');
            $fromName = env('MAIL_FROM_NAME', 'Paystub');
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);
            foreach ($attachments as $a) {
                if (is_file($a)) { $mail->addAttachment($a); }
            }
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            // Optional debug
            $debug = (int)env('SMTP_DEBUG', '0');
            if ($debug > 0) { $mail->SMTPDebug = $debug; $mail->Debugoutput = function($str) { $GLOBALS['logger']->debug('[SMTP] '.$str); }; }
            $mail->send();
            if (isset($GLOBALS['logger'])) { $GLOBALS['logger']->info('Email sent', ['to'=>$to,'subject'=>$subject]); }
        } catch (Exception $e) {
            $GLOBALS['logger']->error('Email send failed: ' . $e->getMessage());
        }
    }
}
