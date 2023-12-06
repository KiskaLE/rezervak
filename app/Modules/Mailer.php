<?php

namespace App\Modules;

use Nette;
use Latte\Engine;
use Nette\Mail\SmtpMailer;
use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    private $mailer;
    private $url;
    private $latte;

    public function __construct(
        private Nette\Database\Explorer $database,
        private PHPMailer               $phpMailer
    )
    {
        $this->url = 'http://' . "localhost:8000";
        $this->mailer = new SmtpMailer(
            'smtp.seznam.cz',
            'rezervkainfo@seznam.cz',
            'sxqOgSNiXQ8TQbG',
            465,
            "ssl");

        $this->phpMailer->isSMTP();
        $this->phpMailer->Host = 'smtp.seznam.cz';
        $this->phpMailer->SMTPAuth = true;
        $this->phpMailer->Username = 'rezervkainfo@seznam.cz';
        $this->phpMailer->Password = 'sxqOgSNiXQ8TQbG';
        $this->phpMailer->SMTPSecure = 'ssl';
        $this->phpMailer->Port = 465;

        $this->phpMailer->setFrom("rezervkainfo@seznam.cz");
        $this->phpMailer->CharSet = "UTF-8";

        $this->phpMailer->isHTML(true);
        $this->phpMailer->setLanguage("cs");

        $this->latte = new Engine;


    }

    /**
     * Sends a confirmation email to the specified recipient.
     *
     * @param string $to The email address of the recipient.
     * @param string $confirmUrl The URL to confirm the reservation.
     * @return void
     * @throws Some_Exception_Class A description of the exception that may be thrown.
     */
    public function sendConfirmationMail(string $to, string $confirmUrl): void
    {
        $params = [
            'url' => $this->url . $confirmUrl,
        ];
        $this->sendMail($to, "Rezervace", $this->latte->renderToString(__DIR__ . '/Mails/confirmation.latte', $params));
    }

    /**
     * Sends a backup confirmation email to the specified recipient.
     *
     * @param string $to The email address of the recipient.
     * @param string $confirmUrl The URL for confirming the backup.
     * @return void
     * @throws Exception If there is an error sending the email.
     */
    public function sendBackupConfiramationMail(string $to, string $confirmUrl): void
    {
        $params = [
            'url' => $this->url . $confirmUrl,
        ];
        $this->sendMail($to, "Potvzení záložní rezervace", $this->latte->renderToString(__DIR__ . '/Mails/backup.latte', $params));
    }

    /**
     * Sends a cancellation email.
     *
     * @param string $to The email address to send the cancellation email to.
     * @return void
     * @throws Exception If there is an error sending the email.
     */
    public function sendCancelationMail(string $to): void
    {
        $this->sendMail($to, "Zrušení rezervace", $this->latte->renderToString(__DIR__ . '/Mails/cancel.latte'));

    }

    private function sendMail(string $to, string $subject, string $message)
    {
        $this->phpMailer->clearAddresses();
        $this->phpMailer->addAddress($to);
        $this->phpMailer->Subject = $subject;
        $this->phpMailer->Body = $message;
        $this->phpMailer->send();
        $this->phpMailer->clearAddresses();
    }
}