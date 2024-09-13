<?php

declare(strict_types=1);

namespace App\Sessions;

use App\Interfaces\CreatableInterface;
use App\Interfaces\DeletableInterface;
use App\Traits\DeletableTrait;
use App\Users\User;
use App\Users\UserRequiredTrait;
use Kuvardin\FastMysqli\Exceptions\AlreadyExists;
use Kuvardin\TinyOrm\Connection;
use Kuvardin\TinyOrm\EntityAbstract;
use Kuvardin\TinyOrm\Table;

class Authorization extends EntityAbstract implements DeletableInterface
{
    use SessionRequiredTrait;
    use UserRequiredTrait;
    use DeletableTrait;

    public const string COL_SESSION_ID = 'session_id';
    public const string COL_USER_ID = 'user_id';
    public const string COL_DEACTIVATION_DATE = 'deactivation_date';

    protected int $deactivation_date;

    public function __construct(Connection $connection, Table $table, array $data)
    {
        parent::__construct($connection, $table, $data);
        $this->initDeletable($data);
        $this->session_id = $data[self::COL_SESSION_ID];
        $this->user_id = $data[self::COL_USER_ID];
        $this->deactivation_date = $data[self::COL_DEACTIVATION_DATE];
    }

    public static function getEntityTableDefault(): Table
    {
        return new Table('authorizations');
    }

    /**
     * @throws AlreadyExists
     */
    public static function create(
        Session|int $session,
        User|int $user,
        int $deactivation_date,
        int $creation_date = null,
    ): self
    {
        return self::createByValuesSet([
            self::COL_SESSION_ID => $session,
            self::COL_USER_ID => $user,
            self::COL_DEACTIVATION_DATE => $deactivation_date,
            CreatableInterface::COL_CREATION_DATE => $creation_date ?? time(),
        ]);
    }

    public function getDeactivationDate(): int
    {
        return $this->deactivation_date;
    }

    public function setDeactivationDate(int $deactivation_date): void
    {
        $this->deactivation_date = $deactivation_date;
    }

    public function isActive(int $current_timestamp = null): bool
    {
        return $this->deactivation_date > ($current_timestamp ?? time());
    }
}