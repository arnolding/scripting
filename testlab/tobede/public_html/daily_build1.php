<!DOCTYPE html>
<html>

<?php

$check_date = $_GET['check'];
if ($check_date != '') {
	$sql = "select * from daily_build where check_date='" . $check_date . "' order by object_mdate";
} else {
//	$sql = "select count(*) as num_of_obj,check_date,platform,version from daily_build ";
//	$sql .= "where check_date > '2015-12-19 00:00:00' group by check_date,platform,version";
	$sql = "select * from daily_build where check_date > '2015-12-19 00:00:00' order by object_mdate,platform,version";
}
$pdo = new PDO("sqlite:../daily.db");

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
    <title>Daily Build</title>
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
		var daily_build = <?php echo json_encode($all_rec); ?>;
		var flat = {};
		var obj_all = {};
		var platform_all = {};
		var version_all = {};
		function group_table() {
			for (i = daily_build.length - 1 ; i >=0 ; i--) {
		        	if (daily_build[i]['platform'] == 'common' ||
					daily_build[i]['platform']=='docs'||
					daily_build[i]['platform']=='translations')
					{continue;}
				var mdate = daily_build[i]['object_mdate'].substring(0,10);
		//		var f1 = {mdate:{daily_build[i]['platform']:{daily_build[i]['version']:{daily_build[i]['object_name']: i}}}};
				var obj_name = daily_build[i]['object_name'];
				if (obj_name in obj_all ) {
					obj_all[obj_name] += 1;
				} else {
					obj_all[obj_name] = 1;
				}
				if (daily_build[i]['platform'] in platform_all ) {
					platform_all[daily_build[i]['platform']] += 1;
				} else {
					platform_all[daily_build[i]['platform']] = 1;
				}

				if (daily_build[i]['version'] in version_all ) {
					version_all[daily_build[i]['version']] += 1;
				} else {
					version_all[daily_build[i]['version']] = 1;
				}
// extra to get all possible obj names
			}
		}

		group_table();
		$.each(platform_all , function(key,value) {
			$('#platform_sel')
				.append($("<option></option>")
				.attr("value" , key)
				.text(key));
		});
		$.each(version_all , function(key,value) {
			$('#version_sel')
				.append($("<option></option>")
				.attr("value" , key)
				.text(key));
		});


		function show_pv(platform , version) {
			//console.log(platform);
			//console.log(version);


			var pv_obj_all = {};
			var pv_set = [];
			for (i = daily_build.length - 1 ; i >=0 ; i--) {
				if (daily_build[i]['platform'] == platform && daily_build[i]['version'] == version) {
					var obj_name = daily_build[i]['object_name'];
					if (obj_name in pv_obj_all ) {
						pv_obj_all[obj_name] += 1;
					} else {
						pv_obj_all[obj_name] = 1;
					}
					pv_set.push(i);
				}
			}
			flat_str = "<table border='1' >";
			flat_str += "<tr><th>Date</th>"
			var obj_keys = Object.keys(pv_obj_all);
			for ( var i = 0 ; i < obj_keys.length ; i++) {
				flat_str += "<th>" + obj_keys[i] + "</th>";
			}
			flat_str += "</tr>";		

			
			var mdate, pre_mdate;
			pre_mdate = 'a';
			for (var pvi= 0 ; pvi< pv_set.length ; pvi++) {
				var i = pv_set[pvi];
				if (daily_build[i]['platform'] == platform && daily_build[i]['version'] == version) {
					mdate = daily_build[i]['object_mdate'].substring(0,10);
					if (pre_mdate == mdate) {
						mdate_obj[daily_build[i]['object_name']] = i;
					} else {
						if (pre_mdate != 'a') {
							flat_str += "<tr><td>" + pre_mdate + '</td>';
							for (var j = 0 ; j < obj_keys.length ; j++) {
								if (obj_keys[j] in mdate_obj) {
									flat_str += "<td>" + mdate_obj[obj_keys[j]] + "</td>";
								} else {
									flat_str += "<td>" + "&nbsp;" + "</td>";
								}
							}
							flat_str += "</tr>";
						}
						var mdate_obj = {};
						mdate_obj[daily_build[i]['object_name']] = i;
						pre_mdate = mdate;
					}
				}

			}
						if (pre_mdate != 'a') {
							flat_str += "<tr><td>" + pre_mdate + '</td>';
							for (var j = 0 ; j < obj_keys.length ; j++) {
								if (obj_keys[j] in mdate_obj) {
									flat_str += "<td>" + mdate_obj[obj_keys[j]] + "</td>";
								} else {
									flat_str += "<td>" + "&nbsp;" + "</td>";
								}
							}
							flat_str += "</tr>";
						}
			flat_str += "</table>";
			return flat_str;
		}
		$('#pv_go').click(function() {
			$("#flat_table").html(show_pv($("#platform_sel").val(),$("#version_sel").val()));
		});


		function make_table() {
			var html_str = "<table align='right' border='1' >";
			var group_check = 0;
			var header_shown = 0;
			for (i = daily_build.length - 1 ; i >=0 ; i--) {
				if (header_shown == 0) {
					header_shown = 1;
					html_str += "<tr>";
					for (var j in daily_build[i]) {
						html_str += "<th>" + j + "</th>";
						if (j == 'num_of_obj') {
							group_check = 1;
						}
					}
					html_str += "</tr>";
				}

				html_str += "<tr>";				
				if (group_check) {
					for (var j in daily_build[i] ) {
						var iHTML = daily_build[i][j];
						if (j == 'check_date') {
							iHTML = "<a href = 'daily_build.php?check=" + iHTML + "'>" + iHTML + "</a>";
						}
						html_str += "<td>" + iHTML + "</td>";
					}
				} else {
					for (var j in daily_build[i] ) {
						html_str += "<td>" + daily_build[i][j] + "</td>";
					}
				}
				html_str += "</tr>";
			}
			html_str += "</table>";
			return html_str;
		}
//		$("#table0").html(make_table());

		var group_eve = {};
		var mdate;
        	for (i = 0 ; i< daily_build.length ; i++) {
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
	
		var eve = [];
		for (var key in group_eve) {
			var m1 = group_eve[key];
			for (var k = 0 ; k < m1.length ; k++) {
				var e1 = new Object();
				e1.user_obj = m1[k]['obj'];
				e1.user_id = m1[k]['id'];
				e1.user_platform = m1[k]['platform'];
				e1.user_ver = m1[k]['version'];
				e1.title = m1[k]['version']+'/'+ platform_drawing[m1[k]['platform']].abbr;
				e1.start = key;
				e1.color = platform_drawing[m1[k]['platform']].color;
				eve.push(e1);
			}
		}
		
		$('#calendar').fullCalendar({
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,basicWeek,basicDay'
			},
	//		defaultDate: '2015-12-19',
			editable: false,
			eventLimit: true, // allow "more" link when too many events
			events: eve,
			eventClick: function(calEvent, jsEvent, view) {
			        //alert(calEvent.title + '\n' + calEvent.user_obj  );
				$("#platform_sel").val(calEvent.user_platform);
				$("#version_sel").val(calEvent.user_ver);
				$("#flat_table").html(  show_pv(calEvent.user_platform, calEvent.user_ver) );
			        $(this).css('border-color', 'red');
			}
		});

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
