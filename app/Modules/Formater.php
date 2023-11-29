<?php

namespace App\Modules;

use Nette;
use App\Modules\Moment;

class Formater
{

    public function __construct(
        private Nette\Database\Explorer $database,
        private Nette\Security\User     $user,
        private Moment                  $moment
    )
    {
    }

    public function convertToAdminTimezone($time)
    {
        $this->timezone = $this->database->table("settings")->where("user_id=?", $this->user->id)->fetch()->time_zone;
        return $this->moment->getTimezoneTimeFromUTCTime($time . "", $this->timezone);
    }

    public function getDataFromString($string): array
    {
        $datesStartAndEnd = explode("-", trim($string));
        $start = explode(" ", trim($datesStartAndEnd[0]));
        $dateStart = explode("/", $start[0]);
        $timeStart = $start[1];
        $start = trim($dateStart[2] . "-" . $dateStart[1] . "-" . $dateStart[0] . " " . $timeStart);
        //end
        $end = explode(" ", trim($datesStartAndEnd[1]));
        $dateEnd = explode("/", $end[0]);
        $timeEnd = $end[1];
        $end = trim($dateEnd[2] . "-" . $dateEnd[1] . "-" . $dateEnd[0] . " " . $timeEnd);

        return ["start" => $start, "end" => $end, "timeStart" => $timeStart, "timeEnd" => $timeEnd];
    }


}