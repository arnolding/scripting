<?php

$krumo_enabled = $_GET['krumo'];

//$defectNumber = $_POST['defectNumber'];

$inc_path = get_include_path();
$new_inc_path = '..:' . $inc_path;
set_include_path($new_inc_path);
include "krumo/class.krumo.php";
include "class_xml2array.php";
include "ttprosoap.php";


	$cookie = database_login('Software_development');
	$rlist = record_getDropdownFieldValuesForTable($cookie);
//	echo "<br><br>Record List ====<br><br>" . $rlist . "<br><br>";

	$regex="/<ttns:getDropdownFieldValuesForTableResponse>(.*)<\/ttns:getDropdownFieldValuesForTableResponse>/";
	$item = "";
	$message_str = "";
	if (preg_match_all($regex, $rlist, $matches_out)) {
		$item = $matches_out[1][0];
		$arr = XML2Array::createArray($item);

		$users = array();
		foreach ($arr["pValueList"]["item"] as $arr_item) {
			$ent_name = $arr_item["value"];
			$n3 = split(",", $ent_name);
			if ($n3[2]) {
echo "***********" . $ent_name . "******************";
				$lastname = $n3[0];
				$firstname = $n3[1];
				$middlename = $n3[2];
			} else {
				if ($n3[1]) {
					$lastname = $n3[0];
					$firstname = trim($n3[1]);
					$middlename = "";
				} else {
					$lastname = "";
					$middlename = "";
					$firstname = $n3[0];
				}
			}
			
			$tlist = record_getUser($cookie,$firstname,$middlename,$lastname);
			$regex="/<ttns:getUserResponse>(.*)<\/ttns:getUserResponse>/";
			$item = "";
			$message_str = "";
			if (preg_match_all($regex, $tlist, $matches_out)) {
				$item = $matches_out[1][0];
				$brr = XML2Array::createArray($item);
				array_push($users , $brr);
			} 
		}

		$arr["users"] = $users;
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



	
	database_logoff($cookie);

	if ($krumo_enabled) {krumo($arr);}
	echo json_encode($arr);
//	echo "<hr>\n";
//	print_r($arr);

?>
