<?php

include_once(dirname(__FILE__) . "/logging.php");

function route() {
    if (!isset($_SESSION)) {
        session_start();
    }

    $debug = false;
    if (isset($_REQUEST["debugroute"]) && $_REQUEST["debugroute"] == "1") {
        $debug = true;
    }

    if (isset($_SESSION["route.domain"]) && isset($_SESSION["route.subdomain"]) && isset($_SESSION["route.content"])) {
        $domain = $_SESSION["route.domain"];
        $subdomain = $_SESSION["route.subdomain"];
        $contents = $_SESSION["route.content"];
        $contents = str_replace("<?php", "", $contents);
        eval($contents);
        if (!$debug && (!defined("DEBUG") || !DEBUG)) {
            logging::d("route", "route from session: $subdomain.$domain");
            return;
        }
        logging::d("route", "check domain instead of session because DEBUG is true.");
    }

    include_once(dirname(__FILE__) . "/domain.php");
    $d = new domain();
    $domain = $d->domain();
    $subdomain = $d->subdomain();
    $_SESSION["route.domain"] = $domain;
    $_SESSION["route.subdomain"] = $subdomain;

    $routepath = dirname(__FILE__) . "/../route/";

    if (!is_dir($routepath)) {
        return;
    }

    if (isset($_REQUEST["app"])) {
        $subdomain = $_REQUEST["app"];
        $_SESSION["route.subdomain"] = $subdomain;
    }

    $file = $routepath . "$subdomain.$domain.php";
    if (file_exists($file)) {
        logging::d("route", "route to $subdomain.$domain");
        include($file);
        $_SESSION["route.content"] = file_get_contents($file);
        return;
    }
    $file = $routepath . "$domain.php";
    if (file_exists($file)) {
        logging::d("route", "route to $domain");
        include($file);
        $_SESSION["route.content"] = file_get_contents($file);
        return;
    } else if (file_exists("$routepath/default.php")) {
        logging::d("route", "route to default");
        include("$routepath/default.php");
        $_SESSION["route.content"] = file_get_contents("$routepath/default.php");
        return;
    }

    logging::d("route", "route nothing: $domain");
    include_once(dirname(__FILE__) . "/notfound.php");
    die("");
}

function parse_query_string() {
    $defaultindex = false;
    $qr = array();
    if (isset($_REQUEST["action"])) {
        $s = $_REQUEST["action"];
        $qr = explode(".", $s);
    } else {
        $qs = $_SERVER["QUERY_STRING"];
        if (empty($qs)) {
            $qs = "index/index";
            $defaultindex = true;
        }
        $qsr = explode("&", $qs);
        $qs = $qsr[0];
        $qs = trim($qs, " /");
        $qr = explode("/", $qs);
    }

    $length = count($qr);
    if ($length < 3) {
        if (count($qr) == 1) {
            $defaultindex = true;
            $qr[1] = "index";
        }

        $controller = $qr[0]; // . "_controller";
        $action = $qr[1]; // . "_action";
        $path = "";
    } else {
        $controller = $qr[$length - 2]; // . "_controller";
        $action = $qr[$length - 1]; // . "_action";
        unset($qr[$length - 1]);
        unset($qr[$length - 2]);
        $path = implode("/", $qr);
    }

    return array(0 => $path, 1 => $controller, 2 => $action, 3 => $defaultindex);
}

function parse_action_string() {
    $s = isset($_REQUEST["action"]) ? $_REQUEST["action"] : null;
    if ($s === null) {
        die("no action.");
    }

    $arr = explode(".", $s);
    $length = count($arr);

    if ($length < 2) {
        die("invalid action.");
    }

    $className = $arr[$length - 2]; //  . "_controller";
    $funcName = $arr[$length - 1]; //  . "_ajax";
    unset($arr[$length - 1]);
    unset($arr[$length - 2]);
    $path = implode("/", $arr);

    return array(0 => $path, 1 => $className, 2 => $funcName);
}


