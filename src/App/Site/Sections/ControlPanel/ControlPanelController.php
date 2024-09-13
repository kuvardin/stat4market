<?php

declare(strict_types=1);

namespace App\Site\Sections\ControlPanel;

use App\Languages\Locale;
use App\Sessions\Session;
use App\Site\Exceptions\SiteException;
use App\Site\Input\SiteInput;
use App\Site\Sections\ControlPanel\Models\Sidebar\Block as SidebarBlock;
use App\Site\Sections\ControlPanel\Models\Sidebar\Item as SidebarItem;
use App\Site\Sections\ControlPanel\Models\Sidebar\Subitem as SidebarSubitem;
use App\Web\WebRequest;
use Throwable;

class ControlPanelController
{
    private const HANDLERS_POSTFIX = 'PageHandler';

    private function __construct()
    {
    }

    /**
     * @param string[] $route_parts
     */
    public static function handle(Session $session, WebRequest $web_request, array $route_parts): void
    {
        $result_page = $input = null;

        $session->getLanguage()->setPhrases(require PHRASES_DIR . '/control_panel.php');

        $route = implode('/', $route_parts);

        try {
            try {
                /** @var ControlPanelHandler $handler_class */
                $handler_class = self::getHandlerClass($route_parts);
                if ($handler_class === null || !is_subclass_of($handler_class, ControlPanelHandler::class)) {
                    throw new SiteException(SiteException::PAGE_NOT_FOUND);
                }

                if ($route !== 'authorization' && $session->getUser() === null) {
                    throw new SiteException(SiteException::FORBIDDEN);
                }

                $input = new SiteInput(
                    route: $web_request->route,
                    get: $web_request->get,
                    post: $web_request->post,
                    files: [], // TODO
                    input: $web_request->input_string,
                    fields: $handler_class::getInputFields(),
                );

                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Expires: ' . date('r'));
                $result_page = $handler_class::handleRequest($input, $session);
            } catch (SiteException $site_exception) {
                throw $site_exception;
            } catch (Throwable $exception) {
                throw new SiteException(SiteException::INTERNAL_SERVER_ERROR, previous: $exception);
            }
        } catch (SiteException $site_exception) {
            $input ??= new SiteInput(
                route: $web_request->route,
                get: $web_request->get,
                post: $web_request->post,
                files: [], // TODO
                input: $web_request->input_string,
            );

            $result_page = new ControlPanelPage(
                input: $input,
                title: $session->getLanguage()->require('error_with_code', [$site_exception->getCode()]),
            );

            $result_page->content .= $result_page->render($session, 'control_panel/error', [
                'code' => $site_exception->getCode(),
                'message' => $site_exception->getMessage(),
                'throwable' => $site_exception->getPrevious(),
            ]);
        }

        if ($result_page !== null) {
            if (!$result_page->not_use_main_template) {
                $result_page->content = $result_page->render($session, 'control_panel/main', [
                    'sidebar_blocks' => self::getSidebarBlocks($session),
                    'get_params' => $web_request->get,
                    'route' => implode('/', $route_parts),
                ]);
            }

            if ($result_page->getHttpStatus() !== 200) {
                http_response_code($result_page->getHttpStatus());
            }

            echo $result_page->content;
        }
    }

    /**
     * @param string[] $route_parts
     */
    private static function getHandlerClass(array $route_parts): ?string
    {
        if (empty($route_parts[0])) {
            $route_parts[0] = 'index';
        }

        $result = ['App', 'Site', 'Sections', 'ControlPanel', 'Handlers'];

        foreach ($route_parts as $route_part) {
            $words = explode('_', $route_part);

            $namespace_part = '';
            foreach ($words as $word) {
                $namespace_part .= ucfirst($word);
            }

            $result[] = $namespace_part;
        }

        $class = implode('\\', $result) . self::HANDLERS_POSTFIX;

        if (class_exists($class)) {
            return $class;
        }

        $last_part = array_pop($result);
        if (is_string($last_part)) {
            $class = implode('\\', array_merge($result, ['DynamicString'])) . self::HANDLERS_POSTFIX;
            if ((string)(int)$last_part === $last_part && !class_exists($class)) {
                $class = implode('\\', array_merge($result, ['DynamicInt'])) . self::HANDLERS_POSTFIX;
            }
        }

        return class_exists($class) ? $class : null;
    }

    public static function getUri(Locale $locale, string $path, array $get = null): string
    {
        return '/' . $locale->value . '/control_panel/' . ltrim($path, '/')
            . ($get === null ? '' : ('?' . http_build_query($get)));
    }

    public static function getSidebarBlocks(Session $session): array
    {
        $lang = $session->getLanguage();

        if (!$session->isAuthorized()) {
            return [
                new SidebarBlock($lang->require('account'), [
                    SidebarItem::withPath($lang->require('authorization'), 'uil-user', 'authorization'),
                ]),
            ];
        }

        return [
            new SidebarBlock($lang->require('control'), [
                SidebarItem::withSubitems($lang->require('store_parsers_module'), 'mdi mdi-shopping', [
                    new SidebarSubitem($lang->require('online_stores'), 'store_parsers/stores'),
                    new SidebarSubitem($lang->require('online_stores_branches'), 'store_parsers/branches'),
                    new SidebarSubitem($lang->require('areas'), 'store_parsers/areas'),
                    new SidebarSubitem($lang->require('source_products'), 'store_parsers/source_products'),
                    new SidebarSubitem($lang->require('source_categories'), 'store_parsers/source_categories'),
                ]),
                SidebarItem::withSubitems($lang->require('products_catalog'), 'mdi mdi-folder', [
                    new SidebarSubitem($lang->require('products'), 'catalog/products'),
                    new SidebarSubitem($lang->require('brands'), 'catalog/brands'),
                    new SidebarSubitem($lang->require('categories'), 'catalog/categories'),
                    new SidebarSubitem($lang->require('discounts'), 'catalog/discounts'),
                    new SidebarSubitem($lang->require('products_types'), 'catalog/products_types'),
                ]),
            ]),
        ];
    }
}