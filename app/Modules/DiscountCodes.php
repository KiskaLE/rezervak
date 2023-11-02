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
    public function isCodeValid($service_id, string $discountCode)
    {
        //TODO add user id
        $discountCodeRow = $this->database->table("discount_codes")->where("code=? AND active=1", $discountCode)->fetch();
        if ($discountCodeRow) {
            $discountServices = Nette\Utils\Json::decode($discountCodeRow->services);
            in_array($service_id, $discountServices) ?: $discountCodeRow = [];
        }
        return $discountCodeRow;
    }
}