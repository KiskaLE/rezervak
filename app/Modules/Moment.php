<?php

namespace App\Modules;

use Nette;

class Moment
{
    public function __construct() 
    {}

    /**
     * Converts a given time from a specified timezone to UTC.
     *
     * @param string $time The time to convert.
     * @param string $timezone The timezone of the given time.
     * @throws \Moment\Exception\ExceptionInterface If an error occurs during the conversion.
     * @return string The converted time in UTC format.
     */
    public function getUTCTime($time, $timezone)
    {
        $m = new \Moment\Moment($time, $timezone);
        $m->setTimezone('UTC');
        return $m->format("Y-m-d H:i:s");
    }

    /**
     * Converts a UTC time to the specified timezone.
     *
     * @param string $time The UTC time to convert.
     * @param string $timezone The timezone to convert the time to.
     * @throws \Exception If an error occurs during the conversion.
     * @return string The converted time in the specified timezone.
     */
    public function getTimezoneTimeFromUTCTime($time, $timezone)
    {
        $m = new \Moment\Moment($time);
        $m->setTimezone($timezone);
        return $m->format("Y-m-d H:i:s");
    }

    public function getUTCDate($date, $timezone)
    {
        $m = new \Moment\Moment($date, $timezone);
        $m->setTimezone('UTC');
        return $m->format("Y-m-d");
    }

    public function getDate($time)
    {
        $m = new \Moment\Moment($time);
        return $m->format("Y-m-d");
    }

    public function getNowUTC()
    {
        $m = new \Moment\Moment("now");
        return $m->format("Y-m-d H:i:s");
    }
}