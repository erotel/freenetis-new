<?php

declare(strict_types=1);

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

final class Mailer_Lib
{
  private Mailer $mailer;
  private string $defaultFrom;

  public function __construct()
  {
    // Doporučení: dej to do Settings::get(...) nebo configu, jak máš ve FreenetISu zvyklosti.
    $host = Settings::get('smtp_host');         // např. smtp.pvfree.net
    $port = (int)Settings::get('smtp_port');    // 465 nebo 587
    $user = Settings::get('smtp_user');         // nebo prázdné
    $pass = Settings::get('smtp_pass');         // nebo prázdné
    $enc  = Settings::get('smtp_enc');          // 'ssl', 'tls', nebo '' (doporučuju 'tls' pro 587)
    $this->defaultFrom = (string)Settings::get('smtp_from'); // noreply@...

    // DSN skládání:
    // - pro 465: "smtps://user:pass@host:465"
    // - pro 587: "smtp://user:pass@host:587?encryption=tls"
    $dsnUser = rawurlencode((string)$user);
    $dsnPass = rawurlencode((string)$pass);

    if ($port === 465 || $enc === 'ssl') {
      $dsn = sprintf('smtps://%s:%s@%s:%d', $dsnUser, $dsnPass, $host, $port);
    } else {
      // default TLS
      $dsn = sprintf('smtp://%s:%s@%s:%d?encryption=tls', $dsnUser, $dsnPass, $host, $port);
    }

    // Pokud je SMTP bez auth, řeš takto:
    // $dsn = sprintf('smtp://%s:%d?encryption=tls', $host, $port);

    $transport = Transport::fromDsn($dsn);
    $this->mailer = new Mailer($transport);
  }

  public function send(
    string $to,
    string $subject,
    string $htmlBody,
    ?string $textBody = null,
    ?string $from = null,
    array $attachments = [] // [ ['path'=>..., 'name'=>..., 'mime'=>...], ... ]
  ): void {
    $email = (new Email())
      ->from(new Address($from ?: $this->defaultFrom))
      ->to($to)
      ->subject($subject)
      ->html($htmlBody);

    if ($textBody !== null) {
      $email->text($textBody);
    }

    foreach ($attachments as $a) {
      if (!empty($a['path'])) {
        $name = $a['name'] ?? null;
        $mime = $a['mime'] ?? null;
        $email->attachFromPath($a['path'], $name, $mime);
      }
    }

    $this->mailer->send($email);
  }
}
