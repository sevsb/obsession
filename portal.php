<?php

include_once(dirname(__FILE__) . "/route.php");


function start() {
    // dump_var($_SERVER);

    route();

    include_once(dirname(__FILE__) . "/config.php");
    include_once(dirname(__FILE__) . "/logging.php");
    include_once(dirname(__FILE__) . "/helper.php");


    list($path, $controller, $action) = parse_query_string();

    $pth = CONTROLLER_PATH . "/" . $path . "/$controller.php";

    logging::d("Portal", "access: " . APP_NAME . ":$path::$controller::$action");

    if (!file_exists($pth)) {
        include_once(dirname(__FILE__) . "/notfound.php");
        return;
    }

    include_once($pth);

    try {
        $class = new ReflectionClass($controller);
        $thiz = $class->newInstance();

        try {
            $preaction = $class->getMethod("preaction");
            if ($preaction->isPublic() && !$preaction->isStatic()) {
                $preaction->invoke($thiz, $action);
            }
        } catch (ReflectionException $e) {
        }

        $func = $class->getMethod($action);
        if ($func->isPublic() && !$func->isStatic()) {
            $ret = $func->invoke($thiz);
        }


        try {
            $postaction = $class->getMethod("postaction");
            if ($postaction->isPublic() && !$postaction->isStatic()) {
                $postaction->invoke($thiz, $action);
            }
        } catch (ReflectionException $e) {
        }
    } catch (ReflectionException $e) {
        include_once(dirname(__FILE__) . "/notfound.php");
    }
}


