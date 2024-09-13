<?php

declare(strict_types=1);

namespace App\Users;

use App;
use App\Interfaces\CreatableInterface;
use App\Traits\CreationDateRequiredTrait;
use Kuvardin\TinyOrm\Connection;
use Kuvardin\TinyOrm\EntityAbstract;
use Kuvardin\TinyOrm\Exception\AlreadyExists;
use Kuvardin\TinyOrm\Table;

class User extends EntityAbstract
{
    use CreationDateRequiredTrait;

    public const string COL_PHONE_NUMBER = 'phone_number';
    public const string COL_USERNAME = 'username';
    public const string COL_PASSWORD_HASH = 'password_hash';
    public const string COL_FIRST_NAME = 'first_name';
    public const string COL_LAST_NAME = 'last_name';
    public const string COL_MIDDLE_NAME = 'middle_name';
    public const string COL_LAST_REQUEST_DATE = 'last_request_date';

    protected string $phone_number;
    protected ?string $username;
    protected ?string $password_hash;
    protected ?string $first_name;
    protected ?string $last_name;
    protected ?string $middle_name;
    protected ?int $last_request_date;

    public function __construct(Connection $connection, Table $table, array $data)
    {
        parent::__construct($connection, $table, $data);
        $this->phone_number = $data[self::COL_PHONE_NUMBER];
        $this->username = $data[self::COL_USERNAME];
        $this->password_hash = $data[self::COL_PASSWORD_HASH];
        $this->first_name = $data[self::COL_FIRST_NAME];
        $this->last_name = $data[self::COL_LAST_NAME];
        $this->middle_name = $data[self::COL_MIDDLE_NAME];
        $this->last_request_date = $data[self::COL_LAST_REQUEST_DATE];
        $this->creation_date = $data[CreatableInterface::COL_CREATION_DATE];
    }

    public static function getEntityTableDefault(): Table
    {
        return new Table('users');
    }

    /**
     * @throws AlreadyExists
     */
    public static function create(
        string $phone_number,
        string $username = null,
        string $password = null,
        string $first_name = null,
        string $last_name = null,
        string $middle_name = null,
        int $last_request_date = null,
        int $creation_date = null,
    ): self
    {
        return self::createByValuesSet([
            self::COL_PHONE_NUMBER => $phone_number,
            self::COL_USERNAME => $username,
            self::COL_PASSWORD_HASH => self::hashPassword($password),
            self::COL_FIRST_NAME => $first_name,
            self::COL_LAST_NAME => $last_name,
            self::COL_MIDDLE_NAME => $middle_name,
            self::COL_LAST_REQUEST_DATE => $last_request_date,
            CreatableInterface::COL_CREATION_DATE => $creation_date ?? time(),
        ]);
    }

    public static function findOneByUsername(string $username): ?self
    {
        return self::findOneByConditions([
            self::COL_USERNAME => $username,
        ]);
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function getFullName(bool $with_middle_name = true): ?string
    {
        $result = trim(implode(
            ' ',
            $with_middle_name
                ? [$this->last_name, $this->first_name, $this->middle_name]
                : [$this->last_name, $this->first_name],
        ));

        return $result === '' ? null : $result;
    }

    public function fixRequest(int $current_timestamp = null): void
    {
        $this->setLastRequestDate($current_timestamp ?? time());
    }

    public function getPhoneNumber(): string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(string $phone_number): void
    {
        $this->setFieldValue(self::COL_PHONE_NUMBER, $this->phone_number, $phone_number);
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getUsernameFull(): ?string
    {
        return $this->username === null ? null : ('@' . $this->username);
    }

    public function setUsername(?string $username): void
    {
        $this->setFieldValue(self::COL_USERNAME, $this->username, $username);
    }

    public function checkPassword(string $password): bool
    {
        return $this->password_hash !== null && password_verify($password, $this->password_hash);
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(?string $first_name): void
    {
        $this->setFieldValue(self::COL_FIRST_NAME, $this->first_name, $first_name);
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $last_name): void
    {
        $this->setFieldValue(self::COL_LAST_NAME, $this->last_name, $last_name);
    }

    public function getMiddleName(): ?string
    {
        return $this->middle_name;
    }

    public function setMiddleName(?string $middle_name): void
    {
        $this->setFieldValue(self::COL_MIDDLE_NAME, $this->middle_name, $middle_name);
    }

    public function getLastRequestDate(): ?int
    {
        return $this->last_request_date;
    }

    public function setLastRequestDate(?int $last_request_date): void
    {
        $this->setFieldValue(self::COL_LAST_REQUEST_DATE, $this->last_request_date, $last_request_date);
    }

    public function isOnline(int $current_timestamp = null): bool
    {
        return $this->last_request_date !== null
            && $this->last_request_date > (($current_timestamp ?? time()) - App::settings('time_online'));
    }
}