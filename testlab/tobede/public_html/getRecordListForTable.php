<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
//date_default_timezone_set('Asia/Taipei');
$last_period = "";
if (array_key_exists('last' , $_POST)) {
	$last_period = $_POST['last'];
} else {
	if (array_key_exists('last' , $_GET)) {
		$last_period = $_GET['last'];
	}
}
$from = "";
$to = "";
// should be mm-dd-yyyy
if (array_key_exists('from' , $_POST)) {
    $from = $_POST['from'];
} else {
    if (array_key_exists('from' , $_GET)) {
        $from = $_GET['from'];
    }
}
if (array_key_exists('to' , $_POST)) {
    $to = $_POST['to'];
} else {
    if (array_key_exists('to' , $_GET)) {
        $to = $_GET['to'];
    }
}


if (array_key_exists('krumo' , $_GET)) {
	$krumo_enabled = $_GET['krumo'];
} else {
	$krumo_enabled = 0;
}
$logonly = 0;
if (array_key_exists('logonly' , $_POST)) {
	$logonly = 1;
}

$query_str = "NA";
$newfound_str = "NA";
$log_str;
$from_arr  = explode('-', $from);
$to_arr  = explode('-', $to);
if ((count($from_arr) == 3) && (count($to_arr) == 3)) {
	if ( checkdate($from_arr[1], $from_arr[2], $from_arr[0]) &&
		 checkdate($to_arr[1], $to_arr[2], $to_arr[0]) ) {
		$query_str = "{'Date Entered':{'&lt;from&gt;':'" .
			$from . "','&lt;through&gt;':'" .
			$to . "'}}";
		$log_str = "from " . $from . " to " . $to;
	}
}
if ($query_str == "NA") {
// $last_period = W for week, B for BiWeek, M for month, Y for year
	if ( $last_period != 'Week' and
		$last_period != 'BiWeek' and
		$last_period != 'Month' and
		$last_period != 'HalfYr' and
		$last_period != 'Year' and
		$last_period != '2Year') {
	$last_period = 'BiWeek';
	}
	if ($last_period == '2Year') {
		$newfound_str = 'Newly Found 2Y';
	} else {
		$newfound_str = 'Newly Found ' . substr($last_period,0,1);
	}
	$log_str = " with " .$last_period;
}



$date_tag = strftime("%b %d %Y, %X %Z ", time());
$log_msg = $date_tag . "UserName: [" . $_COOKIE["db_mediawikiUserName"] . "], REMOTE_ADDR: [" . $_SERVER["REMOTE_ADDR"] . "], SCRIPT: [" . $_SERVER["SCRIPT_NAME"] . "] " . $log_str . " logonly " . $logonly . "\n";
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


if ($newfound_str != 'NA') {
	$arr = ttpro::newfound_ae_responsible($newfound_str);
} else {
	$arr = ttpro::record_query($query_str);
}
$arr['pFieldList'] = ttpro::get_dropdowns();
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
