<?php
namespace App\Modules;

use Nette;

class AvailableDates {

    private $table = "registereddates";
    public function __construct(
        private Nette\Database\Explorer $database
    )
    {
    }
    /**
     * Retrieves an array of available dates based on the provided parameters.
     *
     * @param string $date The date for which to retrieve available dates.
     * @param int $duration The duration in minutes of each available date.
     * @param string $dayStart The starting time of each day (eg. 8:00).
     * @param string $dayEnd The ending time of each day (eg. 16:00.
     * @return array An array of available starting hours.
     */
    public function getAvailableStartingHours(string $date, int $duration): array{
        $workingHours = $this->database->table("workinghours")->where("weekday=?", $this->getDay($date))->fetch();
        $available = [];
        $dayStartMinutes = $this->convertTimeToMinutes($workingHours->start);
        $dayEndMinutes = $this->convertTimeToMinutes($workingHours->stop);
        $interval = 30;
        $bookedArray = $this->database->table($this->table)->where("date=?", $date)->fetchAll();

        while ($dayStartMinutes < $dayEndMinutes) {
            $sv = true;
            foreach ($bookedArray as $booked) {
                $service = $booked->ref("services", "service_id");
                $start = $this->convertTimeToMinutes($booked->start);
                $duration2 = intval($service->duration);
                if (!$this->isPossible($dayStartMinutes, $duration, $start, $duration2)) {
                    $sv = false;
                    break;
                }
            }
            if ($sv) {
                $available[] = $this->convertMinutesToTime($dayStartMinutes);
            }
            $dayStartMinutes += $interval;
        }
        return $available;
    }
    /**
     * Determines if two intervals of time do not collide with each other.
     *
     * @param int $start1 The start time of the first interval.
     * @param int $duration1 The duration of the first interval.
     * @param int $start2 The start time of the second interval.
     * @param int $duration2 The duration of the second interval.
     * @return bool Returns true if the intervals do not collide, false otherwise.
     */
    private function isPossible(int $start1, int $duration1, int $start2, int $duration2):bool {
        return !($start1 + $duration1-1 >= $start2 && $start2 + $duration2-1 >= $start1);

    }
    /**
     * Converts the given time from the format '9:30' to minutes.
     *
     * @param string $time The time in the format '9:30'.
     * @return int The time converted to minutes.
     */
    private function convertTimeToMinutes($time) {
        $split = explode(":", $time);
        return intval($split[0]) * 60 + intval($split[1]);
    }
    /**
     * Converts minutes to time in hours and minutes format.
     *
     * @param int $minutes The number of minutes to convert.
     * @return string The time in hours and minutes format (e.g. "2:30").
     */
    private function convertMinutesToTime($minutes) {
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;
        return $hours . ":" . str_pad($minutes, 2, "0", STR_PAD_LEFT);
    }
    /**
     * A function to get the day of the week from a given date.
     *
     * @param string $date The date in the format YYYY-MM-DD.
     * @return int The day of the week, where 0 represents Monday and 6 represents Sunday.
     */
    private function getDay($date) {
        return date('N', strtotime($date))-1;
    }
}