<?php

declare(strict_types=1);

namespace App\Site;

use App;
use App\Languages\Locale;
use App\Sessions\Session;
use App\Site\Input\SiteInput;
use App\Site\Sections\ControlPanel\ControlPanelController;
use App\Web\WebRequest;
use RuntimeException;
use Throwable;

class SiteController
{
    private function __construct()
    {
    }

    public static function handleRequest(WebRequest $web_request): void
    {
        App::connectPdo();

        $web_bot_code = $web_request->user_agent?->getWebBotCode();

        $user_agent = $web_request->user_agent;

        $session = $web_bot_code === null
            ? Session::findOneByCookies($web_request->cookies, $web_request->ip_address, $user_agent)
            : Session::findOneByWebBotUserAgent($user_agent);

        if ($session === null) {
            $session = Session::create($web_request->ip_address, $user_agent, $web_bot_code);
            $session->setCookie();
        }

        $route_parts = $web_request->route_parts;
        $locale = Locale::tryFrom($route_parts[0]);

        if ($locale === null) {
            $redirect_url = '/' . $session->getLocale()->value . $_SERVER['REQUEST_URI'];
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Expires: ' . date('r'));
            header('Status: 302');
            header('Location: ' . $redirect_url);
            return;
        }

        // if ($locale !== $session->getLocale()) {
        //    TODO: $session->setLocale($locale);
        // }

        array_shift($route_parts);
        $session->fixRequest($web_request->ip_address, $user_agent, $web_bot_code);
        $session->getUser()?->fixRequest();

//        /** @var Referrer|null $referrer */
//        $referrer = null;
//        if ($web_request->referrer !== null) {
//            $referrer = Referrer::makeByValue($web_request->referrer);
//            if ($referrer === null) {
//                try {
//                    $referrer = Referrer::create($web_request->referrer);
//                } catch (AlreadyExists) {
//                    $referrer = Referrer::makeByValue($web_request->referrer);
//                }
//            }
//        }

//        if ($web_bot_code === null && ($web_request->utm_group !== null || $referrer !== null)) {
//            Click::create($session, $is_new_session, $utm_group, $referrer, $web_request->route, $get);
//        }

        $route_first_part = $route_parts[0] ?? null;

        try {
            if ($route_first_part === 'control_panel') {
                array_shift($route_parts);
                ControlPanelController::handle($session, $web_request, $route_parts);
            } else {
                $index_html_path = ROOT_DIR . '/public/index.html';
                if (!file_exists($index_html_path)) {
                    throw new RuntimeException('Index.html not exists');
                }

                $index_html = file_get_contents($index_html_path);
                if ($index_html === false) {
                    throw new RuntimeException('Index.html content read error');
                }

                if ($index_html === '') {
                    throw new RuntimeException('Index.html is empty');
                }

                echo $index_html;
            }
        } catch (Throwable $exception) {
            echo '<pre>', $exception, '</pre>', PHP_EOL;
        } finally {
            $session->saveChanges();
            $session->getUser()?->saveChanges();
        }
    }

    public static function getErrorPage(SiteInput $input, Session $session, int $code, ?string $message): Page
    {
        $page = new Page(
            $input,
            $session->getLanguage()->require('error_with_code', [$code]),
            no_indexing: true,
        );

        $page->setHttpStatus($code);
        $page->content .= $page->render($session, 'site/error', [
            'error_code' => $code,
            'error_message' => $message,
        ]);

        return $page;
    }
}