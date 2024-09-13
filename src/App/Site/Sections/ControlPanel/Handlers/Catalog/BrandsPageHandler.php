<?php

declare(strict_types=1);

namespace App\Site\Sections\ControlPanel\Handlers\Catalog;

use App\Sessions\Session;
use App\Site\Input\SiteInput;
use App\Site\Sections\ControlPanel\ControlPanelHandler;
use App\Site\Sections\ControlPanel\ControlPanelPage;

class BrandsPageHandler extends ControlPanelHandler
{
    public static function handleRequest(SiteInput $input, Session $session): ?ControlPanelPage
    {
        $lang = $session->getLanguage();
        $page = new ControlPanelPage($input, $lang->require('brands'));

        return $page;
    }
}