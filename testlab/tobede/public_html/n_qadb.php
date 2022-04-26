<?php
$delete_id = $_GET['delete'];
$del_val = $_GET['val'];
$cookie_manage = (isset($_COOKIE['manage'])) ? $_COOKIE['manage'] : 0;
$cookie_view = (isset($_COOKIE['view'])) ? $_COOKIE['view'] : 1;
$cookie_test_id = (isset($_COOKIE['test_id'])) ? $_COOKIE['test_id'] : 0;
$test_id = $_GET['test_id'];
$manage = $_GET['manage'];
if ( $_COOKIE[db_mediawikiUserName] != 'Arnoldh') {
	$manage = '';
}
?>

<?php
if ($manage != '') {
	$table_idx = "test_idx";
	$table_detail = "test";
} else {
	$table_idx = "test_idx_v";
	$table_detail = "test";
}
if ($delete_id != '') {
	if ($del_val != '') {
	$sql = "update test_idx set deleted = '" . $del_val . "' where test_id='" . $delete_id . "' ";
	} else {
	$sql = "update test_idx set deleted = 'Y' where test_id='" . $delete_id . "' ";
	}
} elseif ($test_id != '' ) {
	$sql = "select * from " . $table_detail . " where test_id='" . $test_id . "' ";
} else {
//	$sql = "select count(*) as num_of_obj,check_date,platform,version from daily_build ";
//	$sql .= "where check_date > '2015-12-19 00:00:00' group by check_date,platform,version";
	$sql = "select * from " . $table_idx;
}
$pdo = new PDO("sqlite:../qadb.db");

try {
	$pdoStatement = $pdo->prepare($sql);
	$pdoStatement->execute();
} catch (PDOException $e) {
	echo $e->getMessage();
}
if ($delete_id != '') {
	if($pdoStatement->rowCount()==1) {
		header( 'Location: n_qadb.php?manage=1' ) ;
	} else  {
		echo 'update failed';
	}
}

$all_rec = array();

while ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC) ) {
	array_push($all_rec , $row);

}


?>   
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <title>QADB</title>
    <meta name="author" content="Arnold Ho">
    <link rel="stylesheet" href="../Pikaday-1.3.2/css/pikaday.css">
<link rel="stylesheet" href="../Pikaday-1.3.2/css/site.css">
<link rel="stylesheet" href="../tablesorter/green/style.css" />
<link rel="stylesheet" href="header.css" />
<script src="header.js"></script>

<script src='../jquery-2.2.1.min.js'></script>
<script type="text/javascript" src="../tablesorter/jquery-latest.js"></script> 
<script type="text/javascript" src="../tablesorter/jquery.tablesorter.js"></script> 
<script src="../js_sorter/script.js"></script>

<script>
	$(document).ready(function() {
		var test_idx = <?php echo json_encode($all_rec); ?>;
		var flat = {};
		var obj_all = {};
		var platform_all = {};
		var version_all = {};
		var manage = '<?php echo $manage;?>';
		var index_page = '<?php echo $test_id; ?>';
		show_header('<?php echo $_COOKIE[db_mediawikiUserName] ?>');

		function make_table() {
			var html_str = "<table id='table1' class='tablesorter'>";
			var group_check = 1;
			var header_shown = 0;
			for (i = test_idx.length - 1 ; i >=0 ; i--) {
				if (header_shown == 0) {
					header_shown = 1;
					html_str += "<thead><tr>";
					for (var j in test_idx[i]) {
						html_str += "<th>" + j + "</th>";
					}
					html_str += "</tr></thead><tbody>";
				}

				html_str += "<tr>";
				var idx = test_idx[i]['test_id'];
				if (group_check) {
					for (var j in test_idx[i] ) {
						var iHTML = test_idx[i][j];
//console.log(j , "[" , index_page ,"][", user,"]");
						if (j == 'test_id' && index_page=='') {
							iHTML = "<a href = 'n_qadb.php?test_id=" + iHTML + "'>" + iHTML + "</a>";
						} else if (j == 'deleted' ) {
							if (iHTML == 'Y') {
							iHTML = "<a href = 'n_qadb.php?val=N&delete=" + idx + "'>" + iHTML + "</a>";
							} else {
							iHTML = "<a href = 'n_qadb.php?delete=" + idx + "'>" + iHTML + "</a>";
							}
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
			html_str += "</tbody></table>";

			return html_str;
		}
		$("#table0").html(make_table());
        $("#table1").tablesorter();

		var group_eve = {};
		var mdate;

		var platform_drawing = {
			'x86_64-linux' : {color: '#257e2a' , abbr: 'x86-lnx'} ,
			'x86_64-solaris' : {color: '#257e5a' , abbr: 'x86-sol'} ,
			'x86_64-windows' : {color: '#257e8a' , abbr: 'x86-win'} ,
			'sparc-solaris2' : {color: '#254eea' , abbr: 'spc-sol'} };
	
	});
function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

</script>
<script>
function mode_selector() {
	var searchObj = {};
	var query_str = window.location.search;
	var queries = query_str.replace(/^\?/,'').split('&');
    for ( i = 0 ;i < queries.length ; i++) {
        anb = queries[i].split('=');
        if (anb[1]) {
            searchObj[anb[0]] = anb[1];
        }
    }
	return searchObj;
}
function add_mode(mode) {
	var mode_hash = mode_selector();
	if ( !("manage" in mode_hash) ) {
		var qstr = window.location.search;
		qstr += "?manage=1";
		window.location = window.location+qstr;
	}
}

</script>
<style>

	body {
		margin: 40px 10px;
		padding: 0;
		font-family: "Lucida Grande",Helvetica,Arial,Verdana,sans-serif;
		font-size: 14px;
	}


</style>
</head>
<body >
<br><br>
<table><tr>
<td>Platform: <select id='platform_sel'></select> Version: <select id='version_sel'></select> <button id='pv_go' value='Go'>Show Objects</button></td>
<td valign=bottom align=right id='mode_selector'><a onclick='add_mode("manage")'>Manage</a></td>
<td ><a href=n_qadb.php>Index</a></td>
<td ><a href=n_qadb.php>Delete</a></td>
</tr></table> 
<div id='flat_table' ></div><br><br>
<div id='table0' ></div><br><br>


</body>
</html> 
