<?php

include_once(dirname(__FILE__) . "/config.php");
include_once(FRAMEWORK_PATH . "logging.php");
include_once(FRAMEWORK_PATH . "cache.php");

class database {

    private $escape_table = array();
    private $pdo = null;

    private function __construct() {
        
    }

    public function init($db = null) {
        // if (file_exists($db)) {
        //     logging::i("Database", "Connect sqlite: $db");
        //     $this->pdo = new PDO("sqlite:$db");
        // } else {
            $dbname = "";
            if ($db != null) {
                $dbname = ";dbname=$db";
            }
            $this->pdo = new PDO("mysql:host=" . MYSQL_SERVER . "$dbname", MYSQL_USERNAME, MYSQL_PASSWORD);
        // }
    }

    public function close() {
        $this->pdo = null;
    }

    protected function escape($text) {
        return $this->pdo->quote($text);
    }

    protected function unescape($text) {
        return $text;
    }

    private function do_escape($table, $data) {
        if ($this->escape_table != null) {
            if (isset($this->escape_table[$table])) {
                foreach ($data as $k => $v) {
                    if (in_array($k, $this->escape_table[$table])) {
                        $v = $this->escape($v);
                        $data[$k] = $v;
                    }
                }
            }
        } else {
            foreach ($data as $k => $v) {
                $v = $this->escape($v);
                $data[$k] = $v;
            }
        }
        return $data;
    }

    private function do_unescape($arr) {
        foreach ($arr as $k => $v) {
            $arr[$k] = $this->unescape($v);
        }
        return $arr;
    }

    private function query($query) {
        if (strlen($query) > 100) {
            $more = strlen($query) - 100;
            logging::d("Database", substr($query, 0, 100) . "...($more bytes available)");
        } else {
            logging::d("Database", $query);
        }
        $res = $this->pdo->query($query);
        if ($res === false) {
            logging::d("Database", "FAIL query: " . dump_var($this->pdo->errorInfo(), true));
        }
        return $res;
    }

    protected function exec($exec) {
        logging::d("Database", $exec);
        return $this->pdo->exec($exec);
    }

    public function last_insert_id() {
        return $this->pdo->lastInsertId();
    }

    public function insert($table, $data) {
        if (!is_array($data)) {
            return false;
        }

        $data = $this->do_escape($table, $data);

        $keys = array_keys($data);
        $keyStr = implode(',', $keys);
        $values = implode(",", $data);
        $sql = "INSERT INTO {$table} ({$keyStr}) VALUES ({$values})";
        $ret = $this->query($sql);
        return ($ret !== false) ? $this->last_insert_id() : $ret;
    }

    public function update($table, $data, $where = null, $escape = true) {
        if (!is_array($data) || empty($where)) {
            return false;
        }

        if ($escape) {
            $data = $this->do_escape($table, $data);
        }
        if ($where != null) {
            $where = "WHERE $where";
        }

        $condition = '';
        foreach ($data as $key => $value) {
            $condition .= "{$key}={$value},";
        }
        $condition = substr($condition, 0, -1);
        $sql = "UPDATE {$table} SET {$condition} {$where}";
        $ids = $this->query($sql);
        return ($ids !== false);
    }

    public function delete($table, $where) {
        if (empty($table) || empty($where)) {
            return false;
        }
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $ids = $this->query($sql);
        return ($ids !== false);
    }

    protected function get_all($sql) {
        $result = $this->query($sql);
        // $result->setFetchMode(PDO::FETCH_ASSOC);
        $resArray = array();
        if ($result !== false) {
            while ($tmpArray = $result->fetch(PDO::FETCH_ASSOC)) {
                $tmpArray = $this->do_unescape($tmpArray);
                if (isset($tmpArray["id"]))
                    $resArray[$tmpArray["id"]] = $tmpArray;
                else
                    $resArray[] = $tmpArray;
            }
        }
        return $resArray;
    }

    private function get_one($sql) {
        $result = $this->query($sql);
        $resArray = $result->fetch(PDO::FETCH_ASSOC);
        $resArray = $this->do_unescape($resArray);
        return $resArray;
    }

    public function get_all_table($table, $where = "", $addons = "") {
        if (!empty($where)) {
            $where = "WHERE $where";
        }

        $query = "SELECT * FROM $table $where $addons";
        return $this->get_all($query);
    }
    
    public function show_all_tables() {
        $query = "show tables";
        return $this->get_all($query);
    }

    public function get_one_table($table, $where, $addons = "") {
        $addons = "$addons LIMIT 1";
        $res = $this->get_all_table($table, $where, $addons);
        if (empty($res)) {
            return null;
        }
        return array_shift($res);
    }

    public function get_count($table, $where = '') {
        if (!empty($where)) {
            $where = "WHERE $where";
        }
        $query = "SELECT COUNT(*) as count FROM {$table} " . $where;
        $result = $this->get_one($query);
        return $result['count'];
    }

    public function begin_transaction() {
        $this->pdo->beginTransaction();
    }

    public function rollback() {
        $this->pdo->rollback();
    }

    public function commit() {
        $this->pdo->commit();
    }

    protected function get_cached($cachename, $table, $where = "", $addons = "") {
        $cached = cache::instance()->load($cachename);
        if ($cached != null)
            return $cached;
        $au = $this->get_all_table($table, $where, $addons);
        cache::instance()->save($cachename, $au);
        return $au;
    }
}

