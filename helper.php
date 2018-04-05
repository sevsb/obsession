<?php

include_once(dirname(__FILE__) . "/config.php");

if (!isset($_SESSION)) {
    session_start();
}

class ScopedTrace {
    private $start;
    private $name;
    public function __construct($name) {
        $this->start = gettimeofday(true);
        $this->name = $name;
    }

    public function __destruct() {
        $now = gettimeofday(true);
        $diff = $now - $this->start;
        logging::d("ScopedTrace", "{$this->name} costs $diff seconds.");
    }
};

function dump_var($var, $die = null) {
    if ($die === true) {
        $v = print_r($var, true);
        $v = str_replace("\n", " ", $v);
        $v = str_replace("\r", " ", $v);
        $v = preg_replace("/\s+/", " ", $v);
        return $v;
    }

    @Header("Content-type: text/html; charset=utf-8");
    echo "<pre>";
    // var_dump($var);
    print_r($var);
    echo "</pre>";
    if ($die != null) {
        die($die);
    }
}

function get_request($key, $default = null) {
    return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
}

function get_session($key, $default = null) {
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
}

function get_request_assert($key, $message = "someone is attempting to try parameter.") {
    if (isset($_REQUEST[$key])) {
        return $_REQUEST[$key];
    }
    logging::fatal("Helper", $message . ": $key");
    die("");
}

function get_session_assert($key, $message = "someone is attempting to try parameter.") {
    if (isset($_SESSION[$key])) {
        return $_SESSION[$key];
    }
    logging::fatal("Helper", $message . ": $key");
    die("");
}

function fatal($message = "invalid parameter") {
    logging::fatal("Helper", $message);
    die($message);
}

function jsUnescape($escstr) {
    // echo "input: ";
    // dump_var($escstr);
    preg_match_all("/%u[0-9A-Za-z]{4}|%.{2}|[0-9a-zA-Z.+-_ ]+/", $escstr, $matches);
    $ar = &$matches[0];
    $c = "";
    foreach ($ar as $val) {
        if (substr($val, 0, 1) != "%") {
            $c .= $val;
        } else if (substr($val, 1, 1) != "u") {
            $x = hexdec(substr($val, 1, 2));
            $c .= chr($x);
        } else {
            $val = intval(substr($val, 2), 16);
            if ($val < 0x7F) { // 0000-007F
                $c .= chr($val);
            } elseif ($val < 0x800) { // 0080-0800
                $c .= chr(0xC0 | ($val / 64));
                $c .= chr(0x80 | ($val % 64));
            } else { // 0800-FFFF
                $c .= chr(0xE0 | (($val / 64) / 64));
                $c .= chr(0x80 | (($val / 64) % 64));
                $c .= chr(0x80 | ($val % 64));
            }
        }
    }
    // echo "output: ";
    // dump_var($c);
    return $c;
}

function go($path) {
    $app = get_request("jumpsubdomain");
    if ($app != null) {
        $d = new domain();
        $domain = $d->domain();
        $subdomain = $d->subdomain();
        $url = "//$app.$domain/?$path";
    } else {
        $url = "?$path";
    }
    Header("Location: $url");
    die("");
}


/**
 * 将全角字符转换为半角字符
 */
function convertStrType($str) {
    $full_width = array("ａ", "ｂ", "ｃ", "ｄ", "ｅ", "ｆ", "ｇ", "ｈ", "ｉ", "ｊ", "ｋ", "ｌ", "ｍ", "ｎ", "ｏ", "ｐ", "ｑ", "ｒ", "ｓ", "ｔ", "ｕ", "ｖ", "ｗ", "ｘ", "ｙ", "ｚ", "０", "１", "２", "３", "４", "５", "６", "７", "８", "９");
    $half_width = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "0", '1', '2', '3', '4', '5', '6', '7', '8', '9');

    $toStr = str_replace($full_width, $half_width, $str);
    return $toStr;
}


function is_root_debug() {
    return (DEBUG && isset($_SESSION["userid"]) && $_SESSION["userid"] == 1);
}

function format_period($time){
    $t = time() - $time;
    $f = array(
        '31536000' => '年',
        '2592000' => '个月',
        '604800' => '星期',
        '86400' => '天',
        '3600' => '小时',
        '60' => '分钟',
        '1' => '秒'
    );
    foreach ($f as $k => $v) {
        if (0 != ($c = floor($t / (int)$k))) {
            return $c . $v . '前';
        }
    }
}

function mk_domain_url($url) {
    if (!isset($_SERVER["HTTP_HOST"])) {
        $domain = rtrim(DOMAIN_URL, "/");
    } else {
        $domain = $_SERVER["HTTP_HOST"];
    }
    $url = "http://$domain/$url";
    return $url;
}

function get_current_url() {
    return mk_domain_url(ltrim($_SERVER["REQUEST_URI"], "/"));
}

function read_url($url, $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    if ($data != null) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $out = curl_exec($ch);
    curl_close($ch);
    return $out;
}

function __array_column($array, $column_key, $index_key = null) {
    if (version_compare(PHP_VERSION,'5.5.0', '>=')) {
        return array_column($array, $column_key, $index_key);
    }  
    $result = array();
    foreach($array as $arr) {
        if(!is_array($arr)) continue;

        if(is_null($column_key)){
            $value = $arr;
        }else{
            $value = $arr[$column_key];
        }

        if(!is_null($index_key)){
            $key = $arr[$index_key];
            $result[$key] = $value;
        }else{
            $result[] = $value;
        }
    }
    return $result; 
}

