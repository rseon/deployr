<?php

namespace Deployr\Controller;

use Deployr\{
    Application,
    Db,
    Message,
    Utils,
};

use Deployr\Exception\ControllerException;

abstract class AbstractController
{
    use Utils;

    const VIEWS_PATH = '/views';
    const PAGES_PATH = '/pages';
    const FILE_EXT = '.phtml';
    const LAYOUT_FILE = 'layout';

    protected $app; // @var Application
    protected $db; // @var Db
    protected $message; // @var Message
    protected $path; // @var string
    protected $vars = []; // @var array
    protected $view = ''; // @var string
    protected $page_file = ''; // @var string

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
        $this->path = $this->app->getBasePath().static::VIEWS_PATH;
    }

    /**
     * Set the view
     *
     * @param string $view
     * @throws ControllerException
     */
    public function setView(string $view)
    {
        $this->view = $view;
        $this->page_file = $this->path.static::PAGES_PATH.DIRECTORY_SEPARATOR.$view.static::FILE_EXT;
        if(!file_exists($this->page_file)) {
            throw new ControllerException("View file not found : {$this->page_file}");
        }
    }

    /**
     * Assign data to view
     *
     * @param string|array $key
     * @param mixed $value
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
     * Render the layout
     *
     * @return false|string
     * @throws ControllerException
     */
    public function render()
    {
        // By default in all controllers
        $this->assign([
            'version' => $this->app::VERSION,
            'settings' => $this->db->getRow('settings'),
            'flash' => $_SESSION['flash'] ?? '',
        ]);
        unset($_SESSION['flash']);

        $file = $this->path.DIRECTORY_SEPARATOR.static::LAYOUT_FILE.static::FILE_EXT;
        if(!$file) {
            throw new ControllerException('Please set view first');
        }

        ob_start();
        include $file;
        return ob_get_clean();
    }

    /**
     * Get content of the page
     *
     * @return string
     * @throws ControllerException
     */
    public function getContent(): string
    {
        $file = $this->page_file;
        if(!$file) {
            throw new ControllerException('Please set view first');
        }

        ob_start();
        include $file;
        return ob_get_clean();
    }
    
}