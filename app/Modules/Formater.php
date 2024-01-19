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
        return number_format($currency, 0, ",", " ");
    }

public function getDataFromString($string): array
{
    [$dateStart, $timeStart] = explode(" ", trim(explode("-", trim($string))[0]));
    [$dateEnd, $timeEnd] = explode(" ", trim(explode("-", trim($string))[1]));

    $start = implode("-", array_reverse(explode("/", $dateStart))) . " " . $timeStart;
    $end = implode("-", array_reverse(explode("/", $dateEnd))) . " " . $timeEnd;

    return ["start" => $start, "end" => $end, "timeStart" => $timeStart, "timeEnd" => $timeEnd];
}

    public function getDataFromRange($string) {
        [$start, $end] = array_map('trim', explode("-", $string));

        [$startYear, $startMonth, $startDay] = explode("/", $start);
        [$endYear, $endMonth, $endDay] = explode("/", $end);

        $start = "$startYear-$startMonth-$startDay";
        $end = "$endYear-$endMonth-$endDay";

        return ["start" => $start, "end" => $end];
    }

public function getDataFromRangeInFormatDMY($string) {
    list($start, $end) = array_map('trim', explode("-", $string));
    list($start, $end) = array_map(function ($date) {
        list($day, $month, $year) = explode("/", $date);
        return "$year-$month-$day";
    }, [$start, $end]);

    return ["start" => $start, "end" => $end];
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