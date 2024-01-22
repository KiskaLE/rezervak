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
     * @param int $user_id The user ID.
     * @param int $service_id The service ID.
     * @param string $discountCode The discount code to validate.
     * @return array The discount code row if valid, an empty array otherwise.
     */
    public function isCodeValid(int $service_id, string $discountCode)
    {
        $discountCodeRow = $this->database
            ->table("discount_codes")
            ->where("code LIKE ? AND active = 1", $discountCode)
            ->fetch();

        if (!$discountCodeRow || strval($discountCodeRow->code) !== $discountCode) {
            return [];
        }

        $service2DiscountCodeRows = $discountCodeRow
            ->related("service2discount_code.discount_code_id")
            ->fetchAll();

        $selectedServices = array_map(
            fn ($row) => $row->ref("services", "service_id")?->id,
            $service2DiscountCodeRows
        );

        if (!in_array($service_id, $selectedServices)) {
            return [];
        }

        return $discountCodeRow;
    }

    public function getService($id) {
        return $this->database->table("services")->get($id);
    }
}