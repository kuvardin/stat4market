<?php

declare(strict_types=1);

namespace App\Traits;

use App;
use App\Actions\Action;
use App\Interfaces\CreatableInterface;
use App\Sessions\Session;
use Kuvardin\TinyOrm\Connection;
use Kuvardin\TinyOrm\EntityAbstract;
use Kuvardin\TinyOrm\Exception\AlreadyExists;
use Kuvardin\TinyOrm\Table;
use PDOException;
use Throwable;

/**
 * @mixin EntityAbstract
 */
trait CreatableTrait
{
    protected ?int $creation_id;

    protected function initCreatable(array $data): void
    {
        $this->creation_id = $data[CreatableInterface::COL_CREATION_ID];
    }

    /**
     * @see CreatableInterface::getCreationId()
     */
    public function getCreationId(): ?int
    {
        return $this->creation_id;
    }

    /**
     * @see CreatableInterface::getCreation()
     */
    public function getCreation(): ?Action
    {
        return $this->creation_id === null ? null : Action::requireOneById($this->creation_id);
    }

    protected function setCreation(Action|int $creation): void
    {
        $this->setFieldValue(CreatableInterface::COL_CREATION_ID, $this->creation_id, $creation);
    }

    /**
     * @return $this
     * @throws PDOException
     * @throws AlreadyExists
     */
    protected static function createCreatable(
        ?Session $session,
        array $data,
        Connection $connection = null,
        Table $table = null,
    ): static
    {
        $connection ??= App::pdo();
        $transaction_already_started = $connection->inTransaction();

        if (!$transaction_already_started) {
            $connection->beginTransaction();
        }

        try {
            $object = self::createByValuesSet(
                array_merge($data, [
                    CreatableInterface::COL_CREATION_ID => null,
                ]),
                connection: $connection,
                table: $table,
            );

            if ($session !== null) {
                $creation = Action::create($session, $object, Action::CREATE);
                $object->setCreation($creation);
                $object->saveChanges();
            }

            if (!$transaction_already_started) {
                $connection->commit();
            }
        } catch (Throwable $exception) {
            if (!$transaction_already_started) {
                $connection->rollback();
            }

            throw $exception;
        }

        return $object;
    }
}