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
    ) {
    }

    /**
     * Creates a payment for a reservation.
     *
     * @param mixed $database The database object.
     * @param mixed $reservation The reservation object.
     * @param string $discountCode (optional) The discount code.
     * @return bool Returns true if the payment creation is successful, false otherwise.
     */
    public function createPayment($database, $reservation, string $discountCode = ""): bool
    {
        $status = true;

        try {
            $price = $this->createPrice($reservation, $discountCode);
            $idTransaction = date("Y") . "09" . str_pad($reservation->id, 4, "0", STR_PAD_LEFT);

            $database->table("payments")->insert([
                "price" => $price,
                "reservation_id" => $reservation->id,
                "id_transaction" => $idTransaction,
                "discount_code" => $this->discountCodes->isCodeValid($reservation->service_id, $discountCode) ? $discountCode : "",
            ]);
        } catch (\Throwable $th) {
            $status = false;
        }

        return $status;
    }

    /**
     * Generates a payment code based on the provided ID.
     *
     * @param object $payment The payment object.
     * @param int $user_id The ID of the user.
     * @return string The generated payment code.
     */
    public function generatePaymentCode($payment, int $user_id)
    {
        $qrPlatba = new QRPlatba();
        $account = $this->database->table("users")->order("created_at ASC")->get($user_id)->payment_info;

        $qrPlatba->setAccount($account)
            ->setVariableSymbol($payment->id_transaction)
            ->setAmount($payment->price)
            ->setDueDate(new \DateTime());

        return $qrPlatba->getQRCodeImage();
    }

    /**
     * Updates the time of a reservation in the database.
     *
     * @param int $reservationId The ID of the reservation.
     * @throws Exception If an error occurs while updating the time in the database.
     */
    public function updateTime($reservationId)
    {
        $now = date("Y-m-d H:i:s");
        $this->database->table("payments")
            ->where("reservation_id=?", $reservationId)
            ->update([
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
        $payments = $this->database
            ->table("payments")
            ->where("reservation_id=?", $reservation->id)
            ->fetchAll();

        return $payments;
    }

    private function createPrice($reservation, string $discountCode): int
    {
        $price = $reservation->ref("services", "service_id")->price;
        $discountCodeRow = $this->discountCodes->isCodeValid($reservation->service_id, $discountCode);
        if ($discountCodeRow) {
            $discountType = $discountCodeRow["type"];
            $discountValue = $discountCodeRow["value"];

            if ($discountType == 0 && $discountValue >= $price) {
                $price = 0;
            } elseif ($discountType == 0) {
                $price -= $discountValue;
            } elseif ($discountType == 1 && $discountValue >= 100) {
                $price = 0;
            } else {
                $price -= $price * $discountValue / 100;
            }
        }

        return $price;
    }
}
