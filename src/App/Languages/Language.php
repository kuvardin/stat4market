<?php

declare(strict_types=1);

namespace App\Languages;

use RuntimeException;

class Language
{
    /**
     * @var string[][]
     */
    protected array $phrases = [];

    public function __construct(
        public readonly Locale $locale,
        public readonly bool $test_mode = false,
    )
    {

    }

    public function requirePhrase(
        string $phrase_name,
        array $arguments = null,
        array $vars = null,
    ): Phrase
    {
        if (empty($this->phrases[$phrase_name])) {
            throw new RuntimeException("Phrase with name $phrase_name not found");
        }

        $phrase_data = [];

        foreach ($this->phrases[$phrase_name] as $locale_value => $phrase_value) {
            if (empty($phrase_value)) {
                continue;
            }

            if ($arguments !== null) {
                $phrase_value = sprintf($phrase_value, ...$arguments);
            }

            if (!empty($vars)) {
                $phrase_value = self::addVariables($phrase_value, $vars);
            }

            if ($this->test_mode) {
                $phrase_value = "$phrase_name: $phrase_value";
            }

            $phrase_data[$locale_value] = $phrase_value;
        }

        if ($phrase_data === []) {
            throw new RuntimeException("Empty phrase with name $phrase_name");
        }

        return new Phrase($phrase_data);
    }

    private static function addVariables(string $string, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $string = str_replace(
                '{' . $key . '}',
                is_int($value) | is_float($value) ? (string)$value : $value,
                $string
            );
        }

        return $string;
    }

    public function get(
        string $phrase_name,
        array $arguments = null,
        array $vars = null,
    ): ?string
    {
        if (empty($this->phrases[$phrase_name])) {
            return null;
        }

        $result = $this->phrases[$phrase_name][$this->locale->value] ?? null;

        if (empty($result)) {
            foreach (Locale::cases() as $locale) {
                if (!empty($this->phrases[$phrase_name][$locale->value])) {
                    $result = $this->phrases[$phrase_name][$locale->value];
                }
            }
        }

        if ($result !== null) {
            if ($arguments !== null) {
                $result = sprintf($result, ...$arguments);
            }

            if (!empty($vars)) {
                $result = self::addVariables($result, $vars);
            }

            if ($this->test_mode) {
                $result = "$phrase_name: $result";
            }
        }

        return $result;
    }

    public function require(string $phrase_name, array $arguments = null, array $vars = null): string
    {
        return $this->get($phrase_name, $arguments, $vars);
    }

    public function isPhraseExists(string $phrase_name): bool
    {
        return array_key_exists($phrase_name, $this->phrases);
    }

    public function requireFrom(Phrase|array $phrase, array $arguments = null, array $vars = null): string
    {
        return $this->getFrom($phrase, $arguments, $vars);
    }

    public function getFrom(Phrase|array $phrase, array $arguments = null, array $vars = null): ?string
    {
        $phrases = is_array($phrase) ? $phrase : $phrase->toArray();

        $found_phrase = null;
        if (!empty($phrases[$this->locale->value])) {
            $found_phrase = $phrases[$this->locale->value];
        } else {
            foreach (Locale::cases() as $lang_code) {
                if (!empty($phrases[$lang_code->value])) {
                    $found_phrase = $phrases[$lang_code->value];
                    break;
                }
            }
        }

        if ($found_phrase === null) {
            return null;
        }

        $result = $arguments === null ? $found_phrase : sprintf($found_phrase, ...$arguments);

        if (!empty($vars)) {
            $result = self::addVariables($result, $vars);
        }

        return $result;
    }

    /**
     * @return string[][]
     */
    public function getPhrases(): array
    {
        return $this->phrases;
    }

    public function setPhrases(array $phrases): void
    {
        $locale_values = array_map(
            static fn(Locale $locale) => $locale->value,
            Locale::cases(),
        );

        foreach ($phrases as $phrase_name => $phrase_in_languages) {
            if (array_key_exists($phrase_name, $this->phrases)) {
                throw new RuntimeException("Phrase with name $phrase_name already exists");
            }

            $phrase_data = [];

            foreach ($locale_values as $locale_value) {
                if (!array_key_exists($locale_value, $phrase_in_languages)) {
                    continue;
                }

                if ($phrase_in_languages[$locale_value] === null) {
                    continue;
                }

                if (!is_string($phrase_in_languages[$locale_value])) {
                    throw new RuntimeException(sprintf(
                        'Wrong phrase %s type %s and value: %s',
                        $phrase_name,
                        gettype($phrase_in_languages[$locale_value]),
                        print_r($phrase_in_languages[$locale_value], true),
                    ));
                }

                if (trim($phrase_in_languages[$locale_value]) === '') {
                    continue;
                }

                $phrase_data[$locale_value] = $phrase_in_languages[$locale_value];
            }

            if ($phrase_data === []) {
                throw new RuntimeException("Empty phrase with name $phrase_name");
            }

            $this->phrases[$phrase_name] = $phrase_data;
        }
    }
}
