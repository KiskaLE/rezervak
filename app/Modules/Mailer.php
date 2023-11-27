<?php

namespace App\Modules;

use Nette;
use Latte\Engine;
use Nette\Mail\SmtpMailer;

final class Mailer
{
    private $mailer;
    private $url;

    public function __construct(
        private Nette\Database\Explorer $database
    )
    {
        $this->url = 'http://' . $_SERVER['HTTP_HOST'];
        $this->mailer = new SmtpMailer(
            'smtp.seznam.cz',
            'rezervkainfo@seznam.cz',
            'sxqOgSNiXQ8TQbG',
            465,
            "ssl");
    }

    private function createMail(string $from, string $to, string $subject, string $message): Nette\Mail\Message
    {
        $mail = new Nette\Mail\Message;
        $mail->setFrom($from);
        $mail->addTo($to);
        $mail->setSubject($subject);
        $mail->setHtmlBody($message);

        return $mail;
    }

    public function sendMail(string $from, string $to, string $subject, string $message)
    {
        $mailer = new Nette\Mail\SendmailMailer;
        $mail = $this->createMail($from, $to, $subject, $message);
        $mailer->send($mail);
    }

//TODO code it in DRY
    public function sendConfirmationMail(string $to, string $confirmUrl)
    {
        $latte = new Engine;
        $params = [
            'url' => $this->url . $confirmUrl,
        ];
        $mail = new Nette\Mail\Message;
        $mail->setFrom('rezervkainfo@seznam.cz');
        $mail->addTo($to);
        $mail->setSubject('Rezervace');
        $mail->setHtmlBody($latte->renderToString(__DIR__ . '/Mails/confirmation.latte', $params));

        $this->mailer->send($mail);
    }

    public function sendBackupConfiramationMail(string $to, string $confirmUrl)
    {
        $latte = new Engine;
        $params = [
            'url' => $this->url . $confirmUrl,
        ];
        $mail = new Nette\Mail\Message;
        $mail->setFrom('rezervkainfo@seznam.cz');
        $mail->addTo($to);
        $mail->setSubject('Rezervace');
        $mail->setHtmlBody($latte->renderToString(__DIR__ . '/Mails/backup.latte', $params));

        $mailer = new Nette\Mail\SendmailMailer;
        $mailer->send($mail);
    }

    public function sendCancelationMail(string $to)
    {
        $latte = new Engine;
        $mail = new Nette\Mail\Message;
        $mail->setFrom('rezervkainfo@seznam.cz');
        $mail->addTo($to);
        $mail->setSubject('Zrušení rezervace');
        $mail->setHtmlBody($latte->renderToString(__DIR__ . '/Mails/cancel.latte'));

        $mailer = new Nette\Mail\SendmailMailer;
        $mailer->send($mail);

    }
}