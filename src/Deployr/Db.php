<?php

namespace Deployr;

use Deployr\Db\Builder;

class Db
{

    protected $dbo;

    /**
     * Db constructor.
     *
     * @param $database
     */
    public function __construct($database)
    {
        $this->dbo = Builder::connect($database);
    }

    /**
     * Save new log
     *
     * @param string $author
     * @param string $message
     * @param bool|null $status
     * @param array|null $files
     */
    public function log(string $author, string $message, ?bool $status = true, ?array $files = [])
    {
        $datas = [
            'author' => $author,
            'message' => $message,
            'date' => date('Y-m-d H:i:s'),
        ];

        if($status) {
            $datas['status'] = (int) $status;
        }
        if($files) {
            $datas['files'] = implode(',', $files);
        }

        $this->dbo->insert('log', $datas);
    }

    /**
     * Get specific setting
     *
     * @param $key
     * @param null|string $default_value
     * @return array|bool|mixed|null|string
     */
    public function getSetting($key, ?string $default_value = '')
    {
        $value = $this->dbo->getValue('value', 'settings', [
            'key' => $key
        ]);

        return $value ?: $default_value;
    }

    /**
     * @return mixed
     */
    public function getSettings(): array
    {
        $res = $this->dbo->getAll('settings');
        $settings = [];

        foreach($res as $r) {
            $settings[$r['key']] = $r['value'];
        }

        return $settings;
    }

    /**
     * Get the DbBuilder
     *
     * @return Builder
     */
    public function getBuilder()
    {
        return $this->dbo;
    }
}