<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/lib/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_email_smtp($to, string $subject, string $textBody, ?string $htmlBody = null, array $attachments = [], ?string $replyToEmail = null, ?string $replyToName = null): bool
{
    try {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;          // Gmail App Password
        $mail->SMTPSecure = SMTP_SECURE;        // 'tls' for 587
        $mail->Port       = SMTP_PORT;

        // From
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);

        if ($replyToEmail && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyToEmail, $replyToName ?: $replyToEmail);
        }

        // Recipients
        if (is_array($to)) {
            foreach ($to as $item) {
                if (is_array($item)) {
                    $email = (string)($item['email'] ?? '');
                    $name  = (string)($item['name'] ?? '');
                    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $mail->addAddress($email, $name);
                    }
                } else {
                    $email = (string)$item;
                    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $mail->addAddress($email);
                    }
                }
            }
        } else {
            $email = (string)$to;
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
            $mail->addAddress($email);
        }

        $mail->Subject = $subject;

        if ($htmlBody !== null) {
            $mail->isHTML(true);
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody;
        } else {
            $mail->isHTML(false);
            $mail->Body = $textBody;
        }

        // Attachments
        foreach ($attachments as $a) {
            $path = (string)($a['path'] ?? '');
            $name = (string)($a['name'] ?? '');
            if ($path !== '') {
                // allow relative paths from project root
                $fullPath = $path;
                if (!str_starts_with($path, '/') && file_exists(__DIR__ . '/' . $path)) {
                    $fullPath = __DIR__ . '/' . $path;
                }
                if (file_exists($fullPath)) {
                    $name ? $mail->addAttachment($fullPath, $name) : $mail->addAttachment($fullPath);
                }
            }
        }

        return $mail->send();
    } catch (Throwable $e) {
        error_log('send_email_smtp failed: ' . $e->getMessage());
        return false;
    }
}

