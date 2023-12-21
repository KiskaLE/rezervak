<?php


declare(strict_types=1);

namespace App\Modules\admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Ramsey\Uuid\Uuid;
use App\Modules\AvailableDates;
use Nette\DI\Attributes\Inject;

final class WorkhoursPresenter extends SecurePresenter
{
    private $id;
    private $editId;
    private $day;

    private $exceptionUuid;

    #[Inject] public Nette\Database\Explorer $database;

    public function __construct(
        private Nette\Security\User     $user,
        private AvailableDates          $availableDates
    )
    {

    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->backlink = $this->storeRequest();
        $this->template->backlink = $this->backlink;
    }

    public function renderShow()
    {
        $days = $this->database->table("workinghours")->where("user_id=?", $this->user->id)->fetchAll();
        $this->template->days = $days;
        //exceptions
        $exceptions = $this->database->table("workinghours_exceptions")->where("user_id=? AND end>=?", [$this->user->id, date("Y-m-d H:i")])->fetchAll();
        $this->template->exceptions = $exceptions;

        //exceptions conflicts
        $conflicts = $this->availableDates->getReservationsConflictsIds($this->user->id);
        $this->template->conflicts = $conflicts;

    }

    public function renderExcerptionsConflicts($id)
    {
        $reservations = $this->availableDates->getConflictedReservations($id);
        $this->template->reservationsUTC = $reservations;
    }

    public function actionEdit($id)
    {
        $this->id = $id;
        $day = $this->database->table("workinghours")->where("user_id=? AND id=?", [$this->user->id, $id])->fetch();
        $this->day = $day;
        $this->template->day = $day;
        //breaks
        $breaks = $day->related("breaks")->fetchAll();
        $this->template->breaks = $breaks;
    }

    public function actionCreateBreak($id)
    {
        $this->template->id = $id;
        $this->id = $id;
        $day = $this->database->table("workinghours")->where("user_id=? AND id=?", [$this->user->id, $id])->fetch();
        $this->template->day = $day;

    }

    public function actionEditBreak($id, $edit_id)
    {
        $this->template->id = $id;
        $this->id = $id;
        $this->editId = $edit_id;
        $this->template->edit_id = $edit_id;
        $day = $this->database->table("workinghours")->where(":breaks.id=?", $id)->fetch();
        $this->template->day = $day;
    }

    public function actionDeleteBreak($id, $edit_id)
    {
        $this->template->id = $id;
        $this->editId = $edit_id;
        $this->template->edit_id = $edit_id;
        $this->id = $id;
    }

    public function actionEditException($id)
    {
        $this->exceptionUuid = $id;
        $exception = $this->database->table("workinghours_exceptions")->where("uuid=?", $id)->fetch();
        $this->template->exception = $exception;
    }

    public function handleDeleteException($uuid)
    {
        $this->database->table("workinghours_exceptions")->where("uuid=?", $uuid)->delete();
        $this->flashMessage("Smazano", "success");
        $this->redirect("show");
    }

    public function handleDeleteBreak($break_id)
    {
        $this->database->table("breaks")->get($break_id)->delete();
        $this->flashMessage("Smazano", "success");
        $this->redirect("edit", $this->id);
    }

    protected function createComponentEditForm(): Form
    {
        $form = new Form;
        $form->addText("start")
            ->setDefaultValue($this->day->start)
            ->setHtmlAttribute("type", "time")
            ->setRequired();
        $form->addText("stop")
            ->setDefaultValue($this->day->stop)
            ->setHtmlAttribute("type", "time")
            ->setRequired();
        $form->addSubmit("submit", "Uložit");

        $form->onSuccess[] = [$this, "editSuccess"];

        return $form;
    }

    public function editSuccess(Form $form, $data)
    {
        $start = strtotime($data->start);
        $stop = strtotime($data->stop);
        if ($start > $stop) {
            $this->flashMessage("Začátek nemůže být větší než konec", "error");
        } else {
            $this->database->table("workinghours")->where("id=?", $this->id)->update([
                "start" => $data->start,
                "stop" => $data->stop,
            ]);
            $this->flashMessage("Uloženo", "success");
        }

    }


    /*
     *
     * Breaks
     */

    protected function createComponentCreateBreakForm(): Form
    {
        $form = new Form;
        $form->addText("start")
            ->setHtmlAttribute("type", "time")
            ->setRequired();
        $form->addText("stop")
            ->setHtmlAttribute("type", "time")
            ->setRequired();
        $form->addSubmit("submit", "Uložit");

        $form->onSuccess[] = [$this, "createBreakSuccess"];

        return $form;
    }

    public function createBreakSuccess(Form $form, $data)
    {
        $day = $this->database->table("workinghours")->where("id=?", $this->id)->fetch();
        $dayStart = strtotime($day->start);
        $dayEnd = strtotime($day->stop);

        if (strtotime($data->start) >= $dayStart && strtotime($data->stop) <= $dayEnd) {
            $start = strtotime($data->start);
            $stop = strtotime($data->stop);
            if ($start > $stop) {
                $this->flashMessage("Začátek nemůže být větší než konec", "error");
            } else {
                $this->database->table("breaks")->insert([
                    "start" => $data->start,
                    "end" => $data->stop,
                    "workinghour_id" => $this->id,
                    "type" => 0
                ]);
                $this->flashMessage("Přestávka byla úspešně vytvořenag", "success");
                $this->redirect("Workhours:edit", $this->id);
            }
        } else {
            $this->flashMessage("Přestávka musí být v rozsahu {$day->start} - {$day->stop} hodin", "error");
        }
    }

    protected function createComponentDeleteBreakForm(): Form
    {
        $form = new Form;

        $form->addSubmit("submit", "Smazat");
        $form->onSuccess[] = [$this, "deleteBreakSuccess"];

        return $form;
    }

    public function deleteBreakSuccess(Form $form, $data)
    {
        try {
            $this->database->table("breaks")->where("id=?", $this->id)->delete();
            $this->flashMessage("Smazáno", "success");
        } catch (\Throwable $th) {
            $this->flashMessage("Nepodarilo se smazat", "error");
        }
        $this->redirect("Workhours:edit", $this->editId);
    }

    protected function createComponentEditBreakForm(): Form
    {
        $id = $this->id;
        $break = $this->database->table("breaks")->where("id=?", $id)->fetch();
        $form = new Form;
        $form->addText("start")
            ->setHtmlAttribute("type", "time")
            ->setRequired()
            ->setDefaultValue($break->start);
        $form->addText("stop")
            ->setHtmlAttribute("type", "time")
            ->setRequired()
            ->setDefaultValue($break->end);
        $form->addSubmit("submit", "Uložit");
        $form->onSuccess[] = [$this, "editBreakSuccess"];
        return $form;
    }

    public function editBreakSuccess(Form $form, $data)
    {
        $day = $this->database->table("workinghours")->where("id=?", $this->editId)->fetch();
        $dayStart = strtotime($day->start);
        $dayEnd = strtotime($day->stop);

        if (strtotime($data->start) >= $dayStart && strtotime($data->stop) <= $dayEnd) {
            $start = strtotime($data->start);
            $stop = strtotime($data->stop);
            if ($start > $stop) {
                $this->flashMessage("Začátek nemůže být větší než konec", "error");
            } else {
                $this->database->table("breaks")->where("id=?", $this->id)->update([
                    "start" => $data->start,
                    "end" => $data->stop,
                ]);
                $this->flashMessage("Uloženo", "success");
                $this->redirect("Workhours:edit", $this->editId);
            }
        } else {
            $this->flashMessage("Přestávka musí být v rozsahu {$day->start} - {$day->stop} hodin", "error");
        }
    }

    /*
     *
     * Exceptions
     */
    protected function createComponentCreateExceptionForm(): Form
    {
        $form = new Form;

        $form->addText("name")
            ->setRequired();
        $form->addText("date")
            ->setRequired();
        $form->addSubmit("submit", "Vytvořit");

        $form->onSuccess[] = [$this, "createExceptionSuccess"];

        return $form;
    }

    public function createExceptionSuccess(Form $form, $data)
    {
        $uuid = Uuid::uuid4();
        $datesStartAndEnd = explode("-", trim($data->date));

        //start
        $start = explode(" ", trim($datesStartAndEnd[0]));
        $dateStart = explode("/", $start[0]);
        $timeStart = $start[1];
        $start = trim($dateStart[2] . "-" . $dateStart[1] . "-" . $dateStart[0] . " " . $timeStart);
        //end
        $end = explode(" ", trim($datesStartAndEnd[1]));
        $dateEnd = explode("/", $end[0]);
        $timeEnd = $end[1];
        $end = trim($dateEnd[2] . "-" . $dateEnd[1] . "-" . $dateEnd[0] . " " . $timeEnd);

        $this->database->table("workinghours_exceptions")->insert([
            "uuid" => $uuid,
            "name" => $data->name,
            "start" => $start,
            "end" => $end,
            "user_id" => $this->user->id,
        ]);
        $this->flashMessage("Vytvořeno", "success");
        $this->redirect("Workhours:show");
    }

    protected function createComponentEditExceptionForm(): Form
    {

        $exception = $this->database->table("workinghours_exceptions")->where("uuid=?", $this->exceptionUuid)->fetch();


        $form = new Form;

        $form->addText("name")
            ->setDefaultValue($exception->name)
            ->setRequired();
        $form->addText("date")
            ->setRequired();
        $form->addSubmit("submit", "Uložit");

        $form->onSuccess[] = [$this, "editExceptionSuccess"];

        return $form;
    }

    public function editExceptionSuccess(Form $form, $data)
    {
        $datesStartAndEnd = explode("-", trim($data->date));

        //start
        $start = explode(" ", trim($datesStartAndEnd[0]));
        $dateStart = explode("/", $start[0]);
        $timeStart = $start[1];
        $start = trim($dateStart[2] . "-" . $dateStart[1] . "-" . $dateStart[0] . " " . $timeStart) . ":00";
        //end
        $end = explode(" ", trim($datesStartAndEnd[1]));
        $dateEnd = explode("/", $end[0]);
        $timeEnd = $end[1];
        $end = trim($dateEnd[2] . "-" . $dateEnd[1] . "-" . $dateEnd[0] . " " . $timeEnd) . "00";

        $this->database->table("workinghours_exceptions")->where("uuid=?", $this->exceptionUuid)->update([
            "name" => $data->name,
            "start" => $start,
            "end" => $end,
        ]);
        $this->flashMessage("Uloženo", "success");
        $this->redirect("Workhours:show");
    }


}
