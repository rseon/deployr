<?php

namespace Deployr\Db;

use PDO;
use Exception;

class Builder
{
    private static $_instance;
    protected $pdo;

    /**
     * @param string $database
     * @return Builder
     */
    public static function connect(string $database) {
        if(self::$_instance instanceof self) {
            return self::$_instance;
        }
        return new self($database);
    }

    /**
     * @return Builder
     * @throws Exception
     */
    public static function getInstance() {
        if(!(self::$_instance instanceof self)) {
            throw new Exception('Please connect first');
        }
        return self::$_instance;
    }

    /**
     * Accessor constructor.
     * @param string $database
     */
    protected function __construct(string $database) {
        try {
            $this->pdo = new PDO("sqlite:{$database}", '', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        }
        catch(Exception $e) {
            die(__METHOD__ . ' error : ' .$e->getMessage().PHP_EOL);
        }
        self::$_instance = $this;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPDO() {
        return $this->pdo;
    }

    /**
     * @param $table
     * @param array $conditions
     * @param array $order
     * @param null $limit
     * @return mixed
     */
    public function getAll($table, $conditions = [], $order = [], $limit = null) {
        $sql = '
            SELECT * FROM '.$table.'
        ';
        if($conditions) {
            $where = ' AND ';
            foreach($conditions as $field => $value) {
                $_where = '';
                if(is_array($value)) {
                    if(!empty($value)) {
                        $_where = $field . ' IN (' . implode(', ', array_map(function($v){ return '\''.$v.'\''; }, $value)) . ')';
                    }
                }
                else {
                    if(is_numeric($field)) {
                        list($value, $operator) = self::splitOperator($value);
                        $_where = $value . ' ' . $operator;
                        unset($conditions[$field]);
                    }
                    else {
                        list($field, $operator) = self::splitOperator($field);
                        $_where = $field . ' ' . $operator . ' :' . $field;
                    }
                }
                if($_where) {
                    $where .= $_where . ' AND ';
                }
            }
            $where = substr($where, 0, -5);
            if($where) {
                $sql .= ' WHERE 1 ' . $where;
            }
        }
        if($order) {
            $sql .= ' ORDER BY ' . implode(', ', $order);
        }
        if($limit) {
            $limit_nb = null;
            $limit_offset = 0;
            if(is_numeric($limit)) {
                $limit_nb = (int)$limit;
            }
            elseif(is_array($limit)) {
                if(isset($limit[0])) {
                    $limit_nb = (int)$limit[0];
                }
                if(isset($limit[1])) {
                    $limit_offset = (int)$limit[1];
                }
            }
            if($limit_nb) {
                $sql .= ' LIMIT ' . $limit_nb . ' OFFSET ' . $limit_offset;
            }
        }
        $stmt = $this->pdo->prepare($sql);
        if($conditions) {
            foreach($conditions as $field => $value) {
                if(!is_array($value)) {
                    list($field, $operator) = self::splitOperator($field);
                    $stmt->bindValue(':'.$field, $value, self::getParamType($value));
                }
            }
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $table
     * @param array $conditions
     * @return array
     */
    public function getRow($table, $conditions = []) {
        $res = $this->getAll($table, $conditions, [], 1);
        if(isset($res[0])) {
            return $res[0];
        }
        return [];
    }

    /**
     * @param $fieldname
     * @param $table
     * @param array $conditions
     * @return array|bool|mixed
     */
    public function getValue($fieldname, $table, $conditions = []) {
        $res = $this->getRow($table, $conditions);
        if(!$res) {
            return false;
        }
        if(is_array($fieldname)) {
            $returns = [];
            foreach($fieldname as $f) {
                if(isset($res[$f])) {
                    $returns[$f] = $res[$f];
                }
                else {
                    $returns[$f] = false;
                }
            }
            return $returns;
        }
        if(isset($res[$fieldname])) {
            return $res[$fieldname];
        }
        return false;
    }

    /**
     * @param $sql
     * @param array $datas
     * @return mixed
     */
    public function fetchAll($sql, $datas = []) {
        $stmt = $this->pdo->prepare($sql);
        foreach($datas as $field => $value) {
            $stmt->bindValue(':'.$field, $value, self::getParamType($value));
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $sql
     * @param array $datas
     * @return array
     */
    public function fetchRow($sql, $datas = []) {
        $res = $this->fetchAll($sql, $datas);
        if(isset($res[0])) {
            return $res[0];
        }
        return [];
    }

    /**
     * @param $table
     * @param array $datas
     * @return int
     */
    public function insert($table, array $datas) {
        $keys = array_keys($datas);
        // Bulk insert
        $bulk = false;
        if(is_numeric($keys[0]) && is_array($datas[0])) {
            $bulk = true;
            $base_keys = array_keys(array_values($datas)[0]);
            $sql = 'INSERT INTO '.$table.' ('.implode(',', array_map(function($v) { return '`' . $v . '`'; }, $base_keys)).') VALUES ';
            $values = [];
            $exec_values = [];
            foreach($datas as $row) {
                $values[] = '(' . implode(', ', array_map(function($v) { return '?'; }, $row)) . ')';
                $exec_values = array_merge($exec_values, array_values($row));
            }
            $sql .= implode(', ', $values);
        }
        else {
            // Regular insert
            $sql = 'INSERT INTO '.$table.' ('.implode(', ', $keys).') VALUES ('.implode(', ', array_map(function($v) { return ':'.$v; }, $keys)).')';
        }
        $stmt = $this->pdo->prepare($sql);
        try {
            $this->pdo->beginTransaction();
            // Bulk insert
            if($bulk) {
                $stmt->execute($exec_values);
            }
            else {
                // Regular insert
                foreach($datas as $field => $value) {
                    $stmt->bindValue(':'.$field, $value, self::getParamType($value));
                }
                $stmt->execute();
            }
            $id = $this->pdo->lastInsertId();
            $this->pdo->commit();
            return (int) $id;
        } catch(\PDOException $e) {
            $this->pdo->rollback();
            exit('Error : '.$e->getMessage().'<br />');
        }
    }

    /**
     * @param $table
     * @param array $datas
     * @param array $conditions
     * @return int
     */
    public function update($table, array $datas, array $conditions) {
        $sql = 'UPDATE '.$table.' SET ';
        foreach($datas as $field => $value) {
            $sql .= $field . ' = :' . $field . ', ';
        }
        $sql = substr($sql, 0, -2);
        $sql .= ' WHERE ';
        foreach($conditions as $field => $value) {
            if(is_array($value)) {
                $sql .= $field . ' IN ('.implode(', ', array_map(function($v){ return '\''.$v.'\''; }, $value)).') AND ';
            }
            else {
                if(is_numeric($field)) {
                    list($value, $operator) = self::splitOperator($value);
                    $sql .= $value . ' ' . $operator . ' AND ';
                    unset($conditions[$field]);
                }
                else {
                    list($field, $operator) = self::splitOperator($field);
                    $sql .= $field . ' ' . $operator . ' :' . $field . ' AND ';
                }
            }
        }
        $sql = substr($sql, 0, -5);
        $stmt = $this->pdo->prepare($sql);
        try {
            foreach($datas as $field => $value) {
                $stmt->bindValue(':'.$field, $value, self::getParamType($value));
            }
            foreach($conditions as $field => $value) {
                list($field, $operator) = self::splitOperator($field);
                $stmt->bindValue(':'.$field, $value, self::getParamType($value));
            }
            $this->pdo->beginTransaction();
            $stmt->execute();
            $nb_rows = $stmt->rowCount();
            $this->pdo->commit();
            return (int) $nb_rows;
        } catch(\PDOException $e) {
            $this->pdo->rollback();
            exit('Error : '.$e->getMessage().'<br />');
        }
    }

    /**
     * @param $table
     * @param array $conditions
     * @return int
     */
    public function delete($table, array $conditions) {
        $sql = 'DELETE FROM '.$table.' WHERE ';
        foreach($conditions as $field => $value) {
            if(is_array($value)) {
                $sql .= $field . ' IN ('.implode(', ', array_map(function($v){ return '\''.$v.'\''; }, $value)).') AND ';
                unset($conditions[$field]);
            }
            else {
                if(is_numeric($field)) {
                    list($value, $operator) = self::splitOperator($value);
                    $sql .= $value . ' ' . $operator . ' AND ';
                    unset($conditions[$field]);
                }
                else {
                    list($field, $operator) = self::splitOperator($field);
                    $sql .= $field . ' ' . $operator . ' :' . $field . ' AND ';
                }
            }
        }
        $sql = substr($sql, 0, -5);
        $stmt = $this->pdo->prepare($sql);
        try {
            foreach($conditions as $field => $value) {
                list($field, $operator) = self::splitOperator($field);
                $stmt->bindValue(':'.$field, $value, self::getParamType($value));
            }
            $this->pdo->beginTransaction();
            $stmt->execute();
            $nb_rows = $stmt->rowCount();
            $this->pdo->commit();
            return (int) $nb_rows;
        } catch(\PDOException $e) {
            $this->pdo->rollback();
            exit('Error : '.$e->getMessage().'<br />');
        }
    }

    /**
     * @param $name
     * @param array $params
     * @return array
     */
    public function routine($name, $params = [])
    {
        $query = 'CALL `'.$name.'`('.implode(',', array_fill(0, count($params), '?')).');';
        $stmt = $this->pdo->prepare($query);
        try {
            $i = 0;
            foreach($params as $p) {
                $stmt->bindValue(++$i, $p, PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {}
        return [];
    }

    /**
     * @param $value
     * @return bool|int
     */
    public static function getParamType($value)
    {
        if(is_int($value)) {
            return \PDO::PARAM_INT;
        }
        if(is_bool($value)) {
            return \PDO::PARAM_BOOL;
        }
        if(is_null($value)) {
            return \PDO::PARAM_NULL;
        }
        if(is_string($value)) {
            return \PDO::PARAM_STR;
        }
        return false;
    }

    /**
     * @param $value
     * @return array
     */
    public static function splitOperator($value)
    {
        $operator = '=';
        if(strpos($value, ' ') !== false) {
            $tmp = explode(' ', $value);
            $value = array_shift($tmp);
            $operator = implode(' ', $tmp);
            unset($tmp);
        }
        return [$value, $operator];
    }
}
