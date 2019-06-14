<?php

namespace Deployr;

class Message {

    protected $options = [
        'default_lang' => 'en',
        'i18n_path' => __DIR__.'/i18n',
    ];

    protected $lang = 'en';
    protected $translations = [];
    protected $missing_translations = [];

    /**
     * Message constructor.
     *
     * @param array|null $options
     */
    public function __construct(?array $options = [])
    {
        if($options) {
            $this->setOptions($options);
        }
    }

    /**
     * Set options
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Set language
     *
     * @param string $lang
     */
    public function setLang(string $lang)
    {
        $this->lang = $lang;
        $file = $this->options['i18n_path'].DIRECTORY_SEPARATOR.$lang.'.json';
        if(file_exists($file)) {
            $this->translations = json_decode(file_get_contents($file), true);
        }
        else {
            $this->translations = [];
        }
    }

    /**
     * Return current lang
     * 
     * @return string
     */
    public function getLang(): string
    {
        return $this->lang;
    }

    /**
     * Get translated string if exists
     *
     * @param string $string
     * @param array $params
     * @return mixed|string
     */
    public function get(string $string, array $params = [])
    {
        $exists = isset($this->translations[$string]) && $this->translations[$string] !== '';
        $string = $exists ? $this->translations[$string] : $string;

        if(!$exists && $this->lang !== $this->options['default_lang'] && !in_array($string, $this->missing_translations)) {
            $this->missing_translations[] = $string;
        }

        foreach($params as $k => $v) {
            $string = str_replace(":$k", $v, $string);
        }

        return $string;
    }

    /**
     * Get available languages
     *
     * @return mixed
     */
    public function getAvailable()
    {
        return json_decode(file_get_contents($this->options['i18n_path'].DIRECTORY_SEPARATOR.'available.json'), true);
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
}