<?php

declare(strict_types=1);

namespace App\Site\Sections\ControlPanel;

use App\Sessions\Session;
use App\Site\Exceptions\SiteException;
use App\Site\Input\SiteField;
use App\Site\Input\SiteFieldType;
use App\Site\Input\SiteInput;

abstract class ControlPanelHandler
{
    private function __construct()
    {
    }

    public static function hasPagination(): bool
    {
        return false;
    }

    /**
     * @return SiteField[]
     */
    public static function getInputFields(): array
    {
        return [];
    }

    final public static function getAllInputFields(): array
    {
        $input_fields = static::getInputFields();

        if (static::hasPagination()) {
            $input_fields['page'] = new SiteField(SiteFieldType::Integer, false, description: 'Номер страницы');
            $input_fields['ord'] = new SiteField(SiteFieldType::String, false, description: 'Поле сортировки');
            $input_fields['sort'] = new SiteField(SiteFieldType::String, false, description: 'Направление сортировки');
        }

        return $input_fields;

    }

    public static function getRequiredPermissions(): array
    {
        return [];
    }

    /**
     * @return string[][]
     */
    public static function getPhrases(): array
    {
        return [];
    }

    /**
     * @throws SiteException
     */
    abstract public static function handleRequest(SiteInput $input, Session $session): ?ControlPanelPage;
}