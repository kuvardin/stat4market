<?php

declare(strict_types=1);

namespace App\Site\Sections\ControlPanel\Handlers\StoreParsers;

use App\Sessions\Session;
use App\Site\Input\SiteInput;
use App\Site\Sections\ControlPanel\ControlPanelHandler;
use App\Site\Sections\ControlPanel\ControlPanelPage;
use App\StoreParsers\Entities\Branch;
use Generator;

class BranchesPageHandler extends ControlPanelHandler
{
    public static function handleRequest(SiteInput $input, Session $session): ?ControlPanelPage
    {
        $lang = $session->getLanguage();

        $page = new ControlPanelPage(
            input: $input,
            title: $lang->require('online_stores_branches'),
        );

        $branches = Branch::findByConditions();

        $page->appendContent($session, 'control_panel/store_parsers/branches/branches', [
            'branches' => $branches->valid() ? iterator_to_array($branches) : [],
        ]);

        return $page;
    }
}