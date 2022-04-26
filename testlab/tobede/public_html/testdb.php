<?
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "10.129.9.30";
//$servername = "localhost";
$username = "arnoldho";
$password = "123456";
$dbname = "testdb";
$dbconnect = "mysql:host=".$servername.";port=3306;dbname=".$dbname."";


// Create connection
$conn = new PDO($dbconnect, $username, $password);


//
$sql = "SELECT * FROM Persons";
$result = $conn->query($sql);
//
//if ($result->num_rows > 0) {
    // output data of each row
//  while($row = $result->fetch_assoc()) {
//     echo "id: " . $row["PID"]. " - Name: " . $row["FirstName"]. " " . $row["City"]. "<br>";
//  }
//} else {
//              echo "0 results";
//}
//               $conn->close();

?>
