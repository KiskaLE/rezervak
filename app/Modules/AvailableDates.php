<?php
namespace App\Modules;

use Nette;

class AvailableDates {

    private string $table = "reservations";
    public function __construct(
        private Nette\Database\Explorer $database
    )
    {}

    public function getAvailableDates(int $duration ,int $numberOfDays): array{
       $date = date("Y-m-d");
       $available = [];
        //add one day to curDay
        for ($i = 0; $i < $numberOfDays; $i++) {
            if (!$this->getAvailableStartingHours($date, $duration ) == []) {
                $available[] = $date;
            }
            $date = date('Y-m-d', strtotime($date. ' +1 days'));
        }
        return $available;

    }

    /**
     * Retrieves the backup hours for a given date and duration.
     *
     * @param string $date The date for which to retrieve the backup hours.
     * @param int $duration The duration for which to retrieve the backup hours.
     * @return array The array of backup hours.
     */
    public function getBackupHours(string $date, int $duration) : array{
        $backupDatesRows = $this->database->query("SELECT reservations.*, services.duration FROM reservations LEFT JOIN services ON reservations.service_id = services.id WHERE date='$date' AND services.duration='$duration'")->fetchAll();
        $backupDates = [];
        foreach ($backupDatesRows as $row) {
            $backupDates[] = $row->start;
        }
        return $backupDates;
    }
    /**
     * Retrieves an array of available dates based on the provided parameters.
     *
     * @param string $date The date for which to retrieve available dates.
     * @param int $duration The duration in minutes of each available date.
     * @return array An array of available starting hours.
     */
    public function getAvailableStartingHours(string $date, int $duration): array{
        $workingHours = $this->database->table("workinghours")->where("weekday=?", $this->getDay($date))->fetch();
        $available = [];
        $dayStartMinutes = $this->convertTimeToMinutes($workingHours->start);
        $dayEndMinutes = $this->convertTimeToMinutes($workingHours->stop);
        //todo interval set in admin
        $interval = 30;
        $unverified = $this->database->table($this->table)->where("date=? AND status=?", [$date, "UNVERIFIED"])->fetchAll();
        $bookedArray = $this->database->table($this->table)->where("date=? AND status=?", [$date, "VERIFIED"])->fetchAll();
        //adds unverified dates that still can be verified
        foreach ($unverified as $row) {
            //todo set time in admin settings
            $isLate = strtotime(strval($row->created_at)) < strtotime(date("Y-m-d H:i:s"). ' -15 minutes');
            if (!$isLate) {
                $bookedArray[] = $row;
            }
        }

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
    private function convertTimeToMinutes(string $time):int {
        $split = explode(":", $time);
        return intval($split[0]) * 60 + intval($split[1]);
    }
    /**
     * Converts minutes to time in hours and minutes format.
     *
     * @param int $minutes The number of minutes to convert.
     * @return string The time in hours and minutes format (e.g. "2:30").
     */
    private function convertMinutesToTime(int $minutes):string {
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
    private function getDay(string $date):string {
        return date('N', strtotime($date))-1;
    }
}