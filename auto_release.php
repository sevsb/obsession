<?php

include_once("logging.php");
include_once("helper.php");


function update($dir) {
    chdir($dir);

    $cwd = getcwd();
    logging::d("Hook", "release to: $cwd");

    $r = "";
    $f = popen("git pull 2>&1", "r");
    logging::d("Hook", "f = " . dump_var($f, true));
    while (($s = fgets($f)) != FALSE) {
        $r .= $s;
    }
    pclose($f);
    logging::d("Hook", "git pull\n$r");
}

if (!isset($_REQUEST["hook"])) {
    die("");
}

$hook = $_REQUEST["hook"];
$hook = json_decode($hook, true);

if ($hook["password"] != "aabbcc") {
    logging::d("Hook", "invalid password.");
    die("");
}

logging::d("Hook", "update release.");

update(ROOT_PATH);
update("/var/www/html/znpf_dev");


