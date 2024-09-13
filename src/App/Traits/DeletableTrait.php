<?php

declare(strict_types=1);

namespace App\Traits;

use App;
use App\Actions\Action;
use App\Interfaces\DeletableInterface;
use App\Sessions\Session;
use Kuvardin\TinyOrm\EntityAbstract;
use RuntimeException;
use Throwable;

/**
 * @implements DeletableInterface
 * @mixin EntityAbstract
 */
trait DeletableTrait
{
    protected bool $has_deletion_id_column = false;

    protected ?int $deletion_date;
    protected ?int $deletion_id = null;

    protected function initDeletable(array $data): void
    {
        if (array_key_exists(DeletableInterface::COL_DELETION_ID, $data)) {
            $this->has_deletion_id_column = true;
            $this->deletion_id = $data[DeletableInterface::COL_DELETION_ID];
        }

        $this->deletion_date = $data[DeletableInterface::COL_DELETION_DATE];
    }

    /**
     * @see DeletableInterface::isDeleted()
     */
    public function isDeleted(): bool
    {
        return $this->deletion_date !== null;
    }

    /**
     * @see DeletableInterface::getDeletionDate()
     */
    public function getDeletionDate(): ?int
    {
        return $this->deletion_date;
    }

    /**
     * @see DeletableInterface::getDeletionId()
     */
    public function getDeletionId(): ?int
    {
        return $this->deletion_id;
    }

    /**
     * @see DeletableInterface::getDeletion()
     */
    public function getDeletion(): ?Action
    {
        return $this->deletion_id === null ? null : Action::findOneById($this->deletion_id);
    }

    /**
     * @see DeletableInterface::delete()
     */
    public function delete(?Session $session, int $deletion_date = null): void
    {
        if ($this->isDeleted()) {
            throw new RuntimeException('Element already deleted');
        }

        App::pdo()->beginTransaction();
        try {
            if ($session !== null && $this->has_deletion_id_column) {
                $deletion = Action::create($session, $this, Action::DELETE);
                $this->setFieldValue(DeletableInterface::COL_DELETION_ID, $this->deletion_id, $deletion);
            }

            $this->setFieldValue(DeletableInterface::COL_DELETION_DATE, $this->deletion_date, $deletion_date ?? time());
            $this->saveChanges();
            App::pdo()->commit();
        } catch (Throwable $throwable) {
            App::pdo()->rollback();
            /** @noinspection PhpUnhandledExceptionInspection */
            throw $throwable;
        }
    }

    /**
     * @see DeletableInterface::delete()
     */
    public function restore(?Session $session): void
    {
        if (!$this->isDeleted()) {
            throw new RuntimeException('Element already restored');
        }

        App::pdo()->beginTransaction();
        try {
            if ($this->has_deletion_id_column) {
                if ($session !== null) {
                    Action::create($session, $this, Action::RESTORE);
                }

                $this->setFieldValue(DeletableInterface::COL_DELETION_ID, $this->deletion_id, null);
            }

            $this->setFieldValue(DeletableInterface::COL_DELETION_DATE, $this->deletion_date, null);
            $this->saveChanges();
            App::pdo()->commit();
        } catch (Throwable $throwable) {
            App::pdo()->rollback();
            /** @noinspection PhpUnhandledExceptionInspection */
            throw $throwable;
        }
    }
}