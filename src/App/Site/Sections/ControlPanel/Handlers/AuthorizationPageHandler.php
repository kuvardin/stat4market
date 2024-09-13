<?php

declare(strict_types=1);

namespace App\Site\Sections\ControlPanel\Handlers;

use App\Enums\BootstrapColor;
use App\Sessions\Authorization;
use App\Sessions\Session;
use App\Site\Input\SiteField;
use App\Site\Input\SiteFieldType;
use App\Site\Input\SiteInput;
use App\Site\Sections\ControlPanel\ControlPanelHandler;
use App\Site\Sections\ControlPanel\ControlPanelPage;
use App\Site\Sections\ControlPanel\Models\Alert;
use App\Users\User;

class AuthorizationPageHandler extends ControlPanelHandler
{
    public const FIELD_USERNAME = 'username';
    public const FIELD_PASSWORD = 'password';

    public static function getInputFields(): array
    {
        return [
            self::FIELD_USERNAME => new SiteField(SiteFieldType::String, true),
            self::FIELD_PASSWORD => new SiteField(SiteFieldType::String, true),
        ];
    }

    public static function handleRequest(SiteInput $input, Session $session): ?ControlPanelPage
    {
        $lang = $session->getLanguage();

        $page = new ControlPanelPage(
            input: $input,
            title: $lang->require('authorization'),
        );

        $authorized_successfully = false;

        if ($input->hasPost()) {
            $is_fine = true;

            $username = $input->getString(self::FIELD_USERNAME);
            if ($username === null) {
                $page->addError(self::FIELD_USERNAME, $lang->require('error_empty_field'));
                $is_fine = false;
            }

            $password = $input->getString(self::FIELD_PASSWORD);
            if ($password === null) {
                $page->addError(self::FIELD_PASSWORD, $lang->require('error_empty_field'));
                $is_fine = false;
            }

            if ($is_fine) {
                $user = User::findOneByUsername($username);
                if ($user === null || !$user->checkPassword($password)) {
                    $page->alerts[] = new Alert($lang->require('authorization_failed'), BootstrapColor::Danger);
                } else {
                    $authorization = Authorization::create(
                        session: $session,
                        user: $user,
                        deactivation_date: time() + 365 * 24 * 3600,
                    );

                    $session->setAuthorization($authorization);
                    $session->saveChanges();

                    $page->alerts[] = new Alert($lang->require('authorized_successfully'), BootstrapColor::Success);
                    $authorized_successfully = true;
                }
            }
        }

        $page->appendContent($session, 'control_panel/authorization', [
            'authorized_successfully' => $authorized_successfully,
        ]);

        return $page;
    }
}