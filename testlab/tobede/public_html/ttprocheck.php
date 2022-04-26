<?php

$host = $_POST['host'];
$port = $_POST['port'];
$user = $_POST['user'];
$pass = $_POST['pass'];
if ($host == "") {
	$host = "10.1.1.165";
	$port = 443;
	$user = "ttpro";
	$pass = "ap4ttpro!";
}
$krumo_enabled = $_GET['krumo'];

//$defectNumber = $_POST['defectNumber'];

$inc_path = get_include_path();
$new_inc_path = '..:' . $inc_path;
set_include_path($new_inc_path);
include "krumo/class.krumo.php";
include "class_xml2array.php";
include "ttprovari.php";
//set_time_limit(18);

	$plist = project_list($host , $port , $user , $pass);
//	$cookie = database_login('Software_development ');
	$rlist = "";
//getDefect($cookie, $defectNumber);
//	echo "<br><br>Record List ====<br><br>" . $rlist . "<br><br>";
//	database_logoff($cookie);

	$regex="/<ttns:getProjectListResponse>(.*)<\/ttns:getProjectListResponse>/";
	$item = "";
	$message_str = "";
	if (preg_match_all($regex, $plist, $matches_out)) {
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


//	$arr = XML2Array::createArray($item);

	$ret['host'] = $host;
	$ret['port'] = $port;
	$ret['prjlist'] = htmlspecialchars($item);

	if ($krumo_enabled) {krumo($arr);}
	echo json_encode($ret);
//	echo "<hr>\n";
//	print_r($arr);

?>
