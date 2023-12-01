<?php

namespace App\Modules;

use Nette;


class DiscountCodes
{
    public function __construct(
        private Nette\Database\Explorer $database,
    )
    {
    }

    /**
     * Validates a discount code for a reservation.
     *
     * @param mixed $reservation The reservation object.
     * @param string $discountCode The discount code to validate.
     * @return array The discount code row if valid, an empty array otherwise.
     */
    public function isCodeValid(int $user_id, int $service_id, string $discountCode)
    {
        $discountCodeRow = $this->database->table("discount_codes")->where("user_id=? AND code=? AND active=1", [strval($user_id), $discountCode])->fetch();
        if ($discountCodeRow) {
            $services2discountCode = $discountCodeRow->related("service2discount_code.discount_code_id")->fetchAll();
            $selectedServices = [];
            foreach ($services2discountCode as $row) {
                $selectedServices[] = $row->ref("services", "service_id")->id;
            }
            in_array($service_id, $selectedServices) ?: $discountCodeRow = [];
        }
        return $discountCodeRow;
    }

    public function getService(int $service_id)
    {
        return $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
    }
}