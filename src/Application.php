<?php

namespace Deployr;

use Deployr\Exception\ApplicationException;

class Application
{
    const VERSION = '1.1.0';

    const DEFAULT_PAGE = 'index';
    const PARAM_PAGE = 'p';
    const DEFAULT_LANG = 'en';
    const DATABASE_FILE = 'database.db';

    /**
     * @var array
     */
    protected $options = [
        'key' => '',
        'access_key_name' => 'access_key',
        'allowed_ip' => ['127.0.0.1', '::1'],
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
     * Run application
     *
     * @throws ApplicationException
     */
    public function run()
    {
        if(!$this->options['key']) {
            throw new ApplicationException('Please provide access key');
        }

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
     * Init database
     */
    protected function initDb()
    {
        $this->db = Db::connect(static::DATABASE_FILE);
    }

    /**
     * Init messages and translations
     */
    protected function initMessage()
    {
        $this->message = new Message(static::DEFAULT_LANG);
        $this->message->setLang($this->db->getValue('settings', 'value', ['key' => 'lang']));
    }

    /**
     * Init securizations
     */
    protected function initSecure()
    {
        // Check access key
        if(!isset($_REQUEST[$this->options['access_key_name']]) || $_REQUEST[$this->options['access_key_name']] !== $this->options['key']) {
            header("HTTP/1.1 401 Unauthorized");
            die($this->message->get('Missing or invalid access key.'));
        }

        // Check IP access
        $allowed_ip = $this->options['allowed_ip'];
        $client_ip = Tools::getClientIp();
        if(!(in_array($client_ip, $allowed_ip))) {
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
            $this->db->insertLog('system', 'Assets copied successfully', true);
        }

        $page = $_GET[static::PARAM_PAGE] ?? static::DEFAULT_PAGE;
        $page = Tools::sanitize($page);

        $controllerClass = 'Deployr\\Controller\\'.ucfirst(strtolower($page)).'Controller';
        if(ucfirst(strtolower($page)) === 'Abstract' || !@class_exists($controllerClass)) {
            $controllerClass = 'Deployr\\Controller\\NotFoundController';
        }

        $this->controller = new $controllerClass($this, $this->db, $this->message);
        $this->controller->setView($page);
        $this->controller->init();
    }
}