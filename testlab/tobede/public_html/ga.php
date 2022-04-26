<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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


		$att_file = getA($cookie , $recordid, $archname);
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
function getA($cookie, $recordid, $archName )
{
	$ttpro_soap_command = 'getAttachment';
	$eventid = 0;

	$soap_defect_mesg = 
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<tablename xsi:type="xsd:string">Defect</tablename>
		<recordID xsi:type="xsd:long">'.$recordid.'</recordID>
		<eventID xsi:type="xsd:long">'.$eventid.'</eventID>
		<pszArchiveName xsi:type="xsd:string">'.$archName.'</pszArchiveName>
      </urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = ttpro_post2($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}

function ttpro_post2($action,$mesg)
{
//	$address = '10.1.15.212';  // for 'issues'; Changed after 2018/1/1
	$address = '10.1.1.164';  // for 'issues';
	$address = '10.1.1.165';  // for 'caalm01';
	$port = 443;
	$len = strlen($mesg);
	$header =
'POST /cgi-bin/ttsoapcgi.exe HTTP/1.1
Accept-Encoding: gzip,deflate
Content-Type: text/xml;charset=UTF-8
SOAPAction: "urn:testtrack-interface#'.$action.'"
User-Agent: Jakarta Commons-HttpClient/3.1
Host: ' . $address .'
' . sprintf("Content-Length: %d\r\n\r\n",strlen($mesg));

	$msg2socket = $header . $mesg;

	$context = stream_context_create(
		array('ssl' => array('verify_peer' => false,
						'verify_peer_name' => false))
		);
	$rv = "";
	if ($sock = stream_socket_client('ssl://'.$address.':'.$port, $err_no, $err_str,30,STREAM_CLIENT_CONNECT,$context))  {
		fwrite($sock , $msg2socket);
		do {
//			$rv0 = fread($sock , 1024);
			$rv0 = stream_get_contents($sock);
echo "length: " . strlen($rv0) . "<br>\n";
			$rv = $rv . $rv0;
		} while (strlen($rv0) > 0);
//		} while (!feof($sock));
		fclose($sock);
	}

	return $rv;
}
?>
