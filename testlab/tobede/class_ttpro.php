<?php
include "class_xml2array.php";
include "class_ttprosoap.php";
class ttpro {

	private static $time_out = 3600; // 3600 seconds or 1 hour
	private static $time_tag_fname = "/main/stage/wiki/testlab/cache/timetag_newmodified.txt";
	private static $queried_fname = "/main/stage/wiki/testlab/cache/result_newmodified.txt";
	private static $log_fname = "/main/stage/wiki/testlab/user.log";
	private static $login_fname = "/main/stage/wiki/testlab/user_log/";
	private static $login_lock = "/main/stage/wiki/testlab/user_log/lock";
	private static $login_knock = "/main/stage/wiki/testlab/user_log/knock.log";
	private static $prj_name = "Software_development_1";


	// $action to be either "in" or "off"
	public static function log_user($action) {
		$log_time = time();
		$remote_addr = $_SERVER["REMOTE_ADDR"];
		$date_tag = strftime(", %b %d %Y, %X %Z\n", $log_time);
		$log_msg = "PHPLOGIN " . $log_time . " " . $remote_addr . " " . $action . $date_tag;
		if (file_exists(self::$login_knock)) {
			if (filesize(self::$login_knock) > 100000) {
				$nf_tag = strftime("_%b_%d.log", $log_time);
				$nfname = str_replace(".log" , $nf_tag , self::$login_knock);
				rename(self::$login_knock , $nfname);
			}
		}
		$abc = file_put_contents(self::$login_knock , $log_msg , FILE_APPEND);

		$lock_mtime = -1;
		if ($action == "in") {
			$try = 5;
			while (file_exists(self::$login_lock ) and ($try > 0)) {
				sleep(1);
				$try = $try -1;
			}
			if (file_exists(self::$login_lock )) {
				$lock_mtime = filemtime(self::$login_lock);
				$occupied = file_get_contents(self::$login_lock);
				$seconds = $log_time - $lock_mtime;
				$log_time = time();
				$date_tag = strftime(", %b %d %Y, %X %Z\n", $log_time);
				$log_msg = "LOCKFAIL " . $log_time . " " .
					$remote_addr . " " . $action . $date_tag;
				$abc = file_put_contents(self::$login_knock , $log_msg , FILE_APPEND);
//				exit("TTPro access is currently used by others (remote_addr: " . $occupied . ") " . $seconds . " seconds ago, please reload later<br>Or something wrong in lock file");
			} else {
				$log_time = time();
				$date_tag = strftime(", %b %d %Y, %X %Z\n", $log_time);
				$log_msg = "LOCKPASS " . $log_time . " " .
					$remote_addr . " retry_count " . (5-$try) . $date_tag;
				$abc = file_put_contents(self::$login_knock , $log_msg , FILE_APPEND);
				$abc = file_put_contents(self::$login_lock , $remote_addr , FILE_APPEND);
			}
		} else {
			if (file_exists(self::$login_lock )) {
				unlink(self::$login_lock);
			}
		}
	}
	public static function log_user_ttpro($action, $command = null) {
		if (null === $command) {
			$command = "NA";
		}
		$log_time = time();
		$remote_addr = $_SERVER["REMOTE_ADDR"];

		$remote_logf = str_replace(".","_",$remote_addr );
		$remote_logf = self::$login_fname . "r" . $remote_logf;
//		if ($_COOKIE["db_mediawikiUserName"] == "Arnoldh" ) { return;}
		if ($action == "in") {
			$date_tag = strftime("%b %d %Y, %X %Z ", $log_time);
			$log_msg = $date_tag . "UserName: [" . $_COOKIE["db_mediawikiUserName"];
			$log_msg .= "], REMOTE_ADDR: [" . $remote_addr;
			$log_msg .= "], SERVER_ADDR: [" . $_SERVER["SERVER_ADDR"];
			$log_msg .= "], SCRIPT: [" . $_SERVER["SCRIPT_NAME"] . " with " . $command . "]\n";
			if (file_exists(self::$log_fname)) {
				if (filesize(self::$log_fname) > 100000) {
					$nf_tag = strftime("_%b_%d.log", $log_time);
					$nfname = str_replace(".log" , $nf_tag , self::$log_fname);
					$seq = 0;
					while (file_exists($nfname)) {
						$seq++;
						$nf_tag = strftime("_%b_%d.log", $log_time);
						$nf_tag = str_replace(".log", "_$seq.log",$nf_tag);
						$nfname = str_replace(".log" , $nf_tag , self::$log_fname);
					}
						
					rename(self::$log_fname , $nfname);
				}
			}
			$abc = file_put_contents(self::$log_fname , $log_msg , FILE_APPEND);
		}
		$date_tag = $action . sprintf(" %d" , $log_time) . strftime(" %b %d %Y, %X %Z ", $log_time) . "\n";
		$abc = file_put_contents($remote_logf , $date_tag , FILE_APPEND);
	}

	public static function release() {
		self::log_user("in");
		
		$cookie = database_login(self::$prj_name);
		if ($cookie > 0) {
			self::log_user_ttpro("in");
		} else {
			self::log_user("oxx");
			exit("Successfully login in php but failed to login TTPro");
		}
		$rlist = record_release($cookie);
		//	echo "<br><br>Record List ====<br><br>" . $rlist . "<br><br>";
		database_logoff($cookie);
		self::log_user("off");

		$regex="/<ttns:getRecordListForTableResponse>(.*)<\/ttns:getRecordListForTableResponse>/";
		$item = "";
		if (preg_match_all($regex, $rlist, $matches_out)) {
			$item = $matches_out[1][0];
		} else {
			$item = "<Error>" . $rlist . "</Error>";
		}

		$arr = XML2Array::createArray($item);
		return $arr;
	}
	function get_software_project () {
		$plist = soap::project_list();
		$regex="/<ttns:getProjectListResponse>(.*)<\/ttns:getProjectListResponse>/";
		$item = "";
		if (preg_match_all($regex, $plist, $matches_out)) {
			$item = $matches_out[1][0];
		} else {
			$item = "<Error>Failed in getProjectList</Error>";
		}
		$arr = XML2Array::createArray($item);

		foreach ($arr['pProjList']['item'] as $prj1) {
			if (substr($prj1['database']['name'], 0, 8) == "Software") {
				return $prj1['database']['name'];
			}
		}
		return "NOPROJECT";
	}
	public static function get_columns() {
		self::log_user("in");
		$prj_name = self::get_software_project();

		$cookie = soap::database_login($prj_name);
		if ($cookie > 0) {
			self::log_user_ttpro("in");
			$rlist = soap::columns($cookie);
			soap::database_logoff($cookie);
			self::log_user("off");
			$regex="/<ttns:getColumnsForTableResponse>(.*)<\/ttns:getColumnsForTableResponse>/";
			$item = "";
			if (preg_match_all($regex, $rlist, $matches_out)) {
				$item = $matches_out[1][0];
			} else {
				$item = "<Error>" . $rlist . "</Error>";
			}
		} else {
			self::log_user("oxx");
			echo "<pre>" . soap::$msg . "</pre>";
			$item = "<Error>" . soap::$msg . "</Error>";
		}

		$arr = XML2Array::createArray($item);

		return $arr;
	}
	public static function newfound_ae_responsible($filter)	{
		self::log_user("in");
		$prj_name = self::get_software_project();

		$cookie = soap::database_login($prj_name);
		if ($cookie > 0) {
			self::log_user_ttpro("in");
			$rlist = soap::record_newfound_ae_responsible($cookie, $filter);
		//	echo "<br><br>Record List ====<br><br>" . $rlist . "<br><br>";
			soap::database_logoff($cookie);
			self::log_user("off");
			$regex="/<ttns:getRecordListForTableResponse>(.*)<\/ttns:getRecordListForTableResponse>/";
			$item = "";
			if (preg_match_all($regex, $rlist, $matches_out)) {
				$item = $matches_out[1][0];
			} else {
				$item = "<Error>" . $rlist . "</Error>";
			}
		} else {
			self::log_user("oxx");
//			echo "<pre>" . soap::$msg . "</pre>";
			$item = "<Error>" . soap::$msg . "</Error>";
		}

		$arr = XML2Array::createArray($item);

		return $arr;
	}
	public static function record_query($q_str = "")	{
		self::log_user("in");
		$prj_name = self::get_software_project();
		$cookie = soap::database_login($prj_name);
		$fields = array('Number','Summary','Product','Type','Status',
			'Entered by','Priority','Date Entered','Disposition',
			'Date Modified','Reference', 'Currently Assigned To',
			'AE Responsible', 'Assigned To User','Closed By','Fixed By User',
			'Date Created','In Development Date','Fix Date','Closed Date',
			'Assign Date'
            );
		$query_str = "{'Date Entered':{'&lt;from&gt;':'2018-01-01','&lt;through&gt;':'2018-02-14'}}";
		$query_str = "{'Number':{'&lt;numbers&gt;':[30001,30002]}}";
		$query_str = "{'&lt;and&gt;': [{'Product':'Expert'},{'Date Entered':{'&lt;from&gt;':'2017-05-26','&lt;through&gt;':'2017-06-27'}}]}";
		$filter = "";
		if ($q_str != "") {
			$query_str = $q_str;
		}

		if ($cookie > 0) {
			self::log_user_ttpro("in");
			$rlist = soap::record_with_query($cookie,
				$filter, $query_str, $fields);
			soap::database_logoff($cookie);
			self::log_user("off");
			$regex="/<ttns:getRecordListForTableWithQueryResponse>(.*)<\/ttns:getRecordListForTableWithQueryResponse>/";
			$item = "";
			if (preg_match_all($regex, $rlist, $matches_out)) {
				$item = $matches_out[1][0];
			} else {
echo "$rlist";
				$item = "<Error>" . $rlist . "</Error>";
			}
		} else {
			self::log_user("oxx");
			$item = "<Error>" . soap::$msg . "</Error>";
		}
	
		$arr = XML2Array::createArray($item);

		return $arr;
	}
	public static function get_dropdowns()	{
		self::log_user("in");
		$prj_name = self::get_software_project();

		$cookie = soap::database_login($prj_name);
		if ($cookie > 0) {
			self::log_user_ttpro("in");
		} else {
			self::log_user("oxx");
			exit("Successfully login in php but failed to login TTPro");
		}
		$rlist = soap::dropdowns($cookie );
		$regex="/<ttns:getDropdownFieldForTableResponse>(.*)<\/ttns:getDropdownFieldForTableResponse>/";
		$item = "";
		if (preg_match_all($regex, $rlist, $matches_out)) {
			$item = $matches_out[1][0];
		} else {
			echo "<br>" . $rlist . "<br>";
		}

		$arr = XML2Array::createArray($item);

		$concerned = array("Disposition", "Severity", "Platform",
					"Reproducible" , "Priority", "Type" , "Product" );
		$re_arr = array();

		foreach ($arr['pFieldList']['item'] as $scan_pos => $narr) {
			$field_name = $narr['name'];
		  if (in_array($field_name , $concerned)) {
			$rlist = soap::dd_values($cookie  , $field_name);
	
			$regex="/<ttns:getDropdownFieldValuesForTableResponse>(.*)<\/ttns:getDropdownFieldValuesForTableResponse>/";
			$item = "";
			if (preg_match_all($regex, $rlist, $matches_out)) {
				$item = $matches_out[1][0];
			} else {
				echo "<br>" . $rlist . "<br>";
			}

			$arr2 = XML2Array::createArray($item);
//			$arr['pFieldList']['item'][$scan_pos]["pValueList"] =
//				$arr2["pValueList"];
			array_push($re_arr, 
						array("name" => $field_name,
						"pValueList" => $arr2["pValueList"]["item"]));
		  }
		}

		soap::database_logoff($cookie);
		self::log_user("off");
		return $re_arr;
	}
	public static function newfound_eng($filter)	{
		self::log_user("in");
		$prj_name = self::get_software_project();

		$cookie = soap::database_login($prj_name);
		if ($cookie > 0) {
			self::log_user_ttpro("in" , $filter);
		} else {
			self::log_user("oxx");
			exit("Successfully login in php but failed to login TTPro");
		}
		//$rlist = record_newfound_4ref($cookie, $filter);
		$rlist = soap::record_newfound_eng($cookie, $filter);
		//	echo "<br><br>Record List ====<br><br>" . $rlist . "<br><br>";
		soap::database_logoff($cookie);
		
		self::log_user("off");
	
		$regex="/<ttns:getRecordListForTableResponse>(.*)<\/ttns:getRecordListForTableResponse>/";
		$item = "";
		if (preg_match_all($regex, $rlist, $matches_out)) {
			$item = $matches_out[1][0];
		} else {
			echo "<br>" . $rlist . "<br>";
		}


		$arr = XML2Array::createArray($item);

		return $arr;
	}
	public static function query_ttpro($filter)	{
		$cookie = database_login(self::$prj_name);
		//	$clist = column_list($cookie);
		//	echo "<br><br>Column List ====<br><br>" . $clist . "<br><br>";
		//	$rlist = record_query($cookie, '{"Date Modified":"8/14/2015 at 6:31 AM"}');
		$rlist = record_newmodified($cookie, $filter);
		//	echo "<br><br>Record List ====<br><br>" . $rlist . "<br><br>";
	
		$regex="/<ttns:getRecordListForTableResponse>(.*)<\/ttns:getRecordListForTableResponse>/";
		$item = "";
		if (preg_match_all($regex, $rlist, $matches_out)) {
			$item = $matches_out[1][0];
		} else {
			echo "<br>" . $rlist . "<br>";
		}


		$arr = XML2Array::createArray($item);

		return $arr;

// 2016/12/19 arnold
// Following code is redundant to get details of each ticket.
// Then save to a local file(to web server, but can save time link to database server next time)
// However, the connect from web client is highly possible to be disconnected

		$number_pos = -1;
		foreach ($arr['recordlist']['columnlist']['item'] as $scan_pos => $varr) {
			if ($varr['name']  == 'Number') {
				$number_pos = $scan_pos;
				break;
			}
		}

		$count = 0;
		$de_arr = array();
		$reported_fields = array( 'foundby' , 'datefound' );
		$event_fields = array( 'user' , 'date' , 'eventaddorder' , 'name' , 'resultingstate' , 'assigntolist' );
		if ($number_pos >= 0) {
			$partial_arr = $arr['recordlist']['records']['item'];
			foreach ($partial_arr as $row_arr) {
				$defect_no = $row_arr['row']['item'][$number_pos]['value'];
	
				$rlist = getDefect($cookie, $defect_no);
	
				$regex="/<ttns:getDefectResponse>(.*)<\/ttns:getDefectResponse>/";
				$item = "";
				$message_str = "";
				if (preg_match_all($regex, $rlist, $matches_out)) {
					$item = $matches_out[1][0];
					$defect_arr = XML2Array::createArray($item);
					$arr1 = array();
					$arr2 = array();
					foreach ( $reported_fields as $field ) {
						$arr1[$field] = $defect_arr['pDefect']['reportedbylist']['item'][$field];
					}
					foreach ( $event_fields as $field) {
						$arr2[$field] = $defect_arr['pDefect']['eventlist']['item'][$field];
					}
					$tmp_arr = array('defectnumber' => $defect_arr['pDefect']['defectnumber'] , 
							'reportedbylist' => $defect_arr['pDefect']['reportedbylist'], 
							'eventlist' => $defect_arr['pDefect']['eventlist'] );
					$de_arr[$count] = $tmp_arr;
					$count++;
				} else {
					$regex="/<faultstring>(.*)<\/faultstring>/";
					if (preg_match_all($regex, $rlist, $matches_out)) {
						$item = $matches_out[1][0];
						echo "<br>" . "<u>The maximum ticket number might be reached." . "</u><br>";
						if (strcmp("The specified record does not exist in the database." , $item) == 0) {
							$message_str = "Following is the error message from TTPro Server:";
						} else {
							$message_str = "Following is the error message from TTPro Server. Please copy and send to arnold.ho@silvaco.com";
						}
						echo "<br>" . $message_str . "<br>";
						echo "<br>" . $item . "<br>";
					} else {
						echo "<br>" . "Copy the string and send to arnold.ho@silvaco.com" . "<br>";
						echo "<br>" . $rlist . "<br>";
					}
				}
			}
		}
		database_logoff($cookie);
		$arr['detail'] = $de_arr;
		self::save_file($arr);
	
		return $arr;
	}
}
?>
