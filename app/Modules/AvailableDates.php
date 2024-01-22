<?php

namespace App\Modules;

use Nette;
use App\Modules\Moment;

class AvailableDates
{

    public function __construct(
        private Nette\Database\Explorer $database,
        private Moment              $moment,
        private Nette\Security\User $user,
    )
    {
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

public function isTimeToPay(string $start, $userSettings): bool
{
    $now = $this->moment->getNowPrague();
    $lastTimeToPay = date("Y-m-d H:i:s", strtotime($start . ' -' . $userSettings->time_to_pay . ' hours'));
    if ($now >= $lastTimeToPay) {
        return false;
    }
    return true;
}

public function getAvailableStartingHours(string $u, string $date, $service)
{
    $available = [];
    $user = $this->database->table("users")->where("uuid=?", $u)->fetch();
    $userSettings = $user->related("settings")->fetch();
    $service = $this->database->table("services")->get($service->id);

    if ($service->type == 1) {
        $serviceCustomSchedules = $service->related("services_custom_schedules")->fetchAll();
        foreach ($serviceCustomSchedules as $schedule) {
            $results = $this->getCustomScheduleAvailability($user, $userSettings, $schedule, $service, $date);
            $available = array_merge($available, $results);
        }
    } else if ($service->type == 0) {
        $workingHours = $this->database->table("workinghours")->where("user_id=? AND weekday=?", [$user->id, $this->getDay($date)])->fetchAll();
        $available = [];
        foreach ($workingHours as $row) {
            $start = $date . " " . $row->start;
            $end = $date . " " . $row->stop;
            $result = $this->checkAvailability($user, $userSettings, $start, $end, $service, $row);
            foreach ($result as $res) {
                $available[] = $res;
            }
        }
    }

    return array_unique($available);
}

public function getBackupHours(string $u, string $date, $service): array
{
    $results = [];
    $user = $this->database->table("users")
        ->where("uuid=?", $u)
        ->fetch();

    $userSettings = $user->related("settings")
        ->fetch();

    $dayStart = $date . " 00:00:00";
    $dayEnd = $date . " 23:59:59";

    $reservations = $this->database->table("reservations")
        ->where("user_id=? AND start BETWEEN ? AND ? AND type=0 AND reservations.status='VERIFIED' AND service_id=?", [$user->id, $dayStart, $dayEnd, $service->id])
        ->where(":payments.status=0")
        ->fetchAll();

    foreach ($reservations as $row) {
        if ($this->checkIfPaymentIsPossibleToBePaid($row->start, $userSettings, $user)) {
            $results[] = date("H:i", strtotime($row->start));
        }
    }

    $verificationTime = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i") . ' -' . $userSettings->verification_time . ' minutes'));
    $unverifiedReservations = $this->database->table("reservations")
        ->where("user_id=? AND start BETWEEN ? AND ? AND status='UNVERIFIED' AND created_at > ? AND service_id=?", [$user->id, $dayStart, $dayEnd, $verificationTime, $service->id])
        ->fetchAll();

    foreach ($unverifiedReservations as $row) {
        if ($this->checkIfPaymentIsPossibleToBePaid($row->start, $userSettings, $user)) {
            $results[] = date("H:i", strtotime($row->start));
        }
    }

    $results = array_unique($results);

    return $results;
}

    public function getAvailableDates(string $u, int $numberOfDays, $service): array
    {
        $date = date("Y-m-d");
        $available = [];
        for ($i = 0; $i < $numberOfDays; $i++) {
            if ($this->getAvailableStartingHours($u, $date, $service) || $this->getBackupHours($u, $date, $service)) {
                $available[] = $date;
            }
            //add one day to curDay
            $date = date('Y-m-d', strtotime($date . ' +1 days'));
        }
        return $available;

    }

public function getNumberOfAvailableTimes(string $u, int $numberOfDays, $service): int {
    $date = date("Y-m-d");
    $available = 0;
    
    for ($i = 0; $i < $numberOfDays && $available <= 10; $i++) {
        $count = count($this->getAvailableStartingHours($u, $date, $service));
        $available += $count;

        // add one day to curDay
        $date = date('Y-m-d', strtotime($date . ' +1 days'));
    }
    
    return $available;
}


    public function isTimeAvailable(string $u, $reservation, $service): bool
    {
        $date = date("Y-m-d", strtotime($reservation->start));
        $time = date("H:i", strtotime($reservation->start));
        $times = $this->getAvailableStartingHours($u, $date, $service);
        //if you find start in times array return true
        if (in_array($time, $times)) {
            return true;
        }
        return false;
    }

    public function getCustomSchedulesConflictsIds($service)
    {
        $conflicts = [];

        $customShedules = $service->related("services_custom_schedules")
            ->where("end >= NOW()")
            ->fetchAll();
        foreach ($customShedules as $row) {
            if ($this->getCustomSchedulesConflicts($service, $row)) {
                $conflicts[] = $row->id;
            }
        }
        return $conflicts;
    }

    public function getCustomSchedulesConflicts($service, $customSchedule): array
    {
        $conflicts = [];

        $days = $customSchedule->related("service_custom_schedule_days")->fetchAll();
        $userSettings = $this->database->table("settings")->where("user_id=?", $this->user->id)->fetch();
        $timeToVerify = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i") . ' -' . $userSettings->verification_time . ' minutes'));
        //get reservation in customSchedule range
        $reservations = $this->database->table("reservations")->where("start BETWEEN ? AND ? AND service_id=? AND (status='VERIFIED' OR (created_at > ? AND status='UNVERIFIED'))", [$customSchedule->start, $customSchedule->end, $service->id, $timeToVerify])->fetchAll();
        //check if reservation is in day range
        foreach ($reservations as $reservation) {
            $reservationEnd = date("Y-m-d H:i", strtotime($reservation->start . " + " . $service->duration . " minutes"));
            foreach ($days as $day) {
                if (!($reservation->start >= $day->start && $reservationEnd <= $day->end)) {
                    $conflicts[] = $reservation;
                    break;
                }
            }
        }
        return $conflicts;
    }


    private function checkIfPaymentIsPossibleToBePaid($timestamp, $userSettings, $user): bool
    {
        $now = date("Y-m-d H:i:s");
        //get number of backup reservations
        $numberOfBackupReservations = $this->getNumberOfBackupReservations($user, $userSettings, $timestamp);
        $timeToPay = ($numberOfBackupReservations + 1) * $userSettings->time_to_pay;
        $lastTimeToPay = date("Y-m-d H:i:s", strtotime($timestamp . ' -' . $timeToPay . ' hours'));

        return $lastTimeToPay > $now;
    }

    private function getNumberOfBackupReservations($user, $userSettings, string $timestamp): int
    {
        $backupReservationsCount = $this->database->table("reservations")
            ->where("user_id=? AND start=? AND type=1 AND status='VERIFIED'", [$user->id, $timestamp])
            ->count();
        $timeToVerify = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i") . ' -' . $userSettings->verification_time . ' minutes'));
        $unverifiedReservationsCount = $this->database->table("reservations")
            ->where("user_id=? AND start=? AND created_at > ? AND type=1 AND status='UNVERIFIED'", [$user->id, $timestamp, $timeToVerify])
            ->count();
        return $backupReservationsCount + $unverifiedReservationsCount;

    }

    private function checkAvailability($admin ,$userSettings, $start, $end, $service, $workingHour)
    {
        $availableTimes = array();
        $verifiedReservations = $this->database->table("reservations")->where("user_id=? AND start BETWEEN ? AND ? AND status='VERIFIED' AND type=0", [$admin->id, $start, $end])->fetchAll();
        $verificationTime = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i") . ' -' . $userSettings->verification_time . ' minutes'));
        $unverifiedReservations = $this->database->table("reservations")->where("user_id=? AND start BETWEEN ? AND ? AND status='UNVERIFIED' AND created_at > ? AND type=0 ", [$admin->id, $start, $end, $verificationTime])->fetchAll();
        $exceptions = $this->database->table("workinghours_exceptions")->where("user_id=?", $admin->id)->fetchAll();

        $breaks = $workingHour->related("breaks")->fetchAll();
        $date = $this->moment->getDate($start);

        $start = strtotime($start);
        $end = strtotime($end);

        while ($start < $end) {
            $newReservationEnd = strtotime(date("Y-m-d H:i", $start) . " + " . $service->duration - 1 . " minutes");
            $isAvailable = true;

            //check if it is time to pay
            if (!$this->isTimeToPay(date("Y-m-d H:i", $start), $userSettings)) {
                $isAvailable = false;
            }

            //exceptions
            if ($isAvailable) {
                foreach ($exceptions as $row) {
                    $rowStart = strtotime($row->start . " + 1 minute");
                    $rowEnd = strtotime($row->end . " - 1 minute");
                    $overlap = $this->timesOverlaps($start, $newReservationEnd, $rowStart, $rowEnd);
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
                    $overlap = $this->timesOverlaps($start, $newReservationEnd, $rowStart, $rowEnd);
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
                    $overlap = $this->timesOverlaps($start, $newReservationEnd, $rowStart, $rowEnd);
                    if ($overlap) {
                        $isAvailable = false;
                        break;
                    }
                }
            }

            if ($isAvailable && $newReservationEnd > $end) {
                $isAvailable = false;
            }

            if ($isAvailable) {
                //convert time to customer timezone
                $availableTimes[] = date("H:i", $start);
            }

            $start = $start + $userSettings->sample_rate * 60;
        }

        return $availableTimes;
    }

    private function getCustomScheduleAvailability($user ,$userSettings, $schedule, $service, $date)
    {
        $result = array();

        $dayStart = $date . " 00:00";
        $dayEnd = $date . " 23:59";

        $verifiedReservations = $this->database->table("reservations")
            ->where("user_id=? AND start BETWEEN ? AND ?", [$user->id, $dayStart, $dayEnd])
            ->where("status=? AND type=0 AND service_id = ?", ["VERIFIED", $service->id])
            ->fetchAll();
        $verificationTime = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i") . ' -' . $userSettings->verification_time . ' minutes'));
        $unverifiedReservations = $this->database->table("reservations")->where("user_id=? AND start BETWEEN ? AND ? AND status='UNVERIFIED' AND created_at > ? ", [$user->id, $dayStart, $dayEnd, $verificationTime])->fetchAll();
        $exceptions = $this->database->table("workinghours_exceptions")->where("user_id=?", $user->id)->fetchAll();

        $days = $schedule->related("service_custom_schedule_days")->where("start BETWEEN ? AND ?", [$dayStart, $dayEnd])->fetchAll();
        foreach ($days as $day) {
            $start = strtotime($day->start);
            $end = strtotime($day->end);
            while ($start < $end) {
                $isAvailable = true;
                $newReservationEnd = strtotime(date("Y-m-d H:i", $start) . " + " . $service->duration - 1 . " minutes");

                //check if it is time to pay
                if (!$this->isTimeToPay(date("Y-m-d H:i", $start), $userSettings)) {
                    $isAvailable = false;
                }

                //exceptions
                if ($isAvailable) {
                    foreach ($exceptions as $row) {
                        $rowStart = strtotime($row->start . " + 1 minute");
                        $rowEnd = strtotime($row->end . " - 1 minute");

                        $overlap = $this->timesOverlaps($rowStart, $rowEnd, $start, $newReservationEnd);
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
                        $overlap = $this->timesOverlaps($rowStart, $rowEnd, $start, $newReservationEnd);
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
                        $overlap = $this->timesOverlaps($rowStart, $rowEnd, $start, $newReservationEnd);
                        if ($overlap) {
                            $isAvailable = false;
                            break;
                        }
                    }
                }

                if ($isAvailable && $newReservationEnd > $end) {
                    $isAvailable = false;
                }

                if ($isAvailable) {
                    $result[] = date("H:i", $start);
                }

                $start = $start + $userSettings->sample_rate * 60;
            }
        }
        return $result;
    }

    private function timesOverlaps($start1, $end1, $start2, $end2) {
        $overlap = ($start2 >= $start1 && $start2 <= $end1) || // Start of the second period is within the first period
                            ($end2 >= $start1 && $end2 <= $end1) || // End of the second period is within the first period
                            ($start1 >= $start2 && $start1 <= $end2) || // Start of the first period is within the second period
                            ($end1 >= $start2 && $end1 <= $end2) ||    // End of the first period is within the second period
                            ($start1 == $start2) || // Starts of both periods are the same
                            ($end1 == $end2); // Ends of both periods are the same
        return $overlap;
    }

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
}

