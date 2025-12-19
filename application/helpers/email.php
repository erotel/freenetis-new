<?php defined('SYSPATH') or die('No direct script access.');

require_once __DIR__ . '/../../vendor/autoload.php';


use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Mime\Address;

/**
 * Email helper class (PHP 8 compatible, Symfony Mailer)
 */
class email
{
	protected static ?Mailer $mailer = null;

	protected static function getMailer(): Mailer
	{
		if (self::$mailer !== null) {
			return self::$mailer;
		}

		$host = Settings::get('email_hostname');
		$port = (int)Settings::get('email_port');
		$user = Settings::get('email_username');
		$pass = Settings::get('email_password');
		$enc  = Settings::get('email_encryption'); // 'ssl', 'tls', ''

		$dsnUser = rawurlencode((string)$user);
		$dsnPass = rawurlencode((string)$pass);

		if ($port === 465 || $enc === 'ssl') {
			// SMTPS
			$dsn = sprintf(
				'smtps://%s:%s@%s:%d',
				$dsnUser,
				$dsnPass,
				$host,
				$port
			);
		} else {
			// SMTP + STARTTLS
			$dsn = sprintf(
				'smtp://%s:%s@%s:%d?encryption=tls',
				$dsnUser,
				$dsnPass,
				$host,
				$port
			);
		}

		$transport = Transport::fromDsn($dsn);
		self::$mailer = new Mailer($transport);

		return self::$mailer;
	}

	/**
	 * Send an email message.
	 *
	 * @param string|array $to
	 * @param string|array $from
	 * @param string       $subject
	 * @param string       $message
	 * @param bool         $html
	 * @return int
	 */
	public static function send($to, $from, $subject, $message, $html = false): int
	{
		$mailer = self::getMailer();

		$email = new SymfonyEmail();

		// FROM
		if (is_array($from)) {
			$email->from(new Address($from[0], $from[1] ?? ''));
		} else {
			$email->from($from);
		}

		// TO
		if (is_array($to)) {
			$email->to(new Address($to[0], $to[1] ?? ''));
		} else {
			$email->to($to);
		}

		$email->subject($subject);

		if ($html) {
			$email->html($message);
		} else {
			$email->text($message);
		}

		$mailer->send($email);

		// zachováme návratovou hodnotu jako u Swiftu
		return 1;
	}

	public static function connect($config = null)
	{
		return self::getMailer();
	}

	public static function send_full(
		string $to,
		string $from,
		string $subject,
		string $htmlBody,
		array $bcc = [],
		array $attachments = []
	): void {
		$mailer = self::getMailer();

		$m = new SymfonyEmail();
		$m->from($from)
			->to($to)
			->subject($subject)
			->html($htmlBody);

		foreach ($bcc as $b) {
			$m->addBcc($b);
		}

		foreach ($attachments as $a) {
			$m->attachFromPath(
				$a['path'],
				$a['name'] ?? null,
				$a['mime'] ?? null
			);
		}

		$mailer->send($m);
	}
}
