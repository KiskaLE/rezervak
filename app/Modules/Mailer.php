<?php

namespace App\Modules;

use Nette;
use Latte\Engine;
use Nette\Mail\SmtpMailer;
use PHPMailer\PHPMailer\PHPMailer;
use Nette\Mail\SendmailMailer;

final class Mailer
{
    private $url;
    private $latte;

    public function __construct(
        private Nette\Database\Explorer $database,
        private PHPMailer          $phpMailer,
        private Nette\Mail\Mailer  $mailer,
        private Nette\DI\Container $container
    )
    {
        $mailerConfig = $this->container->getParameters();
        if (isset($_SERVER['SERVER_NAME'])) {
            if ($_SERVER['SERVER_NAME'] === "localhost") {
                $this->url = "http://" . "localhost:8000";
            } else {
                $this->url = "http://".$_SERVER['SERVER_NAME'];
            }
        } else {
            $this->url = "http://" . "localhost:8000";
        }
        
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
    public function sendConfirmationMail(string $to, string $confirmUrl, $reservation): void
    {

        $user = $reservation->ref("users", "user_id");
        $userSettings = $user->related("settings")->fetch();
        $params = [
            'url' => $this->url . $confirmUrl,
            'user' => $user,
            'userSettings' => $userSettings,
            'reservation' => $reservation

        ];
        $this->sendMail($to, "Potvrzení Vaší rezervace - Vyžadováno Ověření", $this->latte->renderToString(__DIR__ . '/Mails/confirmation.latte', $params));
    }

    /**
     * Sends a backup confirmation email to the specified recipient.
     *
     * @param string $to The email address of the recipient.
     * @param string $confirmUrl The URL for confirming the backup.
     * @return void
     * @throws Exception If there is an error sending the email.
     */
    public function sendBackupConfiramationMail(string $to, string $confirmUrl, $reservation): void
    {
        $user = $reservation->ref("users", "user_id");
        $userSettings = $user->related("settings")->fetch();
        $params = [
            'url' => $this->url . $confirmUrl,
            'user' => $user,
            'userSettings' => $userSettings,
            'reservation' => $reservation

        ];
        $this->sendMail($to, "Potvrzení Vaší záložní rezervace - Vyžadováno Ověření", $this->latte->renderToString(__DIR__ . '/Mails/backup.latte', $params));
    }

    /**
     * Sends a cancellation email.
     *
     * @param string $to The email address to send the cancellation email to.
     * @param mixed $reservation The reservation object.
     * @param string $reason The reason for the cancellation.
     * @return void
     * @throws Some_Exception_Class Description of the exception that may be thrown.
     */
    public function sendCancelationMail(string $to, $reservation, string $reason): void
    {
        $user = $reservation->ref("users", "user_id");
        $userSettings = $user->related("settings")->fetch();
        $params = [
            'user' => $user,
            'userSettings' => $userSettings,
            'reservation' => $reservation,
            'reason' => $reason

        ];
        $this->sendMail($to, "Zrušení rezervace", $this->latte->renderToString(__DIR__ . '/Mails/cancel.latte', $params));

    }

    public function sendPaymentConfirmationMail(string $to, $reservation): void
    {
        $user = $reservation->ref("users", "user_id");
        $userSettings = $user->related("settings")->fetch();
        $params = [
            'user' => $user,
            'userSettings' => $userSettings,
            'reservation' => $reservation
        ];
        $this->sendMail($to, "Potvrzení platby", $this->latte->renderToString(__DIR__ . '/Mails/paymentConfirmation.latte', $params));
    }

    public function sendNotifyMail(string $to, $reservation): void {

        $user = $reservation->ref("users", "user_id");
        $userSettings = $user->related("settings")->fetch();
        $params = [
            'user' => $user,
            'userSettings' => $userSettings,
            'reservation' => $reservation,
            'url' => $this->url
        ];

        $this->sendMail($to, "Upozornení", $this->latte->renderToString(__DIR__ . '/Mails/notify.latte', $params));
    }

    private function sendMail(string $to, string $subject, $message)
    {
        $from = $this->database->table("settings")->fetch()->info_email;
        $mail = new Nette\Mail\Message;
        $mail->setFrom($from ?? "info@rezervak.cz")
            ->addTo($to)
            ->setSubject($subject)
            ->setHtmlBody($message);

        $this->mailer->send($mail);

        /*
        $this->phpMailer->clearAddresses();
        $this->phpMailer->addAddress($to);
        $this->phpMailer->Subject = $subject;
        $this->phpMailer->Body = $message;
        $this->phpMailer->send();
        $this->phpMailer->clearAddresses();
        */
    }
}