<!DOCTYPE html>
<html>

<?php

$check_date = $_GET['test_id'];
if ($check_date != '') {
	$sql = "select * from test_detail_v where test_id='" . $check_date . "' ";
} else {
//	$sql = "select count(*) as num_of_obj,check_date,platform,version from daily_build ";
//	$sql .= "where check_date > '2015-12-19 00:00:00' group by check_date,platform,version";
	$sql = "select * from test_idx";
}

echo "[" . $sql . "]<br>";
$pdo = new PDO("sqlite:../qadb.db");

try {
	$pdoStatement = $pdo->prepare($sql);
	$pdoStatement->execute();
} catch (PDOException $e) {
	echo $e->getMessage();
}

$all_rec = array();

while ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC) ) {
	array_push($all_rec , $row);
	echo "[" . $row . "]<br>";
}
echo "oab<br>";
?>   
<head>
</head>
<body >
<br><br><div id='calendar'></div><br>
Platform: <select id='platform_sel'></select> Version: <select id='version_sel'></select> <button id='pv_go' value='Go'>Show Objects</button><br>
<div id='flat_table' ></div><br><br>
<div id='table0' ></div><br><br>


</body>
</html> 
