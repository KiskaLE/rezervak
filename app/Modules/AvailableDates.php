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
        private Moment                  $moment
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
    //TODO add customerTimezone
    public function getBackupHours(string $u, string $date, int $duration, $customerTimezone = "Europe/Prague"): array
    {
        $results = array();
        $user = $this->database->table("users")->where("uuid=?", $u)->fetch();
        //customer
        $customerDayStartUTC = $this->moment->getUTCTime($date . "T00:00:00", $customerTimezone);
        $customerDayEndUTC = $this->moment->getUTCTime(date("Y-m-d", strtotime($date . " +1 days")) . "T00:00:00", $customerTimezone);

        $reservationsUTC = $this->database
            ->table("reservations")
            ->where("user_id=? AND start BETWEEN ? AND ? AND type=0 AND reservations.status='VERIFIED'", [$user->id, $customerDayStartUTC, $customerDayEndUTC])
            ->where(":payments.status=0")
            ->fetchAll();
        foreach ($reservationsUTC as $rowUTC) {
            $rowUTCStart = $rowUTC->start;
            $rowDuration = $rowUTC->ref("services", "service_id")->duration;

            if ($duration <= $rowDuration) {
                $time = $this->moment->getTimezoneTimeFromUTCTime($rowUTCStart."", $customerTimezone);
                $results[] = date("H:i", strtotime($time.""));
            }
        }
        return $results;
    }

    private function checkAvailability($start, $end, $duration, $interval, string $customerTimezone, $workingHour)
    {
        $availableTimes = array();
        $verifiedReservations = $this->database->table("reservations")->where("user_id=? AND start BETWEEN ? AND ? AND status='VERIFIED' AND type=0", [$this->user_id, $start, $end])->fetchAll();
        $verificationTime = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i") . ' -' . $this->user_settings->verification_time . ' minutes'));
        $unverifiedReservations = $this->database->table("reservations")->where("user_id=? AND start BETWEEN ? AND ? AND status='UNVERIFIED' AND created_at > ? ", [$this->user_id, $start, $end, $verificationTime])->fetchAll();
        $exceptions = $this->database->table("workinghours_exceptions")->where("user_id=? AND start < ? AND end > ?", [$this->user_id, $start, $end])->fetchAll();

        $start = strtotime($start);
        $end = strtotime($end);
        while ($start < $end) {
            $newReservationEnd = strtotime(date("Y-m-d H:i") . " + " . $duration . " minutes");
            $isAvailable = true;
            //reservations
            foreach ($verifiedReservations as $row) {
                $rowDuration = $row->ref("services", "service_id")->duration;
                $rowStart = strtotime($row->start);
                $rowEnd = strtotime($row->start . " + " . $rowDuration-1 . " minutes");

                //check if the reservation intersect row
                $overlap = ($start >= $rowStart && $start <= $rowEnd) || // Start of the second period is within the first period
                    ($newReservationEnd >= $rowStart && $newReservationEnd <= $rowEnd) || // End of the second period is within the first period
                    ($rowStart >= $start && $rowStart <= $newReservationEnd) || // Start of the first period is within the second period
                    ($rowEnd >= $start && $rowEnd <= $newReservationEnd) ||    // End of the first period is within the second period
                    ($rowStart == $start) || // Starts of both periods are the same
                    ($rowEnd == $newReservationEnd); // Ends of both periods are the same
                if ($overlap) {
                    $isAvailable = false;
                    break;
                }
            }
            //uverified reservations
            foreach ($unverifiedReservations as $row) {
                $rowDuration = $row->ref("services", "service_id")->duration;
                $rowStart = strtotime($row->start);
                $rowEnd = strtotime($row->start . " + " . $rowDuration-1 . " minutes");

                //check if the reservation intersect row
                $overlap = ($start >= $rowStart && $start <= $rowEnd) || // Start of the second period is within the first period
                    ($newReservationEnd >= $rowStart && $newReservationEnd <= $rowEnd) || // End of the second period is within the first period
                    ($rowStart >= $start && $rowStart <= $newReservationEnd) || // Start of the first period is within the second period
                    ($rowEnd >= $start && $rowEnd <= $newReservationEnd) ||    // End of the first period is within the second period
                    ($rowStart == $start) || // Starts of both periods are the same
                    ($rowEnd == $newReservationEnd); // Ends of both periods are the same
                if ($overlap) {
                    $isAvailable = false;
                    break;
                }
            }
            //exceptions
            foreach ($exceptions as $row) {
                $rowStart = strtotime($this->moment->getUTCTime($row->start."", $this->user_settings->time_zone));
                $rowEnd = strtotime($this->moment->getUTCTime($row->end."", $this->user_settings->time_zone));

                //check if the reservation intersect row
                $overlap = ($start >= $rowStart && $start <= $rowEnd) || // Start of the second period is within the first period
                    ($newReservationEnd >= $rowStart && $newReservationEnd <= $rowEnd) || // End of the second period is within the first period
                    ($rowStart >= $start && $rowStart <= $newReservationEnd) || // Start of the first period is within the second period
                    ($rowEnd >= $start && $rowEnd <= $newReservationEnd) ||    // End of the first period is within the second period
                    ($rowStart == $start) || // Starts of both periods are the same
                    ($rowEnd == $newReservationEnd); // Ends of both periods are the same
                if ($overlap) {
                    $isAvailable = false;
                    break;
                }
            }

            if ($isAvailable) {
                //convert time to customer timezone
                $availableTimes[] = date("H:i", strtotime($this->moment->getTimezoneTimeFromUTCTime($start, $customerTimezone)));
            }

            $start = $start + $interval * 60;
        }
        //remove time 00:00 if not on first index from array
        if (count($availableTimes) > 0 && $availableTimes[count($availableTimes)-1] == "00:00") {
            unset($availableTimes[count($availableTimes)-1]);
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
    //TODO add customerTimezone
    public function getAvailableStartingHours(string $u, string $date, int $duration, $customerTimezone = "Europe/Prague"): array
    {
        $user = $this->database->table("users")->where("uuid=?", $u)->fetch();
        $user_settings = $user->related("settings")->fetch();
        $this->user_id = $user->id;
        $this->user_settings = $user_settings;

        //customer
        $customerDayStartUTC = $this->moment->getUTCTime($date . "T00:00:00", $customerTimezone);
        $customerDayEndUTC = $this->moment->getUTCTime(date("Y-m-d", strtotime($date . " +1 days")) . "T00:00:00", $customerTimezone);

        $adminDayStart = $this->moment->getTimezoneTimeFromUTCTime($customerDayStartUTC, $user_settings->time_zone);
        $adminDayEnd = $this->moment->getTimezoneTimeFromUTCTime($customerDayEndUTC, $user_settings->time_zone);
        $adminStartDay = $this->moment->getDate($adminDayStart);
        $adminEndDay = $this->moment->getDate($adminDayEnd);
        $available = [];
        if ($adminStartDay == $adminEndDay) {
            //jeden den
            $workingHoursStart = $this->database->table("workinghours")->where("user_id=? AND weekday=?", [$this->user_id, $this->getDay($adminStartDay)])->fetch();
            $startUTC = $this->moment->getUTCTime($adminStartDay . " " . $workingHoursStart->start, $user_settings->time_zone);
            if ($startUTC < $adminDayStart) {
                $startUTC = $this->moment->getUTCTime($adminDayStart, $user_settings->time_zone);
            }
            $endUTC = $this->moment->getUTCTime($adminStartDay . " " . $workingHoursStart->stop, $user_settings->time_zone);
            $available = $this->checkAvailability($startUTC, $endUTC, $duration, $user_settings->sample_rate, $customerTimezone, $workingHoursStart);
        } else {
            //dva dny

            $workingHoursStart = $this->database->table("workinghours")->where("user_id=? AND weekday=?", [$this->user_id, $this->getDay($adminStartDay)])->fetch();
            $startUTC = $this->moment->getUTCTime($adminStartDay . " " . $workingHoursStart->start, $user_settings->time_zone);
            if ($startUTC < $adminDayStart) {
                $startUTC = $this->moment->getUTCTime($adminDayStart, $user_settings->time_zone);
            }
            $endUTC = $this->moment->getUTCTime($adminStartDay . " " . $workingHoursStart->stop, $user_settings->time_zone);
            $day1 = $this->checkAvailability($startUTC, $endUTC, $duration, $user_settings->sample_rate, $customerTimezone, $workingHoursStart);

            foreach ($day1 as $day) {
                $available[] = $day;
            }

            $workingHoursEnd = $this->database->table("workinghours")->where("user_id=? AND weekday=?", [$this->user_id, $this->getDay($adminEndDay)])->fetch();
            $startUTC = $this->moment->getUTCTime($adminEndDay . " " . $workingHoursEnd->start, $user_settings->time_zone);
            $endUTC = $adminDayEnd;
            $day2 = $this->checkAvailability($startUTC, $endUTC, $duration, $user_settings->sample_rate, $customerTimezone, $workingHoursEnd);
            foreach ($day2 as $day) {
                $available[] = $day;
            }

        }
        return $available;
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
            $start = strtotime($row->start);
            foreach ($exceptions as $exception) {
                if (strtotime($exception->start) <= $start && strtotime($exception->end) >= $start) {
                    $conflicts[] = $exception->id;
                }
            }
        }
        return $conflicts;

    }

    public function getConflictedReservations($uuid): array
    {
        $exception = $this->database->table("workinghours_exceptions")->where("uuid=?", $uuid)->fetch();
        $user_settings = $this->database->table("settings")->where("user_id=?", $exception->user_id)->fetch();
        $exceptionStartUTC = strtotime($this->moment->getUTCTime($exception->start."", $user_settings->time_zone));
        $exceptionEndUTC = strtotime($this->moment->getUTCTime($exception->end."", $user_settings->time_zone));
        $reservationsUTC = $this->database->table("reservations")->where("user_id=?", $exception->user_id)->fetchAll();
        $conflicts = [];
        foreach ($reservationsUTC as $reservationUTC) {
            $start = strtotime($reservationUTC->start);
            if ($exceptionStartUTC <= $start && $exceptionEndUTC >= $start) {
                $conflicts[] = $reservationUTC;
            }
        }
        return $conflicts;

    }
}

