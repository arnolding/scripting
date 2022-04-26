<!DOCTYPE html>
<html>
<head>
<?php
$defectNumber = $_GET['defectNumber'];
$defectlist = $_GET['defects'];
$krumo_enabled = $_GET['krumo'];
$date_tag = strftime("%b %d %Y, %X %Z ", time());
$log_msg = $date_tag . "UserName: [" . $_COOKIE["db_mediawikiUserName"] . "], REMOTE_ADDR: [" . $_SERVER["REMOTE_ADDR"] . "], SCRIPT: [" . $_SERVER["SCRIPT_NAME"] . "]\n";
file_put_contents("/main/stage/wiki/testlab/user.log" , $log_msg , FILE_APPEND);

?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <title>Defect ID Summary of Defect List</title>
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
      .StateOpenClass
      {
      font-family: Arial, sans-serif;
      font-size: 12px;
      background-color: #ffffc0;
      color: #ff0000
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


	$defects = explode(",",$defectlist);
	$defects_arr = array();

	$cookie = database_login('Software Development Issues');
	foreach ($defects as $defect_no) {
//		echo $defect_no . "<br>";

//	$rlist = getDefect($cookie, $defectNumber);
		$rlist = getDefect($cookie, $defect_no);
//	echo "<br><br>Record List ====<br><br>" . $rlist . "<br><br>";

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

	

//	if ($krumo_enabled) {krumo($arr);}
		$last_m = preg_split("/[\-T]/",$arr[pDefect][datetimemodified]);
		$arr[pDefect][last_modified] = $last_m[1] . "/" . $last_m[2] . "/" . $last_m[0];

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
		$arr[pDefect][expectation_date] = $expectation_date;
		$arr[pDefect][planned_version] = $planned_version;
	

		$comments = "spy";
		if (array_key_exists('reportedbylist' , $arr[pDefect])) {
			$rep = $arr[pDefect][reportedbylist][item];	
			if ( array_key_exists('recordid' , $rep)) {
				$comments = nl2br($rep[comments]);
			} else {
				foreach (array_reverse($rep) as $rep1) {
					$comments = nl2br($rep[comments]);
					break;
				}
			}
		}
		$arr[pDefect][comments] = $comments;

		$assignto = "NA";
		$notes = "";
		$completion = "";
        if (array_key_exists('eventlist' , $arr[pDefect])) {
            $eve = $arr[pDefect][eventlist][item];
            if ( array_key_exists('name' , $eve)) {
                $s = event_assign($eve);
				if ($s != "NA") {
					$assignto = $s;
				}
				if ( array_key_exists('notes' , $eve)) {
					$arr_dt = date_parse($eve[date]);
                	$e_dt = $arr_dt[month] . '/' . $arr_dt[day] . '/' . $arr_dt[year];

					$notes =  "<span class='ReportOddRowClass'> by " . $eve[user] . " on " . $e_dt . "</span><br>";
					$notes = $notes . nl2br($eve[notes]);
				}
            } else {
                foreach (array_reverse($eve) as $eve1) {
                    $s = event_assign($eve1);
					if ($s != "NA") {
						$assignto = $s;
						break;
					}
                }
                foreach (array_reverse($eve) as $eve1) {
					if ( array_key_exists('notes' , $eve1)) {
						$arr_dt = date_parse($eve1[date]);
                		$e_dt = $arr_dt[month] . '/' . $arr_dt[day] . '/' . $arr_dt[year];

						$notes =  "<span class='ReportOddRowClass'> by " . $eve1[user] . " on " . $e_dt . "</span><br>";
						$notes = $notes . nl2br($eve1[notes]);
						break;
					}
                }
                foreach (array_reverse($eve) as $eve1) {
					if (array_key_exists('fieldlist' , $eve1)) {
						$completion = event_completion($eve1[fieldlist][item]);
					}
				}
            }
        }
		$arr[pDefect][assignto] = $assignto;
		$arr[pDefect][notes] = $notes;
		$arr[pDefect][completion] = $completion;

		$defects_arr[] = $arr;
	}
	database_logoff($cookie);

	if ($krumo_enabled) {krumo($defects_arr);}
function event_completion($fielditem)
{
	$comp = "";
	if ( array_key_exists('name' , $fielditem)) {
		if ($fielditem[name] == 'Completion Date') {
			$comp = $fielditem[value];
		}
	} else {
		foreach (array_reverse($fielditem) as $fi) {
			if ($fi[name] == 'Completion Date') {
				$comp = $fi[value];
				break;
			}
		}
	}

	if ($comp != "") {
		$comp_arr = explode("-",$comp);
		$comp = $comp_arr[1] . "/" . $comp_arr[2] . "/" . $comp_arr[0];
		$comp = "<span class='ReportOddRowClass'>" . $comp . "</span>";
	}
	return $comp;
}
		
function event_assign($eve1)
{
    $arr_dt = date_parse($eve1[date]);
    $e_dt = $arr_dt[month] . '/' . $arr_dt[day] . '/' . $arr_dt[year] . ' ' . $arr_dt[hour] . ':' . $arr_dt[minute];
    if ( $eve1[name] == 'Assign') {
		return $eve1[assigntolist][item];
    }
	return "NA";
}

?>

<table width="100%" border="0" cellpadding="3" cellspacing="0" > <!---class="ReportDetailRowHeaderClass">--->
<tr>
<td class="ReportHeaderRowClass"><span style="font-weight: normal;">Selected Tickets Summary for Business Development Meeting</span></td>
</tr>
</table>

<br>
<table width="100%" border="1" cellpadding="3" cellspacing="0" >
<tr bgcolor='#ddddff'>
<td>Ticket No.</td>
<td>Summary</td>
<td>Account</td>
<td>Priority</td>
<td>AssignTo</td>
<td>Est. Completion Date</td>
<td>Last Modified</td>
<td>Status</td>
<td>Entered by</td>
<td>Notes</td>
<td>Comments</td>
</tr>
<?php

foreach ($defects_arr as $arr) {
	if ($arr[pDefect][state] == "Open") {
	one_line($arr);
	}
}
foreach ($defects_arr as $arr) {
	if ($arr[pDefect][state] != "Open") {
	one_line($arr);
	}
}

function one_line($arr)
{
	echo "<tr>";
	echo "<td><a href='http://svcwiki/lib/testlab/public_html/onedefect.php?defectNumber=" . $arr[pDefect][defectnumber] . "'>" . $arr[pDefect][defectnumber] . "</a></td>";
	echo "<td>" . $arr[pDefect][summary] . "</td>";
	echo "<td>" . $arr[pDefect][reference] . "</td>";
	echo "<td>" . $arr[pDefect][priority] . "</td>";
	echo "<td>" . $arr[pDefect][assignto] . "</td>";
	echo "<td>" . $arr[pDefect][completion] . "</td>";
	echo "<td><span class='ReportOddRowClass'>" . $arr[pDefect][last_modified] . "</span></td>";
	if ($arr[pDefect][state] == "Open") {
	echo "<td class='StateOpenClass'>" . $arr[pDefect][state] . "</td>";
	} else {
	echo "<td>" . $arr[pDefect][state] . "</td>";
	}
	echo "<td>" . $arr[pDefect][enteredby] . "</td>";
	echo "<td>" . $arr[pDefect][notes] . "</td>";
	echo "<td>" . $arr[pDefect][comments] . "</td>";
//	echo "<td>" . first_lines($arr[pDefect][notes]) . "</td>";
//	echo "<td>" . first_lines($arr[pDefect][comments]) . "</td>";
	echo "</tr>";
}


function first_lines($lline)
{
	$count = 6;
	$startat = 0;
	while ($count > 0) {
		$pos = strpos($lline , "<br>" , $startat);
		if ($pos !== false) {
			$count = $count - 1;
			$startat = $pos + 1;
		} else {
			break;
		}
	}
	if ($pos !== false) {
		return substr($lline , 0 , $pos + 4);
	} else {
		if ($startat == 0) {
			return $lline;
		} else {
			return substr($lline , 0 , $startat + 3);
		}
	}
}
?>


</table>

</body>
</html> 
