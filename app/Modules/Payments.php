<?php

namespace App\Modules;

use Nette;
use App\Modules\DiscountCodes;

final class Payments
{

    public function __construct(
        private Nette\Database\Explorer $database,
        private DiscountCodes           $discountCodes,
    )
    {
    }

    /**
     * Creates a payment for a reservation.
     *
     * @param mixed $reservation The reservation object.
     * @param string $discountCode (optional) The discount code.
     * @return void
     */
    public function createPayment($reservation, string $discountCode = ""): void
    {
        $price = $this->createPrice($reservation, $discountCode);
        $this->database->table("payments")->insert([
            "price" => $price,
            "reservation_id" => $reservation->id
        ]);

    }

    private function createPrice($reservation, string $discountCode): int
    {
        $price = $reservation->ref("services", "service_id")->price;
        $discountCodeRow = $this->discountCodes->isCodeValid($reservation->service_id, $discountCode);
        if ($discountCodeRow) {
            $discountType = $discountCodeRow->type;
            $discountValue = $discountCodeRow->value;
            if ($discountType == 0) {
                if ($discountValue >= $price) {
                    $price = 0;
                } else {
                    $price = $price - $discountValue;
                }
            } else if ($discountType == 1) {
                if ($discountValue >= 100) {
                    $price = 0;
                } else {
                    $price = $price * $discountValue / 100;
                }
            }
        }
        return $price;
    }

    public function generatePaymentCode(int $id)
    {
        $payment = $this->database->table("payments")->where("id=?", $id)->fetch();
        $code = $payment->id . str_replace(":", "", explode(" ", $payment->created_at)[1]);
        return $code;
    }

    /**
     * Retrieves the payments associated with the given reservation.
     *
     * @param mixed $reservation The reservation object.
     * @throws Some_Exception_Class A description of the exception that can be thrown.
     * @return array The array of payment objects.
     */
    public function getPayments($reservation)
    {
        $payments = $this->database->table("payments")->where("reservation_id=?", $reservation->id)->fetchAll();
        foreach ($payments as $payment) {
            $this->payments->generatePaymentCode($payment->id);
        }
        return $payments;
    }
}