<!DOCTYPE html>
<html>
<head>
<?php
include "krumo/class.krumo.php";
include "class_xml2array.php";
include "ttprosoap.php";
$date_tag = strftime("%b %d %Y, %X %Z ", time());
$log_msg = $date_tag . "UserName: [" . $_COOKIE["db_mediawikiUserName"] . "], REMOTE_ADDR: [" . $_SERVER["REMOTE_ADDR"] . "], SERVER_ADDR: [" . $_SERVER["SERVER_ADDR"] ."], SCRIPT: [" . $_SERVER["SCRIPT_NAME"] . "]\n";
file_put_contents("/main/stage/wiki/testlab/user.log" , $log_msg , FILE_APPEND);


// $last_period = W for week, B for BiWeek, M for month, Y for year
$last_period = $_GET['last'];

if ( $last_period != 'Week' and $last_period != 'BiWeek' and $last_period != 'Month' and $last_period != 'Year') {
	$last_period = 'BiWeek';
}

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
//	$clist = column_list($cookie);
//	echo "<br><br>Column List ====<br><br>" . $clist . "<br><br>";
	$rlist = record_newfound_4ref($cookie, 'Newly Found ' . substr($last_period,0,1));
//	echo "<br><br>Record List ====<br><br>" . $rlist . "<br><br>";
	database_logoff($cookie);

	$regex="/<ttns:getRecordListForTableResponse>(.*)<\/ttns:getRecordListForTableResponse>/";
	$item = "";
	if (preg_match_all($regex, $rlist, $matches_out)) {
		$item = $matches_out[1][0];
	} else {
		echo "<br>" . $rlist . "<br>";
	}


	$arr = XML2Array::createArray($item);

//	krumo($arr);
//	echo "<hr>\n";
//	print_r($arr);
//	array2table($arr);

?>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <title>Newly Entered to TTPro Classified by Reference</title>
    <meta name="author" content="Arnold Ho">
    <link rel="stylesheet" href="Pikaday-1.3.2/css/pikaday.css">
    <link rel="stylesheet" href="Pikaday-1.3.2/css/site.css">

	<STYLE>  
		 		.ColumnHeaderClass	{
					font-family: Arial, sans-serif;
					font-weight: bold;
					font-size: 16px;
					COLOR: #ffffff;
				}
	      </STYLE>

	<STYLE>  
		 		.DataCellClass	{
					font-family: Arial, sans-serif;
					font-size: 14px;
					COLOR: #000000;
				}
	      </STYLE>

</head>
<body>
<p id="xxx"></p>
<table width="100%"  ><tr><td rowspan="2" valigh="bottom"><h2>Newly Entered to TTPro Classified by Reference</h2></td>
<td valign=bottom align=right>
<b>Duration: </b><input type=radio name=period style='color:green' onClick="location.href='newfound.php?last=Week'" value="Week" <?php if ($last_period == "Week") echo "checked" ?> >Week
<input type=radio name=period style='color:green' onClick="location.href='newfound.php?last=BiWeek'" value="BiWeek" <?php if ($last_period == "BiWeek") echo "checked" ?> >BiWeek
<input type=radio name=period style='color:blue' onClick="location.href='newfound.php?last=Month'" value="Month" <?php if ($last_period == "Month") echo "checked" ?>>Month
<input type=radio name=period style='color:black' onClick="location.href='newfound.php?last=Year'" value="Year" <?php if ($last_period == "Year") echo "checked" ?>>Year
<!---<input type=radio name=period style='color:black' onClick="period_chg();location.href='newfound_t.php?last=W'" value="N">None --->
</td></tr>
<tr><td valign=bottom align=right>
<label for="datepicker">Date Entered From:</label>
    <input type="button" id="date-b" onChange="do_all()">
<label for="datepicker">To:</label>
    <input type="button" id="date-e" onChange="do_all()">
</td></tr></table>
<div id='debug'></div>
<table><tr><td valign=bottom align=right bgcolor='#d3f3d3'><b>Priority:</b>&nbsp;
<input type=checkbox name=Urgent id="Urgent" onClick="priority_chg()" checked>Urgent
<input type=checkbox name=High id="High" onClick="priority_chg()" checked >High
<input type=checkbox name=Medium id="Medium" onClick="priority_chg()">Medium
<input type=checkbox name=Low id="Low" onClick="priority_chg()">Low
<input type=checkbox name=not_set id="not_set" onClick="priority_chg()">Not Set
</td><td bgcolor='#d3f3d3'><button bgcolor='#d3f3d3' id="change_prio">Redraw</button></td></tr></table>
<div id="chart_status" style="width: 1600px; height: 500px;"></div><br>
<div id="chart_div" style="width: 1200px; height: 500px;"></div>
<p id="statistics"></p>
<hr>

<div id='detail_list'><a name='detail_list'></a></div> 
<h3 id='detail_subject'>Detail List in Duration</h3>
<p id="detail"></p>



    <script src="Pikaday-1.3.2/pikaday.js"></script>
	<script src="https://www.google.com/jsapi"></script>

    <script>
	google.load("visualization", "1", {packages:["corechart"]});
	google.setOnLoadCallback(drawVisualization);

function priority_chg() {
	do_all();
 drawVisualization();
}
function priority_check() {
	var priority_setting = '';
	if (document.getElementById("Urgent").checked) {
		priority_setting += 'Urgent' + ',';
	}
	if (document.getElementById("High").checked) {
		priority_setting += 'High' + ',';
	}
	if (document.getElementById("Medium").checked) {
		priority_setting += 'Medium' + ',';
	}
	if (document.getElementById("Low").checked) {
		priority_setting += 'Low' + ',';
	}
	if (document.getElementById("not_set").checked) {
		priority_setting += 'Not_set' + ',';
	}
	return priority_setting;
}
function pre_count(dt , pe) {
	var pre_week = new Date(dt - 7*86400000);
	var pre_2week = new Date(dt - 14*86400000);
	var pre_month = new Date(dt.getFullYear(), dt.getMonth() -1, dt.getDate());
	var pre_year;
	if (dt.getMonth() == 1 && dt.getDate() == 29) {
		pre_year = new Date(dt.getFullYear() - 1 , dt.getMonth(), dt.getDate() - 1);
	} else {
		pre_year = new Date(dt.getFullYear() - 1 , dt.getMonth(), dt.getDate());
	}
	var pre_b;
	if (pe == 'Week') {pre_b = pre_week;}
	if (pe == 'BiWeek') {pre_b = pre_2week;}
	if (pe == 'Month') {pre_b = pre_month;}
	if (pe == 'Year') {pre_b = pre_year;}
	return pre_b;
}

	
	var period_sel = '<?php echo $last_period; ?>';
	var dt = new Date();
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
	
	var pre_b = pre_count(dt , period_sel);
    
	var picker_b = new Pikaday({
        showWeekNumber: true,
        field: document.getElementById('date-b'),
        firstDay: 1,
        minDate: new Date('2000-01-01'),
        maxDate: new Date('2020-12-31'),
		defaultDate: pre_b,
		setDefaultDate: true,
        yearRange: [2000, 2020]
    });

	Date.prototype.getWeek = function() {
        var onejan = new Date(this.getFullYear(), 0, 1);
        return Math.ceil((((this - onejan) / 86400000) + onejan.getDay() + 1) / 7);
    }

	var ttpro = <?php echo json_encode($arr); ?>;

	var s_obj = weeknum(ttpro);
	var date_b = new Date(document.getElementById('date-b').value);
	var date_e = new Date(document.getElementById('date-e').value);
	var f_obj = filter_by_date(s_obj , date_b , date_e);
	

	var product_stat = collect_product(f_obj);
	var reference_stat = collect_reference(f_obj);

//document.getElementById("statistics").innerHTML = text;
	document.getElementById("detail").innerHTML = f2table(f_obj );

function period_chg()
{
	var period_element = document.getElementsByName('period');
	for ( var i = 0 ; i < period_element.length ; i++) {
		if (period_element[i].checked) {
			period_sel = period_element[i].value;		
		}
	}
	return period_sel;
}
function filter_by_date(sobj , date_b , date_e)
{
	var filtered_tbl = new Object();
	filtered_tbl.header = sobj.header;
	var rows_arr = [];
	var rows = sobj.rows;
	
	for (rx in rows) {
		date_m = new Date(rows[rx][8]);
		if ( date_m > date_b && date_m < date_e) {
			rows_arr[rows_arr.length] = rows[rx];
		}
	}
	filtered_tbl.rows = rows_arr;

	return filtered_tbl;
}
function collect_reference(sobj)
{
	var pobj = new Object();
	var col_list = sobj.header;
	var reference_id = -1;
	var priority_id = -1;
	var status_id = -1;
	var number_id = -1;

	for (hx in col_list) {
		if (col_list[hx] == "Reference") {
			reference_id = hx;
		}
		if (col_list[hx] == "Priority") {
			priority_id = hx;
		}
		if (col_list[hx] == "Status") {
			status_id = hx;
		}
		if (col_list[hx] == "Number") {
			number_id = hx;
		}
	}

	pobj['subtotal'] = {high:0, other:0, Not_Assigned:0, Processing:0, Done:0, Unknown:0};
	var rows = sobj.rows;
	for (rx in rows) {
		var prod_name = rows[rx][reference_id];
		var prio_name = rows[rx][priority_id];
		var stat_name = rows[rx][status_id];
		var number = rows[rx][number_id];

		if (prio_name == '') {
			prio_name = 'Not_set';
		}
		
		if (!(pobj[prod_name])) {
			var arr = new Object();
			arr['total'] = 0; 
			arr['high'] = {count: 0, list:''};
			arr['other'] = {count: 0, list:''};
			arr['Not_Assigned'] = {count: 0, list:''};  // open, not assigned
			arr['Processing'] = {count: 0, list:''}; // open, assigned or In Development
			arr['Done'] = {count: 0, list:''}; // Fixed, Closed, Released to Testing
			arr['Unknown'] = {count: 0, list:''}; // status unknown or not classified
			pobj[prod_name] = arr;
		}
		var arr = pobj[prod_name];
		arr['total'] += 1;
		if (prio_name == 'Urgent' || prio_name == 'High') {
				arr['high'].count += 1;
				arr['high'].list += number + ',';
				pobj['subtotal'].high += 1;
		} else {
				arr['other'].count +=1;
				arr['other'].list += number + ',';
				pobj['subtotal'].other += 1;
		}

		var prio_setting;
		prio_setting = priority_check();
	//	document.getElementById("debug").innerHTML = prio_setting;

		if (prio_setting.indexOf(prio_name) >= 0) {
		if (stat_name.substr(0,4) == 'Open') {
			if ( stat_name.indexOf("not assigned") >= 0) {
				arr['Not_Assigned'].count +=1;
				arr['Not_Assigned'].list += number + ',';
				pobj['subtotal'].Not_Assigned += 1;
			} else {
				arr['Processing'].count += 1;
				arr['Processing'].list += number + ',';
				pobj['subtotal'].Processing += 1;
			}
		} else if (stat_name.substr(0,6) == 'Closed') {
			arr['Done'].count += 1;
			arr['Done'].list += number + ',';
			pobj['subtotal'].Done += 1;
		} else if (stat_name.substr(0,5) == 'Fixed') {
			arr['Done'].count += 1;
			arr['Done'].list += number + ',';
			pobj['subtotal'].Done += 1;
		} else if (stat_name.substr(0,8) == 'Released') {
			arr['Done'].count += 1;
			arr['Done'].list += number + ',';
			pobj['subtotal'].Done += 1;
		} else if (stat_name.substr(0,14) == 'In Development') {
			arr['Processing'].count += 1;
			arr['Processing'].list += number + ',';
			pobj['subtotal'].Processing += 1;
		} else {
			arr['Unknown'].count += 1;
			arr['Unknown'].list += number + ',';
			pobj['subtotal'].Unknown += 1;
		}
		}
	}
	return pobj;
}
function collect_product(sobj)
{
	var pobj = new Object();
	var col_list = sobj.header;
	var product_id = -1;
	var priority_id = -1;
	var status_id = -1;
	var number_id = -1;

	for (hx in col_list) {
		if (col_list[hx] == "Product") {
			product_id = hx;
		}
		if (col_list[hx] == "Priority") {
			priority_id = hx;
		}
		if (col_list[hx] == "Status") {
			status_id = hx;
		}
		if (col_list[hx] == "Number") {
			number_id = hx;
		}
	}

	pobj['subtotal'] = {high:0, other:0, Not_Assigned:0, Processing:0, Done:0, Unknown:0};
	var rows = sobj.rows;
	for (rx in rows) {
		var prod_name = rows[rx][product_id];
		var prio_name = rows[rx][priority_id];
		var stat_name = rows[rx][status_id];
		var number = rows[rx][number_id];

		if (prio_name == '') {
			prio_name = 'Not_set';
		}
		
		if (!(pobj[prod_name])) {
			var arr = new Object();
			arr['total'] = 0; 
			arr['high'] = {count: 0, list:''};
			arr['other'] = {count: 0, list:''};
			arr['Not_Assigned'] = {count: 0, list:''};  // open, not assigned
			arr['Processing'] = {count: 0, list:''}; // open, assigned or In Development
			arr['Done'] = {count: 0, list:''}; // Fixed, Closed, Released to Testing
			arr['Unknown'] = {count: 0, list:''}; // status unknown or not classified
			pobj[prod_name] = arr;
		}
		var arr = pobj[prod_name];
		arr['total'] += 1;
		if (prio_name == 'Urgent' || prio_name == 'High') {
				arr['high'].count += 1;
				arr['high'].list += number + ',';
				pobj['subtotal'].high += 1;
		} else {
				arr['other'].count +=1;
				arr['other'].list += number + ',';
				pobj['subtotal'].other += 1;
		}

		var prio_setting;
		prio_setting = priority_check();
	//	document.getElementById("debug").innerHTML = prio_setting;

		if (prio_setting.indexOf(prio_name) >= 0) {
		if (stat_name.substr(0,4) == 'Open') {
			if ( stat_name.indexOf("not assigned") >= 0) {
				arr['Not_Assigned'].count +=1;
				arr['Not_Assigned'].list += number + ',';
				pobj['subtotal'].Not_Assigned += 1;
			} else {
				arr['Processing'].count += 1;
				arr['Processing'].list += number + ',';
				pobj['subtotal'].Processing += 1;
			}
		} else if (stat_name.substr(0,6) == 'Closed') {
			arr['Done'].count += 1;
			arr['Done'].list += number + ',';
			pobj['subtotal'].Done += 1;
		} else if (stat_name.substr(0,5) == 'Fixed') {
			arr['Done'].count += 1;
			arr['Done'].list += number + ',';
			pobj['subtotal'].Done += 1;
		} else if (stat_name.substr(0,8) == 'Released') {
			arr['Done'].count += 1;
			arr['Done'].list += number + ',';
			pobj['subtotal'].Done += 1;
		} else if (stat_name.substr(0,14) == 'In Development') {
			arr['Processing'].count += 1;
			arr['Processing'].list += number + ',';
			pobj['subtotal'].Processing += 1;
		} else {
			arr['Unknown'].count += 1;
			arr['Unknown'].list += number + ',';
			pobj['subtotal'].Unknown += 1;
		}
		}
	}
	return pobj;
}

function drawStatus() {
  // Some raw data (not necessarily accurate)
  
	var data = new google.visualization.DataTable();
	data.addColumn('string' , 'Reference');
	data.addColumn('number' , 'Not_Assigned (' + reference_stat['subtotal'].Not_Assigned + ')', 'Not_Assigned');
	data.addColumn('number' , 'Processing (' + reference_stat['subtotal'].Processing + ')', 'Processing');
	data.addColumn('number' , 'Done (' + reference_stat['subtotal'].Done + ')', 'Done');
	data.addColumn('number' , 'Unknown (' + reference_stat['subtotal'].Unknown + ')', 'Unknown');
	for (var pp in reference_stat) {
		if (pp == 'subtotal') {continue;}
		data.addRow([pp , reference_stat[pp]['Not_Assigned'].count , reference_stat[pp]['Processing'].count , reference_stat[pp]['Done'].count , reference_stat[pp]['Unknown'].count]);
	}
	var mview = new google.visualization.DataView(data);
	//mview.hideColumns([4]);

	
  var options = {
	title : 'Recent ' + period_sel + ' <Status> by Reference',
	animation: {
		duration: 1000,
		easing: 'out',
	},
	vAxis: {title: "Ticket Entered"},
	hAxis: {direction: '1',
		slantedText: true,
		slantedTextAngle: '60',
		textStyle : {   fontSize: '12' }
	},
	legend: { position: 'top', maxLines: '4' },
	backgroundColor: '#d3f3d3',
	series: {
		0: { color: '#e02020'},
		1: { color: '#0040a0'},
		2: { color: '#00a040'},
		3: { color: '#00a0a0'}
	},
	isStacked: true
  };

  var chart = new google.visualization.ColumnChart(document.getElementById('chart_status'));
  var button = document.getElementById('change_prio');

  function selectHandler() {
    var selection = chart.getSelection();
	if (selection.length == 0) {
		return;
	}

	var lstr = "";
	var pname = "";
    var item = selection[0];
    if (item.row != null && item.column != null) {
		lstr = reference_stat[data.getValue(item.row,0)][data.getColumnId(item.column)].list;
		pname = data.getValue(item.row,0);
    } else if (item.column != null) {
			//alert("column is not null-" + data.getColumnId(item.column) + "|" + data.getColumnLabel(item.column) );
		
		for (var pp in reference_stat) {
			if (pp == 'subtotal') {continue;}
			lstr += reference_stat[pp][data.getColumnId(item.column)].list;
		}
		pname = "All reference";
	} else { 
      return;
    }
         
 //           alert('The user selected ' + lstr);
	document.getElementById("detail").innerHTML = l2table(f_obj ,lstr);
	var label = data.getColumnLabel(item.column);
	var label_h = label.split("(");
	document.getElementById("detail_subject").innerHTML = 'Detail List of <u>' + pname + '</u> and <i>Status ' + label_h[0] + '</i>';

	window.location.href="#detail_list";
          
  }
  function drawChart() {
  	button.disabled = true;
	google.visualization.events.addListener(chart, 'select', selectHandler);  
	google.visualization.events.addListener(chart , 'ready' , function() {
		button.disabled = false;
		});
	chart.draw(data,options);
  }
  button.onclick = function() {
	reference_stat = collect_reference(f_obj);

	data.setColumnLabel(1 , 'Not_Assigned (' + reference_stat['subtotal'].Not_Assigned + ')');
	data.setColumnLabel(2 , 'Processing (' + reference_stat['subtotal'].Processing + ')');
	data.setColumnLabel(3 , 'Done (' + reference_stat['subtotal'].Done + ')');
	data.setColumnLabel(4 , 'Unknown (' + reference_stat['subtotal'].Unknown + ')');
	var rowIndex;
	rowIndex = 0;
	for (var pp in reference_stat) {
		if (pp == 'subtotal') {continue;}
		data.setValue( rowIndex , 1 , reference_stat[pp]['Not_Assigned'].count);
		data.setValue( rowIndex , 2 , reference_stat[pp]['Processing'].count);
		data.setValue( rowIndex , 3 , reference_stat[pp]['Done'].count);
		data.setValue( rowIndex , 4 , reference_stat[pp]['Unknown'].count);
		rowIndex++;
//		data.addRow([pp , product_stat[pp]['Not_Assigned'].count , product_stat[pp]['Processing'].count , product_stat[pp]['Done'].count , product_stat[pp]['Unknown'].count]);
	}
	drawChart();
  }
	

  
	drawChart();
}
function drawVisualization() {
	drawPriority();
	drawPriority();
	drawStatus();
}
function drawPriority() {
  // Some raw data (not necessarily accurate)
  
	var data = new google.visualization.DataTable();
	data.addColumn('string' , 'Product');
	data.addColumn('number' , 'Urgent and High (' + product_stat['subtotal'].high + ')','high');
	data.addColumn('number' , 'Medium and Low (' + product_stat['subtotal'].other + ')','other');
	for (var pp in product_stat) {
		if (pp == 'subtotal') {continue;}
		data.addRow([pp , product_stat[pp]['high'].count , product_stat[pp]['other'].count]);
	}

	
  var options = {
	title : 'Recent ' + period_sel + ' <Priority> by Product',
	vAxis: {title: "Ticket Entered"},
	hAxis: {direction: '1',
		slantedText: true,
		slantedTextAngle: '60',
		textStyle : {   fontSize: '12' }
	},
	legend: { position: 'top', maxLines: '3' },
	backgroundColor: '#eceddc',
	series: {
		0: { color: '#a04040'},
		1: { color: '#0040a0'}
	},
	isStacked: true
  };

  var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));

  function selectHandler_p() {
    var selection = chart.getSelection();
	if (selection.length == 0) {
		return;
	}
	var lstr = "";
	var pname = "";

    var item = selection[0];
    if (item.row != null && item.column != null) {
		lstr = product_stat[data.getValue(item.row,0)][data.getColumnId(item.column)].list;
		pname = data.getValue(item.row,0);
    } else if (item.column != null) {
		for (var pp in product_stat) {
			if (pp == 'subtotal') {continue;}
			lstr += product_stat[pp][data.getColumnId(item.column)].list;
		}
		pname = "All Products";
	} else {
      return;
    }
  
   //                   alert('The user Priority ' + lstr);
	document.getElementById("detail").innerHTML = l2table(f_obj ,lstr);
	var label = data.getColumnLabel(item.column);
	var label_h = label.split("(");
	document.getElementById("detail_subject").innerHTML = 'Detail List of <u>' + pname + '</u> and <i>Priority ' + label_h[0] + '</i>';

	window.location.href="#detail_list";
          
  }
	google.visualization.events.addListener(chart, 'select', selectHandler_p);  
  	chart.draw(data, options);
}
function do_all()
{
	var date_b = new Date(document.getElementById('date-b').value);
	var date_e = new Date(document.getElementById('date-e').value);
//	var date_c = pre_count(date_e , period_sel);

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
	var number_id = -1;
	text += "<table  width='100%' border='0' cellpadding='5' cellspacing='0'><tr bgcolor='#6e99d4' class='ColumnHeaderClass'>";
	var col_list = sobj.header;

	for (hx in col_list) {
		text += "<td>" + col_list[hx] + "</td>";
		if (col_list[hx] == "Number") {
			number_id = hx;
		}
	}
	text += "</tr>";

	var rows = sobj.rows;
	var even=true;
	for (rx in rows) {
		if (even) {			
				text += "<tr>";
				even = false;
			} else {
				text += "<tr bgcolor='#ecfbeb'>";
				even = true;
			}
		var row1 = rows[rx];
		for (cx in row1) {
			if (cx == number_id) {
				text += "<td class='DataCellClass'><a href='onedefect.php?defectNumber=" + row1[cx] + "'>" + row1[cx] + "</a></td>";
			} else {
				text += "<td class='DataCellClass'>" + row1[cx] + "</td>";
			}
		}
		text += "</tr>";
	}

	text += "</table>";
	return text;
}

function l2table(sobj,list_str) {
	var text = "";
	var number_id = -1;
	text += "<table  width='100%' border='0' cellpadding='5' cellspacing='0'><tr bgcolor='#6e99d4' class='ColumnHeaderClass'>";
	var col_list = sobj.header;

	for (hx in col_list) {
		text += "<td>" + col_list[hx] + "</td>";
		if (col_list[hx] == "Number") {
			number_id = hx;
		}
	}
	text += "</tr>";
	
	var rows = sobj.rows;
	var even=true;
	for (rx in rows) {
		var row1 = rows[rx];
		if (list_str.indexOf(row1[number_id]) >=0 ) {
			if (even) {			
				text += "<tr>";
				even = false;
			} else {
				text += "<tr bgcolor='#ecfbeb'>";
				even = true;
			}
			for (cx in row1) {
				if (cx == number_id) {
					text += "<td class='DataCellClass'><a href='onedefect.php?defectNumber=" + row1[cx] + "'>" + row1[cx] + "</a></td>";
				} else {
					text += "<td class='DataCellClass'>" + row1[cx] + "</td>";
				}
			}
			text += "</tr>";
		}
	}

	text += "</table>";
	return text;
}

function s2table(sobj, date_b , date_e) {
	var text = "";
	var number_id = -1;
	text += "<table><tr>";
	var col_list = sobj.header;

	for (hx in col_list) {
		text += "<td>" + col_list[hx] + "</td>";
		if (col_list[hx] == "Number") {
			number_id = hx;
		}
	}
alert("number_id=" );
	text += "</tr>";

	var rows = sobj.rows;
	for (rx in rows) {
		date_m = new Date(rows[rx][7]);
		if ( date_m > date_b && date_m < date_e) {
		text += "<tr>";
		var row1 = rows[rx];
		for (cx in row1) {
			if (cx == number_id) {
				text += "<td><a href='onedefect.php?defectNumber=" + row1[cx] + "'>" + row1[cx] + "</a></td>";
			} else {
				text += "<td>" + row1[cx] + "</td>";
			}
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
