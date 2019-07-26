<?php

use PHPUnit\Framework\Error\Error;
use PHPUnit\Framework\TestCase;
use Deployr\Message;

final class MessageTest extends TestCase
{

    protected $message;

    /**
     * MessageTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->message = new Message();
    }

    /**
     * Set invalid lang
     */
    public function testSetInvalidLang()
    {
        $this->expectException(Error::class);
        $this->message->setLang('invalid');
    }

    /**
     * Get missing translations
     */
    public function testGetMissingTranslations()
    {
        $result = $this->message->getMissingTranslations();
        $this->assertSame([], $result);
    }

    /**
     * Get available langs
     */
    public function testGetAvailableLangs()
    {
        $result = $this->message->getAvailableLangs();
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Get a translation
     */
    public function testGet()
    {
        $this->message->setLang('fr');
        $result = $this->message->get('Author');

        $this->assertSame('Auteur', $result);
    }


}
