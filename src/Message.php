<?php

namespace Deployr;

class Message {


    protected $i18n_path = __DIR__.'/i18n'; // @var string
    protected $default_lang; // @var string
    protected $lang = 'en'; // @var string
    protected $translations = []; // @var array
    protected $missing_translations = []; // @var array

    /**
     * Message constructor.
     *
     * @param string|null $default_lang
     */
    public function __construct(?string $default_lang = 'en')
    {
        $this->default_lang = $default_lang;
    }

    /**
     * Set language
     *
     * @param string $lang
     */
    public function setLang(string $lang)
    {
        $this->lang = $lang;
        $file = $this->i18n_path.DIRECTORY_SEPARATOR.$lang.'.json';
        if(file_exists($file)) {
            $this->translations = $this->getRawContent($file);
        }
        elseif($lang !== $this->default_lang) {
            trigger_error("No translation available for this language : {$lang}");
        }
    }

    /**
     * Get translated string if exists
     *
     * @param string $string
     * @param array $params
     * @return string
     */
    public function get(string $string, array $params = []): string
    {
        $exists = isset($this->translations[$string]) && $this->translations[$string] !== '';
        $string = $exists ? $this->translations[$string] : $string;

        if(!$exists && $this->lang !== $this->default_lang && !in_array($string, $this->missing_translations)) {
            $this->missing_translations[] = $string;
        }

        foreach($params as $k => $v) {
            $string = str_replace(":$k", $v, $string);
        }

        return $string;
    }

    /**
     * Get missing translations
     *
     * @return array
     */
    public function getMissingTranslations(): array
    {
        return $this->missing_translations;
    }

    /**
     * Get available languages
     *
     * @return mixed
     */
    public function getAvailableLangs()
    {
        return json_decode(file_get_contents($this->i18n_path.DIRECTORY_SEPARATOR.'available.json'), true);
    }

    /**
     * Get content of translation flie
     *
     * @param string $file
     * @return array
     */
    protected function getRawContent(string $file): array
    {
        return json_decode(file_get_contents($file) ?: '{}', true);
    }
}