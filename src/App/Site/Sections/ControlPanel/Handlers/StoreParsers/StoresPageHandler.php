<?php

declare(strict_types=1);

namespace App\Site\Sections\ControlPanel\Handlers\StoreParsers;

use App\Actions\Action;
use App\Sessions\Session;
use App\Site\Input\SiteInput;
use App\Site\Sections\ControlPanel\ControlPanelHandler;
use App\Site\Sections\ControlPanel\ControlPanelPage;
use App\StoreParsers\Entities\Store;

class StoresPageHandler extends ControlPanelHandler
{
    public static function getRequiredPermissions(): array
    {
        return [
            Store::class => Action::SHOW,
        ];
    }

    public static function handleRequest(SiteInput $input, Session $session): ?ControlPanelPage
    {
        $lang = $session->getLanguage();

        $page = new ControlPanelPage(
            input: $input,
            title: $lang->require('online_stores'),
        );

        /** @var Store[] $stores */
        $stores = [];
        $stores_generator = Store::findByConditions();
        if ($stores_generator->valid()) {
            $stores = iterator_to_array($stores_generator);
        }

        $page->appendContent($session, 'control_panel/store_parsers/stores/stores', [
            'stores' => $stores,
        ]);

        return $page;
    }
}