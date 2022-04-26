<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


function ttpro_post($action,$mesg)
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
	$count = 0;
	if ($sock = stream_socket_client('ssl://'.$address.':'.$port, $err_no, $err_str,30,STREAM_CLIENT_CONNECT,$context))  {
		fwrite($sock , $msg2socket);
		$rv = "";
		do {
//echo $count . " 1024B gotten<br>";
$count++;
			$rv0 = fread($sock , 1024);
			if ( $rv0 === false) {
	    		echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($sock)) . "<br><br>";
				break;
			}
			$rv = $rv . $rv0;
		} while (strlen($rv0) > 0);
		fclose($sock);
	} else {
    	echo "socket failed: $err_no - $err_str " . "<br><br>";
	}

//echo "after " . $count . "K,just want to return ttpro_post<br>";
	return $rv;
}

function database_logoff($logoff_cookie)
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
	$logoff_resp = ttpro_post($action , $logoff_mesg);
}


function database_login($database_name)
{
	$retry = 9;
	while ($retry >= 0) {
		$cookie = database_login0($database_name);
		if ($cookie > 0) {
			return $cookie;
		}
		$retry--;
	}
		echo "<pre>Unable to connect to [" . $database_name . "]</pre><br>";
		return -1;
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


	$login_resp = ttpro_post($action , $login_mesg);
	$regex="/<Cookie>(\d*)<\/Cookie>/";

	$cookie = 0;
	if (preg_match_all($regex, $login_resp, $matches_out)) {
		$cookie = $matches_out[1][0];
	} else {
		echo "<pre>$login_resp</pre><br>";
	}

//	echo "this is cookie : " . $cookie;
//	echo "<br><br>";
	return $cookie;
}

function defect_report($cookie,$defect_rpt)
{
	$ttpro_soap_command = 'getReportRunResultsByName';
	$id_type = 'name';

	$soap_defect_mesg =
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
         <cookie xsi:type="xsd:long">'.$cookie.'</cookie>
         <'.$id_type.' xsi:type="xsd:string">'.$defect_rpt.'</'.$id_type.'>
         <summary xsi:type="xsd:string"></summary>
         <bDownloadAttachments xsi:type="xsd:boolean">0</bDownloadAttachments>
      </urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';

	$soap_defect_resp = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $soap_defect_resp;
}
function project_list()
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


	$projects = ttpro_post($action , $login_mesg);
	
	return $projects;
}


function table_list($cookie)
{
	$ttpro_soap_command = 'getTableList';

	$soap_defect_mesg =
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
         <cookie xsi:type="xsd:long">'.$cookie.'</cookie>
      </urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';

	$lists= ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}

function column_list($cookie, $table_name = "Defect")
{
	$ttpro_soap_command = 'getColumnsForTable';

	$soap_defect_mesg =
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
         <cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		 <tablename xsi:type="xsd:string">'.$table_name.'</tablename> 
      </urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';

	$lists= ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}

function user_list($cookie, $table_name = "Defect")
{
	$ttpro_soap_command = 'getUser';

	$soap_defect_mesg =
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
         <cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		 <tablename xsi:type="xsd:string">'.$table_name.'</tablename> 
      </urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';

	$lists= ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}


function filter_listx($cookie)
{
	$ttpro_soap_command = 'getFilterListForTable';

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
		 

	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}


function record_list($cookie, $filter_name)
{
	$ttpro_soap_command = 'getRecordListForTable';

	$soap_defect_mesg = 
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<tablename xsi:type="xsd:string">Defect</tablename>
		<filtername xsi:type="xsd:string">'.$filter_name.'</filtername>
		<columnlist soap-enc:arraytype="urn:CTableColumn[7]" xsi:type="urn:ArrayOfCTableColumn">
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Number</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Product</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Summary</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Type</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Reference</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Status</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Date Modified</name></item>
		</columnlist>
      </urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}

function record_newfound_ae_responsible($cookie, $filter_name)
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
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}
function record_newfound_eng_query($cookie, $filter_name)
{
	$ttpro_soap_command = 'getRecordListForTableWithQuery';

	$fields0 = array('Number','Summary','Product','Type','Status','Entered by',
			'Priority','Date Entered','Disposition','Date Modified','Reference',
			'Currently Assigned To','Assigned To User','Closed By','Fixed By User'
			);
	$fields = array('Number','Summary','Product','Type','Status','Entered by',
			'Priority','Date Entered','Disposition','Date Modified','Reference',
			'Currently Assigned To','Assigned To User','Closed By'
			);

	$field_str = '<columnlist soap-enc:arraytype="urn:CTableColumn[' . count($fields) . ']" xsi:type="urn:ArrayOfCTableColumn">';
	foreach ($fields as $one_field) {
		$field_str .= '<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">' . $one_field . '</name></item>';
	}
	$field_str .= '</columnlist>';

	$query_str = '{"Date Entered":{"&lt;from&gt;":"2017-05-26","&lt;through&gt;":"2017-06-27"}}';
	//$query_str = "{'Date Entered': '2017-05-29'}";

	$soap_defect_mesg = 
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<tablename xsi:type="xsd:string">Defect</tablename>
		<filtername xsi:type="xsd:string"></filtername> 
		<query xsi:type="xsd:string">'. $query_str . '</query>' .
		$field_str . '</urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}
function record_newfound_eng($cookie, $filter_name)
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
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}
function record_getUser($cookie,$fname,$mname,$lname)
{
	$ttpro_soap_command = 'getUser';

	$soap_defect_mesg = 
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<firstName xsi:type="xsd:string">' . $fname . '</firstName>
		<middleInitials xsi:type="xsd:string">' . $mname . '</middleInitials>
		<lastName xsi:type="xsd:string">' . $lname . '</lastName>
		</urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}
function record_getDropdownFieldValuesForTable($cookie)
{
	$ttpro_soap_command = 'getDropdownFieldValuesForTable';

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
		<fieldname xsi:type="xsd:string">Entered by</fieldname>' .
		'</urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}
function record_getDropdownFieldForTable($cookie)
{
	$ttpro_soap_command = 'getDropdownFieldForTable';

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
		<tablename xsi:type="xsd:string">Defect</tablename>' .
		'</urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}
function record_newfound($cookie, $filter_name)
{
	$ttpro_soap_command = 'getRecordListForTable';

	$fields = array('Number','Summary','Product','Type','Status','Entered by',
			'Priority','Date Entered','Disposition','Date Modified','Reference',
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
</soapenv:Envelope>';
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}

function record_newfound_4ref($cookie, $filter_name)
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
		<filtername xsi:type="xsd:string">'.$filter_name.'</filtername>
		<columnlist soap-enc:arraytype="urn:CTableColumn[10]" xsi:type="urn:ArrayOfCTableColumn">
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Number</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Summary</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Product</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Reference</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Type</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Status</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Entered by</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Priority</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Date Entered</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Disposition</name></item>
		</columnlist>
      </urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}

function record_release($cookie)
{
	$ttpro_soap_command = 'getRecordListForTable';
	$filter_name = 'Release';
	
	$fields = array('Number','Summary','Product','Planned Version','Type','Status','Date Modified','Entered by','Priority','Reference',
			'Current Assignment Date', 'Fix Date', 'Release to Testing Date', 'Verify Date', 'Release to Customer Testing Date', 'Force Close Date'			);

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
		$field_str .'</urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>';
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}

function record_newmodified($cookie, $filter_name)
{
	$ttpro_soap_command = 'getRecordListForTable';
	
	$fields = array('Number','Summary','Product','Type','Status','Entered by','Priority','Date Modified','Modified By','Reference',
			'Date Entered','Closed Date','Assign Date','Disposition',
			);

//echo "In record_newmodified<br>";

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
		$field_str .'</urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>';
//echo "before ttpro_post<br>";
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
//echo "Just want to leave record_newmodified<br>";
	return $lists;
}

function record_estimate($cookie, $filter_name)
{
	$ttpro_soap_command = 'getRecordListForTable';

	$soap_defect_mesg = 
'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<tablename xsi:type="xsd:string">Defect</tablename>
		<filtername xsi:type="xsd:string">'.$filter_name.'</filtername>
		<columnlist soap-enc:arraytype="urn:CTableColumn[13]" xsi:type="urn:ArrayOfCTableColumn">
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Number</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Summary</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Product</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Type</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Status</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Entered by</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Priority</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Date Entered</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Assign Date</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Current Assignment Date</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Estimate Completion Date</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Fix Date</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Force Close Date</name></item>
		</columnlist>
      </urn:'.$ttpro_soap_command.'>
   </soapenv:Body>
</soapenv:Envelope>
';
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}

function defect_list($cookie)
{
	$ttpro_soap_command = 'getRecordListForTable';
	$filter_name = 'DefectList_all';

	$soap_defect_mesg = 

'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<tablename xsi:type="xsd:string">Defect</tablename>
		<filtername xsi:type="xsd:string">'.$filter_name.'</filtername>
		<columnlist soap-enc:arraytype="urn:CTableColumn[13]" xsi:type="urn:ArrayOfCTableColumn">
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Number</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Summary</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Product</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Type</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Status</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Entered by</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Priority</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Date Entered</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Assign Date</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Current Assignment Date</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Estimate Completion Date</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Fix Date</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Force Close Date</name></item>
		</columnlist>
      </urn:'.$ttpro_soap_command.'>
      
   </soapenv:Body>

</soapenv:Envelope>

';
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}

function getAttachment($cookie, $recordid, $archName )
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
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}
function getDefect($cookie, $defectNumber, $downloadAtta = "true")
{
	$ttpro_soap_command = 'getDefect';

	$soap_defect_mesg = 

'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>

      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<defectNumber xsi:type="xsd:long">'.$defectNumber.'</defectNumber>
		<bDownloadAttachments xsi:type="xsd:boolean"> ' . $downloadAtta .
		' </bDownloadAttachments></urn:'.$ttpro_soap_command.
	'> </soapenv:Body> </soapenv:Envelope>';
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
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

function record_query($cookie, $query)
{
	$ttpro_soap_command = 'getRecordListForTableWithQuery';
	$filter_name = '';

	$soap_defect_mesg = 

'<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:testtrack-interface">
   <soapenv:Header/>
   <soapenv:Body>

      <urn:'.$ttpro_soap_command.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
		<cookie xsi:type="xsd:long">'.$cookie.'</cookie>
		<tablename xsi:type="xsd:string">Defect</tablename>
		<filtername xsi:type="xsd:string">'.$filter_name.'</filtername>
		<query xsi:type="xsd:string">'.$query.'</query>
		<columnlist soap-enc:arraytype="urn:CTableColumn[8]" xsi:type="urn:ArrayOfCTableColumn">
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Number</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Summary</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Product</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Type</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Status</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Entered by</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Priority</name></item>
			<item xsi:type="urn:CTableColumn"><name xsi:type="xsd:string">Date Entered</name></item>
		</columnlist>
      </urn:'.$ttpro_soap_command.'>

   </soapenv:Body>

</soapenv:Envelope>

';
	$lists = ttpro_post($ttpro_soap_command,$soap_defect_mesg);
	return $lists;
}

?>

