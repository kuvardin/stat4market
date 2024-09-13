<?php

declare(strict_types=1);

namespace App\Site\Input;

use App;
use App\Statistics\Click;
use App\Utils\DateTime;
use App\Files\Uploading;
use DateTimeZone;
use Kuvardin\FastMysqli\SelectionData;
use RuntimeException;
use Throwable;

class SiteInput
{
    readonly public string $route;

    /**
     * @var array Данные из $_GET
     */
    protected array $get;

    /**
     * @var array Данные из $_POST
     */
    protected array $post;

    /**
     * @var array Данные о загруженных файлах
     */
    protected array $files;

    /**
     * @var string|null Данные из тела запроса
     */
    protected ?string $input;

    /**
     * @var SiteField[] Список возможных полей
     */
    protected array $fields;

    /**
     * @var int|null Числовой ID из адреса страницы
     */
    protected ?int $id_from_route;

    /**
     * @var bool Признак получения GET-данных
     */
    protected bool $has_get = false;

    /**
     * @var bool Признак получения POST-данных
     */
    protected bool $has_post = false;

    protected ?Click $click;

    /**
     * @param SiteField[] $fields
     */
    public function __construct(
        string $route,
        array $get,
        array $post,
        array $files,
        ?string $input,
        array $fields = [],
        int $id_from_route = null,
        Click $click = null,
    )
    {
        $this->route = trim($route, '/');
        $this->get = [];
        $this->post = [];
        $this->files = $files;
        $this->input = $input;
        $this->click = $click;

        foreach ($fields as $name => $field) {
            if ($field->from_post) {
                if (isset($post[$name]) && $post[$name] !== '' && $post[$name] !== []) {
                    $this->post[$name] = $post[$name];
                    $this->has_post = true;
                }
            } elseif (isset($get[$name]) && $get[$name] !== '' && $get[$name] !== []) {
                $this->get[$name] = $get[$name];
                $this->has_get = true;
            }
        }

        $this->fields = $fields;
        $this->id_from_route = $id_from_route;
    }

    public function hasPost(): bool
    {
        return $this->has_post;
    }

    public function hasGet(): bool
    {
        return $this->has_get;
    }

    public function clearPost(string $key = null): void
    {
        if ($key !== null) {
            if (isset($this->post[$key])) {
                unset($this->post[$key]);
            }
        } else {
            $this->post = [];
        }
    }

    public function getParamsAsString(array $modify = [], bool $from_post = false): string
    {
        if (empty($modify)) {
            return http_build_query($from_post ? $this->post : $this->get);
        }

        $result = $from_post ? $this->post : $this->get;
        foreach ($modify as $key => $value) {
            if (!array_key_exists($key, $this->fields)) {
                throw new RuntimeException("Input field with name $key not found");
            }

            if (is_bool($value)) {
                $result[$key] = $value ? '1' : '0';
            } else {
                $result[$key] = $value;
            }
        }

        return http_build_query($result);
    }

    public function getBool(string $name): ?bool
    {
        $var = $this->get($name, SiteFieldType::Boolean);

        if ($var === 'true' || $var === 'false') {
            return $var === 'true';
        }

        if ($var === '1' || $var === '0') {
            return $var === '1';
        }

        return null;
    }

    protected function get(string $name, SiteFieldType $type): string|array|null
    {
        if (!array_key_exists($name, $this->fields)) {
            throw new RuntimeException("Incorrect field name: $name");
        }

        if ($this->fields[$name]->type !== $type) {
            throw new RuntimeException("Incorrect field named $name type: {$type->name}");
        }

        $var = $this->fields[$name]->from_post
            ? ($this->post[$name] ?? null)
            : ($this->get[$name] ?? null);

        if ($var !== null && !is_string($var) && !is_array($var)) {
            $var_type = gettype($var);
            throw new RuntimeException("Unknown var $name type: $var_type");
        }

        if (is_string($var)) {
            $var = trim($var);
            if ($var === '') {
                return null;
            }
        }

        return $var;
    }

    public function getFloat(string $name): ?float
    {
        $var = $this->get($name, SiteFieldType::Float);
        return $var === null ? null : (float)$var;
    }

    public function requireIdFromRoute(): int
    {
        return $this->id_from_route;
    }

    public function getTimestamp(string $name, DateTimeZone $date_time_zone = null): ?int
    {
        return $this->getDateTime($name, $date_time_zone)?->getTimestamp();
    }

    /**
     * @return DateTime[]|null
     */
    public function getDateRange(string $name, DateTimeZone $date_time_zone = null): ?array
    {
        $var = $this->get($name, SiteFieldType::DateRange);
        if (is_string($var) && preg_match('|^([0-9/]+)\s*-\s*([0-9/]+)$|', $var, $match)) {
            $date_start = DateTime::createFromFormat('d/m/Y', $match[1], $date_time_zone);
            $date_finish = DateTime::createFromFormat('d/m/Y', $match[2], $date_time_zone);

            if ($date_start !== false && $date_finish !== false &&
                $date_start->getTimestamp() <= $date_finish->getTimestamp()) {
                return [$date_start, $date_finish];
            }
        }

        return null;
    }

    public function getDateTime(string $name, DateTimeZone $date_time_zone = null): ?DateTime
    {
        $var = $this->get($name, SiteFieldType::DateTime);
        if ($var === null) {
            return null;
        }

        try {
            return new DateTime($var, $date_time_zone);
        } catch (Throwable) {
            return null;
        }
    }

    public function getDateTimeHtml(string $name, bool $with_time = null, DateTimeZone $date_time_zone = null): ?string
    {
        $var = $this->get($name, SiteFieldType::DateTime);
        if ($var === null) {
            return null;
        }

        try {
            return (new DateTime($var, $date_time_zone))->formatForHtml($with_time);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param string $name
     * @param string[]|null $language_codes
     * @return string[]|null
     */
    public function getPhrases(string $name, array $language_codes = null): ?array
    {
        $var = $this->get($name, SiteFieldType::Phrase);
        if ($var === null) {
            return null;
        }

        $result = [];
        foreach (($language_codes ?? App::settings('languages')) as $lang_code) {
            if (isset($var[$lang_code]) && is_string($var[$lang_code]) &&
                ($var[$lang_code] = trim($var[$lang_code])) !== '') {
                $result[$lang_code] = $var[$lang_code];
            }
        }

        return $result === [] ? null : $result;
    }

    public function getPhraseInLanguage(string $lang_code, string $name, bool $html_filter = false): ?string
    {
        $var = $this->get($name, SiteFieldType::Phrase);
        if ($var !== null && isset($var[$lang_code]) && is_string($var[$lang_code]) && $var[$lang_code] !== '') {
            $var[$lang_code] = trim($var[$lang_code]);
            if ($var[$lang_code] !== '') {
                return $html_filter ? htmlspecialchars($var[$lang_code]) : $var[$lang_code];
            }
        }

        return null;
    }

    public function getArray(string $name): ?array
    {
        $var = $this->get($name, SiteFieldType::Array);
        if ($var === null) {
            return null;
        }

        if (is_array($var) && $var !== []) {
            return $var;
        }

        return null;
    }

    /**
     * @return int[]|null
     */
    public function getArrayOfInt(string $name): ?array
    {
        $var = $this->get($name, SiteFieldType::ArrayOfInteger);
        if ($var === null) {
            return null;
        }

        if (is_string($var)) {
            $var = explode(',', trim($var, ' ,'));
        }

        if (!is_array($var) || $var === []) {
            return null;
        }

        $result = [];
        foreach ($var as $key => $value) {
            $value = trim($value);

            if (!is_int($value) && !(is_string($value) && preg_match('|^[1-9]\d*$|', $value))) {
                return null;
            }

            $result[$key] = (int)$value;
        }

        return $result;
    }

    /**
     * @param string $name
     * @return float[]|null
     */
    public function getArrayOfFloat(string $name): ?array
    {
        $var = $this->get($name, SiteFieldType::ArrayOfFloat);
        if ($var === null) {
            return null;
        }

        if (is_string($var)) {
            $var = explode(',', trim($var, ' ,'));
        }

        if (!is_array($var) || $var === []) {
            return null;
        }

        $result = [];
        foreach ($var as $key => $value) {
            $value = trim($value);

            if (!is_float($value) && !(is_string($value) && is_numeric($value))) {
                return null;
            }

            $result[$key] = (float)$value;
        }

        return $result;
    }

    /**
     * @return string[]|null[]|null
     */
    public function getArrayOfString(string $name, bool $html_filter = false): ?array
    {
        $var = $this->get($name, SiteFieldType::ArrayOfString);
        if ($var === null) {
            return null;
        }

        if (!is_array($var) || $var === []) {
            return null;
        }

        $result = [];
        $is_empty = true;
        foreach ($var as $key => $value) {
            if ($value === null || !is_string($value) || $value === '') {
                $result[$key] = null;
            } else {
                $result[$key] = $html_filter ? htmlspecialchars($value) : $value;
                $is_empty = false;
            }
        }

        return $is_empty ? null : $result;
    }

    /**
     * @param int $total_amount
     * @param string[]|null $ord_variants
     * @param string|null $ord_default
     * @param string|null $sort_default
     * @param int|null $limit
     * @param bool|null $no_limit
     * @return SelectionData
     */
    public function getSelectionData(
        int $total_amount, array $ord_variants = null, string $ord_default = null,
        string $sort_default = null, int $limit = null, bool $no_limit = null,
    ): SelectionData
    {
        if ($limit !== null && $no_limit === true) {
            throw new RuntimeException('Wrong data');
        }

        if ($limit === null) {
            $limit = $no_limit ? null : App::settings('items_limit.default');
        }

        $selection_data = new SelectionData($limit, $ord_variants);
        $selection_data->total_amount = $total_amount;

        if (!$no_limit) {
            $selection_data->setPage($this->getInt('page') ?? 1);
        }

        $ord = $this->getString('ord');
        if ($ord_variants === null || in_array($ord, $ord_variants, true)) {
            $selection_data->setOrd($this->getString('ord'));
        } else {
            $selection_data->setOrd($ord_default);
        }

        $sort = $this->getString('sort');
        if ($sort !== null && SelectionData::checkSort($sort)) {
            $selection_data->setSort($sort);
        } elseif ($sort_default !== null) {
            $selection_data->setSort($sort_default);
        }

        return $selection_data;
    }

    public function getInt(string $name, bool $zero_to_null = false): ?int
    {
        $var = $this->get($name, SiteFieldType::Integer);
        if ($var !== null) {
            $int_var = (int)$var;
            if ((string)$int_var !== $var) {
                return null;
            }

            return $zero_to_null && $int_var === 0 ? null : $int_var;
        }

        return null;
    }

    public function getString(string $name, bool $html_filter = false): ?string
    {
        $var = $this->get($name, SiteFieldType::String);
        if ($var !== null) {
            $var = trim($var);
            if ($var !== '') {
                return $html_filter ? htmlspecialchars($var) : $var;
            }
        }

        return null;
    }

    public function getFileUploading(string $name): ?Uploading
    {
        if (!array_key_exists($name, $this->fields)) {
            throw new RuntimeException("Incorrect field name: $name");
        }

        if ($this->fields[$name]->type !== SiteFieldType::File) {
            throw new RuntimeException("Incorrect field named $name type: {$this->fields[$name]->type->name}");
        }

        if (isset($this->files[$name]['name'])) {
            return Uploading::make($this->files[$name]);
        }

        return null;
    }

    /**
     * @param string $name
     * @return Uploading[]|null
     */
    public function getFilesUploadings(string $name): ?array
    {
        if (!array_key_exists($name, $this->fields)) {
            throw new RuntimeException("Incorrect field name: $name");
        }

        if ($this->fields[$name]->type !== SiteFieldType::ArrayOfFile) {
            throw new RuntimeException("Incorrect field named $name type: {$this->fields[$name]->type->name}");
        }

        if (isset($this->files[$name])) {
            return Uploading::makeList($this->files[$name]);
        }

        return null;
    }

    /**
     * @param string $name
     * @param DateTimeZone|null $date_time_zone
     * @return DateTime[]|null
     */
    public function getArrayOfDateTime(string $name, DateTimeZone $date_time_zone = null): ?array
    {
        $var = $this->get($name, SiteFieldType::ArrayOfDateTime);
        if ($var === null) {
            return null;
        }

        if (!is_array($var) || $var === []) {
            return null;
        }

        $result = [];
        $is_empty = true;
        foreach ($var as $key => $value) {
            if (!is_string($value) || $value === '') {
                $result[$key] = null;
            } else {
                try {
                    $result[$key] = new DateTime($value, $date_time_zone);
                    $is_empty = false;
                } catch (Throwable) {
                    $result[$key] = null;
                }
            }
        }

        return $is_empty ? null : $result;
    }

    public function getDataArray(bool $from_post = null): array
    {
        return $from_post ? $this->post : $this->get;
    }

    public function getClick(): ?Click
    {
        return $this->click;
    }

    public function isFieldExists(string $field_name): bool
    {
        return array_key_exists($field_name, $this->fields);
    }
}