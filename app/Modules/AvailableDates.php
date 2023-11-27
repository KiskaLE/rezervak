<?php

namespace App\Modules;

use Nette;
use App\Modules\Moment;

class AvailableDates
{

    private string $table = "reservations";
    private $user_id;
    private $user_settings;

    public function __construct(
        private Nette\Database\Explorer $database,
        private Moment $moment
    )
    {
    }

    public function getAvailableDates(string $u, int $duration, int $numberOfDays): array
    {
        $date = date("Y-m-d");
        $available = [];
        for ($i = 0; $i < $numberOfDays; $i++) {
            if (!$this->getAvailableStartingHours($u, $date, $duration) == []) {
                $available[] = $date;
            }
            //add one day to curDay
            $date = date('Y-m-d', strtotime($date . ' +1 days'));
        }
        return $available;

    }

    public function isTimeAvailable(string $u, string $date, string $start, int $duration): bool
    {
        $times = $this->getAvailableStartingHours($u, $date, $duration);
        //if you find start in times array return true
        if (in_array($start, $times)) {
            return true;
        }
        return false;
    }

    /**
     * Retrieves the backup hours for a given date and duration.
     *
     * @param string $date The date for which to retrieve the backup hours.
     * @param int $duration The duration for which to retrieve the backup hours.
     * @return array The array of backup hours.
     */
    public function getBackupHours(string $u, string $date, int $duration): array
    {
        //TODO přepracovat
        $backupDates = [];
         /*
        $user = $this->database->table("users")->where("uuid=?", $u)->fetch();
        $verificationTime = $user->related("settings")->fetch()->verification_time;
        $time = date("Y-m-d H:i:s", strtotime("-" . $verificationTime . " minutes"));
        $backupDatesRows = $this->database->query("SELECT reservations.*, services.duration FROM reservations LEFT JOIN services ON reservations.service_id = services.id WHERE reservations.user_id=$user->id AND date='$date' AND services.duration='$duration' AND type=0 AND (status='VERIFIED' OR reservations.created_at > '$time')")->fetchAll();
        
        foreach ($backupDatesRows as $row) {
            $backupDates[] = $row->start;
        }
        */
        return $backupDates;
    }

    private function checkAvailability($start, $end, $duration, $interval, string $customerTimezone)
    {
        $availableTimes = array();
        $verifiedReservations = $this->database->table("reservations")->where("user_id=? AND start BETWEEN ? AND ? AND status='VERIFIED' AND type=0", [$this->user_id, $start, $end])->fetchAll();
        $verificationTime = strtotime(date("Y-m-d H:i:s") . ' -' . $this->user_settings->verification_time . ' minutes');
        $unverifiedReservations = $this->database->table("reservations")->where("user_id=? AND start BETWEEN ? AND ? AND status='UNVERIFIED' AND created_at > ? ", [$this->user_id, $start, $end, $verificationTime])->fetchAll();
        $start = strtotime($start);
        $end = strtotime($end);
        while ($start < $end) {
            $isAvailable = true;
            
            if ($isAvailable){
                //convert time to customer timezone
                $availableTimes[] = date("H:i", strtotime($this->moment->getTimezoneTimeFromUTCTime($start, $customerTimezone)));
            }
            
            $start = $start + $interval * 60;
        }
       return $availableTimes;
    }

    /**
     * Retrieves an array of available dates based on the provided parameters.
     *
     * @param string $date The date for which to retrieve available dates.
     * @param int $duration The duration in minutes of each available date.
     * @return array An array of available starting hours.
     */
    public function getAvailableStartingHours(string $u, string $date, int $duration, $customerTimezone = "Europe/Prague"): array
    {
        $user = $this->database->table("users")->where("uuid=?", $u)->fetch();
        $user_settings = $user->related("settings")->fetch();
        $this->user_id = $user->id;
        $this->user_settings = $user_settings;


        //customer
        $customerDayStartUTC = $this->moment->getUTCTime($date." 00:00:00", $customerTimezone);
        $customerDayEndUTC = $this->moment->getUTCTime($date." 23:59:59", $customerTimezone);
        //jaký den je tohle pro admina?
        $adminDayStart = $this->moment->getTimezoneTimeFromUTCTime($customerDayStartUTC, $user_settings->time_zone);
        $adminDayEnd = $this->moment->getTimezoneTimeFromUTCTime($customerDayEndUTC, $user_settings->time_zone);
        $adminStartDay = $this->moment->getDate($adminDayStart);
        $adminEndDay = $this->moment->getDate($adminDayEnd);
        $available = [];
        if ($adminStartDay == $adminEndDay) {
            //jeden den
            $workingHoursStart = $this->database->table("workinghours")->where("user_id=? AND weekday=?", [$this->user_id, $this->getDay($adminStartDay)])->fetch();
            $startUTC = $this->moment->getUTCTime($adminStartDay . " " . $workingHoursStart->start, $user_settings->time_zone);
            $endUTC = $this->moment->getUTCTime($adminStartDay . " " . $workingHoursStart->stop, $user_settings->time_zone);
            $available = $this->checkAvailability($startUTC, $endUTC, $duration, $user_settings->sample_rate, $customerTimezone );
        } else {
            //dva dny
            bdump("test");
            $workingHoursStart = $this->database->table("workinghours")->where("user_id=? AND weekday=?", [$this->user_id, $this->getDay($adminStartDay)])->fetch();
            $startUTC = $this->moment->getUTCTime($adminStartDay . " " . $workingHoursStart->start, $user_settings->time_zone);
            bdump($startUTC);
            $endUTC = $this->moment->getUTCTime($adminStartDay . " " . $workingHoursStart->stop, $user_settings->time_zone);
            bdump($endUTC);
            $day1 = $this->checkAvailability($startUTC, $endUTC, $duration, $user_settings->sample_rate, $customerTimezone );

            foreach( $day1 as $day ) {
                $available[] = $day;
            }

            // $workingHoursEnd = $this->database->table("workinghours")->where("user_id=? AND weekday=?", [$this->user_id, $this->getDay($adminEndDay)])->fetch();
            // $startUTC = $this->moment->getUTCTime($adminStartDay . " " . $workingHoursEnd->start, $user_settings->time_zone);
            // $endUTC = $this->moment->getUTCTime($adminStartDay . " " . $workingHoursEnd->stop, $user_settings->time_zone);
            // $day2 = $this->checkAvailability($startUTC, $endUTC, $duration, $user_settings->sample_rate, $customerTimezone );
            // foreach( $day2 as $day ) {
            //     $available[] = $day;
            // }
            
        }
        return $available;
        


        die;
        //admin
       


        $available = [];
        $workingHours = $this->database->table("workinghours")->where("user_id=? AND weekday=?", [$user_id, $this->getDay($date)])->fetch();


        $breaks = $workingHours->related("breaks")->fetchAll();
        $startTime = $this->moment->getUTCTime($date . " " . $workingHours->start, $user_settings->time_zone);
        $endTime = $this->moment->getUTCTime($date . " " . $workingHours->stop, $user_settings->time_zone);
    

        $dayStartMinutes = $this->convertTimeToMinutes($workingHours->start);
        $dayEndMinutes = $this->convertTimeToMinutes($workingHours->stop);

        $interval = $user_settings->sample_rate;
        $unverified = $this->database->table($this->table)->where("date=? AND status=? AND type=0", [$date, "UNVERIFIED"])->fetchAll();
        $bookedArray = $this->database->table($this->table)->where("date=? AND status=? AND type=0", [$date, "VERIFIED"])->fetchAll();
        $exceptionsArray = $this->getExceptions($u);
        //add unverified dates that still can be verified
        foreach ($unverified as $row) {
            $verification_time = $user_settings->verification_time;
            $isLate = strtotime(strval($row->created_at)) < strtotime(date("Y-m-d H:i:s") . ' -' . $verification_time . ' minutes');
            if (!$isLate) {
                $bookedArray[] = $row;
            }
        }
        //add breaks in booked array
        while ($dayStartMinutes < $dayEndMinutes) {
            $sv = true;
            //check for breaks
            foreach ($breaks as $break) {
                $start = $this->convertTimeToMinutes($break->start);
                $duration2 = $this->convertTimeToMinutes($break->end) - $this->convertTimeToMinutes($break->start);
                if (!$this->isPossible($dayStartMinutes, $duration, $start, $duration2)) {
                    $sv = false;
                    break;
                }
            }
            if ($sv) {
                foreach ($bookedArray as $booked) {
                    $service = $booked->ref("services", "service_id");
                    $start = $this->convertTimeToMinutes($booked->start);
                    $duration2 = intval($service->duration);
                    if (!$this->isPossible($dayStartMinutes, $duration, $start, $duration2)) {
                        $sv = false;
                        break;
                    }
                }
            }
            if ($sv) {
                foreach ($exceptionsArray as $exception) {
                    $start = strtotime($date . " " . $this->convertMinutesToTime($dayStartMinutes));;
                    if (strtotime($exception->start) <= $start && strtotime($exception->end) >= $start) {
                        $sv = false;
                        break;
                    }
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
    private function isPossible(int $start1, int $duration1, int $start2, int $duration2): bool
    {
        return !($start1 + $duration1 - 1 >= $start2 && $start2 + $duration2 - 1 >= $start1);

    }

    /**
     * Converts the given time from the format '9:30' to minutes.
     *
     * @param string $time The time in the format '9:30'.
     * @return int The time converted to minutes.
     */
    private function convertTimeToMinutes(string $time): int
    {
        $split = explode(":", $time);
        return intval($split[0]) * 60 + intval($split[1]);
    }

    /**
     * Converts minutes to time in hours and minutes format.
     *
     * @param int $minutes The number of minutes to convert.
     * @return string The time in hours and minutes format (e.g. "2:30").
     */
    private function convertMinutesToTime(int $minutes): string
    {
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
    private function getDay(string $date): string
    {
        return date('N', strtotime($date)) - 1;
    }

    private function getExceptions(string $u): array
    {
        $user = $this->database->table("users")->where("uuid=?", $u)->fetch();
        $now = date("Y-m-d H:i:s");
        $exceptions = $this->database->table("workinghours_exceptions")->where("user_id=?  AND end >=?", [$user->id, $now])->fetchAll();
        return $exceptions;
    }

    public function getReservationsConflictsIds(int $id): array
    {
        $user = $this->database->table("users")->where("id=?", $id)->fetch();
        $reservations = $user->related("reservations")->where("start>=?", date("Y-m-d H:i:s"))->fetchAll();
        $exceptions = $user->related("workinghours_exceptions")->where("end>=?", date("Y-m-d H:i:s"))->fetchAll();
        $conflicts = [];
        foreach ($reservations as $row) {
            $start = strtotime(explode(" ", $row->date)[0]." ".$row->start);
            foreach ($exceptions as $exception) {
                if (strtotime($exception->start) <= $start && strtotime($exception->end) >= $start) {
                    $conflicts[] = $exception->id;
                }
            }
        }
        return $conflicts;

    }

    public function getConflictedReservations($uuid):array
    {
        $exception = $this->database->table("workinghours_exceptions")->where("uuid=?", $uuid)->fetch();
        $exceptionStart = strtotime($exception->start);
        $exceptionEnd = strtotime($exception->end);
        $reservations = $this->database->table("reservations")->where("user_id=?", $exception->user_id)->fetchAll();
        $conflicts = [];
        foreach ($reservations as $reservation) {
            $start = strtotime(explode(" ", $reservation->date)[0]." ".$reservation->start);
            if ($exceptionStart <= $start && $exceptionEnd >= $start) {
                $conflicts[] = $reservation;
            }
        }
        return $conflicts;

    }
}

