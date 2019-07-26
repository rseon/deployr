<?php

use Deployr\Application;
use Deployr\Exception\ApplicationException;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{

    protected $app;

    /**
     * ApplicationTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->app = new Application('1234');
    }


    /**
     * Get base path
     */
    public function testGetBasePath()
    {
        $expected = str_replace('\\', '/', dirname(__DIR__).'/src');
        $result = str_replace('\\', '/', $this->app->getBasePath());
        $this->assertSame($expected, $result);
    }

    /**
     * Get root path
     */
    public function testGetRootPath()
    {
        $expected = realpath(dirname(dirname($this->app->getBasePath())));
        $result = $this->app->getRootPath();
        $this->assertSame($expected, $result);
    }

    /**
     * Get option
     */
    public function testGetOption()
    {
        $expected = '1234';
        $result = $this->app->getOption('key');
        $this->assertSame($expected, $result);
    }

    /**
     * Set option
     */
    public function testSetOptions()
    {
        $expected = 'value';
        $this->app->setOptions(['test' => 'value']);
        $result = $this->app->getOption('test');
        $this->assertSame($expected, $result);
    }

    /**
     * Run app without key
     *
     * @throws ApplicationException
     */
    public function testRunMissingKey()
    {
        $this->app = new Application('');
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Please provide access key');
        $this->app->run();
    }

    /**
     * Run app with wrong key
     */
    public function testRunWrongKey()
    {
        $_REQUEST[$this->app->getOption('access_key_name')] = 'WRONGKEY';

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Missing or invalid access key.');
        $this->app->run();
    }

    /**
     * Run app with no IP
     */
    public function testRunNoIp()
    {
        $_REQUEST[$this->app->getOption('access_key_name')] = $this->app->getOption('key');

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Unauthorized IP');
        $this->app->run();
    }

    /**
     * Run app with not allowed IP
     */
    public function testRunNotAllowedIp()
    {
        $_REQUEST[$this->app->getOption('access_key_name')] = $this->app->getOption('key');
        $_SERVER['REMOTE_ADDR'] = '0.0.0.0';

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Unauthorized IP');
        $this->app->run();
    }

    /**
     * Run app successfully
     */
    public function testRunSuccess()
    {
        $_REQUEST[$this->app->getOption('access_key_name')] = $this->app->getOption('key');
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_GET[$this->app->getOption('access_key_name')] = $this->app->getOption('key');
        $_SESSION = [];

        ob_start();
        $this->app->run();
        $result = ob_get_clean();

        $expected = '<title>Deployr!</title>';
        $this->assertStringContainsString($expected, $result);
    }


}
