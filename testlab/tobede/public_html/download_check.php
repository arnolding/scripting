<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
$date_tag = strftime("%b %d %Y, %X %Z ", time());
$log_msg = $date_tag . "UserName: [" . $_COOKIE["db_mediawikiUserName"] . "], REMOTE_ADDR: [" . $_SERVER["REMOTE_ADDR"] . "], SCRIPT: [" . $_SERVER["SCRIPT_NAME"] ."]\n";
file_put_contents("/main/stage/wiki/testlab/user.log" , $log_msg , FILE_APPEND);

// main.log is generated by "download_detail.pl"
$mainlog = file_get_contents("/main/stage/wiki/ia/LogOfPackage/main.log");
$jj = parse_base($mainlog);
#system("curl --url \"smtps://smtp.gmail.com:465\" --ssl-reqd --mail-from \"qa_notification@silvaco.com\" --mail-rcpt \"arnold.ho@silvaco.com\" --upload-file mail.txt --user \"qa_notification@silvaco.com:productqa\" --insecure");
#system("curl --url \"smtps://smtp.gmail.com:465\" --ssl-reqd --mail-from \"arnold.ho@silvaco.com\" --mail-rcpt \"arnold.ho@silvaco.com\" --upload-file mail.txt --user \"arnold.ho@silvaco.com:password\" --insecure 2>&1");
//$maintt = file_get_contents("/main/stage/wiki/ia/LogOfPackage/main_tt.log");
//$tt = parse_tt($maintt);

//$arr['main'] = $jj;
//$arr['tt'] = $tt;
//echo json_encode($arr);
echo json_encode($jj);

//echo $mainlog;

function parse_base($base)
{
	$bname_uniq = array();
	$base_array = array();
	$lines = explode("\n" , $base);
	foreach ($lines as $line1) {
		$jobj = json_decode($line1,true);
		if ($jobj) {
			$bname = $jobj['bname'];
		if (array_key_exists($bname , $bname_uniq)) {
			$bname_uniq[$bname]++;
		} else {
			$bname_uniq[$bname] = 1;
			array_push($base_array , $jobj);
		}
		}
	}

	return $base_array;
}
function parse_tt($base)
{
	$bname_uniq = array();
	$base_array = array();
	$lines = explode("\n" , $base);
	foreach ($lines as $line1) {
		$jobj = json_decode($line1,true);
		if ($jobj) {
			$bname = $jobj['bname'];
			$bname_uniq[$bname] = $jobj['latest_mtime'];
		if (array_key_exists($bname , $bname_uniq)) {
		} else {
			array_push($base_array , $jobj);
		}
		}
	}

	return $bname_uniq;
}

?>

