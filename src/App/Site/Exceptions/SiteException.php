<?php

declare(strict_types=1);

namespace App\Site\Exceptions;

use App\Languages\Language;
use Exception;
use Throwable;

class SiteException extends Exception
{
    public const MOVED_PERMANENTLY = 301;
    public const FOUND = 302;
    public const BAD_REQUEST = 400;
    public const FORBIDDEN = 403;
    public const PAGE_NOT_FOUND = 404;
    public const INTERNAL_SERVER_ERROR = 500;

    protected ?string $redirect_url;

    public function __construct(
        int $code,
        string $message = null,
        string $redirect_url = null,
        Throwable $previous = null,
    )
    {
        $this->redirect_url = $redirect_url;
        parent::__construct($message ?? '', $code, $previous);
    }

    public function getDescription(Language $language): string
    {
        if (!empty($this->message)) {
            return $this->message;
        }

        switch ($this->code) {
            case self::BAD_REQUEST:
                return $language->require('error_bad_request');

            case self::FORBIDDEN:
                return $language->require('error_forbidden');

            case self::PAGE_NOT_FOUND:
                return $language->require('error_page_not_found');

            case self::INTERNAL_SERVER_ERROR:
                return $language->require('error_internal_server_error');

            default:
                return $language->require('error_unknown');
        }
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirect_url;
    }
}