<?php
namespace App\Modules;

use Nette;
use Latte\Engine;

final class Mailer {
    public function __construct(
        private Nette\Database\Explorer $database
    ) {
    }

    private function createMail(string $from , string $to,string $subject,string $message): Nette\Mail\Message {
        $mail = new Nette\Mail\Message;
        $mail->setFrom($from);
        $mail->addTo($to);
        $mail->setSubject($subject);
        $mail->setHtmlBody($message);

        return $mail;
    }

    public function sendMail(string $from , string $to,string $subject,string $message) {
        $mailer = new Nette\Mail\SendmailMailer;
        $mail = $this->createMail($from, $to, $subject, $message);
        $mailer->send($mail);
    }
//TODO code it in DRY
    public function sendConfirmationMail(string $to, string $confirmUrl) {
        $latte = new Engine;
        $params = [
            'url' => "http://localhost:8000".$confirmUrl,
        ];
        $mail = new Nette\Mail\Message;
        $mail->setFrom('vojtakylar@seznam.cz');
        $mail->addTo($to);
        $mail->setSubject('Rezervace');
        $mail->setHtmlBody($latte->renderToString(__DIR__.'/Mails/confirmation.latte', $params));

        $mailer = new Nette\Mail\SendmailMailer;
        $mailer->send($mail);
    }

    public function sendBackupConfiramationMail(string $to, string $confirmUrl) {
        $latte = new Engine;
        $params = [
            'url' => "http://localhost:8000".$confirmUrl,
        ];
        $mail = new Nette\Mail\Message;
        $mail->setFrom('vojtakylar@seznam.cz');
        $mail->addTo($to);
        $mail->setSubject('Rezervace');
        $mail->setHtmlBody($latte->renderToString(__DIR__.'/Mails/backup.latte', $params));

        $mailer = new Nette\Mail\SendmailMailer;
        $mailer->send($mail);
    }
}