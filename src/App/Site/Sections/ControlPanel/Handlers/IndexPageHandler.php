<?php

declare(strict_types=1);

namespace App\Site\Sections\ControlPanel\Handlers;

use App\Sessions\Session;
use App\Site\Input\SiteInput;
use App\Site\Sections\ControlPanel\ControlPanelHandler;
use App\Site\Sections\ControlPanel\ControlPanelPage;

class IndexPageHandler extends ControlPanelHandler
{
    public static function handleRequest(SiteInput $input, Session $session): ?ControlPanelPage
    {
        $page = new ControlPanelPage(
            input: $input,
            title: 'Index page',
            content: 'Content',
        );

        return $page;
    }
}