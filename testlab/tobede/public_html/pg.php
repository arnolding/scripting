<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "111";
$pdo = new PDO("pgsql:host=tweda01;port=5432;dbname=qadb;user=arnoldh;password=Flying-chef058&");
echo "YES";
$pdo->setAttribute(PDO::ATTR_TIMEOUT , 10);

try {
	$pdoStatement = $pdo->prepare($sql);
	$pdoStatement->execute();
} catch (PDOException $e) {
	echo $e->getMessage();
}
?>
