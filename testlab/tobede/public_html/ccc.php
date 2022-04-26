<?php

$php_time = (array) date_create();
$all_timezone = DateTimeZone::listAbbreviations();

if ( $php_time["timezone_type"] == 3 ) {
	foreach ($all_timezone as $zone_abbr => $k) {
		foreach ($k as $m) {
			if ($m["timezone_id"] == $php_time["timezone"]) {
				echo "$zone_abbr" , " > ";
				print_r($m);
				echo "<br>";
			}
		}
	}
}

//$arr["timezone"] = date_create();
//$arr["timezone_offset"] = DateTimeZone::listAbbreviations();
//date_timezone_get();
//timezone_offset_get();


?>
