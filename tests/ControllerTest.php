<?php

use Deployr\Application;
use Deployr\Db;
use Deployr\Exception\ApplicationException;
use Deployr\Exception\ControllerException;
use Deployr\Message;
use PHPUnit\Framework\TestCase;

final class ControllerTest extends TestCase
{

    protected $app;
    protected $db;
    protected $message;
    protected $controller;

    /**
     * ControllerTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     * @throws ApplicationException
     * @throws ReflectionException
     * @throws \Deployr\Exception\DbException
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->createApp();
        $this->createDb();
        $this->createMessage();

        $this->controller = $this->getMockForAbstractClass('Deployr\Controller\AbstractController', [
            $this->app,
            $this->db,
            $this->message
        ]);
    }

    /**
     * Create application
     *
     * @throws ApplicationException
     */
    protected function createApp()
    {
        $this->app = new Application('1234');
        $_REQUEST[$this->app->getOption('access_key_name')] = $this->app->getOption('key');
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_GET[$this->app->getOption('access_key_name')] = $this->app->getOption('key');
        $_SESSION = [];

        ob_start();
        $this->app->run();
        ob_get_clean();
    }

    /**
     * Create database
     *
     * @throws \Deployr\Exception\DbException
     */
    protected function createDb()
    {
        @unlink('test.db');
        $this->db = Db::connect('test.db');
    }

    /**
     * Create message
     */
    protected function createMessage()
    {
        $this->message = new Message();
    }

    /**
     * Set invalid view
     */
    public function testSetInvalidView()
    {
        $this->expectException(ControllerException::class);
        $this->controller->setView('invalid');
    }

    /**
     * Assign data
     */
    public function testAssign()
    {
        $this->controller->assign('key1', 'value1');
        $this->assertSame('value1', $this->controller->key1);
    }

    /**
     * Assign multiple data
     */
    public function testAssignMultiple()
    {
        $this->controller->assign([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);
        $this->assertSame('value2', $this->controller->key2);
    }

    /**
     * Get content
     */
    public function testGetContent()
    {
        $this->controller->setView('404');
        $result = $this->controller->getContent();
        $expected = 'Return to index';
        $this->assertStringContainsString($expected, $result);
    }

    /**
     * Get content without view
     */
    public function testGetContentWithoutView()
    {
        $this->expectException(ControllerException::class);
        $this->expectExceptionMessage('Please set view first');
        $this->controller->getContent();
    }

    /**
     * Render view
     */
    public function testRender()
    {
        $this->controller->setView('404');
        $result = $this->controller->render();
        var_dump($result);
        $expected = '<title>Deployr!</title>';
        $this->assertStringContainsString($expected, $result);
    }

    /**
     * Render with no view
     */
    public function testRenderWithoutView()
    {
        $this->expectException(ControllerException::class);
        $this->expectExceptionMessage('Please set view first');
        $this->controller->render();
    }
}
