<?php
class soap {
	public static $msg = "";

function ttpro_post($action,$mesg) {
	$address = '10.1.15.212';  // for 'issues'; but changed after 2018/1/1
	$address = '10.1.1.164';  // for 'issues';
	$address = '10.1.1.165';  // for 'caalm01' changed after 2020/4/20;
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
	$count = 0;
	if ($sock = stream_socket_client('ssl://'.$address.':'.$port, $err_no, $err_str,30,STREAM_CLIENT_CONNECT,$context))  {
		fwrite($sock , $msg2socket);
		$rv = "";
		do {
//echo $count . " 1024B gotten<br>";
$count++;
			$rv0 = fread($sock , 1024);
			if ( $rv0 === false) {
	    		self::$msg = "socket_read() failed: reason: " . socket_strerror(socket_last_error($sock));
				break;
			}
			$rv = $rv . $rv0;
		} while (strlen($rv0) > 0);
		fclose($sock);
	} else {
    	self::$msg = "socket failed: $err_no - $err_str ";
	}

//echo "after " . $count . "K,just want to return ttpro_post<br>";
	return $rv;
}

public static function database_logoff($logoff_cookie)
{
	$action = 'DatabaseLogoff';
	$logoff_mesg =
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:DatabaseLogoff soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
         <cookie xsi:type="xsd:long">'.$logoff_cookie.'</cookie>
      </urn:DatabaseLogoff>
   </soapenv:Body>
</soapenv:Envelope>
';
	$logoff_resp = self::ttpro_post($action , $logoff_mesg);
}


function database_login0($database_name)
{

	$action = 'ProjectLogon';
	$login_mesg =
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
     <urn:ProjectLogon soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
       <pProj xsi:type="urn:CProject">
         <database xsi:type="urn:CDatabase">
           <name xsi:type="xsd:string">'.$database_name.'</name>
         </database>
         <options xsi:type="urn:ArrayOfCProjectDataOption" soapenc:arrayType="urn:CProjectDataOption[]">
           <item xsi:type="urn:CProjectDataOption">
             <name xsi:type="xsd:string">TestTrack Pro</name>
           </item>
         </options>
         <servernumber xsi:type="xsd:int">0</servernumber>
       </pProj>
       <username xsi:type="xsd:string">ttpro</username>
       <password xsi:type="xsd:string">ap4ttpro!</password>
     </urn:ProjectLogon>

   </soapenv:Body>
 </soapenv:Envelope>
';


	$login_resp = self::ttpro_post($action , $login_mesg);
	$regex="/<Cookie>(\d*)<\/Cookie>/";

	$cookie = 0;
	if (preg_match_all($regex, $login_resp, $matches_out)) {
		$cookie = $matches_out[1][0];
	} else {
		$regex="/<faultstring>(.*)<\/faultstring>/";
		if (preg_match_all($regex, $login_resp, $matches_out)) {
			$item = $matches_out[1][0];
			self::$msg = $item;
		}
		if (self::$msg =="") {
			self::$msg = "login to ttpro failed";
		}
	}
	return $cookie;
}

public static function database_login($database_name)
{
	$retry = 0;
	while ($retry >= 0) {
		$cookie = self::database_login0($database_name);
		if ($cookie > 0) {
			return $cookie;
		}
		$retry--;
	}
	return -1;
}
public static function project_list()
{
	$action = 'getProjectList';
	$login_mesg =
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
     <urn:getProjectList soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
       <username xsi:type="xsd:string">ttpro</username>
       <password xsi:type="xsd:string">ap4ttpro!</password>
     </urn:getProjectList>
   </soapenv:Body>
 </soapenv:Envelope>
';

	$projects = self::ttpro_post($action , $login_mesg);
	
	return $projects;
}




public static function record_with_query($cookie, $filter_name, $json_query_str, $cols)
{
	$ttpro_soap_command = 'getRecordListForTableWithQuery';

	$field_str = '<columnlist soap-enc:arraytype="urn:CTableColumn[' . count($cols) . ']" xsi:type="urn:ArrayOfCTableColumn">';
	foreach ($cols as $one_field) {
		$field_str .= '<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">' . $one_field . '</name></item>';
	}
	$field_str .= '</columnlist>';

	$soap_defect_mesg = 
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<tablename xsi:type="xsd:string">Defect</tablename>
		<filtername xsi:type="xsd:string">'.$filter_name.'</filtername>
		<query xsi:type="xsd:string">'. $json_query_str . '</query>' .
		$field_str .
		'</urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = self::ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}

public static function record_newfound_eng($cookie, $filter_name)
{
	$ttpro_soap_command = 'getRecordListForTable';

$fields = array('Number','Summary','Product','Type','Status','Entered by',
            'Priority','Date Entered','Disposition','Date Modified','Reference',
            'Currently Assigned To','Assigned To User','Closed By','Fixed By User'
            );


	$field_str = '<columnlist soap-enc:arraytype="urn:CTableColumn[' . count($fields) . ']" xsi:type="urn:ArrayOfCTableColumn">';
	foreach ($fields as $one_field) {
		$field_str .= '<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">' . $one_field . '</name></item>';
	}
	$field_str .= '</columnlist>';

	$soap_defect_mesg = 
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<tablename xsi:type="xsd:string">Defect</tablename>
		<filtername xsi:type="xsd:string">'.$filter_name.'</filtername>' .
		$field_str . '</urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = self::ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}

public static function record_newfound_ae_responsible($cookie, $filter_name)
{
	$ttpro_soap_command = 'getRecordListForTable';

	$fields = array('Number','Summary','Product','Type','Status','Entered by',
			'Priority','Date Entered','Disposition','Date Modified','Reference',
			'Currently Assigned To','AE Responsible', 'Assigned To User','Closed By','Fixed By User',
			'Date Created','In Development Date','Fix Date','Closed Date','Assign Date'
//			,'Date Found','Re-Open Date','Force Close Date','Customer Verify Date',
//			'Release to Customer Testing Date','Release to Testing Date',
//			'Paused Date','Current Assignment Date','Verify Date'
			);

	$field_str = '<columnlist soap-enc:arraytype="urn:CTableColumn[' . count($fields) . ']" xsi:type="urn:ArrayOfCTableColumn">';
	foreach ($fields as $one_field) {
		$field_str .= '<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">' . $one_field . '</name></item>';
	}
	$field_str .= '</columnlist>';

	$soap_defect_mesg = 
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<tablename xsi:type="xsd:string">Defect</tablename>
		<filtername xsi:type="xsd:string">'.$filter_name.'</filtername>' .
		$field_str . '</urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = self::ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}

public static function columns($cookie)
{
	$ttpro_soap_command = 'getColumnsForTable';

	$soap_defect_mesg = 
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<tablename xsi:type="xsd:string">Defect</tablename>
	  </urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = self::ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}


function getLinksForDefect($cookie, $defectRecordID)
{
	$ttpro_soap_command = 'getLinksForDefect';

	$soap_defect_mesg = 
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<defectRecordID xsi:type="xsd:long">'.$defectRecordID.'</defectRecordID>
      </urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}
function getDefect($cookie, $defectNumber)
{
	$ttpro_soap_command = 'getDefect';

	$soap_defect_mesg = 
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<defectNumber xsi:type="xsd:long">'.$defectNumber.'</defectNumber>
		<bDownloadAttachments xsi:type="xsd:boolean">true</bDownloadAttachments>
      </urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}

function dd_values($cookie , $name)
{
	$ttpro_soap_command = 'getDropdownFieldValuesForTable';

	$soap_defect_mesg = 
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<tablename xsi:type="xsd:string">Defect</tablename>
		<fieldname xsi:type="xsd:string">'.$name.'</fieldname>' .
		'</urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = self::ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}
public static function dropdowns($cookie)
{
	$ttpro_soap_command = 'getDropdownFieldForTable';

	$soap_defect_mesg = 
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<tablename xsi:type="xsd:string">Defect</tablename>' .
		'</urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = self::ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}

}

?>

