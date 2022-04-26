<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Taipei');
if (array_key_exists('krumo' , $_GET)) {
	$krumo_enabled = $_GET['krumo'];
} else {
	$krumo_enabled = 0;
}
$logonly = 0;
if (array_key_exists('logonly' , $_POST)) {
	$logonly = 1;
}
$date_tag = strftime("%b %d %Y, %X %Z ", time());
$log_msg = $date_tag . "UserName: [" . $_COOKIE["db_mediawikiUserName"] . "], REMOTE_ADDR: [" . $_SERVER["REMOTE_ADDR"] . "], SCRIPT: [" . $_SERVER["SCRIPT_NAME"] . " logonly " . $logonly . "]\n";
file_put_contents("/main/stage/wiki/testlab/user.log" , $log_msg , FILE_APPEND);

if ($logonly == 1) {
	return;
}

$inc_path = get_include_path();
$new_inc_path = '..:' . $inc_path;
set_include_path($new_inc_path);
include "krumo/class.krumo.php";
include "class_ttpro.php";
date_default_timezone_set('America/Los_Angeles');



	$arr = ttpro::get_dropdowns();
$arr["_COOKIE"] = $_COOKIE;

$php_time = (array)date_create();
$php_time["offset"] = 0;
$all_timezone = DateTimeZone::listAbbreviations();
if ( $php_time["timezone_type"] == 3 ) {
	foreach ($all_timezone as $zone_abbr => $k) {
		foreach ($k as $m) {
			if ($m["timezone_id"] == $php_time["timezone"]) {
				$php_time["offset"] = $m["offset"];
				break 2;
            }
        }
    }
}


$arr["timezone"] = $php_time;
//date_timezone_get();
//timezone_offset_get();

if ($krumo_enabled) {krumo($arr);}
echo json_encode($arr);


?>
