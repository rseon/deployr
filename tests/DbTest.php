<?php

use Deployr\Db;
use PHPUnit\Framework\TestCase;

final class DbTest extends TestCase
{
    const DBNAME = 'test.db';
    protected $db;

    /**
     * DbTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     * @throws \Deployr\Exception\DbException
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        @unlink(self::DBNAME);
        $this->db = Db::connect(self::DBNAME);
    }


    /**
     * Check if file is created
     */
    public function testCreationFile()
    {
        $this->assertFileExists(self::DBNAME);
        $this->assertFileIsWritable(self::DBNAME);
        $this->assertFileIsReadable(self::DBNAME);
    }

    /**
     * Insert log
     */
    public function testInsertLog()
    {
        $result = $this->db->insertLog('author', 'Message 1');
        $this->assertSame(1, $result);

        $result = $this->db->insertLog('author', 'Message 2');
        $this->assertSame(2, $result);
    }

    /**
     * Retrieve all settings
     */
    public function testGetSettings()
    {
        $expected = [
            'lang' => 'en',
            'excludes' => '/vendor/phpunit/phpunit,/node_modules/,/vendor/',
        ];
        $result = $this->db->getSettings();
        $this->assertSame($expected, $result);
    }

    /**
     * Retrieve one setting
     */
    public function testGetSetting()
    {
        $expected = 'en';
        $result = $this->db->getSetting('lang');
        $this->assertSame($expected, $result);
    }

    /**
     * Retrieve one invalid setting
     */
    public function testGetInvalidSetting()
    {
        $expected = null;
        $result = $this->db->getSetting('invalid');
        $this->assertSame($expected, $result);
    }

    /**
     * Retrieve all rows
     */
    public function testGetAll()
    {
        $expected = [
            [
                'id' => 1,
                'key' => 'lang',
                'value' => 'en',
            ],
            [
                'id' => 2,
                'key' => 'excludes',
                'value' => '/vendor/phpunit/phpunit,/node_modules/,/vendor/',
            ],
        ];
        $result = $this->db->getAll('settings');
        $this->assertSame($expected, $result);
    }

    /**
     * Retrieve all rows with condition
     */
    public function testGetAllWithCondition()
    {
        $expected = [
            [
                'id' => 2,
                'key' => 'excludes',
                'value' => '/vendor/phpunit/phpunit,/node_modules/,/vendor/',
            ],
        ];
        $result = $this->db->getAll('settings', ['key' => 'excludes']);
        $this->assertSame($expected, $result);
    }

    /**
     * Retrieve all rows ordered
     */
    public function testGetAllWithOrder()
    {
        $expected = [
            [
                'id' => 2,
                'key' => 'excludes',
                'value' => '/vendor/phpunit/phpunit,/node_modules/,/vendor/',
            ],
            [
                'id' => 1,
                'key' => 'lang',
                'value' => 'en',
            ],
        ];
        $result = $this->db->getAll('settings', [], 'key ASC');
        $this->assertSame($expected, $result);
    }

    /**
     * Retrieve all rows limited
     */
    public function testGetAllWithLimit()
    {
        $expected = [
            [
                'id' => 1,
                'key' => 'lang',
                'value' => 'en',
            ],
        ];
        $result = $this->db->getAll('settings', [], '', 1);
        $this->assertSame($expected, $result);
    }

    /**
     * Retrieve all rows from invalid table
     */
    public function testGetAllWithInvalidTable()
    {
        $expected = [];
        $result = $this->db->getAll('invalid');
        $this->assertSame($expected, $result);
    }

    /**
     * Retrieve one row
     */
    public function testGetRow()
    {
        $expected = [
            'id' => 1,
            'key' => 'lang',
            'value' => 'en',
        ];
        $result = $this->db->getRow('settings');
        $this->assertSame($expected, $result);
    }


    /**
     * Retrieve one row with condition
     */
    public function testGetRowWithCondition()
    {
        $expected = [
            'id' => 2,
            'key' => 'excludes',
            'value' => '/vendor/phpunit/phpunit,/node_modules/,/vendor/',
        ];
        $result = $this->db->getRow('settings', ['key' => 'excludes']);
        $this->assertSame($expected, $result);
    }

    /**
     * Retrieve one row from invalid table
     */
    public function testGetRowWithInvalidTable()
    {
        $expected = [];
        $result = $this->db->getRow('invalid');
        $this->assertSame($expected, $result);
    }

    /**
     * Retrieve one value
     */
    public function testGetValue()
    {
        $expected = 'en';
        $result = $this->db->getValue('settings', 'value');
        $this->assertSame($expected, $result);
    }

    /**
     * Retrieve one value with condition
     */
    public function testGetValueWithCondition()
    {
        $expected = '/vendor/phpunit/phpunit,/node_modules/,/vendor/';
        $result = $this->db->getValue('settings', 'value', ['key' => 'excludes']);
        $this->assertSame($expected, $result);
    }

    /**
     * Retrieve one value with invalid field name
     */
    public function testGetValueWithInvalidFieldname()
    {
        $expected = null;
        $result = $this->db->getValue('settings', 'invalid');
        $this->assertSame($expected, $result);
    }

    /**
     * Retrieve one value with invalid condition
     */
    public function testGetValueWithInvalidCondition()
    {
        $expected = null;
        $result = $this->db->getValue('settings', 'value', ['key' => 'invalid']);
        $this->assertSame($expected, $result);
    }

    /**
     * Retrieve one value from invalid table
     */
    public function testGetValueWithInvalidTable()
    {
        $expected = null;
        $result = $this->db->getValue('invalid', 'canbeempty');
        $this->assertSame($expected, $result);
    }

    /**
     * Insert
     */
    public function testInsert()
    {
        $result = $this->db->insert('test', [
            'key1' => 'value1',
            'key2' => 'value2',
        ]);

        $expectedData = [
            [
                'id' => 1,
                'key1' => 'value1',
                'key2' => 'value2',
            ]
        ];

        $resultData = $this->db->getAll('test');

        $this->assertSame(1, $result);
        $this->assertSame($expectedData, $resultData);
    }


    /**
     * Bulk insert
     */
    public function testBulkInsert()
    {
        $this->db->insert('test', [
            [
                'key1' => 'value1',
                'key2' => 'value2',
            ],
            [
                'key1' => 'value3',
                'key2' => 'value4',
            ],
        ]);

        $expectedData = [
            [
                'id' => 1,
                'key1' => 'value1',
                'key2' => 'value2',
            ],
            [
                'id' => 2,
                'key1' => 'value3',
                'key2' => 'value4',
            ],
        ];

        $resultData = $this->db->getAll('test');

        $this->assertSame($expectedData, $resultData);
    }

    /**
     * Delete
     *
     * @todo testDeleteFromId
     * @todo testDeleteInvalidTable
     * @todo testDeleteInvalidCondition
     */
    public function testDelete()
    {
        $result = $this->db->delete('settings', [
            'key' => 'lang'
        ]);

        $expectedData = [
            [
                'id' => 2,
                'key' => 'excludes',
                'value' => '/vendor/phpunit/phpunit,/node_modules/,/vendor/',
            ]
        ];
        $resultData = $this->db->getAll('settings');

        $this->assertSame(1, $result);
        $this->assertSame($expectedData, $resultData);
    }

    /**
     * Drop table
     */
    public function testDrop()
    {
        $expected = true;
        $result = $this->db->drop('settings');
        $this->assertSame($expected, $result);
    }
}
