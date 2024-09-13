<?php

declare(strict_types=1);

namespace App\Sessions;

use App;
use App\Actions\Action;
use App\Interfaces\CreatableInterface;
use App\Languages\Locale;
use App\Languages\LocaleRequiredTrait;
use App\Traits\CreationDateRequiredTrait;
use App\Utils\DateTime;
use App\Exceptions\NotEnoughRightsException;
use App\Users\User;
use DateTimeZone;
use Kuvardin\TinyOrm\Conditions\Condition;
use Kuvardin\TinyOrm\Conditions\ConditionsList;
use Kuvardin\TinyOrm\Connection;
use Kuvardin\TinyOrm\EntityAbstract;
use Kuvardin\TinyOrm\Enums\Operator;
use Kuvardin\TinyOrm\SpecialValues;
use Kuvardin\TinyOrm\Table;
use Kuvardin\TinyOrm\Exception\AlreadyExists;

class Session extends EntityAbstract
{
    use UserAgentByValueTrait;
    use AuthorizationTrait;
    use WebBotCodeTrait;
    use LocaleRequiredTrait;
    use IpAddressRequiredTrait;
    use CreationDateRequiredTrait;

    public const string COL_SECRET_CODE = 'secret_code';
    public const string COL_AUTHORIZATION_ID = 'authorization_id';
    public const string COL_WEB_BOT_CODE = 'web_bot_code';
    public const string COL_IP_ADDRESS = 'ip_address';
    public const string COL_USER_AGENT = 'user_agent';
    public const string COL_HAS_COOKIES = 'has_cookies';
    public const string COL_REQUESTS_NUMBER = 'requests_number';
    public const string COL_LAST_REQUEST_DATE = 'last_request_date';

    public const PERMISSIONS = [

    ];

    protected string $secret_code;
    protected bool $has_cookies;
    protected int $requests_number;
    protected int $last_request_date;

    public bool $is_new = false;

    public function __construct(Connection $pdo, Table $table, array $data)
    {
        parent::__construct($pdo, $table, $data);
        $this->secret_code = fgets($data[self::COL_SECRET_CODE]);
        $this->authorization_id = $data[self::COL_AUTHORIZATION_ID];
        $this->web_bot_code_value = $data[self::COL_WEB_BOT_CODE];
        $this->ip_address_hex = fgets($data[self::COL_IP_ADDRESS]);
        $this->user_agent_value = $data[self::COL_USER_AGENT];
        $this->has_cookies = $data[self::COL_HAS_COOKIES];
        $this->requests_number = $data[self::COL_REQUESTS_NUMBER];
        $this->last_request_date = $data[self::COL_LAST_REQUEST_DATE];
        $this->locale_value = App::settings('language.default');
        $this->creation_date = $data[CreatableInterface::COL_CREATION_DATE];
    }

    public static function getEntityTableDefault(): Table
    {
        return new Table('sessions');
    }

    public static function findOneBySecretCode(string $secret_code, Table $table = null): ?self
    {
        return self::findOneByConditions(new Condition(self::COL_SECRET_CODE, $secret_code), $table);
    }

    /**
     * @throws AlreadyExists
     */
    public static function create(
        IpAddress $ip_address,
        ?UserAgent $user_agent,
        int $requests_number = null,
        int $last_request_date = null,
        int $creation_date = null,
    ): self
    {
        $current_timestamp = null;
        $result = self::createByValuesSet([
            self::COL_SECRET_CODE => App::getRandomString(32, '0123456789abcdef'),
            self::COL_WEB_BOT_CODE => $user_agent?->getWebBotCode()?->value,
            self::COL_IP_ADDRESS => $ip_address->getHexString(),
            self::COL_USER_AGENT => $user_agent?->value,
            self::COL_HAS_COOKIES => false,
            self::COL_REQUESTS_NUMBER => $requests_number ?? 0,
            self::COL_LAST_REQUEST_DATE => $last_request_date ?? ($current_timestamp ??= time()),
            CreatableInterface::COL_CREATION_DATE => $creation_date ?? ($current_timestamp ?? time()),
        ]);

        $result->is_new = true;
        return $result;
    }

    public static function findOneByWebBotUserAgent(UserAgent $user_agent): ?self
    {
        $web_bot_code = $user_agent->getWebBotCode();
        if ($web_bot_code === null) {
            return null;
        }

        return self::findOneByConditions(ConditionsList::fromValuesArray([
            self::COL_WEB_BOT_CODE => $web_bot_code->value,
            self::COL_USER_AGENT => $user_agent->value,
        ]));
    }

    public static function findOneByCookies(
        array $cookies,
        IpAddress $ip_address,
        ?UserAgent $user_agent,
    ): ?self
    {
        if ($cookies === []) {
            return self::findOneByConditions(
                ConditionsList::fromValuesArray([
                    self::COL_HAS_COOKIES => false,
                    self::COL_IP_ADDRESS => $ip_address->getHexString(),
                    self::COL_USER_AGENT => $user_agent?->value ?? SpecialValues::isNull(),
                    self::COL_AUTHORIZATION_ID => SpecialValues::isNull(),
                ]),
            );
        }

        $result = null;

        $cookie_name = App::settings('cookies.names.session_id');
        if (!empty($cookies[$cookie_name]) && is_string($cookies[$cookie_name])) {
            $result = self::findOneBySecretCode($cookies[$cookie_name]);
            if ($result !== null && !$result->hasCookies()) {
                $result->setHasCookies(true);
                $result->saveChanges();
            }
        }

        return $result;
    }

    public function setCookie(): void
    {
        setcookie(
            App::settings('cookies.names.session_id'),
            $this->secret_code,
            App::settings('cookies.expires'),
            App::settings('cookies.path'),
            App::settings('cookies.domain'),
        );
    }

    public function setAuthorization(Authorization|int|null $authorization): self
    {
        $this->setFieldValue(self::COL_AUTHORIZATION_ID, $this->authorization_id, $authorization);
        return $this;
    }

    public function setWebBotCode(?WebBotCode $web_bot_code): self
    {
        $this->setFieldValue(self::COL_WEB_BOT_CODE, $this->web_bot_code_value, $web_bot_code?->value);
        return $this;
    }

    public function getRequestsNumber(): int
    {
        return $this->requests_number;
    }

    public function getLastRequestDate(): int
    {
        return $this->last_request_date;
    }

    public function setLastRequestDate(int $last_request_date): self
    {
        $this->setFieldValue(self::COL_LAST_REQUEST_DATE, $this->last_request_date, $last_request_date);
        return $this;
    }

    public function hasCookies(): bool
    {
        return $this->has_cookies;
    }

    public function setHasCookies(bool $has_cookies): self
    {
        $this->setFieldValue(self::COL_HAS_COOKIES, $this->has_cookies, $has_cookies);
        return $this;
    }

    public function fixRequest(
        IpAddress $ip_address,
        ?UserAgent $user_agent,
        WebBotCode $web_bot_code = null,
        int $last_request_date = null,
    ): void
    {
        $this->setFieldValue(self::COL_IP_ADDRESS, $this->ip_address_hex, $ip_address->getHexString());
        $this->setFieldValue(self::COL_USER_AGENT, $this->user_agent_value, $user_agent?->value);

        $last_request_date ??= time();
        $this->setLastRequestDate($last_request_date);

        if ($user_agent !== null && $user_agent->isWebBot()) {
            $this->setWebBotCode($user_agent->getWebBotCode());
        }

        $this->setFieldValue(
            self::COL_REQUESTS_NUMBER,
            $this->requests_number,
            App::pdo()->getExpressionBuilder()->sum($this->entity_table->getColumn(self::COL_REQUESTS_NUMBER), 1),
        );

        $this->requests_number++;
        $this->saveChanges();
    }

    public function getDateTime(int $timestamp): DateTime
    {
        return DateTime::makeByTimestamp($timestamp, $this->getDateTimeZone());
    }

    public function getDateTimeZone(): DateTimeZone
    {
        return new DateTimeZone(App::settings('timezone.default'));
    }

    /**
     * @throws NotEnoughRightsException
     */
    public function requirePermission(string $object, int $actions): void
    {
        if (!$this->can($object, $actions)) {
            throw new NotEnoughRightsException($object, $actions);
        }
    }

    public function can(string $class_name, int|array $actions): bool
    {
        return $this->getAuthorization() !== null;

        $allowed_actions = self::PERMISSIONS[$class_name] ?? 0;
        return $this->getUser()?->can($class_name, $actions, $allowed_actions)
            ?? (($allowed_actions & $actions) === $actions);
    }

    public function canShowDeletedItems(): bool
    {
        return $this->can(Action::CLASS_DELETED_ITEMS, Action::SHOW);
    }

    public function countSimilar(): int
    {
        return self::countByConditions(new ConditionsList([
            new Condition(self::COL_IP_ADDRESS, $this->ip_address_hex),
            new Condition(self::COL_USER_AGENT, $this->user_agent ?? SpecialValues::isNull()),
            new Condition(EntityAbstract::COL_ID, $this->id, Operator::NotEquals),
        ]));
    }

    /**
     * @throws NotEnoughRightsException
     */
    public function showDeletedItems(?bool $deleted): ?bool
    {
        if (!$this->can(Action::CLASS_DELETED_ITEMS, Action::SHOW)) {
            if ($deleted) {
                throw new NotEnoughRightsException(Action::CLASS_DELETED_ITEMS, Action::SHOW);
            }

            return false;
        }

        return $deleted;
    }

    public function isAuthorized(): bool
    {
        return $this->authorization_id !== null;
    }

    public function getUserId(): ?int
    {
        return $this->getAuthorization()?->getUserId();
    }

    public function requireUserId(): int
    {
        return $this->getAuthorization()->getUserId();
    }

    public function getUser(): ?User
    {
        return $this->getAuthorization()?->getUser();
    }

    public function requireUser(): User
    {
        return $this->getAuthorization()->getUser();
    }
}