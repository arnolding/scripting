<!DOCTYPE html>
<html>
<head>
<?php
$inc_path = get_include_path();
$new_inc_path = '..:' . $inc_path;
set_include_path($new_inc_path);
//include "krumo/class.krumo.php";
include "class_ttpro.php";
	

// $last_period = W for week, B for BiWeek, M for month, Y for year
$last_period = $_GET['last'];
$role = $_GET['role'];

if ( $last_period != 'Week' and
	 $last_period != 'BiWeek' and
	 $last_period != 'Month' and
	 $last_period != 'Year' and
	 $last_period != '2Years' ) {
	$last_period = 'BiWeek';
}

$ttpro_filter = 'Newly Found ' . substr($last_period,0,1);
if ($last_period == '2Years' ) {
	$ttpro_filter = 'Newly Found 2Y';
}
$arr = ttpro::newfound_ae_responsible($ttpro_filter);
$arr_cnt = 	count($arr['recordlist']['records']['item']);
	

	
//	if ($arr_cnt > 1500) {
//		$arr10 = array_slice($arr['recordlist']['records']['item'] , 1500);
//		$arr1['recordlist']['records']['item'] = $arr10;
//		$arr['recordlist']['records']['item'] = array_slice($arr['recordlist']['records']['item'] , 0 , 1500);
//	}


//	krumo($arr);


?>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
<title>Engineer Classification</title>
<meta name="author" content="Arnold Ho">
<link rel="stylesheet" href="../Pikaday-1.3.2/css/pikaday.css">
<link rel="stylesheet" href="../Pikaday-1.3.2/css/site.css">

<STYLE>  
	.ColumnHeaderClass	{
		font-family: Arial, sans-serif;
		font-weight: bold;
		font-size: 16px;
		COLOR: #ffffff;
	}
	.DataCellClass	{
		font-family: Arial, sans-serif;
		font-size: 14px;
		COLOR: #000000;
	}
td.h2_bottom {
	vertical-align: bottom;
}
/* Dropdown Button */
.dropbtn {
    background-color: #4CAF50;
    color: white;
    padding: 2px;
    font-size: 12px;
    border: none;
    cursor: pointer;
}

/* The container <div> - needed to position the dropdown content */
.dropdown {
    position: relative;
    display: inline-block;
}

/* Dropdown Content (Hidden by Default) */
.dropdown-content {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    box-shadow: 0px 8px 10px 0px rgba(0,0,0,0.2);
    z-index: 1;
}

/* Links inside the dropdown */
.dropdown-content a {
    color: black;
    padding: 2px 10px;
    text-decoration: none;
    display: block;
}

/* Change color of dropdown links on hover */
.dropdown-content a:hover {
	background-color: #007161;
	color: white;
}

/* Show the dropdown menu on hover */
.dropdown:hover .dropdown-content {
    display: block;
}

/* Change the background color of the dropdown button when the dropdown content is shown */
.dropdown:hover .dropbtn {
    background-color: #3e8e41;
}
	      </STYLE>

<link rel="stylesheet" href="../js_sorter/style.css" />
<link rel="stylesheet" href="header.css" />
<script src="header.js"></script>
</head>
<body onload="show_header('<?php echo $_COOKIE[db_mediawikiUserName] ?>')">

<table border="0px solid black" width="100%"  ><tr><td width="50%" class="h2_bottom" rowspan="2" align=right><font size=6>AE Responsible</font></td>
<td colspan="2" valign=bottom align=right id='duration_selector'>
</td></tr>
<tr><td bgcolor='#ffa323' valign=bottom align=right> 
<label for="datepicker">Date Entered From:</label>
    <input type="button" id="date-b" >
<label for="datepicker">To:</label>
    <input type="button" id="date-e" >
</td><td rowspan="2" bgcolor='#ffa323' align="center"><button style="background-color:lightgreen;height:40" id="change_prio" onClick="priority_redraw()" >Redraw</button></td></tr>
<tr>
<td align=right bgcolor='#ffa323'><button bgcolor='#d3f3d3' onClick="display_legend()">Legend</button>
<button bgcolor='#d3f3d3' onClick="window.location.href='#st_list';">Table</button>
<button bgcolor='#d3f3d3' onClick="add_tcad()">TCAD</button> &nbsp;
<div class="dropdown">
  <button class="dropbtn">Sort x-axis</button>
  <div class="dropdown-content">
    <a onclick="sorton()">Link 1</a>
    <a onclick="sorton()">Link 2</a>
    <a onclick="sorton()">Link 3</a>
  </div>
</div></td>
<td valign=bottom align=right bgcolor='#ffa323'>
<b>Priority:</b>&nbsp;
<input type=checkbox name=Urgent id="Urgent"  checked>Urgent
<input type=checkbox name=High id="High" checked >High
<input type=checkbox name=Medium id="Medium" >Medium
<input type=checkbox name=Low id="Low"  >Low
<input type=checkbox name=not_set id="not_set" >Not Set
</td>
</tr></table>
<div id="chart_status" ></div><br>
<!--- <div id="chart_priority" style="width: 1200px; height: 500px;"></div> -->
<br><div><a name='st_list'></a></div>
<h4 align='center'>[&nbsp;&nbsp;&nbsp;[&nbsp;&nbsp;[&nbsp;Click on the head row to sort, click on cell to show detail list&nbsp;]&nbsp;&nbsp;]&nbsp;&nbsp;&nbsp;]</h4><br>
<p id="statistics"></p>
<hr>

<div id='detail_list'><a name='detail_list'></a></div> 
<h3 id='detail_subject'>Detail List in Duration</h3>
<p id="detail"></p>


	<script src="../sweetalert-master/dist/sweetalert.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../sweetalert-master/dist/sweetalert.css">
    <script src="../Pikaday-1.3.2/pikaday.js"></script>
	<script src="https://www.google.com/jsapi"></script>
	<script type="text/javascript" src="../js_sorter/script.js"></script>
    <script>

	duration_selector();
function sorton()
{
	var msg_txt = 'Date Entered range exceeds Duration\n';

	swal({
		title: 'Sort On',
		text: msg_txt,
		html:false,
		timer: 3000
	} );
}
function display_legend()
{
	var msg_txt = '<table><tr><td bgcolor="#f02020">&nbsp;&nbsp;</td><td align="left">NotReviewed</td>';
	msg_txt += '<td align="left">Status Not Assigned and Disposition Not Reviewed</td></tr>';

	msg_txt += '<tr><td bgcolor="#ffbf40">&nbsp;&nbsp;</td><td align="left">Reviewed</td>';
	msg_txt += '<td align="left">Status Not Assigned but Disposition Set</td></tr>';

	msg_txt += '<tr><td bgcolor="#00a040">&nbsp;&nbsp;</td><td align="left">Processing</td>';
	msg_txt += '<td align="left">Status Assigned, not yet Fixed nor Verified</td></tr>';

	msg_txt += '<tr><td bgcolor="#a53535">&nbsp;&nbsp;</td><td align="left">ToBeVerified</td>';
	msg_txt += '<td align="left">Status Fixed, or Released to Testing</td></tr>';

	msg_txt += '<tr><td bgcolor="#0040f0">&nbsp;&nbsp;</td><td align="left">Done</td>';	
	msg_txt += '<td align="left">Status Close(Verified)</td></tr>';

	msg_txt += '</table>';
	swal({
		title: 'Status Legend',
		text: msg_txt,
		html:true,
		timer: 5000
	} );

//		0: { color: '#f02020'},
	//	1: { color: '#ffbf40'},
		//2: { color: '#00a040'},
//		3: { color: '#a53535'},
	//	4: { color: '#0040f0'}
}

function period_message()
{
	var date_b = new Date(document.getElementById('date-b').value);
	var date_e = new Date(document.getElementById('date-e').value);
	var msg_txt = 'Date Entered range exceeds Duration\n';
	if (date_b < period_ttpro.begin) {
		msg_txt += 'Date From is reset to ';
		var b_datestr = period_ttpro.begin.toDateString();
		msg_txt += b_datestr;;
		document.getElementById('date-b').value = b_datestr;
	}

	swal({
		title: 'Date Entered range Reset',
		text: msg_txt,
		html:false,
		timer: 5000
	} );
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
	var pre_2year;
	if (dt.getMonth() == 1 && dt.getDate() == 29) {
		pre_2year = new Date(dt.getFullYear() - 2 , dt.getMonth(), dt.getDate() - 1);
	} else {
		pre_2year = new Date(dt.getFullYear() - 2 , dt.getMonth(), dt.getDate() - 1);
	}
	var pre_b;
	if (pe == 'Week') {pre_b = pre_week;}
	if (pe == 'BiWeek') {pre_b = pre_2week;}
	if (pe == 'Month') {pre_b = pre_month;}
	if (pe == 'Year') {pre_b = pre_year;}
	if (pe == '2Years') {pre_b = pre_2year;}
	return pre_b;
}
function get_duration(sobj) {
	var pe = new Object();
	var date_id = -1;
	var hx;
	var col_list = sobj.header;
	var rows = sobj.rows;

	for (hx in col_list) {
		if (col_list[hx] == "Date Entered") {
			date_id = hx;
		}
	}
console.log("index of Date Entered " , date_id);

	var date_b = new Date();
	var date_e = new Date();
	
	for (rx in rows) {
		date_m = new Date(rows[rx][date_id]);
		if ( date_m < date_b) {
			date_b = date_m;
		}
		if ( date_m > date_e) {
			date_e = date_m;
		}
	}

	pe.begin = date_b;
	pe.end = date_e;

	return pe;
}

	
	var period_sel = '<?php echo $last_period; ?>';
	var ttpro = <?php echo json_encode($arr); ?>;
	var s_obj = weeknum(ttpro);
	var period_ttpro = get_duration(s_obj);
	var dt = new Date();
	var picker_e = new Pikaday({
        showWeekNumber: true,
        field: document.getElementById('date-e'),
        firstDay: 1,
        minDate: new Date('2000-01-01'),
        maxDate: new Date('2020-12-31'),
		defaultDate: period_ttpro.end,
		setDefaultDate: true,
        yearRange: [2000, 2020]
    });
	
//	var pre_b = pre_count(dt , period_sel);
    
	var picker_b = new Pikaday({
        showWeekNumber: true,
        field: document.getElementById('date-b'),
        firstDay: 1,
        minDate: new Date('2000-01-01'),
        maxDate: new Date('2020-12-31'),
		defaultDate: period_ttpro.begin,
		setDefaultDate: true,
        yearRange: [2000, 2020]
    });

	Date.prototype.getWeek = function() {
        var onejan = new Date(this.getFullYear(), 0, 1);
        return Math.ceil((((this - onejan) / 86400000) + onejan.getDay() + 1) / 7);
    }


	var temp = 0;

	
	var date_b = new Date(document.getElementById('date-b').value);
	var date_e = new Date(document.getElementById('date-e').value);
	var f_obj = filter_by_date(s_obj , date_b , date_e);
	

	var product_stat = collect_product(f_obj);
	var sorter = new TINY.table.sorter("sorter");
	sorter.head = "head";
	sorter.asc = "asc";
	sorter.desc = "desc";
	sorter.even = "evenrow";
	sorter.odd = "oddrow";
	sorter.evensel = "evenselected";
	sorter.oddsel = "oddselected";
	sorter.paginate = false;
	sorter.currentid = "currentpage";
	sorter.limitid = "pagelimit";
//document.getElementById("statistics").innerHTML = text;
	document.getElementById("detail").innerHTML = f2table(f_obj );

	show_statistics();

	if (typeof google !== 'undefined' ) {
	google.load("visualization", "1", {packages:["corechart"]});
	google.setOnLoadCallback(drawStatus);
	}

function priority_redraw() {
	console.log("INTO priority_redraw");
	if (typeof google === 'undefined' ) {
		product_stat = collect_product(f_obj);
		show_statistics();
	}
}
function show_statistics() {
	var stat_node = document.getElementById('statistics');
	if (stat_node.hasChildNodes()) {
		stat_node.removeChild(stat_node.firstChild)
	}
	stat_node.appendChild(table_statistics());
	sorter.init("sst",1);
	sorter.wk(1);
}
function add_tcad() {
	var searchObj = {};
	var query_str = window.location.search;
console.log("add_tcad" , query_str);
	var queries = query_str.replace(/^\?/,'').split('&');
	var anb;
	for ( i = 0 ;i < queries.length ; i++) {
		anb = queries[i].split('=');
		if (anb[1]) {
			searchObj[anb[0]] = anb[1];
		}
	}
	if ( searchObj['role'] && searchObj['role']=='tcad') {
// do nothing
	} else {
		if (Object.keys(searchObj).length > 0) {
			window.location.href+="&role=tcad";
		} else {
			window.location.href+="?role=tcad";
		}
	}

}
function duration_selector() {
	var ele_dur = document.getElementById('duration_selector');
/*
	var str = '<b>Duration: </b><input type=radio name=period style="color:green" onClick="location.href=\'byeng.php?last=Week\'" value="Week" <?php if ($last_period == "Week") echo "checked" ?> >Week';
	str += '<input type=radio name=period style="color:green" onClick="location.href=\'byeng.php?last=BiWeek\'" value="BiWeek" <?php if ($last_period == "BiWeek") echo "checked" ?> >BiWeek';
	str += '<input type=radio name=period style="color:blue" onClick="location.href=\'byeng.php?last=Month\'" value="Month" <?php if ($last_period == "Month") echo "checked" ?>>Month';
	str += '<input type=radio name=period style="color:black" onClick="location.href=\'byeng.php?last=Year\'" value="Year" <?php if ($last_period == "Year") echo "checked" ?>>Year';
	ele_dur.innerHTML = str;
*/

	var bold = document.createElement("B");
	var label = document.createTextNode('Duration: ');
	bold.appendChild(label);
	ele_dur.appendChild(bold);
	var dur_arr = ['Week','BiWeek','Month','Year','2Years'];
	var query_str = window.location.search;
console.log(query_str);
	var searchObj = {};
	var last_period;
	var queries = query_str.replace(/^\?/,'').split('&');
	for ( i = 0 ;i < queries.length ; i++) {
		anb = queries[i].split('=');
		if (anb[1]) {
			searchObj[anb[0]] = anb[1];
		}

	}

	last_period = searchObj['last'];
	if (dur_arr.indexOf(last_period) < 0) {
		last_period = 'BiWeek';
	}


	for (var count in dur_arr) {
		var but = document.createElement("input");
		var txt = document.createTextNode(dur_arr[count]);
		but.value = dur_arr[count];
		but.id = dur_arr[count];
		but.type = 'radio';
		but.name = 'period';
		if (last_period == dur_arr[count]) {
			but.checked = true;
		} else {
			but.checked = false;
		}
		but.onclick = function() {
			//location.href='byeng.php?last=' + dur_arr[count];
			var radios = document.getElementsByName("period");
			for ( var i = 0; i < radios.length ; i++) {
				if (radios[i].checked) {
//					alert(radios[i].value);
					var goto_loc;
					if (query_str.indexOf('last') >= 0) {
						goto_loc = query_str.replace(/last=(\w+)/ , "last=" + radios[i].value);
						location.href = 'by_ae_responsible.php' + goto_loc;
					} else if (query_str.indexOf('?') >= 0) {
						location.href = window.location + '&last=' + radios[i].value;
					} else {
						location.href = window.location + '?last=' + radios[i].value;
					}
				}
			}

		}
		ele_dur.appendChild(but);
		ele_dur.appendChild(txt);
	}
}
function filter_by_date(sobj , date_b , date_e)
{
	var filtered_tbl = new Object();
	filtered_tbl.header = sobj.header;
	var col_list = sobj.header;
	var rows_arr = [];
	var rows = sobj.rows;
	var date_id = -1;

	for (hx in col_list) {
		if (col_list[hx] == "Date Entered") {
			date_id = hx;
		}
	}
	
	for (rx in rows) {
		date_m = new Date(rows[rx][date_id]);
		if ( date_m >= date_b && date_m <= date_e) {
			rows_arr[rows_arr.length] = rows[rx];
		}
	}
	filtered_tbl.rows = rows_arr;

	return filtered_tbl;
}
function collect_product(sobj)
{
	var pobj = new Object();
	var col_list = sobj.header;
	var product_id = -1;
	var priority_id = -1;
	var status_id = -1;
	var number_id = -1;
	var date_id = -1;
	var disposition_id = -1;
	var ae_responsible_id = -1;
	var fixed_by_user_id = -1;

	for (hx in col_list) {
		if (col_list[hx] == "Date Entered") {
			date_id = hx;
		}
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
		if (col_list[hx] == "Disposition") {
			disposition_id = hx;
		}
		if (col_list[hx] == "AE Responsible") {
			ae_responsible_id = hx;
		}
		if (col_list[hx] == "Fixed By User") {
			fixed_by_user_id = hx;
		}
	}
	var date_b = new Date(document.getElementById('date-b').value);
	var date_e = new Date(document.getElementById('date-e').value);
console.log("begin and end ", date_b, date_e);
	if ((date_b < period_ttpro.begin) ||
		(date_e > period_ttpro.end)) {
		period_message();
		return;
	}

	pobj['subtotal'] = {high:0, other:0, NotReviewed:0, Reviewed:0, Processing:0, ToBeVerified:0, Done:0, Unknown:0};
	var rows = sobj.rows;
	var display_count = 1;
	for (rx in rows) {
		var prod_name = rows[rx][product_id];
		var prio_name = rows[rx][priority_id];
		var stat_name = rows[rx][status_id];
		var number = rows[rx][number_id];
		var disposition = rows[rx][disposition_id];
		var ae_responsible = rows[rx][ae_responsible_id];
		var date_m = new Date(rows[rx][date_id]);
		var fixed_by_user = rows[rx][fixed_by_user_id];

		if (display_count > 0) {
console.log("display_count date_m " , display_count , date_m);
		display_count--;
		}

		if (prio_name == '') {
			prio_name = 'Not_set';
		}

		var eng_name = ae_responsible;
		
		if (!(pobj[eng_name])) {
			var arr = new Object();
			arr['all_total'] = 0;
			arr['Total'] = {count: 0, list:''};  // total for a product 
			arr['high'] = {count: 0, list:''};
			arr['other'] = {count: 0, list:''};
			arr['NotReviewed'] = {count: 0, list:''};  // open, not assigned, not reviewed
			arr['Reviewed'] = {count: 0, list:''}; // open, not assigned, but reviewed
			arr['Processing'] = {count: 0, list:''}; // open, assigned or In Development
			arr['ToBeVerified'] = {count: 0, list:''}; // open, assigned or In Development
			arr['Done'] = {count: 0, list:''}; // Fixed, Closed, Released to Testing
			arr['Unknown'] = {count: 0, list:''}; // status unknown or not classified
			pobj[eng_name] = arr;
		}
		var arr = pobj[eng_name];
		arr['all_total'] += 1;
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

		if ((prio_setting.indexOf(prio_name) >= 0)  &&
			(date_m >= date_b) &&
			(date_m <= date_e)) {
			arr['Total'].count += 1;
			arr['Total'].list += number + ',';
		if (stat_name.substr(0,4) == 'Open') {
			if  ( stat_name.indexOf("not assigned") >= 0) {
				if (disposition == 'Not Reviewed' || disposition == '') {
					arr['NotReviewed'].count +=1;
					arr['NotReviewed'].list += number + ',';
					pobj['subtotal'].NotReviewed += 1;
				} else {
					arr['Reviewed'].count += 1;
					arr['Reviewed'].list += number + ',';
					pobj['subtotal'].Reviewed += 1;
				}
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
			arr['ToBeVerified'].count += 1;
			arr['ToBeVerified'].list += number + ',';
			pobj['subtotal'].ToBeVerified += 1;
		} else if (stat_name.substr(0,8) == 'Released') {
			arr['ToBeVerified'].count += 1;
			arr['ToBeVerified'].list += number + ',';
			pobj['subtotal'].ToBeVerified += 1;
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
console.log("rx " , rx);
	return pobj;
}

function drawStatus() {
  // Some raw data (not necessarily accurate)
  	var seq = [];
	for (var pp in product_stat) {
		if (pp == 'subtotal') {continue;}
		seq.push([pp, product_stat[pp]['NotReviewed'].count]);
	}
	seq.sort(function(a,b) {
		return b[1] - a[1];});

	var data = new google.visualization.DataTable();
	data.addColumn('string' , 'Product');
	data.addColumn('number' , 'NotReviewed (' + product_stat['subtotal'].NotReviewed + ')', 'NotReviewed');
	data.addColumn('number' , 'Reviewed (' + product_stat['subtotal'].Reviewed + ')', 'Reviewed');
	data.addColumn('number' , 'Processing (' + product_stat['subtotal'].Processing + ')', 'Processing');
	data.addColumn('number' , 'ToBeVerified (' + product_stat['subtotal'].ToBeVerified + ')', 'ToBeVerified');
	data.addColumn('number' , 'Done (' + product_stat['subtotal'].Done + ')', 'Done');
//	data.addColumn('number' , 'Legend' , 'Legend');
//	data.addColumn('number' , 'Unknown (' + product_stat['subtotal'].Unknown + ')', 'Unknown');
	var i;
	for (i = 0 ; i < seq.length ; i++) {
//	for (var pp in product_stat) {
		var pp = seq[i][0];

		data.addRow([pp , product_stat[pp]['NotReviewed'].count , product_stat[pp]['Reviewed'].count , product_stat[pp]['Processing'].count , product_stat[pp]['ToBeVerified'].count ,product_stat[pp]['Done'].count ]); // /*, product_stat[pp]['Unknown'].count]*/);
	}
	var mview = new google.visualization.DataView(data);
	//mview.hideColumns([4]);

	var chart_width = seq.length * 30 + 300;
	if (chart_width < 1200 ) {
		chart_width = 1200;
	}
	
console.log("chart width " , chart_width);
  var options = {
	fontSize: 20,
	title : 'Recent ' + period_sel + ' <Status> by Product',
	width : chart_width,
	chartArea: {width: chart_width-200},
	height : 500,
	animation: {
		duration: 1000,
		easing: 'out',
	},
	vAxis: {
		format: '',
		title: "Ticket Entered",
		gridlines: { count: 4},
		textStyle : {   fontSize: '14' }
	},
	hAxis: {direction: '1',
		slantedText: true,
		slantedTextAngle: '60',
		textStyle : {   fontSize: '14' }
	},
	legend: { position: 'top', maxLines: '4', textStyle: {fontSize: 14 } },
	backgroundColor: '#d3f3d3',
	series: {
		0: { color: '#f02020'},
		1: { color: '#ffbf40'},
		2: { color: '#00a040'},
		3: { color: '#a53535'},
		4: { color: '#0040f0'}
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
		lstr = product_stat[data.getValue(item.row,0)][data.getColumnId(item.column)].list;
		pname = data.getValue(item.row,0);
    } else if (item.column != null) {
			//alert("column is not null-" + data.getColumnId(item.column) + "|" + data.getColumnLabel(item.column) );
		if (data.getColumnId(item.column) == 'Legend') {
			window.open('legend.html' , 'New Found Tickets Legend');
		} else {
			for (var pp in product_stat) {
				if (pp == 'subtotal') {continue;}
				lstr += product_stat[pp][data.getColumnId(item.column)].list;
			}
			pname = "All Products";
		}
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
	product_stat = collect_product(f_obj);
	show_statistics();
	var seq = [];
	for (var pp in product_stat) {
		if (pp == 'subtotal') {continue;}
		seq.push(pp);
	}
	seq.sort();
	data.setColumnLabel(1 , 'NotReviewed (' + product_stat['subtotal'].NotReviewed + ')');
	data.setColumnLabel(2 , 'Reviewed (' + product_stat['subtotal'].Reviewed + ')');
	data.setColumnLabel(3 , 'Processing (' + product_stat['subtotal'].Processing + ')');
	data.setColumnLabel(4 , 'ToBeVerified (' + product_stat['subtotal'].ToBeVerified + ')');
	data.setColumnLabel(5 , 'Done (' + product_stat['subtotal'].Done + ')');
//	data.addColumnLabel(5 , 'Legend');
//	data.setColumnLabel(4 , 'Unknown (' + product_stat['subtotal'].Unknown + ')');
	var rowIndex;
	rowIndex = 0;
	var i;
	for (i = 0 ; i < seq.length ; i++) {
//	for (var pp in product_stat) {
		var pp = seq[i];

		data.setValue( rowIndex , 1 , product_stat[pp]['NotReviewed'].count);
		data.setValue( rowIndex , 2 , product_stat[pp]['Reviewed'].count);
		data.setValue( rowIndex , 3 , product_stat[pp]['Processing'].count);
		data.setValue( rowIndex , 4 , product_stat[pp]['ToBeVerified'].count);
		data.setValue( rowIndex , 5 , product_stat[pp]['Done'].count);
//		data.setValue( rowIndex , 4 , product_stat[pp]['Unknown'].count);
		rowIndex++;
//		data.addRow([pp , product_stat[pp]['NotReviewed'].count , product_stat[pp]['Reviewed'].count ,product_stat[pp]['Processing'].count , product_stat[pp]['Done'].count , product_stat[pp]['Unknown'].count]);
	}
	drawChart();
  }
	drawChart();
}



function weeknum(ttpro_obj) {
	var simple_obj = new Object();
	var header_arr = [];

var arr_cnt_js = <?php echo $arr_cnt; ?>;
//var ttpro1 = <?php if ($arr_cnt > 1500) { echo json_encode($arr1);} else { echo '3';} ?>;
//	if (ttpro1 != 3) {
//		ttpro['recordlist']['records']['item'] = ttpro['recordlist']['records']['item'].concat(ttpro1['recordlist']['records']['item']);
//	}
console.log("arr_cnt " , arr_cnt_js);

	var col_list = ttpro_obj['recordlist']['columnlist']['item'];
	for (hx in col_list) {
		header_arr[hx] = col_list[hx].name;
	}
//	header_arr[hx + 1] = "Weeknum";
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
//		var dt = new Date(row_arr[cx]);
//		var w2 = dt.getWeek();
//		if (w2 < 10) { w2 = '0' + w2;}
//		row_arr[cx + 1] = dt.getFullYear() + ",W" + w2;
		rows_arr[rows_arr.length] = row_arr;
	}
console.log("in weeknum rx " , rx , "length " , rows_arr.length);
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
	var status_color = {
		'Open': '#FFFFAB',
		'Open (Verify Failed)': '#FFFFAB',
		'Open (Re-Opened)': '#FFFFAB',
		'In Development': '#FFFFAB',
		'Pause': '#CC8500',
		'Fixed': '#B5FFB5',
		'Released to Testing': '#B5FFB5',
		'Closed': '#B5FFB5',
		'Closed (Fixed)': '#B5FFB5',
		'Closed (Verified)': '#B5FFB5'
	};
	var text = "";
	var number_id = -1;
	var status_id = -1;
	text += "<table  style='width:100%;border:0;cellpadding:5;cellspacing:0;margin=15px 0px;'><tr bgcolor='#6e99d4' class='ColumnHeaderClass'>";
	var col_list = sobj.header;

	for (hx in col_list) {
		text += "<td>" + col_list[hx] + "</td>";
		if (col_list[hx] == "Number") {
			number_id = hx;
		}
		if (col_list[hx] == "Status") {
			status_id = hx;
		}
	}
	text += "</tr>";
	
	var rows = sobj.rows;
	var even=true;
	for (rx in rows) {
		var row1 = rows[rx];
		var status = row1[status_id];
		status = status.split(',')[0]
		if (list_str.indexOf(row1[number_id]) >=0 ) {
//			if (even) {			
//				text += "<tr>";
//				even = false;
//			} else {
//				text += "<tr bgcolor='#ecfbeb'>";
//				even = true;
//			}
			text += "<tr bgcolor='" + status_color[status] + "'>";
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




function table_statistics()
{
	var sst_top = document.createElement("Table");
	sst_top.id = 'sst';
	sst_top.className = 'sortable';

	var header = sst_top.createTHead();

	var tr0 = header.insertRow();
	var hdr = ['Engineer' ,  'NotReviewed' , '%' , 'Reviewed' , '%' , 'Processing', '%', 'ToBeVerified', '%' , 'Done' , '%' ,'Total' ];
	for ( var i = 0 ; i < hdr.length ; i++) {
		var td0 = tr0.insertCell();
		td0.innerHTML = "<h4>" + hdr[i] + "</h4>";
	}

	var tbd = document.createElement("TBody");
	tbd.id = "sst_body";
	sst_top.appendChild(tbd);

	var seq = [];
	var pstat = new Object();
	var cell_s = ['NotReviewed' , 'Reviewed' , 'Processing' , 'ToBeVerified' , 'Done']; 
	var cellrank = ['top_nr', 'top_nrp'];
	var color_s = ['#f02020','#ffbf40','#00a040' , '#a53535','#0040f0'];

	for (var pp in product_stat) {
		if (pp == 'subtotal') {continue;}
		seq.push(pp);
		var arr = new Object();
		var total = product_stat[pp]['Total'].count;
		if (total == 0) {continue;}
		for (var j = 0 ; j < cell_s.length ; j++) {
			arr[cell_s[j]] = product_stat[pp][cell_s[j]].count;
			arr[cell_s[j] + '_per'] = Math.floor(product_stat[pp][cell_s[j]].count / total * 100.0);
		}

		arr['Total'] = total;
		pstat[pp] = arr;
	}
	seq.sort();
	var top_nr = Object.keys(pstat).sort(function(a,b) {return pstat[a]['NotReviewed'] - pstat[b]['NotReviewed']});
	
	

	for (var i = top_nr.length - 1 ; i >=0  ; i--) {
		var tr1 = tbd.insertRow();
		var pp = top_nr[i];
		var td0 = tr1.insertCell();
		td0.innerHTML = pp;

		for ( var j = 0 ; j < cell_s.length; j++) {
			var td1 = tr1.insertCell();
			td1.innerHTML = "<font color=" + color_s[j] + ">" + pstat[pp][cell_s[j]] + "</font>";
			td1.id = pp + '|' + cell_s[j];

			var td2 = tr1.insertCell();
			td2.innerHTML = "<font color=" + color_s[j] + ">" + pstat[pp][cell_s[j]+ '_per'] + "%</font>";
			td2.style.textAlign = 'center';
		}
			var td3 = tr1.insertCell();
			td3.innerHTML = pstat[pp]['Total'];
			td3.id = pp +'|Total';
	}

	var td_event = function () {
		var sst_top_event = document.getElementById('sst');
		var tbd = sst_top_event.tBodies[0];

		for (var i = 0 ; i < tbd.rows.length ; i++) {
			var pd = tbd.rows[i].cells[0].innerHTML;
			for (var j=1 ; j < tbd.rows[i].cells.length ; j+=2) {
				tbd.rows[i].cells[j].onclick = function() {
					var para = this.id;
console.log(para);
					var pararr = para.split('|');
					var pd = pararr[0];
					var st = pararr[1];
					var lstr = 	product_stat[pd][st].list;
					if (lstr.length > 1) {
						document.getElementById("detail").innerHTML = l2table(f_obj ,lstr);
						if (st == 'Total') { st = 'All';}
						document.getElementById("detail_subject").innerHTML = 'Detail List of <u>' + pd + '</u> and <i>Status ' + st + '</i>';
						window.location.href="#detail_list";
					}
				}
			}
		}
	}
	sorter.callback = td_event;

	return sst_top;
}

function tcad_role(eng_name,role)
{
	var emp0 = {
		"Marnoch, Chris" : "eu_sales,eu_jobs,uk" ,
"Tanaka, Koichiro" : "jp_apps,jpsysadmin,jp_eng,jp_license" ,
"Iino, Yoshihisa" : "jp_apps,jp_eng" ,
"Macfarlane, Keith" : "ca_eng" ,
"Broadbent, Steve" : "ma_apps" ,
"Van Breugel, Martijn" : "sysadmin" ,
"Chen, Yun Chiao" : "ca_apps" ,
"Ljepojevic, Neboysha" : "eu_eng,uk,tcad_eng" ,
"Basavarajaiah, Sunil" : "ca_apps,pdkdev" ,
"Kameda, Naoto" : "jp_sales" ,
"Sajima, Kazunori" : "jp_apps,ky_apps,jp_eng,jp_tcad" ,
"Huryi, Pavel" : "ca_eng" ,
"Wilson, Stephen" : "eu_eng,uk" ,
"Blanchette, Michel" : "ca_apps" ,
"Chiu, Oscar" : "tw_apps" ,
"Bradburn, Brian" : "ca_eng" ,
//"Admin" : "" ,
"Temkin, Misha" : "ca_eng,ca_apps,tcad_eng" ,
"Wang, Lei" : "ca_apps,webmaster" ,
"Nicklaw, Chris" : "ca_apps" ,
"Ota, Kazuki" : "jp_apps,ky_apps" ,
"Yu, Hongyi" : "ca_eng,product_owners,tcad_eng" ,
"Roschke, Matthias" : "ca_eng,tcad_eng" ,
"Navarro, Dondee" : "jp_eng" ,
"Green, David" : "eu_apps,uk" ,
"Lee, Sarah" : "QA" ,
"Okashita, Koichi" : "ca_apps" ,
"Klimovich, Konstantin" : "ca_eng" ,
"Lobach, Siarhei" : "ca_eng" ,
"Chen, Howard" : "tw_apps" ,
"Azarenok, Alex" : "ca_eng" ,
"Fujihara, Shinichi" : "jp_apps,ky_apps,jp_eng" ,
"Martynchik, Viktar" : "ca_eng,product_owners" ,
"Plews, Andy" : "eu_eng,uk,tcad_eng" ,
"Hwang, MG" : "kr_sales" ,
"Castellon, Erick" : "ca_mktg,webmaster" ,
"Kanno, Yoshinori" : "jp_apps,ky_apps,jp_eng" ,
"Hatano, Yasuharu" : "jp_apps" ,
"Lejmi, Samir" : "ca_eng,product_owners" ,
"Djuric, Zoran" : "eu_eng" ,
"Smith, Matthew" : "ca_eng,product_owners,tcad_eng" ,
"Fujinaga, Masato" : "jp_apps,jp_eng,jp_tcad" ,
"Yamamoto, Yoshihiko" : "jp_apps,jp_eng" ,
"Vedavyasan, Arunkumar" : "ca_apps,pdk,pdkdev" ,
"Akkapeddi, Naga" : "ca_apps,pdk,pdkdev" ,
"Nejim, Ahmed" : "eu_apps,uk" ,
"Dutton, David" : "ca_sales,ca_exemgt" ,
"Kuwagaki, Takeshi" : "jp_apps,jp_eng" ,
"Kim, Jin-Young" : "kr_apps" ,
"Iontcheva, Ana" : "ca_eng,tcad_eng" ,
"Han, Ji-Woong" : "kr_apps" ,
"Yeh, Mars" : "tw_apps" ,
"Sano, Takeshi" : "jp_apps,jp_eng" ,
"Deal, Roger" : "ca_eng,license" ,
"Eastlick, Mark" : "tcad_eng,eu_eng,uk,tcad_eng" ,
"Ojima, Yuji" : "jpsysadmin,jp_mktg" ,
"Nalobau, Dzmitry" : "ca_eng" ,
"Duluc, Jean-Batiste" : "eu_eng" ,
"Lee, Suping" : "tw_apps" ,
"Shaw, Colin" : "ca_apps" ,
"Hylin, Carl" : "ca_eng,tcad_eng" ,
"Yanagisawa, Hideya" : "jp_sales" ,
"Kimpton, Derek" : "ca_apps" ,
//"Total  " : "" ,
"Hori, Naotomo" : "jp_apps,ky_apps" ,
"French, Andy" : "ca_eng,product_owners" ,
"Babayan, Artem" : "eu_eng,uk,tcad_eng" ,
"West, Andrew" : "ca_eng" ,
"Perepelkin, Vitaly" : "ca_eng" ,
"Hu, Funway" : "tw_apps" ,
"Binder, Thomas" : "eu_eng,product_owners" ,
"Xu, Tao" : "ca_eng" ,
"Foelsche, Peter" : "ca_eng" ,
"Suvorov, Vasily" : "eu_eng,uk" ,
"Tu, Chiping" : "tw_sales,tw_apps" ,
"Heaton, Bill" : "ca_sales" ,
"Smith, David" : "ca_eng,product_owners" ,
"Wang, Dong" : "tx_apps" ,
"Guichard, Eric" : "ca_eng,ca_apps,product_owners" ,
"Tak, Nam-Kyun" : "kr_apps" ,
"Nahvi, Yawar" : "ca_apps,pdkdev" ,
"Shirai, Katsuya" : "jp_apps,jp_tcad" ,
"Linden, Peter" : "tx_sales" ,
"Ohyama, Tadahiro" : "jp_sales" ,
"Script, TTPro" : "ma_apps" ,
"Morikawa, Yoji" : "jp_apps,jp_eng,jp_tcad" ,
"Ho, Arnold" : "QA" ,
"Zharkou, Raman" : "ca_eng" ,
"Nanda, Amit" : "ca_mktg" ,
"Lee, Won-Seok" : "kr_apps" ,
"Denn, Ed" : "ma_sales" ,
"Kashimura, Kaoru" : "jp_apps,jp_eng" ,
"Hasegawa, Atsushi" : "jp_apps,jp_eng" ,
"Babko, Yury" : "ca_eng" ,
"Chan, Chih-Ying" : "tw_apps" ,
"Jet, Thierry" : "eu_eng,product_owners" ,
"Pashkovich, Andrei" : "ca_eng,product_owners" ,
"Chang, Kevin" : "sg_sales,sg_apps" ,
"Pettazzi, Stefano" : "eu_apps,uk" ,
"Fan, Wonder" : "tw_apps" ,
"Scott, Ian" : "eu_eng,uk,tcad_eng" ,
"Ohno, Hikari" : "jp_sales,jpacademic" ,
//"Admin, Backup" : "" ,
"Hitomi, Kunio" : "jp_apps" ,
"Mizoguchi, Naomi" : "jp_sales,jp_license,jpacademic" ,
//"Name" : "" ,
"Vallurupalli, Vaishali" : "ca_apps,pdkdev" ,
"Petrikovski, Viktor" : "ca_eng" ,
"Johnson, JK" : "ma_sales" ,
"Hughes, Robert" : "ca_eng" ,
"Sato, Yuki" : "jp_apps" ,
"Li, Debin" : "jp_apps,jp_eng,jp_tcad" ,
"Zhao, Qing Da" : "sg_apps,sg_sales" ,
"Permthammasin, Komet" : "ca_apps" ,
"Chiu, CM" : "tw_apps" ,
"Lauderback, David" : "ca_eng,product_owners,tcad_eng" ,
"Schlenvogt, Garrett" : "ma_apps" ,
"Townsend, Mark" : "ca_eng,product_owners,tcad_eng" ,
"Furui, Yoshiharu" : "jp_sales,jp_apps,jp_eng,jp_admin,jp_tcad,jp_hr" ,
"Choi, In-Chol" : "kr_apps" ,
"Polito, Marc" : "ma_apps" ,
"Kelly, Sean" : "ca_eng,ca_apps,product_owners" ,
"Li, Yu-Chun" : "tw_apps" ,
"Mijalkovic, Slobodan" : "eu_eng" ,
"Maurer, Mark" : "us_sales" ,
"Ito, Yusuke" : "jp_apps" ,
"Hoessinger, Andreas" : "ca_eng" ,
"Lim, Heetaek" : "ca_eng" ,
"Kondratyev, Vasily" : "eu_eng" ,
"Samoylov, Alex" : "ca_eng" ,
"Rudenko, Anatoli" : "ca_eng" , 
"Chorou, Slim" : "ca_eng,tcad_eng" ,
"Kong, Sung Won" : "ca_apps" , 
"Shulga, Andrey" : "ca_eng" , 
"Orr, Layne" : "ca_apps" , 
"Chiu, Arthur" : "tw_apps" ,
"Yaroshevskiy, Roman" : "QA" , 
"Jin, Mei" : "ca_apps" , 
"Paulavicius, Gediminas" : "ca_apps" ,
"Kozhevnikov, Evgeny" : "ca_eng" ,
"Karabelnikau, Andrei" : "ca_eng" ,
"Amzallag, Joel" : "ca_apps" , 
"Chumakou, Aleh" : "ca_eng" , 
"Zharikov, Alexey" : "ca_eng" ,
"Chen, Dany" : "tw_apps" ,
"Lee, Hung-Li" : "ca_eng"
}

	if ( emp0[eng_name] ) {
		if (emp0[eng_name].indexOf(role) >= 0) {
			return 1;
		} else {
			return 0;
		}
	} else {
		return 0;
	}

	
}
</script>

</body>
</html> 
