<?php
$inc_path = get_include_path();
$new_inc_path = '..:' . $inc_path;
set_include_path($new_inc_path);
include "krumo/class.krumo.php";
include "class_ttpro.php";

// $last_period = W for week, B for BiWeek, M for month, Y for year

$last_period = $_POST['last'];
if ($last_period == "") {
	$last_period = $_GET['last'];
}
$krumo_enabled = $_GET['krumo'];

if ( $last_period != 'Week' and
	$last_period != 'BiWeek' and
	$last_period != 'Month' and
	$last_period != 'HalfYr' and
	$last_period != 'Year') {
	$last_period = 'Year';
//	$last_period = 'BiWeek';
}

$arr = ttpro::newfound_ae_responsible('Newly Found ' . substr($last_period,0,1));
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

$fields = $arr["recordlist"]["columnlist"]["item"];
$idx = 0;
foreach ($fields as $f) {
	echo $idx , $f["name"] , "<br>";
	$idx = $idx+1;
}

$total = 0;
$not_eq = 0;
echo "<BR><BR>";
echo "Following is ticket numbers whose Enter by is different from AE Responsible <br>";
$vals = $arr["recordlist"]["records"]["item"];
foreach ($vals as $v) {
	$total = $total + 1;
	if ($v["row"]["item"][5]["value"] != $v["row"]["item"][12]["value"]) {
	$not_eq = $not_eq + 1;
	echo $v["row"]["item"][0]["value"] , $v["row"]["item"][5]["value"] , $v["row"]["item"][12]["value"] , "<br>";
	}
}
echo "end<br>";

echo "Total=[" , $total , "], not equal =[" , $not_eq , "]<br>";

$arr["timezone"] = $php_time;
//date_timezone_get();
//timezone_offset_get();

//krumo($arr);
//echo json_encode($arr);


?>
