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

    public function getAvailableDates(string $u, int $duration, int $numberOfDays, int $service_id): array
    {
        $date = date("Y-m-d");
        $available = [];
        for ($i = 0; $i < $numberOfDays; $i++) {
            if ($this->getAvailableStartingHours($u, $date, $duration, $service_id) || $this->getBackupHours($u, $date, $duration, intval($service_id))) {
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
    public function getBackupHours(string $u, string $date, int $duration, int $service_id): array
    {
        $results = array();
        $user = $this->database->table("users")->where("uuid=?", $u)->fetch();
        //customer
        $dayStart = $date . " 00:00:00";
        $dayEnd = $date . " 23:59:59";

        $reservations = $this->database
            ->table("reservations")
            ->where("user_id=? AND start BETWEEN ? AND ? AND type=0 AND reservations.status='VERIFIED' AND service_id=?", [$user->id, $dayStart, $dayEnd, $service_id])
            ->where(":payments.status=0")
            ->fetchAll();

        foreach ($reservations as $row) {
            $rowStart = $row->start;
            $rowDuration = $row->ref("services", "service_id")->duration;

            $results[] = date("H:i", strtotime($rowStart));

        }
        $verificationTime = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i") . ' -' . $this->user_settings->verification_time . ' minutes'));
        $unverifiedReservations = $this->database->table("reservations")->where("user_id=? AND start BETWEEN ? AND ? AND status='UNVERIFIED' AND created_at > ? AND service_id=?", [$this->user_id, $dayStart, $dayEnd, $verificationTime, $service_id])->fetchAll();
        foreach ($unverifiedReservations as $row) {
            $rowStart = $row->start;
            $rowDuration = $row->ref("services", "service_id")->duration;

            if ($duration <= $rowDuration) {
                $results[] = date("H:i", strtotime($rowStart));
            }
        }
        return $results;
    }

    private function checkAvailability($start, $end, $duration, $interval, $workingHour)
    {
        $availableTimes = array();
        $verifiedReservations = $this->database->table("reservations")->where("user_id=? AND start BETWEEN ? AND ? AND status='VERIFIED' AND type=0", [$this->user_id, $start, $end])->fetchAll();
        $verificationTime = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i") . ' -' . $this->user_settings->verification_time . ' minutes'));
        $unverifiedReservations = $this->database->table("reservations")->where("user_id=? AND start BETWEEN ? AND ? AND status='UNVERIFIED' AND created_at > ? ", [$this->user_id, $start, $end, $verificationTime])->fetchAll();
        $exceptions = $this->database->table("workinghours_exceptions")->where("user_id=?", $this->user_id)->fetchAll();

        $breaks = $workingHour->related("breaks")->fetchAll();
        //$breaks = $this->database->table("workinghours_breaks")->where("user_id=? AND start < ? AND end > ?", [$this->user_id, $start, $end])->fetchAll();

        $date = $this->moment->getDate($start);

        $start = strtotime($start);
        $end = strtotime($end);

        while ($start < $end) {
            $newReservationEnd = strtotime(date("Y-m-d H:i", $start) . " + " . $duration-1 . " minutes");
            $isAvailable = true;

            //exceptions
            if ($isAvailable) {
                foreach ($exceptions as $row) {
                    $rowStart = strtotime($row->start . " + 1 minute");
                    $rowEnd = strtotime($row->end . " - 1 minute");


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
            }

            if ($isAvailable) {
                //breaks
                foreach ($breaks as $break) {
                    $rowStart = strtotime($date . " " . $break->start);
                    $rowEnd = strtotime($date . " " . $break->end . "- 1 minute");
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
            }

            if ($isAvailable) {
                //reservations
                foreach ($verifiedReservations as $row) {
                    $rowDuration = $row->ref("services", "service_id")->duration;
                    $rowStart = strtotime($row->start);
                    $rowEnd = strtotime($row->start . " + " . $rowDuration - 1 . " minutes");

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
            }

            if ($isAvailable) {
                //uverified reservations
                foreach ($unverifiedReservations as $row) {
                    $rowDuration = $row->ref("services", "service_id")->duration;
                    $rowStart = strtotime($row->start);
                    $rowEnd = strtotime($row->start . " + " . $rowDuration - 1 . " minutes");

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
            }

            if ($isAvailable) {
                //convert time to customer timezone
                $availableTimes[] = date("H:i", $start);
            }

            $start = $start + $interval * 60;
        }

        return $availableTimes;
    }

    private function getCustomScheduleAvailability($user, $schedule, $duration, $date, $service_id)
    {
        $result = array();

        $dayStart = $date . " 00:00";
        $dayEnd = $date . " 23:59";

        $verifiedReservations = $this->database->table("reservations")
            ->where("user_id=? AND start BETWEEN ? AND ?", [$this->user_id, $dayStart, $dayEnd])
            ->where("status=? AND type=0 AND service_id = ?", ["VERIFIED", $service_id])
            ->fetchAll();
        $verificationTime = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i") . ' -' . $this->user_settings->verification_time . ' minutes'));
        $unverifiedReservations = $this->database->table("reservations")->where("user_id=? AND start BETWEEN ? AND ? AND status='UNVERIFIED' AND created_at > ? ", [$this->user_id, $dayStart, $dayEnd, $verificationTime])->fetchAll();
        $exceptions = $this->database->table("workinghours_exceptions")->where("user_id=?", $this->user_id)->fetchAll();


        bdump($verifiedReservations);
        $days = $schedule->related("service_custom_schedule_days")->where("start BETWEEN ? AND ?", [$dayStart, $dayEnd])->fetchAll();
        foreach ($days as $day) {
            $start = strtotime($day->start);
            $end = strtotime($day->end);
            while ($start < $end) {
                $isAvailable = true;
                $newReservationEnd = strtotime(date("Y-m-d H:i", $start) . " + " . $duration-1 . " minutes");
                //exceptions
                if ($isAvailable) {
                    foreach ($exceptions as $row) {
                        $rowStart = strtotime($row->start . " + 1 minute");
                        $rowEnd = strtotime($row->end . " - 1 minute");


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
                }

                //resesevations
                if ($isAvailable) {
                    foreach ($verifiedReservations as $row) {
                        $rowDuration = $row->ref("services", "service_id")->duration;
                        $rowStart = strtotime($row->start);
                        $rowEnd = strtotime($row->start . " + " . $rowDuration - 1 . " minutes");

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
                }
                if ($isAvailable) {
                    //uverified reservations
                    foreach ($unverifiedReservations as $row) {
                        $rowDuration = $row->ref("services", "service_id")->duration;
                        $rowStart = strtotime($row->start);
                        $rowEnd = strtotime($row->start . " + " . $rowDuration - 1 . " minutes");

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
                }

                if ($isAvailable) {
                    $result[] = date("H:i", $start);
                }

                $start = $start + $duration * 60;
            }
        }
        return $result;
    }

    /**
     * Retrieves an array of available dates based on the provided parameters.
     *
     * @param string $date The date for which to retrieve available dates.
     * @param int $duration The duration in minutes of each available date.
     * @return array An array of available starting hours.
     */
    //TODO add customerTimezone
    public function getAvailableStartingHours(string $u, string $date, int $duration, int $service_id)
    {
        $available = [];

        $user = $this->database->table("users")->where("uuid=?", $u)->fetch();
        $user_settings = $user->related("settings")->fetch();
        $this->user_id = $user->id;
        $this->user_settings = $user_settings;
        $service = $this->database->table("services")->get($service_id);
        if ($serviceCustomSchedules = $service->related("services_custom_schedules")->where("start <= ? AND end >= ?", [$date, $date])->fetchAll()) {
            foreach ($serviceCustomSchedules as $schedule) {
                if ($results = $this->getCustomScheduleAvailability($user, $schedule, $duration, $date, $service_id)) {
                    foreach ($results as $result) {
                        $available[] = $result;
                    }

                }

            }

        } else {
            $workingHours = $this->database->table("workinghours")->where("user_id=? AND weekday=?", [$this->user_id, $this->getDay($date)])->fetch();
            $start = $date . " " . $workingHours->start;
            $end = $date . " " . $workingHours->stop;


            $available = $this->checkAvailability($start, $end, $duration, $user_settings->sample_rate, $workingHours);
        }
        bdump($date);
        bdump($available);
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
        $exceptionStartUTC = strtotime($this->moment->getUTCTime($exception->start . "", $user_settings->time_zone));
        $exceptionEndUTC = strtotime($this->moment->getUTCTime($exception->end . "", $user_settings->time_zone));
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

