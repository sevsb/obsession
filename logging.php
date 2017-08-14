<?php
// include_once(dirname(__FILE__) . "/config.php");

defined('LOG_DIR') or define('LOG_DIR', dirname(__FILE__) . '/../logs/');
defined('LOG_FILE_PREFIX') or define('LOG_FILE_PREFIX', "logging-");

class logging {
    private static $instance = null;
    private $fp = null;
    private $logdir_override = null;
    private $logfileprefix = LOG_FILE_PREFIX;
    private $inited = false;

    private function __construct() {
        $inited = false;
    }

    private function init() {
        // if (PHP_SAPI != "cli") {
            if ($this->fp != null) {
                fclose($this->fp);
            }

            $logdir = $this->logdir_override;
            if ($logdir == null) $logdir = LOG_DIR;

            $logdir .= "/" . date("Y");

            if (!is_dir($logdir)) {
                mkdir($logdir, 0777, true);
                chmod($logdir, 0777);
            }
            $path = $logdir . "/" . $this->logfileprefix . date("Y-m-d") . ".log";
            if (!file_exists($path)) {
                touch($path);
            }
            $this->fp = fopen($path, "a");
            chmod($path, 0777);
        // }
    }

    private static function instance() {
        if (self::$instance == null) {
            self::$instance = new logging();
        }
        return self::$instance;
    }

    private function set_dir($dir) {
        $this->logdir_override = $dir;
        $this->init();
    }

    private function write($level, $tag, $message, $strip = true) {
        if (!$this->inited) {
            $this->init();
        }

        if (PHP_SAPI != "cli") {
            if (!$this->fp) {
                return false;
            }
        }

        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        if ($strip) {
            $message = str_replace("\n", " ", $message);
            $message = str_replace("\r", " ", $message);
            $message = preg_replace("/\s+/", " ", $message);
        }

        $currentTime = gettimeofday();
        $ms = $currentTime["usec"];
        $ms = sprintf("%03d", (int) $ms / 1000);
        $tag = sprintf("%-8s", $tag);

        $logs = "<" . date("Y-m-d H:i:s") . ":$ms> ";
        $logs .= "    " . getmypid();
        // $logs .= isset($_SESSION['username']) ? "user:{$_SESSION['username']}" : "user:not login";
        $logs .= "    [";
        $logs .= isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'shell';
        $logs .= "]";
        $logs .= "    (";
        $logs .= isset($_SESSION["user.name"]) ? ($_SESSION["user.name"]) : "NOT LOGIN";
        $logs .= ")";
        $logs .= "    $level/$tag    $message\n";

        if (PHP_SAPI == "cli") {
            printf("%s", $logs);
            if (!$this->fp) {
                return true;
            }
        }
        flock($this->fp, LOCK_EX);
        fwrite($this->fp, $logs);
        flock($this->fp, LOCK_UN);
        return true;
    }

    public static function set_logging_dir($path) {
        logging::instance()->set_dir($path);
    }

    public static function set_file_prefix($prefix) {
        logging::instance()->logfileprefix = $prefix;
    }

    public static function d($tag, $message, $strip = true) {
        logging::instance()->write("D", $tag, $message, $strip);
    }

    public static function e($tag, $message, $strip = true) {
        logging::instance()->write("E", $tag, $message, $strip);
    }

    public static function w($tag, $message, $strip = true) {
        logging::instance()->write("W", $tag, $message, $strip);
    }

    public static function i($tag, $message, $strip = true) {
        logging::instance()->write("I", $tag, $message, $strip);
    }

    public static function v($tag, $message, $strip = true) {
        logging::instance()->write("V", $tag, $message, $strip);
    }

    public static function printStackTrace() {
        $array = debug_backtrace();
        foreach ($array as $key => $row) {
            if (!empty($row["class"])) {
                logging::instance()->write("E", "FATAL", "\t\t" . $row["class"] . "->" . $row["function"], false);
            } else {
                logging::instance()->write("E", "FATAL", "\t\t" . basename($row["file"]) . ":" . $row["function"] . ":" . $row["line"], false);
            }
        }
    }

    public static function fatal($tag = "FATAL", $message = "", $strip = true) {
        logging::instance()->write("F", $tag, $message, $strip);

        $array = debug_backtrace();
        foreach ($array as $key => $row) {
            if (!empty($row["class"])) {
                logging::instance()->write("E", "FATAL", "\t\t" . $row["class"] . "->" . $row["function"], false);
            } else {
                logging::instance()->write("E", "FATAL", "\t\t" . basename($row["file"]) . ":" . $row["function"] . ":" . $row["line"], false);
            }
        }
        die($message);
    }

    public static function assert($condition, $message, $strip = true) {
        if (!$condition) {
            logging::fatal("Assert", "Assertion fail: $message", $strip);
        }
    }

    public static function deprecated() {
        $array = debug_backtrace();

        logging::d("Deprecated", "calling a deprecated function '" . $array[1]["function"] . "' from:");

        foreach ($array as $key => $row) {
            if ($key == 0 || $key == 1) {
                continue;
            }
            if (!empty($row["class"])) {
                logging::instance()->write("D", "Deprecated", "\t\t" . $row["class"] . "->" . $row["function"], false);
            } else {
                logging::instance()->write("D", "Deprecated", "\t\t" . basename($row["file"]) . ":" . $row["function"] . ":" . $row["line"], false);
            }
        }
    }

}

function logging_error_handler($errno, $errstr, $errfile, $errline, $errcontext) {
    // if (!(error_reporting() & $errno)) {
    //     return;
    // }

    $tag_list = array(
        E_ERROR => "Error.Error",
        E_WARNING => "Error.Warning",
        E_PARSE => "Error.ParseError",
        E_NOTICE => "Error.Notice",
        E_CORE_ERROR => "Error.CoreError",
        E_CORE_WARNING => "Error.CoreWarning",
        E_COMPILE_ERROR => "Error.CompileError",
        E_COMPILE_WARNING => "Error.CompileWarning",
        E_USER_ERROR => "Error.UserError",
        E_USER_WARNING => "Error.UserWarning",
        E_USER_NOTICE => "Error.UserNotice",
        E_STRICT => "Error.Strict",
        E_RECOVERABLE_ERROR => "Error.Recoverable",
        E_DEPRECATED => "Error.Deprecated",
        E_USER_DEPRECATED => "Error.Deprecated",
    );
    $tag = isset($tag_list[$errno]) ? $tag_list[$errno] : "Error.Unknown";
    $abort = in_array($errno, array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR));

    logging::e("$tag", "[$errno] $errstr at $errfile:$errline");

    if ($abort) {
        logging::e("Error.Fatal", "Fatal Error. Aborting...");
        exit(1);
    }
    return true;
}

function logging_shutdown() {
    $arr = error_get_last();
    if (!empty($arr)) {
        if (in_array($arr["type"], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_STRICT))) {
            logging_error_handler($arr["type"], $arr["message"], $arr["file"], $arr["line"], null);
        }
    }
}

function logging_exception_handler($e) {
    logging::e("ERROR", "EXCEPTION: " . $e->getMessage());
}


function config_show_errors($show) {
    if ($show) {
        error_reporting(E_ALL);
        ini_set('display_errors', 'On');
    } else {
        error_reporting(0);
        ini_set('display_errors', 'Off');
    }
}

config_show_errors(true);

if (!defined("DEBUG") || !DEBUG) {
    set_error_handler('logging_error_handler');
    set_exception_handler('logging_exception_handler');
    register_shutdown_function("logging_shutdown");
}


// -- logging::d("TEST", "saaaaadsfasfdsadf");
// -- logging::i("TEST", "saaaaadsfasfdsadf");
// -- logging::e("TEST", "saaaaadsfasfdsadf");
// -- logging::v("TEST", "saaaaadsfasfdsadf");






