<?php

namespace App\Modules;

use Nette;
use Latte\Engine;
use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    private $url;
    private $latte;

    public function __construct(
        private Nette\Database\Explorer $database,
        private PHPMailer               $phpMailer,
        private Nette\Mail\Mailer       $mailer,
        private Nette\DI\Container      $container
    )
    {
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

    public function sendConfirmationMail(string $to, string $confirmUrl, $reservation): void
    {
        $userSettings = $this->database->table("settings")->fetch();
        $user = $this->database->table("users")->fetch();
        $params = [
            'url' => $this->url . $confirmUrl,
            "user" => $user,
            'userSettings' => $userSettings,
            'reservation' => $reservation
        ];
        
        $mailContent = $this->latte->renderToString(__DIR__ . '/Mails/confirmation.latte', $params);
        $this->sendMail($to, "Potvrzení rezervace - Vyžadováno Ověření", $mailContent);
    }

    public function sendBackupConfiramationMail(string $to, string $confirmUrl, $reservation): void
    {
        $user = $this->database->table("users")->fetch();
        $userSettings = $this->database->table("settings")->fetch();
        $params = [
            'url' => $this->url . $confirmUrl,
            'user' => $user,
            'userSettings' => $userSettings,
            'reservation' => $reservation
        ];
        
        $mailContents = $this->latte->renderToString(__DIR__ . '/Mails/backup.latte', $params);
        $this->sendMail($to, "Potvrzení záložní rezervace - Vyžadováno Ověření", $mailContents);
    }

    public function sendCancelationMail(string $to, $reservation, string $reason): void
    {
        $user = $this->database->table("users")->fetch();
        $userSettings = $this->database->table("settings")->fetch();
        $params = [
            'user' => $user,
            "url" => $this->url,
            'userSettings' => $userSettings,
            'reservation' => $reservation,
            'reason' => $reason
        ];

        $mailContents = $this->latte->renderToString(__DIR__ . '/Mails/cancel.latte', $params);
        $this->sendMail($to, "Zrušení rezervace č.$reservation->id", $mailContents);

    }

    public function sendPaymentConfirmationMail(string $to, $reservation): void
    {
        $user = $this->database->table("users")->fetch();
        $userSettings = $this->database->table("settings")->fetch();
        $params = [
            'user' => $user,
            'userSettings' => $userSettings,
            'reservation' => $reservation
        ];

        $mailContents = $this->latte->renderToString(__DIR__ . '/Mails/paymentConfirmation.latte', $params);
        $this->sendMail($to, "Potvrzení platby rezervace č.$reservation->id", $mailContents);
    }

    public function sendNotifyMail(string $to, $reservation): void {

        $user = $this->database->table("users")->fetch();
        $userSettings = $this->database->table("settings")->fetch();
        $params = [
            'user' => $user,
            'userSettings' => $userSettings,
            'reservation' => $reservation,
            'url' => $this->url
        ];

        $mailContents = $this->latte->renderToString(__DIR__ . '/Mails/notify.latte', $params);
        $this->sendMail($to, "Upozornení rezervace č.$reservation->id", $mailContents);
    }

private function sendMail(string $to, string $subject, $message)
{
    $from = $this->database
        ->table("settings")
        ->fetch()
        ->info_email;

    $mail = (new Nette\Mail\Message)
        ->setFrom($from ?? "info@rezervak.cz")
        ->addTo($to)
        ->setSubject($subject)
        ->setHtmlBody($message);

    $this->mailer->send($mail);
}
}