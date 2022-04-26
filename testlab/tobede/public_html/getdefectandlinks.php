<?php

$defectNumber = $_POST['defectNumber'];
if ($defectNumber == "") {
	$defectNumber = $_GET['defectNumber'];
}
$krumo_enabled = $_GET['krumo'];

//$defectNumber = $_POST['defectNumber'];
$date_tag = strftime("%b %d %Y, %X %Z ", time());
$log_msg = $date_tag . "UserName: [" . $_COOKIE["db_mediawikiUserName"] . "], REMOTE_ADDR: [" . $_SERVER["REMOTE_ADDR"] . "], SCRIPT: [" . $_SERVER["SCRIPT_NAME"] . " with " . $defectNumber . "]\n";
file_put_contents("/main/stage/wiki/testlab/user.log" , $log_msg , FILE_APPEND);

$inc_path = get_include_path();
$new_inc_path = '..:' . $inc_path;
set_include_path($new_inc_path);
include "krumo/class.krumo.php";
include "class_xml2array.php";
include "ttprosoap.php";
set_time_limit(1800);

	$cookie = database_login('Software_development');
	$rlist = getDefect($cookie, $defectNumber);
//	echo "<br><br>Record List ====<br><br>" . $rlist . "<br><br>";
	database_logoff($cookie);

	$regex="/<ttns:getDefectResponse>(.*)<\/ttns:getDefectResponse>/";
	$item = "";
	$message_str = "";
	if (preg_match_all($regex, $rlist, $matches_out)) {
		$item = $matches_out[1][0];
	} else {
		$regex="/<faultstring>(.*)<\/faultstring>/";
		if (preg_match_all($regex, $rlist, $matches_out)) {
			$item = $matches_out[1][0];
			echo "<br>" . "<u>php encounters error!" . "</u><br>";
			if (strcmp("The specified record does not exist in the database." , $item) == 0) {
				$message_str = "Following is the error message from TTPro Server:";
			} else {
				$message_str = "Following is the error message from TTPro Server. Please copy and send to arnold.ho@silvaco.com";
			}
			echo $message_str . "<br>";
			echo $item . "<br>";
		} else {
			echo "<br>" . "Copy the string and send to arnold.ho@silvaco.com" . "<br>";
			echo "<br>" . $rlist . "<br>";
		}
	}


	$arr = XML2Array::createArray($item);

	

	if ($krumo_enabled) {krumo($arr);}
	echo json_encode($arr);
//	echo "<hr>\n";
//	print_r($arr);

?>
