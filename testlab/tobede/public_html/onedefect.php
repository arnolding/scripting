<!DOCTYPE html>
<html>
<head>
<?php
$defectNumber = $_GET['defectNumber'];
echo "Use http://svcwiki/lib/testlab/public_html/onedefect.html?defectNumber=" . $defectNumber;
exit;
?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <title>Defect ID - <?php echo $defectNumber ?> </title>
    <meta name="author" content="Arnold Ho">
<STYLE>  
		 		.ReportTestTrackTextClass 	{
					FONT-FAMILY: Arial, sans-serif;
					FONT-SIZE: 30px;
					COLOR: #000000;
					FONT-WEIGHT:bold;
				}
	      </STYLE>
<STYLE>  
		 		.ReportTitleClass	{
					FONT-FAMILY: Arial, sans-serif;
					FONT-SIZE: 30px;
					COLOR: #1040f0;
					background: #FCEBA9;
				}
	      </STYLE>
<STYLE>
        .ReportPeriodClass {
          font-family: Arial, sans-serif;
          font-size: 18px;
          COLOR: #000000;
        }
        </STYLE>
<STYLE>  
		 		.ColumnHeaderClass	{
					font-family: Arial, sans-serif;
					font-weight: bold;
					font-size: 14.5px;
					COLOR: #ffffff;
				}
	      </STYLE>
<STYLE>  
		 		.RowHeaderClass	{
					font-family: Arial, sans-serif;
					font-size: 12px;
					COLOR: #000000;
				}
	      </STYLE>
<STYLE>  
		 		.DataCellClass	{
					font-family: Arial, sans-serif;
					font-size: 12px;
					COLOR: #000000;
				}
	      </STYLE>
<STYLE>  
		 		.SubtotalCellClass	{
					font-family: Arial, sans-serif;
					font-size: 12px;
					COLOR: #000000;
				}
	      </STYLE>
<STYLE>  
		 		.SubtotalCellClass	{
					font-family: Arial, sans-serif;
					font-size: 12px;
					COLOR: #000000;
				}
	      </STYLE>
<style>  
         .ColumnTotalsClass	{
            font-family: Arial, sans-serif;
            font-size: 12px;
            background-color: #e7eef7;
         }
      </style>
<STYLE>
      .ReportDetailRowTitleClass
      {
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #000000;
      font-weight: bold;
      text-align: left;
      background-color: #ffffff;
      }
   </STYLE>
<STYLE>
      .ReportDetailRowDataClass
      {
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #000000;
      font-weight: normal;
      text-align: left;
      background-color: #ffffff;
      }
   </STYLE>
<STYLE>
      .ReportTestTrackTextClass
      {
         font-family: Arial, sans-serif;
         font-size: 30px;
         color: #000000;
         font-weight: bold;
      }
   </STYLE>
<STYLE>
      .ReportTitleClass
      {
      font-family: Arial, sans-serif;
      font-size: 30px;
      color: #7F7E84;
      font-weight: normal;
      }
   </STYLE>
<STYLE>
      .BrowseTicketClass
      {
      font-family: Arial, sans-serif;
      font-size: 14.5px;
      color: #ffffff;
      font-weight: bold;
      text-align: right;
      background-color: #6E99D4;
      }
   </STYLE>
<STYLE>
      .ReportHeaderRowClass
      {
      font-family: Arial, sans-serif;
      font-size: 14.5px;
      color: #ffffff;
      font-weight: bold;
      text-align: left;
      background-color: #6E99D4;
      }
   </STYLE>
<STYLE>
      .ReportCoverageSubHeaderClass
      {
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #000000;
      font-weight: bold;
      text-align: left;
      background-color: #c6d6ec;
      }
   </STYLE>
<STYLE>
      .ReportDetailRowTitleClass
      {
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #000000;
      font-weight: bold;
      text-align: left;
      background-color: #ffffff;
      }
   </STYLE>
<STYLE>
      .ReportDetailRowTitleColoredClass
      {
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #000000;
      font-weight: bold;
      text-align: left;
      background-color: #e7eef7;
      }
   </STYLE>
<STYLE>
      .ReportDetailRowHeaderClass
      {
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #000000;
      font-weight: bold;
      text-align: left;
      background-color: #d8d7d7;
      }
   </STYLE>
<STYLE>
      .ReportEvenRowClass
      {
      font-family: Arial, sans-serif;
      font-size: 12px;
      background-color: #ecebeb
      }
   </STYLE>
<STYLE>
      .ReportOddRowClass
      {
      font-family: Arial, sans-serif;
      font-size: 12px;
      background-color: #ffffc0
      }
   </STYLE>
<STYLE>
      .ReportDetailRowDataClass
      {
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #000000;
      font-weight: normal;
      text-align: left;
      background-color: #ffffff;
      }
   </STYLE>
<STYLE>
      .FolderHeaderRowClass
      {
      font-family: Arial, sans-serif;
      font-size: 14.5px;
      color: #ffffff;
      font-weight: bold;
      text-align: left;
      background-color: #6E99D4;
      }
   </STYLE>
<STYLE>
      .FolderHeaderRowDataClass
      {
      font-family: Arial, sans-serif;
      font-size: 14.5px;
      color: #ffffff;
      text-align: left;
      font-weight:normal;
      background-color: #6E99D4;
      }
   </STYLE>
<STYLE>
      .ReportErrorClass
      {
      font-family: Arial, sans-serif;
      font-size: 14.5px;
      color: #000000;
      font-weight: bold;
      text-align: left;
      }
   </STYLE>
<STYLE>
      p, li
      {
      margin: 0px;
      }
   </STYLE>
<STYLE>
      a:link
      {
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #0204fc;
      text-decoration: underline;
      }
   </STYLE>
<STYLE>
      .ReportTableCell
      {
      border-style: solid;
      border-collapse:collapse;
      border-width: 1px;
      border-color: #d2dff2;
      padding: 2px;
      spacing: 1px;
      font-weight: normal;
      }
   </STYLE>
</head>
<body>


<?php
$inc_path = get_include_path();
$new_inc_path = '..:' . $inc_path;
set_include_path($new_inc_path);
include "krumo/class.krumo.php";
include "class_xml2array.php";
include "ttprosoap.php";

function array2table( $arr )
{
	while (list($key, $val) = each($arr)) {
		
		if (strcmp($key , "recordlist") == 0) {
			echo "<table>";
			echo "<tr>";
			foreach ($val['columnlist']['item'] as $header) {
				echo "<td>" . $header['name'] . "</td>";
			}
			echo "</tr>";
			
			foreach ($val['records']['item'] as $row) {
				echo "<tr>";
				foreach ($row['row']['item'] as $cell_val) {
					if (is_array( $cell_val)) {
						echo "<td>" . $cell_val['value'] . "</td>";
					} else {
						echo "<td></td>";
					}
				}
				echo "</tr>";
			}

			echo "</table>";
		} 
	}
}


	$cookie = database_login('Software_development');
	$rlist = getDefect($cookie, $defectNumber);
//	echo "<br><br>Record List ====<br><br>" . $rlist . "<br><br>";
	database_logoff($cookie);

	$regex="/<ttns:getDefectResponse>(.*)<\/ttns:getDefectResponse>/";
	$item = "";
	$message_str = "";
	if (preg_match_all($regex, $rlist, $matches_out)) {
		$item = $matches_out[1][0];
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


	$arr = XML2Array::createArray($item);

	

	if ($krumo_enabled) {krumo($arr);}
//	echo "<hr>\n";
	//print_r($arr);
//	array2table($arr);

	$expectation_date = "";
	$planned_version = "";
	
	if (array_key_exists('customFieldList' , $arr[pDefect])) {
		$cfl = $arr[pDefect][customFieldList][item];
		foreach (array_reverse($cfl) as $cfl1) {
			if ($cfl1[name] == 'Planned Version') {
				$planned_version = $cfl1[value];
			}
			if ($cfl1[name] == 'Expectation Date') {
				$expectation_date = $cfl1[value];
			}
		}
	}



?>

<table width="100%" border="0" cellpadding="3" cellspacing="0" > <!---class="ReportDetailRowHeaderClass">--->
<tr>
<td align="left" valign="top" class="ReportHeaderRowClass" ><?php echo $arr[pDefect][defectnumber]; ?></td>
<td class="ReportHeaderRowClass"><span style="font-weight: normal;"><?php echo $arr[pDefect][summary]; ?></span></td>
<td class="BrowseTicketClass">Goto: <a href='onedefect.php?defectNumber=<?php echo $defectNumber-1; ?>'><?php echo $defectNumber-1; ?></a>&nbsp;<a href='onedefect.php?defectNumber=<?php echo $defectNumber+1; ?>'><?php echo $defectNumber+1; ?></a>
</td></tr>
</table>

<br>
<table width="100%" border="0" cellpadding="3" cellspacing="0" >
<tr>
<td width="33%" align="left" valign="top" class="ReportDetailRowTitleClass">Product: 
<span class="ReportDetailRowDataClass"><?php echo $arr[pDefect][product]; ?></span></td>

<td width="33%" align="left" valign="top" class="ReportDetailRowTitleClass">Date Entered: 
<span class="ReportDetailRowDataClass"><?php echo $arr[pDefect][dateentered]; ?></span></td>

<td width="33%" align="left" valign="top" class="ReportDetailRowTitleClass">Entered by: 
<span class="ReportDetailRowDataClass"><?php echo $arr[pDefect][enteredby]; ?></span></td>
</tr>

<tr>
<td width="33%" align="left" valign="top" class="ReportDetailRowTitleClass">Status: 
<span class="ReportDetailRowDataClass"><?php echo $arr[pDefect][state]; ?></span></td>

<td width="33%" align="left" valign="top" class="ReportDetailRowTitleClass">Severity: 
<span class="ReportDetailRowDataClass"><?php echo $arr[pDefect][severity]; ?></span></td>

<td width="33%" align="left" valign="top" class="ReportDetailRowTitleClass">Priority: 
<span <?php if ($arr[pDefect][priority] == 'Urgent' or $arr[pDefect][priority] == 'High') echo "style='background-color:lightgrey;color:red'";?>class="ReportDetailRowDataClass"><?php echo $arr[pDefect][priority]; ?></span></td>
</tr>

<tr>
<td width="33%" align="left" valign="top" class="ReportDetailRowTitleClass">Type: 
<span class="ReportDetailRowDataClass"><?php echo $arr[pDefect][type]; ?></span></td>

<td width="33%" align="left" valign="top" class="ReportDetailRowTitleClass">Reference: 
<span class="ReportDetailRowDataClass"><?php echo $arr[pDefect][reference]; ?></span></td>


<td width="33%" align="left" valign="top" class="ReportDetailRowTitleClass">Platform: 
<span class="ReportDetailRowDataClass"><?php echo $arr[pDefect][component]; ?></span></td>
</tr>

<tr>

<td class="ReportDetailRowTitleClass" valign="top" width="33%" align="left">Disposition: <span class="ReportDetailRowDataClass"><?php echo $arr[pDefect][disposition]; ?></span></td>

<td width="33%" align="left" valign="top" class="ReportDetailRowTitleClass">Date Modified: 
<span class="ReportDetailRowDataClass"><?php echo substr($arr[pDefect][datetimemodified],0,10); ?></span></td>

<td width="33%" align="left" valign="top" class="ReportDetailRowTitleClass">Modified By: 
<span class="ReportDetailRowDataClass"><?php echo $arr[pDefect][modifiedbyuser]; ?></span></td>

</tr>
<tr>

<td class="ReportDetailRowTitleClass" valign="top" width="33%" align="left">Expectation Date: <span class="ReportDetailRowDataClass"><?php echo $expectation_date; ?></span></td>

<td width="33%" align="left" valign="top" class="ReportDetailRowTitleClass">&nbsp;</td>

<td width="33%" align="left" valign="top" class="ReportDetailRowTitleClass">&nbsp;</td>

</tr>
<tr>

<td class="ReportDetailRowTitleClass" valign="top" width="33%" align="left">Planned Version: <span class="ReportDetailRowDataClass"><?php echo $planned_version; ?></span></td>

<td width="33%" align="left" valign="top" class="ReportDetailRowTitleClass">&nbsp;</td>

<td width="33%" align="left" valign="top" class="ReportDetailRowTitleClass">&nbsp;</td>

</tr>
</table>

<?php
function description_begin()
{
    echo "<hr size='1' width='100%' color='#b6b6b6' align='center'><table>";
}
function description_end()
{
    echo "</table>";
}
function event_list($eve1)
{
		static $even = 1;
		$arr_dt = date_parse($eve1[date]);
		$e_dt = $arr_dt[month] . '/' . $arr_dt[day] . '/' . $arr_dt[year] . ' ' . $arr_dt[hour] . ':' . $arr_dt[minute];
		if ($even == 1) {
			echo "<tr class='ReportEvenRowClass'>";
			$even = 0;
		} else {
			echo "<tr class='ReportOddRowClass'>";
			$even = 1;
		}
		echo "<td>[" . $eve1[name] . "]: </td>";
		echo "<td>by " . $eve1[user] . "</td>";
		echo "<td> on " . $e_dt . "</td>";
		if ( $eve1[name] == 'Assign') {
			echo "<td> to: " . $eve1[assigntolist][item] . "</td>";
		}
		if ( $eve1[name] == 'Verify') {
			echo "<td> " . $eve1[resultingstate] . "</td>";
		}
		
		echo "</tr>";
		if ( $eve1[name] == 'Estimate') {
			echo "<tr><td> Estimate Completion Date: <font color='red'>" . $eve1[fieldlist][item][1][value] . "</font></td></tr>";
		}
		if ( array_key_exists('notes' , $eve1)) {
			echo "<tr><td><span class='ReportDetailRowDataClass'></span></td><td colspan='3'><span class='ReportDetailRowDataClass'>" . nl2br($eve1[notes]) . "</span></td></tr>";
		}
}
function reportedbylist($rep1)
{
	$foundby = $rep1[foundby];
	$datefound = $rep1[datefound];
	$foundinversion = $rep1[foundinversion];
	$comments = $rep1[comments];
	description_begin();
	echo "<tr><td><span class='ReportDetailRowTitleClass'>" . $foundby . " <u>on</u> " . $datefound . " <u>ver:</u> " . $foundinversion . "</span></td></tr>";
	echo "<tr><td><span class='ReportDetailRowDataClass'>" . nl2br($comments) . "</span></td></tr>";
	description_end();
}

	description_begin();
	if (array_key_exists('eventlist' , $arr[pDefect])) {

		$eve = $arr[pDefect][eventlist][item];
		if ( array_key_exists('name' , $eve)) {
			event_list($eve);
		} else {
			foreach (array_reverse($eve) as $eve1) {
				event_list($eve1);
			}
		}
	} else {
		echo "<tr style='background-color:lightgrey;color:red' class='ReportOddRowClass'><td>not assigned</td></tr>";
	}
	description_end();
	
	if (array_key_exists('reportedbylist' , $arr[pDefect])) {
		$rep = $arr[pDefect][reportedbylist][item];	
		if ( array_key_exists('recordid' , $rep)) {
			reportedbylist($rep);
		} else {
			foreach (array_reverse($rep) as $rep1) {
				reportedbylist($rep1);
			}
		}
	}
?>

</body>
</html> 
