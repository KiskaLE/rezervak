<?php
namespace App\Modules;

use Nette;
use App\Modules\Moment;

class Formater {

    private $timezone;
    public function __construct(
        private Nette\Database\Explorer $database,
        private Nette\Security\User $user,
        private Moment $moment
    ){
    }

    public function convertToAdminTimezone($time) {
        $this->timezone = $this->database->table("settings")->where("user_id=?", $this->user->id)->fetch()->time_zone;
        return $this->moment->getTimezoneTimeFromUTCTime($time."", $this->timezone);
    }


}