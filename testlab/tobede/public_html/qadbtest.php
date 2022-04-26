<!DOCTYPE html>
<html>

<?php

$check_date = $_GET['test_id'];
if ($check_date != '') {
	$sql = "select * from test where test_id='" . $check_date . "' ";
} else {
//	$sql = "select count(*) as num_of_obj,check_date,platform,version from daily_build ";
//	$sql .= "where check_date > '2015-12-19 00:00:00' group by check_date,platform,version";
	$sql = "select * from test_idx";
}
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

}

?>   
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <title>QADB</title>
    <meta name="author" content="Arnold Ho">
 
    <link rel="stylesheet" href="../Pikaday-1.3.2/css/pikaday.css">
<link rel="stylesheet" href="../Pikaday-1.3.2/css/site.css">
<link rel="stylesheet" href="../js_sorter/style.css" />
<link rel="stylesheet" href="header.css" />
<script src="header.js"></script>

<link href='../fullcalendar-2.5.0/fullcalendar.css' rel='stylesheet' />
<link href='../fullcalendar-2.5.0/fullcalendar.print.css' rel='stylesheet' media='print' />
<script src='../fullcalendar-2.5.0/lib/moment.min.js'></script>
<script src='../fullcalendar-2.5.0/lib/jquery.min.js'></script>
<script src='../fullcalendar-2.5.0/fullcalendar.min.js'></script>
<script>


</script>
<script>
	$(document).ready(function() {
		var test_idx = <?php echo json_encode($all_rec); ?>;
		var flat = {};
		var obj_all = {};
		var platform_all = {};
		var version_all = {};

		function make_table() {
			var html_str = "<table align='center' border='1' >";
			var group_check = 1;
			var header_shown = 0;
			for (i = test_idx.length - 1 ; i >=0 ; i--) {
				if (header_shown == 0) {
					header_shown = 1;
					html_str += "<tr>";
					for (var j in test_idx[i]) {
						html_str += "<th>" + j + "</th>";
					}
					html_str += "</tr>";
				}

				html_str += "<tr>";				
				if (group_check) {
					for (var j in test_idx[i] ) {
						var iHTML = test_idx[i][j];
						if (j == 'test_id') {
							iHTML = "<a href = 'qadb.php?test_id=" + iHTML + "'>" + iHTML + "</a>";
						}
						html_str += "<td>" + iHTML + "</td>";
					}
				} else {
					for (var j in test_idx[i] ) {
						html_str += "<td>" + test_idx[i][j] + "</td>";
					}
				}
				html_str += "</tr>";
			}
			html_str += "</table>";
			return html_str;
		}
		$("#table0").html(make_table());

		var group_eve = {};
		var mdate;
        	for (i = 0 ; i< test_idx.length ; i++) {
		        if (daily_build[i]['platform'] == 'common' || daily_build[i]['platform']=='docs'|| daily_build[i]['platform']=='translations')
				{continue;}
			var cur_platform = daily_build[i]['platform'];
			var cur_version = daily_build[i]['version'];
			mdate = daily_build[i]['object_mdate'].substring(0,10);
			if (mdate in group_eve) {
				var m1 = group_eve[mdate];
				var j;
				for ( j = 0 ; j < m1.length ; j++) {
       					if (m1[j]['platform'] == cur_platform && m1[j]['version'] == cur_version) {
						break;
					}
				}
				if (j == m1.length) {
					var m1e = new Object();
					m1e['platform'] = cur_platform;
					m1e['version'] = cur_version;
					m1e['obj'] = daily_build[i]['object_name'];
					m1e['id'] = daily_build[i]['checkID'];
					m1.push(m1e);
				} else {
					m1[j]['obj'] += '|\n' + daily_build[i]['object_name'];
					m1[j]['id'] += ',\n' + daily_build[i]['checkID'];
				}
			} else {
				var m1 = [];
				var m1e = new Object();
				m1e['platform'] = cur_platform;
				m1e['version'] = cur_version;
				m1e['obj'] = daily_build[i]['object_name'];
				m1e['id'] = daily_build[i]['checkID'];
				m1.push(m1e);
				group_eve[mdate] = m1;
			}
		}

		var platform_drawing = {
			'x86_64-linux' : {color: '#257e2a' , abbr: 'x86-lnx'} ,
			'x86_64-solaris' : {color: '#257e5a' , abbr: 'x86-sol'} ,
			'x86_64-windows' : {color: '#257e8a' , abbr: 'x86-win'} ,
			'sparc-solaris2' : {color: '#254eea' , abbr: 'spc-sol'} };
	
	});

</script>
<style>

	body {
		margin: 40px 10px;
		padding: 0;
		font-family: "Lucida Grande",Helvetica,Arial,Verdana,sans-serif;
		font-size: 14px;
	}

	#calendar {
		max-width: 900px;
		margin: 0 auto;
	}

</style>
</head>
<body onload="show_header('<?php echo $_COOKIE[db_mediawikiUserName] ?>')">
<br><br><div id='calendar'></div><br>
Platform: <select id='platform_sel'></select> Version: <select id='version_sel'></select> <button id='pv_go' value='Go'>Show Objects</button><br>
<div id='flat_table' ></div><br><br>
<div id='table0' ></div><br><br>


</body>
</html> 
