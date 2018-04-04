<?php

include_once(dirname(__FILE__) . "/route.php");


function start() {
    // dump_var($_SERVER);

    route();
    include_once(dirname(__FILE__) . "/config.php");
    include_once(dirname(__FILE__) . "/logging.php");
    include_once(dirname(__FILE__) . "/helper.php");

    if (file_exists(APP_PATH)) {
        logging::set_logging_dir(APP_PATH . "/logs/");
    }

    list($path, $controller, $action, $defaultindex) = parse_query_string();

    $pth = APP_PATH . '/' . $path . "/$controller/$action.php";
    if ($defaultindex) {
        $pth = APP_PATH . '/' . "$action.php";
    }
    // logging::d("Portal", "controller: " . $controller);
    // logging::d("Portal", "defaultindex: " . $defaultindex);
    // logging::d("Portal", "test file: " . $pth);
    if (file_exists($pth)) {
        $st = new ScopedTrace("action.invoke.file: $path/$controller/$action.php");
        logging::d("Portal", "access page: " . APP_NAME . "/$path/$controller/$action.php");
        include_once(dirname(__FILE__) . "/tpl.php");
        $tpl = new tpl();
        $tpl->display_file($pth);
        return;
    }

    $controller = $controller . "_controller";
    $action = $action . "_action";

    $pth = CONTROLLER_PATH . "/" . $path . "/$controller.php";

    logging::d("Portal", "access: " . APP_NAME . ":$path::$controller::$action");

    if (!file_exists($pth)) {
        include_once(dirname(__FILE__) . "/notfound.php");
        return;
    }

    include_once($pth);

    try {
        $st = new ScopedTrace("action.invoke: $controller:$action");
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
            if (!empty($ret)) {
                if (is_array($ret)) {
                    echo json_encode($ret);
                } else if (is_string($ret)) {
                    echo $ret;
                }
            }
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


