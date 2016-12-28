<?php
include_once(dirname(__FILE__) . "/config.php");

class cache_items {
    public function __construct($value, $timeout) {
        $this->value = $value;
        if ($timeout > 0)
            $this->expired_time = time() + $timeout;
        else
            $this->expired_time = -1;
    }

    public function isValid() {
        if ($this->expired_time < 0)
            return true;
        if (time() > $this->expired_time)
            return false;
        return true;
    }

    public $value;
    public $expired_time;
};

class cache {

    private static $instance = null;
    public static function instance() {
        if (self::$instance == null) {
            self::$instance = new cache();
        }
        return self::$instance;
    }


    private $storage = array();

    private function __construct() {
    }

    function save($key, $value, $timeout = -1) {
        $it = new cache_items($value, $timeout);
        $this->storage[$key] = $it;
    }

    function has($key) {
        return isset($this->storage[$key]);
    }

    function valid($key) {
        if (!$this->has($key))
            return false;
        $it = $this->storage[$key];
        return $it->isValid();
    }

    function load($key, $default = null) {
        if (!$this->valid($key))
            return $default;
        return $this->storage[$key]->value;
    }

    function drop($key) {
        if (isset($this->storage[$key])) {
            unset($this->storage[$key]);
        }
    }
};


// $c = cache::instance();
// $c->save("a", "xcvxvcxv");
// $r = $c->has("a");
// var_dump($r);
// $r = $c->valid("a");
// var_dump($r);
// $r = $c->load("a");
// var_dump($r);
// $r = $c->load("b");
// var_dump($r);







