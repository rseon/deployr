<?php

namespace Deployr\Controller;

use Deployr\Application;
use Deployr\Db;
use Deployr\Message;
use Deployr\Utils;

abstract class AbstractController
{

    use Utils;

    protected $options = [
        'layout' => 'layout',
        'path' => '/views',
        'pages_path' => 'pages',
        'extension' => '.phtml',
    ];

    protected $app;
    protected $db;
    protected $message;
    protected $vars = [];
    protected $view = '';
    protected $page_file = '';

    /**
     * This method must be implemented in controllers.
     *
     * @return mixed
     */
    abstract public function init();

    /**
     * AbstractController constructor.
     *
     * @param Application $app
     * @param Db $db
     * @param Message $message
     */
    public function __construct(Application $app, Db $db, Message $message)
    {
        $this->app = $app;
        $this->db = $db;
        $this->message = $message;
        $this->options['path'] = $this->app->getBasePath().$this->options['path'];
    }

    /**
     * Set the view
     * 
     * @param string $view
     */
    public function setView(string $view)
    {
        $this->view = $view;
        $this->page_file = $this->options['path'].DIRECTORY_SEPARATOR.$this->options['pages_path'].DIRECTORY_SEPARATOR.$view.$this->options['extension'];
    }

    /**
     * Assign data to view
     *
     * @param $key
     * @param null $value
     */
    public function assign($key, $value = null)
    {
        if(is_array($key)) {
            $this->vars = array_merge($this->vars, $key);
        }
        else {
            $this->vars[$key] = $value;
        }
    }

    /**
     * Set data
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->vars[$key] = $value;
    }

    /**
     * Get data
     *
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->vars[$key] ?? null;
    }

    /**
     * Display these informations when var_dump object
     *
     * @return array
     */
    /*public function __debugInfo(): array
    {
        return $this->vars;
    }*/

    /**
     * Render the layout
     *
     * @return string
     */
    public function render()
    {
        // By default in all controllers
        $this->assign([
            'version' => $this->app::VERSION,
            'settings' => $this->db->getSettings(),
            'flash' => $_SESSION['flash'] ?? '',
        ]);
        unset($_SESSION['flash']);

        $file = $this->options['path'].DIRECTORY_SEPARATOR.$this->options['layout'].$this->options['extension'];
        ob_start();
        include $file;
        return ob_get_clean();
    }

    /**
     * Get content of the page
     *
     * @return string
     */
    public function getContent(): string
    {
        $file = $this->page_file;
        ob_start();
        include $file;
        return ob_get_clean();
    }

    
}