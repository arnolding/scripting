<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$servername = "10.129.9.30";
$servername = "localhost";
$servername = "cadb01";
$username = "arnoldho";
$password = "KDas72@!w2";
$dbname = "qadb";
$dbconnect = "mysql:host=".$servername.";port=3306;dbname=".$dbname."";


// Create connection
$pdo = new PDO($dbconnect, $username, $password);

$sql = "select * from test_idx ";

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
