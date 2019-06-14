<?php

namespace Deployr;


class Application
{
    const VERSION = '1.0.0';
    const DEFAULT_PAGE = 'index';

    /**
     * @var array
     */
    protected $options = [
        'key' => '',
        'param_key' => 'access_key',
        'database' => './deployr.db',
        'default_lang' => 'en',
        'param_page' => 'p',
        'restrict_ip' => ['127.0.0.1', '::1'],
    ];

    protected $base_path;
    protected $db;
    protected $message;
    protected $controller;

    /**
     * Application constructor.
     *
     * @param string $key
     */
    public function __construct(string $key)
    {
        session_start();
        $this->options['key'] = $key;
        $this->base_path = __DIR__;
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
     * Get options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get one option
     * 
     * @param string $key
     * @return mixed
     */
    public function getOption(string $key)
    {
        return $this->options[$key];
    }

    /**
     * Run application
     */
    public function run()
    {
        $this->initDb();
        $this->initMessage();
        $this->initSecure();
        $this->initController();

        echo $this->controller->render();

        if($missing = $this->message->getMissingTranslations()) {
            trigger_error('Not translated string in current language ('.$this->message->getLang().') :<br>' . implode('<br>', $missing).'<br>');
        }
    }

    /**
     * Return Application base path
     * 
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->base_path;
    }

    /**
     * Return project path.
     * 
     * @return string
     */
    public function getRootPath(): string
    {
        return realpath(dirname(dirname($this->getBasePath())));
    }

    /**
     * Init database
     */
    protected function initDb()
    {
        $create = !file_exists($this->options['database']);

        $this->db = new Db($this->options['database']);

        // First run : create database
        if($create) {
            $tables = [
                'CREATE TABLE settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key TEXT NOT NULL,
                value TEXT
            );',
                'CREATE TABLE log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                author TEXT,
                files TEXT,
                message TEXT,
                status INTEGER,
                date TEXT
            );',
            ];

            foreach($tables as $t) {
                $this->db->getBuilder()->getPDO()->query($t);
            }

            $this->db->log('system', 'Database created successfully', true);
        }
    }

    /**
     * Init messages and translations
     */
    protected function initMessage()
    {
        $this->message = new Message();
        $this->message->setLang($this->db->getSetting('lang', $this->options['default_lang']));
    }

    /**
     * Init securizations
     */
    protected function initSecure()
    {
        // Check access key
        if(!isset($_REQUEST[$this->options['param_key']]) || $_REQUEST[$this->options['param_key']] !== $this->options['key']) {
            header("HTTP/1.1 401 Unauthorized");
            die($this->message->get('Missing or invalid access key.'));
        }

        // Check IP access
        $restrict_ip = $this->options['restrict_ip'];
        $client_ip = Tools::getClientIp();
        if($restrict_ip && !(in_array($client_ip, $restrict_ip))) {
            header("HTTP/1.1 401 Unauthorized");
            die($this->message->get('Unauthorized IP'));
        }
    }

    /**
     * Init controller
     */
    protected function initController()
    {
        // First run : copy assets
        $assetFolder = pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_DIRNAME).DIRECTORY_SEPARATOR.'assets';
        if(!is_dir($assetFolder)) {
            $source = __DIR__.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'assets';
            $res = Tools::copy($source, $assetFolder);
            $this->db->log('system', 'Assets copied successfully', true);
        }

        $page = $_GET[$this->options['param_page']] ?? static::DEFAULT_PAGE;
        $page = Tools::sanitize($page);

        $controllerClass = 'Deployr\\Controller\\'.ucfirst(strtolower($page)).'Controller';
        if(ucfirst(strtolower($page)) === 'Abstract' || !class_exists($controllerClass)) {
            $controllerClass = 'Deployr\\Controller\\NotFoundController';
        }

        $this->controller = new $controllerClass($this, $this->db, $this->message);
        $this->controller->setView($page);
        $this->controller->init();
    }
}