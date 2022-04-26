<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
$date_tag = strftime("%b %d %Y, %X %Z ", time());
$log_msg = $date_tag . "UserName: [" . $_COOKIE["db_mediawikiUserName"] . "], REMOTE_ADDR: [" . $_SERVER["REMOTE_ADDR"] . "], SCRIPT: [" . $_SERVER["SCRIPT_NAME"] ."]\n";
file_put_contents("/main/stage/wiki/testlab/user.log" , $log_msg , FILE_APPEND);

$builds = file_get_contents("/home/mpr/logs/master-build.log");
echo $builds;
?>
