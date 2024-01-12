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
        $timezone = "Europe/Prague";
        $m = new \Moment\Moment($time."");
        $m->setTimezone($timezone);
        return $m->format("Y-m-d H:i");
    }
    
    public function json(array $array) {
        return json_encode($array);
    }

    public function currency($currency)
    {
        return number_format($currency, 2, ",", " ");
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

    public function getDataFromRange($string) {
        $string = explode("-", $string);
        $start = trim($string[0]);
        $end = trim($string[1]);
        $start = explode("/", $start);
        $end = explode("/", $end);

        $start = trim($start[2] . "-" . $start[0] . "-" . $start[1]);
        $end = trim($end[2] . "-" . $end[0] . "-" . $end[1]);


        return ["start" =>$start, "end" => $end];
    }

    public function getDataFromRangeInFormatDMY($string) {
        $string = explode("-", $string);
        $start = trim($string[0]);
        $end = trim($string[1]);
        $start = explode("/", $start);
        $end = explode("/", $end);

        $start = trim($start[2] . "-" . $start[1] . "-" . $start[0]);
        $end = trim($end[2] . "-" . $end[1] . "-" . $end[0]);


        return ["start" =>$start, "end" => $end];
    }

    public function getDateFromTimeStamp($timestamp)
    {
        return date("Y-m-d", strtotime($timestamp));
    }

    public function getTimeFromTimeStamp($timestamp)
    {
        return date("H:i", strtotime($timestamp));
    }

    public function getTimeFormatedFromTimeStamp($timestamp)
    {
        return date("H:i", strtotime($timestamp));
    }

    public function getDateFormatedFromTimestamp($timestamp)
    {
        return date("d/m/Y", strtotime($timestamp));
    }


}