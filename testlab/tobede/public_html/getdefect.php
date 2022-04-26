<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
ini_set("memory_limit","2048M");


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
	$rlist = getDefect($cookie, $defectNumber, "false");
//	echo "<br><br>Record List ====<br><br>" . $rlist . "<br><br>";

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
			echo "Length of rlist: " . strlen($rlist) . "<br>";
			echo "<br>" . $rlist . "<br>";
			file_put_contents("/main/stage/wiki/testlab/user.log" ,
				substr($rlist, 0, 2000) , FILE_APPEND);
//			echo "<br>" . substr($rlist,  -1000) . "<br>";
		}
	}

	$arr = XML2Array::createArray($item);

	if ($krumo_enabled) {krumo($arr);}
	
	$atta = &$arr["pDefect"]["reportedbylist"]["item"]["attachmentlist"]["item"];
	if (is_array($atta)) {
		if (array_key_exists(0,$atta)) {
			foreach ($atta as &$atta0) {
				one_attachment(&$atta0);
			}
		} else {
			one_attachment(&$atta);
		}
	}
//	print_r($arr);
	database_logoff($cookie);

	echo json_encode($arr);
function one_attachment($atta1)
{
	global $cookie;
	global $arr;
//		foreach ($atta1 as $k => $v) {
//			print_r($k);
//			echo "  ----->   ";
//			print_r($v);
//			echo "  <br>  ";
//		}

	$fn = $atta1["m-strFileName"];
	$not2get = 0;
	if (strcasecmp(substr($fn , -2) , "gz") == 0) {
			$not2get = 1;
	} else {
			$fn3 = substr($fn , -3);
			if ((strcasecmp($fn3 , ".gz") == 0) ||
				(strcasecmp($fn3 , ".7z") == 0)) {
				$not2get = 1;
			} else {
				$fn4 = substr($fn , -4);
				if ((strcasecmp($fn4 , ".zip") == 0) ||
					(strcasecmp($fn4 , ".gds") == 0) ||
					(strcasecmp($fn4 , ".tgz") == 0) ||
					(strcasecmp($fn4 , ".rar") == 0)) {
					$not2get = 1;
				} else {
					if (strcasecmp(substr($fn , -5) , ".pptx") == 0) {
						$not2get = 1;
					}
				}
			}
	}

	if ($not2get == 1) {
		$atta1["Content-Length"] = 0;
		$atta1["m-pFileData"] = null;
	} else {
		$att_file = getAttachment($cookie , $arr["pDefect"]["recordid"], $atta1["m-strArchiveName"]);
		$pp = strpos($att_file , "Content-Length:");
		$pp = $pp + 16;
		$pp_v = 0;
		while (is_numeric(substr($att_file, $pp , 1))) {
			$pp_v = $pp_v * 10 + substr($att_file, $pp , 1);
			$pp++;
		}
		$atta1["Content-Length"] = $pp_v;
		$regex="/<ttns:getAttachmentResponse>(.*)<\/ttns:getAttachmentResponse>/";
		$item = "";
		$message_str = "";
		if (preg_match_all($regex, $att_file, $matches_out)) {
			$item = $matches_out[1][0];
			$atta2 = XML2Array::createArray($item);
			$atta1["m-pFileData"] = $atta2["pAttachment"]["m-pFileData"];
		} else {
			$atta1["m-pFileData"] = null;
		}
	}
}
?>
