<?php

namespace App\Modules;

use Nette;
use App\Modules\DiscountCodes;
use Defr\QRPlatba\QRPlatba;

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
    public function createPayment($database, $reservation, string $discountCode = ""): bool
    {
        $status = false;
        try {
            $user_id = $reservation->user_id;
            $price = $this->createPrice($user_id, $reservation, $discountCode);
            $idTransaction = $reservation->id . date("dHi");
            $database->table("payments")->insert([
                "price" => $price,
                "reservation_id" => $reservation->id,
                "id_transaction" => $idTransaction
            ]);
            $status = true;
        } catch (\Throwable $th) {
        }

        return $status;

    }

    /**
     * Generates a payment code based on the provided ID.
     *
     * @param int $id The ID of the payment.
     * @return string The generated payment code.
     */
    public function generatePaymentCode($payment, int $user_id)
    {
        $qrPlatba = new QRPlatba();
        $account = $this->database->table("settings")->where("user_id", $user_id)->fetch()->payment_info;

        $qrPlatba->setAccount($account)
            ->setVariableSymbol($payment->id_transaction)
            ->setAmount($payment->price)
            ->setDueDate(new \DateTime());

        return $qrPlatba->getQRCodeImage();
    }

    /**
     * Updates the time of a reservation in the database.
     *
     * @param int $reservation_id The ID of the reservation.
     * @throws Exception If an error occurs while updating the time in the database.
     */
    public function updateTime($reservation_id)
    {
        $now = date("Y-m-d H:i:s");
        $this->database->table("payments")->where("reservation_id=?", $reservation_id)->update([
            "updated_at" => $now
        ]);
    }

    /**
     * Retrieves the payments associated with the given reservation.
     *
     * @param mixed $reservation The reservation object.
     * @return array The array of payment objects.
     * @throws Some_Exception_Class A description of the exception that can be thrown.
     */
    public function getPayments($reservation)
    {
        $payments = $this->database->table("payments")->where("reservation_id=?", $reservation->id)->fetchAll();
        return $payments;
    }

    private function createPrice(int $user_id, $reservation, string $discountCode): int
    {
        $price = $reservation->ref("services", "service_id")->price;
        $discountCodeRow = $this->discountCodes->isCodeValid($user_id, $reservation->service_id, $discountCode);
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
                    $price = $price - $price * $discountValue / 100;
                }
            }
        }
        return $price;
    }
}