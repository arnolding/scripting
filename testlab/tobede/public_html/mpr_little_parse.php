<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
include "../krumo/class.krumo.php";
if (array_key_exists('krumo' , $_GET)) {
    $krumo_enabled = $_GET['krumo'];
} else {
    $krumo_enabled = 0;
}
$builds = file_get_contents("/home/mpr/logs/master-build.log");
$lines = explode("\n" , $builds);
$build_struc = array();
$fname_ext = array();
$prod_array = array();
foreach ($lines as $line1) {
	$segs = explode(" " , $line1);
	$segs_num = count($segs);
	$empty_ele = array();
	for ($i = 0; $i < $segs_num ; $i++)  {
		if ($segs[$i] == "") {
//			array_splice($segs , $i , 1);
			array_push($empty_ele , $i);
		}
	}
	foreach (array_reverse($empty_ele) as $idx) {
		array_splice($segs , $idx , 1);
	}
	$segs_num = count($segs);
if (($segs_num <7) or ($segs_num) > 8) {
//	echo "strange number " . $segs_num . "[" . $line1 . "]<br>";
	continue;
}
//	$fn_parse = is_release($segs[0]);
	for ($i = 0; $i < $segs_num ; $i++)  {
	}

//	foreach ($fn_parse as $k => $val) {
//		if ($k == "ext") {
//			array_push($fname_ext , $val);
//		}
//		if ($k == "prod" ) {
//			array_push($prod_array , $val);
//		}
//	}
	array_push($build_struc , $segs);
}
if ($krumo_enabled) {krumo($build_struc);}
echo json_encode($build_struc);

//foreach (array_unique($fname_ext) as $ext1) {
//	echo "ext [" . $ext1 . "]<br>";
//}
//foreach (array_unique($prod_array) as $prod1) {
//	echo "prod [" . $prod1 . "]<br>";
//}
//	krumo($arr);
//	echo "<hr>\n";
//	print_r($arr);

//echo "<br>";

function is_release($fname)
{
	$parse_ret = array();
	$fn = explode("." , $fname);
	if (count($fn) != 2) {
		echo "filename should have extension [" . $fname . "]<br>";
	} else {
		$parse_ret["ext"] = $fn[1];
	}

	$prod_segs = explode("-" , $fn[0]);
	$seg_num = count($prod_segs);
	if ($seg_num == 7) {
		$parse_ret["id"] = $prod_segs[0];
		$parse_ret["prod"] = $prod_segs[1];
		$parse_ret["ver"] = $prod_segs[2] . "-" . $prod_segs[3] . "-" . $prod_segs[4] . "-" . $prod_segs[5];
		$parse_ret["platform"] = $prod_segs[6];
	} elseif ($seg_num == 5) {
		$parse_ret["id"] = $prod_segs[0];
		$parse_ret["prod"] = $prod_segs[1];
		$parse_ret["ver"] = $prod_segs[2] . "-" . $prod_segs[3];
		$parse_ret["platform"] = $prod_segs[4];
	} else {
		echo "filename should have id, then prod, then version, then platform [" . $fn[0] . "]<br>";
	}

	return $parse_ret;
}

?>
