<?php

$check_date = $_GET['test_id'];
if ($check_date != '') {
	$sql = "select * from check_alpha where test_id='" . $check_date . "' ";
} else {
	$sql = "select * from busdev";
}
$pdo = new PDO("sqlite:../busdev.db");

try {
	$pdoStatement = $pdo->prepare($sql);
	$pdoStatement->execute();
} catch (PDOException $e) {
	echo $e->getMessage();
}

$all_rec = array();

while ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC) ) {
	array_push($all_rec , $row);
}


echo(json_encode( $all_rec));
?>   
