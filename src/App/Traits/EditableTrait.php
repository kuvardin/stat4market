<?php

declare(strict_types=1);

namespace App\Traits;

use App\Actions\Action;
use App\Interfaces\EditableInterface;
use App\Sessions\Session;
use Kuvardin\FastMysqli\TableRow;

/**
 * @implements EditableInterface
 * @mixin TableRow
 */
trait EditableTrait
{
    protected bool $has_last_edit_id_column = false;

    protected ?int $last_edit_date;
    protected ?int $last_edit_id = null;

    public function initEditable(array $data): void
    {
        if (array_key_exists(EditableInterface::COL_LAST_EDIT_ID, $data)) {
            $this->has_last_edit_id_column = true;
            $this->last_edit_id = $data[EditableInterface::COL_LAST_EDIT_ID];
        }

        $this->last_edit_date = $data[EditableInterface::COL_LAST_EDIT_DATE];
    }

    /**
     * @see EditableInterface::getLastEditDate()
     */
    public function getLastEditDate(): ?int
    {
        return $this->last_edit_date;
    }

    /**
     * @see EditableInterface::getLastEdit()
     */
    public function getLastEdit(): ?Action
    {
        return $this->last_edit_id === null ? null : Action::findOneById($this->last_edit_id);
    }

    /**
     * @see EditableInterface::getLastEditId()
     */
    public function getLastEditId(): ?int
    {
        return $this->last_edit_id;
    }

    /**
     * @see EditableInterface::isEdited()
     */
    public function isEdited(): bool
    {
        return $this->last_edit_date !== null;
    }

    public function fixEdit(Session $session, int $date = null): void
    {
        $date ??= time();
        $action = Action::create($session, $this, Action::EDIT, $date);
        $this->setFieldValue(EditableInterface::COL_LAST_EDIT_DATE, $this->last_edit_date, $date);
        $this->setFieldValue(EditableInterface::COL_LAST_EDIT_ID, $this->last_edit_id, $action);
        $this->save();
    }
}