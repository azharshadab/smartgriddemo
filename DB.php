<?php

/**
 * [<description>].
 *
 * @category [<category>]
 *
 * @author Ishtiyaq Husain <ishtiyaq.husain@gmail.com>
 * @copyright 2017 Ishtiyaq Husain
 * @license GPL http://ishtiyaq.com/license
 *
 * @version Release: 1.0.0
 *
 * @link http://ishtiyaq.com
 * @since File available since Release 1.0.0
 */
class DB
{
    private static $_instance = null;
    
    private $_pdo, $_query, $_errors = false, $_results, $_lastId, $_count = 0;
//    private $_error_message;

    private function __construct()
    {
        try {
            $this->_pdo = new PDO('mysql:host='.Config::get('mysql/host').';dbname='.Config::get('mysql/db'), Config::get('mysql/username'), Config::get('mysql/password'));
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

     public function check()
    {
        print_r("Hello");
    }

    public function changeDB($db)
    {
        $this->_pdo->exec('USE '.$db);
    }
    
    public function exec_sql($sql)
    {
        return $this->query($sql);
    }
    
    public function query($sql, $params = array())
    {
        $this->_errors = false;
        //$this->_pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
        if ($this->_query = $this->_pdo->prepare($sql)) {
            $x = 1;
            if (count($params)) {
                foreach ($params as $param) {
                    $this->_query->bindValue($x, $param);
                    ++$x;
                }
            }

            if ($this->_query->execute()) {
                $this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
                $this->_count = $this->_query->rowCount();
            } else {
//                $error = $this->_pdo->errorInfo();
//                $this->_error_message = $error[2];
                $this->_errors = true;
            }
        }

        return $this;
    }
    
    private function action($action, $table, $where = array())
    {
        if (count($where) === 3) {
            $operators = array('=', '<', '>', '>=', '<=');

            $field = $where[0];
            $operator = $where[1];
            $value = $where[2];

            if (in_array($operator, $operators)) {
                $sql = "{$action} FROM {$table} WHERE {$field} {$operator} ?";
                if (!$this->query($sql, array($value))->error()) {
                    return $this;
                }
            }
        }

        return false;
    }
    
    public function get($table, $where)
    {
        return $this->action('SELECT *', $table, $where);
    }
    
    public function delete($table, $where)
    {
        return $this->action('DELETE', $table, $where);
    }
    
    public function insert($table, $fields = array())
    {
        $keys = array_keys($fields);
        $values = '';
        $x = 1;

        foreach ($fields as $field) {
            $values .= '?';
            if ($x < count($fields)) {
                $values .= ', ';
            }
            ++$x;
        }

        $sql = "INSERT INTO {$table} (`".implode('`, `', $keys)."`) VALUES ({$values})";

        if (!$this->query($sql, $fields)->error()) {
            $this->_lastId = $this->_pdo->lastInsertId();

            return true;
        }

        return false;
    }
    
    public function update($table, $id, $fields = array())
    {
        $set = ' ';
        $x = 1;

        foreach ($fields as $name => $value) {
            $set .= "{$name} = ?";
            if ($x < count($fields)) {
                $set .= ', ';
            }
            ++$x;
        }

        $sql = "UPDATE {$table} SET {$set} WHERE id = {$id}";

        if (!$this->query($sql, $fields)->error()) {
            return true;
        }

        return false;
    }
    
    public function results()
    {
        return $this->_results;
    }
    
    public function first()
    {
        return $this->results()[0];
    }
    
    public function count()
    {
        return $this->_count;
    }
    
    public function error()
    {
        return $this->_errors;
    }

//    public function error_message()
//    {
//        return $this->_error_message;
//    }
    
    public function lastId()
    {
        return $this->_lastId;
    }
}
