<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$test_id = "";
if (isset($_GET['test_id'])) {
	$test_id = $_GET['test_id'];
} elseif (isset($_POST['test_id'])) {
	$test_id = $_POST['test_id'];
}
$servername = "10.129.9.30";
$servername = "localhost";
$servername = "cadb01";
$username = "arnoldho";
$password = "KDas72@!w2";
$dbname = "qadb";
$dbconnect = "mysql:host=".$servername.";port=3306;dbname=".$dbname."";


// Create connection
$pdo = new PDO($dbconnect, $username, $password);

$sql = "select test_id, cast(test_date as Date) as TestDate, Prod, Ver, test_idx.Platform, hostname as TestMachine from test_idx inner join machine on test_idx.machine_id = machine.machine_id";

if ($test_id == "") {
	$sql = "select * from test_sum1";
} else {
	$sql = "select casename, fname as simulator, memory_MB, time as CPUtime, elapse_time as Elapsetime from test_ps where test_id='" . $test_id . "' group by casename, fname order by memory_MB";
}

$pdoStatement = $pdo->prepare($sql);
if (!$pdoStatement) {
    echo "\nPDO::errorInfo():\n";
    print_r($pdo->errorInfo());
    exit;
}
$pdoStatement->execute();

$all_rec = array();
$result = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
//print_r($result);
//while ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC) ) {
//	array_push($all_rec , $row);
//	echo "[" . $row . "]<br>";
//}
//echo "oab<br>";
$first_row = 1;
foreach ($result as $row) {
	if ($first_row == 1) {
		array_push($all_rec , array_keys($row));
		$first_row = 0;
	}
	array_push($all_rec , array_values($row));
}
//echo json_encode($result);
echo json_encode($all_rec);
?>   
