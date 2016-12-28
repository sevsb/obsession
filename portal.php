<?php
include_once(dirname(__FILE__) . "/config.php");
include_once(dirname(__FILE__) . "/helper.php");
include_once(dirname(__FILE__) . "/logging.php");

function start() {
    // dump_var($_SERVER);

    $qs = $_SERVER["QUERY_STRING"];
    if (empty($qs)) {
        $qs = "index/index";
    }

    $qsr = explode("&", $qs);
    $qs = $qsr[0];

    $qs = trim($qs, " /");

    $qr = explode("/", $qs);
    if (count($qr) == 1) {
        $qr[1] = "index";
    }

    $controller = $qr[0] . "_controller";
    $action = $qr[1] . "_action";

    logging::d("Debug", "access: $controller::$action");

    $pth = CONTROLLER_PATH . "/$controller.php";
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


