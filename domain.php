<?php

class domain {
    private $host = "";
    private $domain = "";
    private $subdomain = "";
    private $port = 80;

    public function __construct($url = null) {
        if ($url == null) { 
            $this->host = $_SERVER["HTTP_HOST"];
            $this->port = $_SERVER["SERVER_PORT"];
        } else {
            $host = parse_url($url);
            if (isset($host["host"])) {
                $this->port = isset($host["port"]) ? $host["port"] : $_SERVER["SERVER_PORT"];
                $this->host = isset($host["host"]) ? $host["host"] : "";
            } else {
                $matches = array();
                preg_match("/([^:\/]+)(:(\d+))*/", $url, $matches);
                if (!empty($matches)) {
                    $this->host = $matches[1];
                    $this->port = isset($matches[3]) ? $matches[3] : $_SERVER["SERVER_PORT"];
                }
            }
        }
        $this->parse();
    }

    private function parse() {
        if ($this->host === "") {
            return;
        }
        if (preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $this->host)) {
            $this->domain = $this->host;
            return;
        }

        $matches = array();
        preg_match("/(.*?)([^\.]+\.[^\.]+)$/", $this->host, $matches);
        if (!empty($matches)) {
            $this->domain = $matches[2];
            $this->subdomain = $matches[1];
            $this->subdomain = rtrim($this->subdomain, ".");
            return;
        }

        if (preg_match("/^[^\.]+$/", $this->host)) {
            $this->domain = $this->host;
        }
    }

    public function domain() {
        return $this->domain;
    }

    public function host() {
        return $this->host;
    }

    public function subdomain() {
        return $this->subdomain;
    }

    public function port() {
        return $this->port;
    }
};

// 
// function test($c1, $c2) {
//     echo ("<p>checking $c1 and $c2: " . (($c1 == $c2) ? "PASS" : "FAIL") . "</p>");
// }
// 
// $t = new domain("www.wuziyi.cc");
// test($t->subdomain(), "www");
// test($t->domain(), "wuziyi.cc");
// test($t->host(), "www.wuziyi.cc");
// test($t->port(), 80);
// 
// $t = new domain("wuziyi.cc");
// test($t->subdomain(), "");
// test($t->domain(), "wuziyi.cc");
// test($t->host(), "wuziyi.cc");
// test($t->port(), 80);
// 
// $t = new domain("o.b.wuziyi.cc");
// test($t->subdomain(), "o.b");
// test($t->domain(), "wuziyi.cc");
// test($t->host(), "o.b.wuziyi.cc");
// test($t->port(), 80);
// 
// $t = new domain("localhost");
// test($t->subdomain(), "");
// test($t->domain(), "localhost");
// test($t->host(), "localhost");
// test($t->port(), 80);
// 
// $t = new domain("192.168.1.1:8080");
// test($t->subdomain(), "");
// test($t->domain(), "192.168.1.1");
// test($t->host(), "192.168.1.1");
// test($t->port(), 8080);
// 
// var_dump(new domain());
// 
