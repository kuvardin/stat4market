<?php

declare(strict_types=1);

namespace App\Site\Sections\ControlPanel;

use App\Sessions\Session;
use App\Site\Input\SiteInput;
use App\Site\Sections\ControlPanel\Models\Alert;
use App\Site\Sections\ControlPanel\Models\Breadcrumb;
use App\TemplatesEngine\TemplatesEngine;
use RuntimeException;

class ControlPanelPage
{
    /**
     * @var array<string,string>
     */
    protected array $errors = [];

    protected int $http_status = 200;

    /**
     * @param string[] $keywords
     * @param Alert[] $alerts
     * @param Breadcrumb[] $breadcrumbs
     * @param string[] $css_files
     * @param string[] $js_files_in_head
     * @param string[] $js_files_in_bottom
     */
    public function __construct(
        protected SiteInput $input,
        public string $title,
        public ?string $description = null,
        public array $keywords = [],
        public string $content = '',
        public array $alerts = [],
        public array $breadcrumbs = [],
        protected ?string $redirect_uri = null,
        public array $css_files = [],
        public array $js_files_in_head = [],
        public array $js_files_in_bottom = [],
        public bool $not_use_main_template = false,
    )
    {

    }

    public function render(Session $session, string $template_path, array $data = []): string
    {
        return TemplatesEngine::render($template_path, array_merge($data, [
            'page' => $this,
            'session' => $session,
            'lang' => $session->getLanguage(),
            'input' => $this->input,
        ]));
    }

    public function appendContent(Session $session, string $template_path, array $data = []): self
    {
        $this->content .= TemplatesEngine::render($template_path, array_merge($data, [
            'page' => $this,
            'session' => $session,
            'lang' => $session->getLanguage(),
            'input' => $this->input,
        ]));

        return $this;
    }

    public function getHttpStatus(): int
    {
        return $this->http_status;
    }

    public function setHttpStatus(int $http_status): void
    {
        if ($http_status < 100 || $http_status > 599) {
            throw new RuntimeException("Incorrect HTTP-status: $http_status");
        }

        if ($this->redirect_uri !== null && $http_status !== 301 && $http_status !== 302) {
            throw new RuntimeException("Incorrect redirect HTTP-status: $http_status (must be 301 or 302)");
        }

        $this->http_status = $http_status;
    }

    public function getRedirectUri(): ?string
    {
        return $this->redirect_uri;
    }

    public function setRedirectUri(?string $redirect_uri, bool $temporarily = true): void
    {
        $this->redirect_uri = $redirect_uri;
        if ($redirect_uri !== null) {
            $this->http_status = $temporarily ? 302 : 301;
        }
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $field_name, string $text): void
    {
        if (!$this->input->isFieldExists($field_name)) {
            throw new RuntimeException("Unknown field $field_name");
        }

        $this->errors[$field_name] = $text;
    }

    public function hasError(string $field_name): bool
    {
        if (!$this->input->isFieldExists($field_name)) {
            throw new RuntimeException("Unknown field $field_name");
        }

        return array_key_exists($field_name, $this->errors);
    }

    public function getErrorText(string $field_name, bool $html_filter = false): ?string
    {
        if (!$this->input->isFieldExists($field_name)) {
            throw new RuntimeException("Unknown field $field_name");
        }

        if (array_key_exists($field_name, $this->errors)) {
            return $html_filter ? htmlspecialchars($this->errors[$field_name]) : $this->errors[$field_name];
        }

        return null;
    }

    public function requireErrorText(string $field_name, bool $html_filter = false): ?string
    {
        return $this->getErrorText($field_name, $html_filter);
    }
}