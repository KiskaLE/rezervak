<?php

namespace App\Modules;

use Nette;


class DiscountCodes
{
    public function __construct(
        private Nette\Database\Explorer $database,
    )
    {}

    /**
     * Validates a discount code for a reservation.
     *
     * @param mixed $reservation The reservation object.
     * @param string $discountCode The discount code to validate.
     * @return array The discount code row if valid, an empty array otherwise.
     */
    public function isCodeValid(int $user_id ,$service_id, string $discountCode)
    {
        $discountCodeRow = $this->database->table("discount_codes")->where("code=? AND user_id=? AND active=1", [$discountCode, $user_id])->fetch();
        if ($discountCodeRow) {
            $discountServices = Nette\Utils\Json::decode($discountCodeRow->services);
            in_array($service_id, $discountServices) ?: $discountCodeRow = [];
        }
        return $discountCodeRow;
    }

    public function getService(int $service_id) {
        return $service = $this->database->table("services")->where("id=?", $service_id)->fetch();
    }
}