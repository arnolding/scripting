<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <title>TCAD Weekly from TTPro</title>
    <meta name="author" content="Maxime Thirouin">
    <link rel="stylesheet" href="Pikaday-1.3.2/css/pikaday.css">
    <link rel="stylesheet" href="Pikaday-1.3.2/css/site.css">
</head>
<body>
<p id="xxx"></p>
<label for="datepicker">Date Modified From:</label>
    <input type="text" id="date-b" onchange="do_all()">
<label for="datepicker">To:</label>
    <input type="text" id="date-e" onchange="do_all()">
    <script src="Pikaday-1.3.2/pikaday.js"></script>
    <script>
	var dt = new Date();
	var mm = dt.getMonth();
	var qtr = Math.floor(mm/3);  // qtr is quarter, from 0 ~ 3
	var qtr_b = new Date(dt.getFullYear(),qtr*3,1); 
	var dow = qtr_b.getDay();
	qtr_b.setDate(qtr_b.getDate() - dow + 1);
	
    var picker_b = new Pikaday({
        showWeekNumber: true,
        field: document.getElementById('date-b'),
        firstDay: 1,
        minDate: new Date('2000-01-01'),
        maxDate: new Date('2020-12-31'),
		defaultDate: qtr_b,
		setDefaultDate: true,
        yearRange: [2000, 2020]
    });

	var picker_e = new Pikaday({
        showWeekNumber: true,
        field: document.getElementById('date-e'),
        firstDay: 1,
        minDate: new Date('2000-01-01'),
        maxDate: new Date('2020-12-31'),
		defaultDate: new Date(),
		setDefaultDate: true,
        yearRange: [2000, 2020]
    });

    </script>
<?php
include "krumo/class.krumo.php";
include "class_xml2array.php";
include "ttprosoap.php";
?>

<?php



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



//	$pp = project_list();

	$cookie = database_login('Software Development Issues');
//	$tlist = table_list($cookie);
//	echo "<br><br>Table List ====<br><br>" . $tlist . "<br><br>";

//	$clist = column_list($cookie);
//	echo "<br><br>Column List ====<br><br>" . $clist . "<br><br>";

//	$flist = filter_listx($cookie);
//	echo "<br><br>Filter List ====<br><br>" . $flist . "<br><br>";

	$rlist = record_list($cookie, 'phpTCAD');
//	echo "<br><br>Record List ====<br><br>" . $rlist . "<br><br>";
	database_logoff($cookie);

	$regex="/<ttns:getRecordListForTableResponse>(.*)<\/ttns:getRecordListForTableResponse>/";
	$item = "";
	if (preg_match_all($regex, $rlist, $matches_out)) {
		$item = $matches_out[1][0];
	}

	$arr = XML2Array::createArray($item);

//	krumo($arr);
//	echo "<hr>\n";
//	print_r($arr);
//	array2table($arr);




?>
<p id="detail"></p>
<script>
Date.prototype.getWeek = function() {
        var onejan = new Date(this.getFullYear(), 0, 1);
        return Math.ceil((((this - onejan) / 86400000) + onejan.getDay() + 1) / 7);
    }

	var ttpro = <?php echo json_encode($arr); ?>;

	var s_obj = weeknum(ttpro);
	var date_b = new Date(document.getElementById('date-b').value);
	var date_e = new Date(document.getElementById('date-e').value);
	var f_obj = filter_by_date(s_obj , date_b , date_e);
	document.getElementById("detail").innerHTML = f2table(f_obj );


function filter_by_date(sobj , date_b , date_e)
{
	var filtered_tbl = new Object();
	filtered_tbl.header = sobj.header;
	var rows_arr = [];
	var rows = sobj.rows;
	
	for (rx in rows) {
		date_m = new Date(rows[rx][6]);
		if ( date_m > date_b && date_m < date_e) {
			rows_arr[rows_arr.length] = rows[rx];
		}
	}
	filtered_tbl.rows = rows_arr;

	return filtered_tbl;
}
function do_all()
{
	var date_b = new Date(document.getElementById('date-b').value);
	var date_e = new Date(document.getElementById('date-e').value);
	f_obj = filter_by_date(s_obj , date_b , date_e);
	document.getElementById("detail").innerHTML = f2table(f_obj);
}

function weeknum(ttpro_obj) {
	var simple_obj = new Object();
	var header_arr = [];
	var col_list = ttpro_obj['recordlist']['columnlist']['item'];
	for (hx in col_list) {
		header_arr[hx] = col_list[hx].name;
	}
	header_arr[hx + 1] = "Weeknum";
	simple_obj.header = header_arr;
	

	var rows = ttpro_obj['recordlist']['records']['item'];
	var rows_arr = [];
	for (rx in rows) {
		var row_arr = [];
		var row1 = rows[rx]['row']['item'];
		for (cx in row1) {
			if (typeof(row1[cx].value) != "undefined") {
				row_arr[cx] = row1[cx].value;
			} else {
				row_arr[cx] = "";
			}
		}
		var dt = new Date(row_arr[cx]);
		var w2 = dt.getWeek();
		if (w2 < 10) { w2 = '0' + w2;}
		row_arr[cx + 1] = dt.getFullYear() + ",W" + w2;
		rows_arr[rows_arr.length] = row_arr;
	}
	simple_obj.rows = rows_arr;
	return simple_obj;
}
function f2table(sobj) {
	var text = "";
	text += "<table><tr>";
	var col_list = sobj.header;

	for (hx in col_list) {
		text += "<td>" + col_list[hx] + "</td>";
	}
	text += "</tr>";

	var rows = sobj.rows;
	for (rx in rows) {
		text += "<tr>";
		var row1 = rows[rx];
		for (cx in row1) {
				text += "<td>" + row1[cx] + "</td>";
		}
		text += "</tr>";
	}

	text += "</table>";
	return text;
}
function s2table(sobj, date_b , date_e) {
	var text = "";
	text += "<table><tr>";
	var col_list = sobj.header;

	for (hx in col_list) {
		text += "<td>" + col_list[hx] + "</td>";
	}
	text += "</tr>";

	var rows = sobj.rows;
	for (rx in rows) {
		date_m = new Date(rows[rx][6]);
		if ( date_m > date_b && date_m < date_e) {
		text += "<tr>";
		var row1 = rows[rx];
		for (cx in row1) {
				text += "<td>" + row1[cx] + "</td>";
		}
		text += "</tr>";
		}
	}

	text += "</table>";
	return text;
}


function obj2table(ttpro_obj) {
	var header;
	var text = "";
	text += "<table><tr>";
	var col_list = ttpro_obj['recordlist']['columnlist']['item'];

	for (hx in col_list) {
		text += "<td>" + col_list[hx].name + "</td>";
	}
	text += "</tr>";

	var rows = ttpro_obj['recordlist']['records']['item'];
	for (rx in rows) {
		text += "<tr>";
		var row1 = rows[rx]['row']['item'];
		for (cx in row1) {
			if (typeof(row1[cx].value) != "undefined") {
				text += "<td>" + row1[cx].value + "</td>";
			} else {
				text += "<td></td>";
			}
		}
		text += "</tr>";
	}

	text += "</table>";
	return text;
	
}
</script>

</body>
</html> 
