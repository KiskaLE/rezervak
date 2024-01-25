<?php

namespace App\Modules;

use Nette;

class Moment
{
    public function __construct()
    {
    }


    public function getNowPrague()
    {
        $m = new \Moment\Moment("now");
        $m->setTimezone('Europe/Prague');
        return $m->format("Y-m-d H:i:s");
    }

    /**
     * Converts a given time from a specified timezone to UTC.
     *
     * @param string $time The time to convert.
     * @param string $timezone The timezone of the given time.
     * @return string The converted time in UTC format.
     * @throws \Moment\Exception\ExceptionInterface If an error occurs during the conversion.
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
     * @return string The converted time in the specified timezone.
     * @throws \Exception If an error occurs during the conversion.
     */
    public function getTimezoneTimeFromUTCTime($time, $timezone)
    {
        $m = new \Moment\Moment($time, "UTC");
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