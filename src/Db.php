<?php

namespace Deployr;

use Deployr\Exception\DbException;

use DateTime;

/**
 * NOTE : This class wrap some methods to request JSON file like SQL.
 * Warning : this is not a full generic implementation, it's specific for this project Deployr.
 * If you want to request JSON as SQL, please see : https://github.com/nahid/jsonq
 */

class Db
{

    protected static $_instance; // @var Db
    protected $file; // @var string
    protected $data = []; // @var array

    /**
     * Connect database
     *
     * @param string $file
     * @return Db
     * @throws DbException
     */
    public static function connect(string $file): Db
    {
        if(static::$_instance instanceof static) {
            return static::$_instance;
        }
        return new static($file);
    }

    /**
     * Shortcut to create log.
     *
     * @param string $author
     * @param string $message
     * @param bool|null $status
     * @param array|null $files
     * @return int
     * @throws DbException
     */
    public function insertLog(string $author, string $message, ?bool $status = true, ?array $files = []): int
    {
        $data = [
            'author' => $author,
            'message' => $message,
            'date' => date('Y-m-d H:i:s'),
        ];

        if($status) {
            $data['status'] = (bool) $status;
        }
        if($files) {
            $data['files'] = implode(',', $files);
        }

        return $this->insert('logs', $data);
    }


    /**
     * Shortcut to get settings
     *
     * @return array
     */
    public function getSettings(): array
    {
        $res = $this->getAll('settings');
        $settings = [];

        foreach($res as $r) {
            $settings[$r['key']] = $r['value'];
        }

        return $settings;
    }

    /**
     * Shortcut to get specific setting
     *
     * @param string $key
     * @return string
     */
    public function getSetting(string $key)
    {
        return $this->getValue('settings', 'value', [
            'key' => $key,
        ]);
    }

    /**
     * Get all rows from $table according to $conditions, ordered by $order and only $limit
     *
     * @param string $table
     * @param array $conditions  Ex : ['key' => $value]
     * @param string $order  Ex : 'date', 'date DESC'
     * @param int $limit  0 is no limit
     * @return array
     */
    public function getAll(string $table, array $conditions = [], string $order = '', $limit = 0): array
    {
        if(!isset($this->data[$table])) {
            return [];
        }

        $result = [];

        if($conditions) {
            foreach($this->data[$table] as $index => $row) {
                $conditions_ok = 0;
                foreach($conditions as $field => $value) {
                    if(isset($row[$field]) && $row[$field] == $value) {
                        ++$conditions_ok;
                    }
                }

                if($conditions_ok === count($conditions)) {
                    $result[] = array_merge(['id' => $index +1], $row);
                }
            }
        }
        else {
            foreach($this->data[$table] as $index => $row) {
                $result[] = array_merge(['id' => $index +1], $row);
            }
        }

        if($order) {
            $orderWay = 'ASC';
            if(strpos($order, ' ') !== false) {
                list($order, $orderWay) = explode(' ', $order);
            }

            usort($result, function($a, $b) use ($order) {
                if($a === $b) {
                    return 0;
                }
                if($order === 'date') {
                    $dtA = new DateTime($a['date']);
                    $dtB = new DateTime($b['date']);

                    return $dtA < $dtB ? -1 : 1;
                }

                return $a[$order] < $b[$order] ? -1 : 1;
            });

            if($orderWay === 'DESC') {
                $result = array_reverse($result);
            }
        }

        if($limit) {
            $result = array_slice($result, 0, $limit);
        }

        return $result;
    }

    /**
     * Get first row from $table according to $conditions.
     *
     * @param string $table
     * @param array $conditions
     * @return array
     */
    public function getRow(string $table, array $conditions = []): array
    {
        $res = $this->getAll($table, $conditions, '', 1);
        return $res[0] ?? [];
    }

    /**
     * Get value $fieldname from $table according to $conditions
     *
     * @param string $table
     * @param string $fieldname
     * @param array $conditions
     * @return mixed|null
     */
    public function getValue(string $table, string $fieldname, array $conditions = [])
    {
        $res = $this->getRow($table, $conditions);
        return $res[$fieldname] ?? null;
    }

    /**
     * Insert $data into $table and returns inserted id.
     *
     * @param string $table
     * @param array $data
     * @return int
     * @throws DbException
     */
    public function insert(string $table, array $data): int
    {
        if(!isset($this->data[$table])) {
            $this->data[$table] = [];
        }

        $keys = array_keys($data);

        // Bulk insert
        if(is_numeric($keys[0]) && is_array($data[0])) {
            foreach($data as $d) {
                $this->data[$table][] = $d;
            }
        }
        else {
            $this->data[$table][] = $data;
        }

        $this->writeFile();
        return count($this->data[$table]) - 1;
    }

    /**
     * Delete from $table accroding to $conditions and returns number of deletions.
     *
     * @param $table
     * @param array $conditions
     * @return int
     * @throws DbException
     */
    public function delete($table, array $conditions): int
    {
        $nb_deletes = 0;
        if(isset($this->data[$table])) {
            foreach($this->data[$table] as $index => $line) {

                foreach($conditions as $key => $value) {
                    if(is_array($value)) {
                        foreach($value as $val) {
                            if(isset($line[$key]) && $line[$key] == $val) {
                                unset($this->data[$table][$index]);
                                ++$nb_deletes;
                            }
                        }
                    }
                    else {
                        if($key === 'id') {
                            if($index+1 === (int) $value) {
                                unset($this->data[$table][$index]);
                                ++$nb_deletes;
                            }
                        }
                        elseif(isset($line[$key]) && $line[$key] == $value) {
                            unset($this->data[$table][$index]);
                            ++$nb_deletes;
                        }
                    }
                }

            }
        }

        $this->writeFile();
        return $nb_deletes;
    }

    /**
     * Drop table
     *
     * @param string $table
     * @return bool
     * @throws DbException
     */
    public function drop(string $table)
    {
        if($table === '*') {
            $this->data = [];
        }
        else {
            unset($this->data[$table]);
        }

        return $this->writeFile();
    }

    /**
     * Db constructor.
     *
     * @param string $file
     * @throws DbException
     */
    protected function __construct(string $file)
    {
        $this->file = $file;

        if(!file_exists($file)) {
            $this->drop('*');
            $this->insertLog('system', 'Database created successfully');
            $this->insert('settings', [
                [
                    'key' => 'lang',
                    'value' => Application::DEFAULT_LANG,
                ],
                [
                    'key' => 'excludes',
                    'value' => implode(',', [
                        str_replace(str_replace('\\', '/', dirname(__DIR__)), '', pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_DIRNAME)),
                        '/node_modules/',
                        '/vendor/',
                    ]),
                ],
            ]);
        }

        $this->data = json_decode($this->getRawContent(), true);

        static::$_instance = $this;
    }

    /**
     * Write database file
     *
     * @return bool
     * @throws DbException
     */
    protected function writeFile()
    {
        $write = @file_put_contents($this->file, json_encode($this->data));
        if(!$write) {
            throw new DbException("Unable to write file {$this->file}");
        }

        return true;
    }

    /**
     * Get content of database
     *
     * @return string
     */
    protected function getRawContent(): string
    {
        return file_get_contents($this->file) ?: '{}';
    }

}
