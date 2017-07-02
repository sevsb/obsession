<?php

include_once(dirname(__FILE__) . "/route.php");

route();

include_once(dirname(__FILE__) . "/config.php");
include_once(dirname(__FILE__) . "/logging.php");
include_once(dirname(__FILE__) . "/helper.php");


function update($dir) {
    chdir($dir);

    $cwd = getcwd();
    logging::d("Hook", "update app path: $cwd");

    $r = "";
    $f = popen("git pull 2>&1", "r");
    logging::d("Hook", "f = " . dump_var($f, true));
    while (($s = fgets($f)) != FALSE) {
        $r .= $s;
    }
    pclose($f);
    logging::d("Hook", "git pull\n$r");
}


update(dirname(__FILE__));
update(APP_PATH);


