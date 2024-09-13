<?php

declare(strict_types=1);

namespace App\Site\Sections\ControlPanel\Handlers\StoreParsers;

use App\Actions\Action;
use App\Sessions\Session;
use App\Site\Input\SiteInput;
use App\Site\Sections\ControlPanel\ControlPanelHandler;
use App\Site\Sections\ControlPanel\ControlPanelPage;
use App\StoreParsers\Entities\Product as SourceProduct;

class SourceProductsPageHandler extends ControlPanelHandler
{
    public static function getRequiredPermissions(): array
    {
        return [
            SourceProduct::class => Action::SHOW,
        ];
    }

    public static function handleRequest(SiteInput $input, Session $session): ?ControlPanelPage
    {
        $lang = $session->getLanguage();

        $page = new ControlPanelPage(
            input: $input,
            title: $lang->require('source_products'),
        );

        $source_products = SourceProduct::findByConditions();

        $page->appendContent($session, 'control_panel/store_parsers/source_products/source_products', [
            'source_products' => $source_products->valid() ? iterator_to_array($source_products) : [],
        ]);

        return $page;
    }
}