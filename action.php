<?php
include_once(dirname(__FILE__) . "/route.php");

function dispatch_action() {

    route();

    include_once(dirname(__FILE__) . "/config.php");
    include_once(dirname(__FILE__) . "/logging.php");
    include_once(dirname(__FILE__) . "/helper.php");


    list($path, $className, $funcName) = parse_action_string();

    $pth = CONTROLLER_PATH . "/" . $path . "/$className.php";
    logging::d("Action", "access: " . APP_NAME . ":$path::$className::$funcName");

    if (!file_exists($pth)) {
        include_once(dirname(__FILE__) . "/notfound.php");
        return;
    }

    include_once($pth);

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



