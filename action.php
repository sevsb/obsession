<?php
include_once(dirname(__FILE__) . "/config.php");
include_once(dirname(__FILE__) . "/logging.php");
include_once(FRAMEWORK_PATH . "helper.php");

function dispatch_action() {
    spl_autoload_register(function($class) {
        $cf = CONTROLLER_PATH . "/$class.php";
        if (is_file($cf)) {
            include($cf);
        }
    });


    $s = get_request("action");
    if ($s == null) {
        return;
    }

    $arr = explode(".", $s);
    if (count($arr) != 2) {
        die("invalid action.");
    }

    $className = $arr[0] . "_controller";
    $funcName = $arr[1] . "_ajax";

    logging::d("Debug", "access: $className::$funcName");

    try {
        $class = new ReflectionClass($className);
        $thiz = $class->newInstance();
        $func = $class->getMethod($funcName);
        if ($func->isPublic() && !$func->isStatic()) {
            $ret = $func->invoke($thiz);
            if (is_string($ret)) {
                $retarr = explode("|", $ret);
                if (count($retarr) == 2) {
                    echo json_encode(array("ret" => $retarr[0], "reason" => $retarr[1]));
                } else {
                    echo json_encode(array("ret" => $ret));
                }
            } else if (is_array($ret)) {
                echo json_encode($ret);
            }
        }
    } catch(ReflectionException $e) {
        echo json_encode(array("ret" => "fail", "reason" => "no such action."));
    }
}



