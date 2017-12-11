<?php

if (file_exists(dirname(__FILE__) . "/../PATH.php")) {
    include_once(dirname(__FILE__) . "/../PATH.php");
}

// app
defined('APP_NAME') or define('APP_NAME', "app");

// path
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__) . "/..");
// defined('FRAMEWORK_PATH') or define('FRAMEWORK_PATH', ROOT_PATH . "/framework/");
defined('FRAMEWORK_PATH') or define('FRAMEWORK_PATH', dirname(__FILE__) . "/");
defined('APP_PATH') or define('APP_PATH', ROOT_PATH . "/" . APP_NAME . "/");
defined('CONTROLLER_PATH') or define('CONTROLLER_PATH', ROOT_PATH . "/" . APP_NAME . "/controller");

// url
defined('DOMAIN_URL') or define('DOMAIN_URL', "http://180.76.188.68/");
defined('ROOT_URL') or define('ROOT_URL', "");
// defined('INSTANCE_URL') or define('INSTANCE_URL', "/instance/");
defined('INSTANCE_URL') or define('INSTANCE_URL', "/" . basename(dirname(dirname(__FILE__))) . "/");
defined('HOME_URL') or define('HOME_URL', DOMAIN_URL . INSTANCE_URL);
defined('APP_URL') or define('APP_URL', ROOT_URL . INSTANCE_URL . APP_NAME . "/");
defined('VENDOR_URL') or define('VENDOR_URL', ROOT_URL . INSTANCE_URL . "/vendor/");

// log
// defined('LOG_DIR') or define('LOG_DIR', ROOT_PATH . '/logs/');

// template
defined('TPL_PATH') or define('TPL_PATH', "/" . APP_NAME . "/tpl/");
defined('TPL_JS_PATH') or define('TPL_JS_PATH', "/" . APP_NAME . "/js/");
defined('TPL_CSS_PATH') or define('TPL_CSS_PATH', "/" . APP_NAME . "/css/");

// for debug
defined('DEBUG') or define('DEBUG', false);

defined('PARSE_SCRIPT') or define('PARSE_SCRIPT', false);

defined('ORGANIZATION_NAME') or define('ORGANIZATION_NAME', "");

if (!isset($_SESSION)) {
    session_start();
}


