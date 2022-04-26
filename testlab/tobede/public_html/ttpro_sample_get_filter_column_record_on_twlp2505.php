<!DOCTYPE html>
<html>
<body>

<?php



// Create client connection
$address = '10.1.4.40';  // for 'issues';
$port = 80;
$client = new SoapClient('http://10.1.4.40/ttsoapcgi.wsdl', array('location' => "http://10.1.4.40/cgi-bin/ttsoapcgi.exe", 'uri' => "urn:testtrack-interface/") );
$client->soap_defencoding = 'UTF-8';

$sessionId = 0;
try
{
   // Better to fetch then find the CProject than trying to create one.
   $strSelectPrj = 'Software Development Issues';
   $prj = null;

   	$aprj = $client->getProjectList("ttpro", "ap4ttpro!");
   for ($i = 0; $i < count($aprj); ++$i)
   {
      if ($aprj[$i]->database->name == $strSelectPrj) { $prj = $aprj[$i]; break; }
   }

   // Login
   $sessionId = $client->ProjectLogon($prj, "ttpro", "ap4ttpro!");

	echo "<br><br> This is session id:" . $sessionId . "<br><br>\n";

	$areports = $client->getFilterListForTable($sessionId, "Defect");

	echo "<br>" . $areports[0]->name . "<br>";
	for ($i = 0; $i < count($areports); ++$i)	{
    //this is a report row
		$report = $areports[$i];

   // this is a piece of data about that report.
		$val = $report->name; // "Contains" column

		echo "<br>" . $val . "<br>\n";
	}	

	$creports = $client->getColumnsForTable($sessionId, "Defect");

	echo "<br>" . $areports[0]->name . "<br>";
	for ($i = 0; $i < count($creports); ++$i)	{
    //this is a report row
		$report = $creports[$i];

   // this is a piece of data about that report.
		$val = $report->name; // "Contains" column

		echo "<br>" . $val . "<br>\n";
	}	


	$col_req = array(
		array("name" => "Number"),
		array("name" => "Product"),
		array("name" => "Summary"),
		array("name" => "Type"),
		array("name" => "Reference"),
		array("name" => "Status"),
		array("name" => "Date Modified")
		);
?>
<table><tr>

<?php	for ($i = 0; $i < count($col_req); ++$i) { ?>
<td> <?php 	 echo $col_req[$i]["name"]; ?></td>
<?php 	} ?>
</tr>


<?php
	$breports = $client->getRecordListForTable($sessionId, "Defect", "phpTCAD", $col_req);
	for ($i = 0; $i < count($breports->records); ++$i) {
   // this is a report row
?>
<tr>
<?php
		$report = $breports->records[$i];
		//print_r(array_keys($report->row));
   // this is a piece of data about that report.
		
		for ( $j = 0 ; $j < count($report->row) ; ++$j) {
			$rowj = $report->row[$j];
?>
<td><?php       if (isset($rowj->value)) echo $rowj->value; ?></td>
<?php 	} ?>
</tr>
<?php 	} 

}
catch (Exception $e) {
	echo "<br><br>ERRRRRRRRRRRRRR<br><br>\n";
	echo "<br>" . $e->getMessage() . "<br>\n";
} // do something with the exception!

// When you're finished, log off.
if ($sessionId != 0) { $client->DatabaseLogoff($sessionId); }





?>

</body>
</html> 
