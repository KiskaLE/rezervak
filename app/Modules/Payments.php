<?php
namespace App\Modules;

use Nette;

final class Payments {

    public function __construct(
        private Nette\Database\Explorer $database,
    ) {}

    public function createPayment($reservation, $discountCode = null): void
    {
        $discountCodeRow = $this->database->table("discount_codes")->where("code=? AND active=1", $discountCode)->fetch();
        $discountServices = Nette\Utils\Json::decode($discountCodeRow->services);
        in_array($reservation->service_id, $discountServices) ?: $discountCodeRow = null;

        $price = $reservation->ref("services", "service_id")->price;
        if ($discountCodeRow) {
            $discountType = $discountCodeRow->type;
            $discountValue = $discountCodeRow->value;
            if ($discountType == 0) {
                if ($discountValue >= $price) {
                    $price = 0;
                }else {
                    $price = $price - $discountValue;
                }
            } else if ($discountType == 1) {
                if ($discountValue >=100) {
                    $price = 0;
                }else {
                    $price = $price * $discountValue / 100;
                }
            }
        }
        $this->database->table("payments")->insert([
            "price" => $price,
            "reservation_id" => $reservation->id
        ]);

    }
   public function generatePaymentCode(int $id) {
        $payment = $this->database->table("payments")->where("id=?", $id)->fetch();
        $code = $payment->id.str_replace(":","", explode(" ", $payment->created_at)[1]);
        return $code;
    }
}