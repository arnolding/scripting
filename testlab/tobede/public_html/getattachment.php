<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
ini_set("memory_limit","2048M");


$recordid = $_POST['recordid'];
if ($recordid == "") {
	$recordid = $_GET['recordid'];
}
$archname = $_POST['archname'];
if ($archname == "") {
	$archname = $_GET['archname'];
}

$date_tag = strftime("%b %d %Y, %X %Z ", time());
$log_msg = $date_tag . "UserName: [" . $_COOKIE["db_mediawikiUserName"] . "], REMOTE_ADDR: [" . $_SERVER["REMOTE_ADDR"] . "], SCRIPT: [" . $_SERVER["SCRIPT_NAME"] . " with " . $recordid . " and " . $archname . "]\n";
file_put_contents("/main/stage/wiki/testlab/user.log" , $log_msg , FILE_APPEND);

$inc_path = get_include_path();
$new_inc_path = '..:' . $inc_path;
set_include_path($new_inc_path);
include "class_xml2array.php";
include "ttprosoap.php";
set_time_limit(1800);

	$cookie = database_login('Software_development');
//	print_r($arr);
	$atta = one_attachment($recordid , $archname);
	database_logoff($cookie);

	echo json_encode($atta);
function one_attachment($recordid, $archname)
{
	global $cookie;


		$att_file = getAttachment($cookie , $recordid, $archname);
		$pp = strpos($att_file , "Content-Length:");
		$pp = $pp + 16;
		$pp_v = 0;
		while (is_numeric(substr($att_file, $pp , 1))) {
			$pp_v = $pp_v * 10 + substr($att_file, $pp , 1);
			$pp++;
		}
		$regex="/<ttns:getAttachmentResponse>(.*)<\/ttns:getAttachmentResponse>/";
		$item = "";
		$message_str = "";
		if (preg_match_all($regex, $att_file, $matches_out)) {
			$item = $matches_out[1][0];
			$atta2 = XML2Array::createArray($item);
//			$atta2["pAttachment"]["m-pFileData"];
//			print_r($atta2);
		
		} else {
			$atta2["pAttachment"]["m-pFileData"] = null;
		}
		$atta2["Content-Length"] = $pp_v;

		return $atta2;
}
?>
