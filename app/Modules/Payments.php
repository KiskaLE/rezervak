<?php
namespace App\Modules;

use Nette;

final class Payments {

    public function __construct(
        private Nette\Database\Explorer $database,
    ) {}

    public function test() {
        bdump($this->generatePaymentCode(4));
    }
   public function generatePaymentCode(int $id) {
        $payment = $this->database->table("payments")->where("id=?", $id)->fetch();
        $code = $payment->id.str_replace(":","", explode(" ", $payment->created_at)[1]);
        return $code;
    }
}