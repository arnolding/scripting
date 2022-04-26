<!DOCTYPE html>
<html>
<head>
<?php
$inc_path = get_include_path();
$new_inc_path = '..:' . $inc_path;
set_include_path($new_inc_path);
include "krumo/class.krumo.php";
include "class_ttpro.php";




// $last_period = W for week, B for BiWeek, M for month, Y for year
$last_period = $_GET['last'];
$lookat = $_GET['lookat'];
$top = $_GET['top'];

if ( $last_period != 'Week' and $last_period != 'BiWeek' and $last_period != 'Month' and $last_period != 'HalfYr' and $last_period != 'Year') {
	$last_period = 'Year';
}
//echo "$last_period <br>";
	$arr = ttpro::newfound('nf_time');

	krumo($arr);


?>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <title>Newly Entered to TTPro</title>
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
	      </STYLE>

	<STYLE>  
		 		.DataCellClass	{
					font-family: Arial, sans-serif;
					font-size: 14px;
					COLOR: #000000;
				}
	      </STYLE>
<link rel="stylesheet" href="../js_sorter/style.css" />
<link rel="stylesheet" href="header.css" />
<script src="header.js"></script>
</head>
<body onload="show_header('<?php echo $_COOKIE[db_mediawikiUserName] ?>')">
<table width="100%"  ><tr><td rowspan="2" valigh="bottom"><h1>Newly Entered to TTPro</h1></td>
<td valign=bottom align=right>
<b>Duration: </b><input type=radio name=period style='color:green' onClick="location.href='newfound_vs_time.php?last=Week'" value="Week" <?php if ($last_period == "Week") echo "checked" ?> >Week
<input type=radio name=period style='color:green' onClick="location.href='newfound_vs_time.php?last=BiWeek'" value="BiWeek" <?php if ($last_period == "BiWeek") echo "checked" ?> >BiWeek
<input type=radio name=period style='color:blue' onClick="location.href='newfound_vs_time.php?last=Month'" value="Month" <?php if ($last_period == "Month") echo "checked" ?>>Month
<input type=radio name=period style='color:black' onClick="location.href='newfound_vs_time.php?last=Year'" value="Year" <?php if ($last_period == "Year") echo "checked" ?>>Year
<!---<input type=radio name=period style='color:black' onClick="period_chg();location.href='newfound_t.php?last=W'" value="N">None --->
</td></tr>
<tr><td valign=bottom align=right>
<label for="datepicker">Date Entered From:</label>
    <input type="button" id="date-b" >
<label for="datepicker">To:</label>
    <input type="button" id="date-e" >
</td></tr></table>
<div id='debug'></div>
<table><tr><td valign=bottom align=right bgcolor='#d3f3d3'><b>Product:</b>&nbsp;
<input type=checkbox name="Victory Process" id="Victory Process" checked>Victory Process
<input type=checkbox name="Victory Device" id="Victory Device" checked >Victory Device
<input type=checkbox name="Victory Stress" id="Victory Stress" >Victory Stress
<input type=checkbox name="Victory" id="Victory" >Victory
<input type=checkbox name=not_set id="not_set" >Not Set
</td>
<td bgcolor='#d3f3d3'><button bgcolor='#d3f3d3' id="change_prio" onClick="priority_redraw()" >Redraw</button></td>
<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
<td ><button bgcolor='#d3f3d3' onClick="display_legend()">Legend</button></td>
<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
<td ><button bgcolor='#d3f3d3' onClick="window.location.href='#st_list';">Table</button></td>
</tr></table>
<div id="chart_status" ></div><br>
<br><div><a name='st_list'></a></div>
<h4 align='center'>[&nbsp;&nbsp;&nbsp;[&nbsp;&nbsp;[&nbsp;Click on the head row to sort, click on cell to show detail list&nbsp;]&nbsp;&nbsp;]&nbsp;&nbsp;&nbsp;]</h4><br>
<p id="statistics"></p><br>
<hr>
<div id='detail_list'><a name='detail_list'></a></div> 
<h3 id='detail_subject'>Detail List in Duration</h3>
<p id="detail"></p><br>
<div id="chart_div" ></div>

	<script src="../sweetalert-master/dist/sweetalert.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../sweetalert-master/dist/sweetalert.css">
    <script src="../Pikaday-1.3.2/pikaday.js"></script>
	<script src="https://www.google.com/jsapi"></script>
	<script type="text/javascript" src="../js_sorter/script.js"></script>

    <script>
var legend_series = {
		0: { color: '#f02020'},
		1: { color: '#ffbf40'},
		2: { color: '#00a040'},
		3: { color: '#a53535'},
		4: { color: '#0040f0'}
	};
var legend_color = {
		'NotReviewed': '#f02020',
		'Reviewed': '#ffbf40',
		'Processing': '#00a040',
		'ToBeVerified': '#a53535',
		'Done': '#0040f0'
	};
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

}

function priority_check() {
	var priority_setting = '';
	if (document.getElementById("Victory Process").checked) {
		priority_setting += 'Victory Process' + ',';
	}
	if (document.getElementById("Victory Device").checked) {
		priority_setting += 'Victory Device' + ',';
	}
	if (document.getElementById("Victory Stress").checked) {
		priority_setting += 'Victory Stress' + ',';
	}
	if (document.getElementById("Victory").checked) {
		priority_setting += 'Victory' + ',';
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
	var pre_halfyr = new Date(dt - 183*86400000);
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
	if (pe == 'HalfYr') {pre_b = pre_halfyr;}
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
	var time_obj = added_weeknum(s_obj);
	


	var detail_sorter = new TINY.table.sorter("detail_sorter");
	detail_sorter.head = "head";

	detail_sorter.asc = "asc";

	detail_sorter.desc = "desc";

	detail_sorter.even = "evenrow";

	detail_sorter.odd = "oddrow";

	detail_sorter.evensel = "evenselected";

	detail_sorter.oddsel = "oddselected";

	detail_sorter.paginate = false;

	detail_sorter.currentid = "currentpage";

	detail_sorter.limitid = "pagelimit";

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

	var product_stat = collect_product(f_obj);
	var product_time = time_product(time_obj);
	show_statistics();
	document.getElementById("detail").innerHTML = f2table(f_obj );
	detail_sorter.init('detail_tab',2);
	detail_sorter.wk(2);

	if (typeof google !== 'undefined' ) {
	google.load("visualization", "1", {packages:["corechart"]});
	google.setOnLoadCallback(drawVisualization);
	}
function priority_redraw() {
	console.log("INTO priority_redraw");
	if (typeof google === 'undefined' ) {
		product_time = time_product(time_obj);
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
		date_m = new Date(rows[rx][7]);
		if ( date_m > date_b && date_m < date_e) {
			rows_arr[rows_arr.length] = rows[rx];
		}
	}
	filtered_tbl.rows = rows_arr;

	return filtered_tbl;
}
function added_weeknum(sobj)
{
	var added_tbl = new Object();
	var header_arr = [];
	for (hx in sobj.header) {
	    header_arr[hx] = sobj.header[hx]
	}
	header_arr[header_arr.length] = "weeknum";
	added_tbl.header = header_arr;
	var rows_arr = [];
	var rows = sobj.rows;
	
	
	for (rx in rows) {
		date_m = new Date(rows[rx][7]);
		var row_new = [];
		var row_r = rows[rx];
		for (hx in row_r) {
		    row_new[hx] = row_r[hx];
		}
		row_new[row_new.length] = getWeekNumber(date_m);
		
		rows_arr[rows_arr.length] = row_new;
		
	}
	added_tbl.rows = rows_arr;

	return added_tbl;
}
function getWeekNumber(d) {
    // Copy date so don't modify original
    d = new Date(+d);
    d.setHours(0,0,0);
    // Set to nearest Thursday: current date + 4 - current day number
    // Make Sunday's day number 7
    d.setDate(d.getDate() + 4 - (d.getDay()||7));
    // Get first day of year
    var yearStart = new Date(d.getFullYear(),0,1);
    // Calculate full weeks to nearest Thursday
    var weekNo = Math.ceil(( ( (d - yearStart) / 86400000) + 1)/7);
    // Return array of year and week number
	if (weekNo > 9) {
    return d.getFullYear().toString() + ",W" + weekNo.toString();
	}
    return d.getFullYear().toString() + ",W0" + weekNo.toString();
}
function time_product(tobj)
{
	var pobj = new Object();
	var col_list = tobj.header;
	var product_id = -1;
	var priority_id = -1;
	var status_id = -1;
	var number_id = -1;
	var disposition_id = -1;
	var weeknum_id = -1;
	var top_no = parseInt('<?php echo $top; ?>');


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
		if (col_list[hx] == "Disposition") {
			disposition_id = hx;
		}
		if (col_list[hx] == "weeknum") {
			weeknum_id = hx;
		}
	}

	var rows = tobj.rows;
	for (rx in rows) {
		var prod_name = rows[rx][product_id];
		var prio_name = rows[rx][priority_id];
		var stat_name = rows[rx][status_id];
		var number = rows[rx][number_id];
		var disposition = rows[rx][disposition_id];
		var weeknum = rows[rx][weeknum_id];

		if (prio_name == '') {
			prio_name = 'Not_set';
		}
		
		if (!(pobj[weeknum])) {
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
			pobj[weeknum] = arr;
		}
		var arr = pobj[weeknum];
		arr['all_total'] += 1;

		if (prio_name == 'Urgent' || prio_name == 'High') {
				arr['high'].count += 1;
				arr['high'].list += number + ',';
		} else {
				arr['other'].count +=1;
				arr['other'].list += number + ',';
		}

		var prio_setting;
		var prodname_comma = prod_name+',';
		prio_setting = priority_check();
	//	document.getElementById("debug").innerHTML = prio_setting;
		var chart_category;
		if (prio_setting.indexOf(prodname_comma) >= 0) {
			arr['Total'].count += 1;
			arr['Total'].list += number + ',';
			if (stat_name.substr(0,4) == 'Open') {
				if  ( stat_name.indexOf("not assigned") >= 0) {
					if (disposition == 'Not Reviewed' || disposition == '') {
						arr['NotReviewed'].count +=1;
						arr['NotReviewed'].list += number + ',';
						chart_category = 'NotReviewed';
					} else {
						arr['Reviewed'].count += 1;
						arr['Reviewed'].list += number + ',';
						chart_category = 'Reviewed';
					}
				} else {
					arr['Processing'].count += 1;
					arr['Processing'].list += number + ',';
					chart_category = 'Processing';
				}
			} else if (stat_name.substr(0,6) == 'Closed') {
				arr['Done'].count += 1;
				arr['Done'].list += number + ',';
				chart_category = 'Done';
			} else if (stat_name.substr(0,5) == 'Fixed') {
				arr['ToBeVerified'].count += 1;
				arr['ToBeVerified'].list += number + ',';
				chart_category = 'ToBeVerified';
			} else if (stat_name.substr(0,8) == 'Released') {
				arr['ToBeVerified'].count += 1;
				arr['ToBeVerified'].list += number + ',';
				chart_category = 'ToBeVerified';
			} else if (stat_name.substr(0,14) == 'In Development') {
				arr['Processing'].count += 1;
				arr['Processing'].list += number + ',';
				chart_category = 'Processing';
			} else {
				arr['Unknown'].count += 1;
				arr['Unknown'].list += number + ',';
				chart_category = 'Unknown';
			}
		}
		rows[rx]['chart_category']=chart_category;
	}


	if (Number.isInteger(top_no) && (top_no < Object.keys(pobj).length) && (top_no > 0)) {
		var top_nr = Object.keys(pobj).sort(function(a,b) {return pobj[a]['all_total'] - pobj[b]['all_total']});
		for (var i = 0 ; i < top_nr.length - top_no ; i++) {
			delete pobj[top_nr[i]];
		}
	}

	pobj['subtotal'] = {'high':0, other:0, NotReviewed:0, Reviewed:0, Processing:0, ToBeVerified:0, Done:0, Unknown:0};
	for (var pp in pobj) {
		if (pp == 'subtotal') {continue;}
		pobj['subtotal']['high'] += pobj[pp]['high'].count;
		pobj['subtotal']['other'] += pobj[pp]['other'].count;
		pobj['subtotal']['NotReviewed'] += pobj[pp]['NotReviewed'].count;
		pobj['subtotal']['Reviewed'] += pobj[pp]['Reviewed'].count;
		pobj['subtotal']['Processing'] += pobj[pp]['Processing'].count;
		pobj['subtotal']['ToBeVerified'] += pobj[pp]['ToBeVerified'].count;
		pobj['subtotal']['Done'] += pobj[pp]['Done'].count;
		pobj['subtotal']['Unknown'] += pobj[pp]['Unknown'].count;
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
	var disposition_id = -1;
	var top_no = parseInt('<?php echo $top; ?>');


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
		if (col_list[hx] == "Disposition") {
			disposition_id = hx;
		}
	}

	var rows = sobj.rows;
	for (rx in rows) {
		var prod_name = rows[rx][product_id];
		var prio_name = rows[rx][priority_id];
		var stat_name = rows[rx][status_id];
		var number = rows[rx][number_id];
		var disposition = rows[rx][disposition_id];

		if (prio_name == '') {
			prio_name = 'Not_set';
		}
		
		if (!(pobj[prod_name])) {
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
			pobj[prod_name] = arr;
		}
		var arr = pobj[prod_name];
		arr['all_total'] += 1;

		if (prio_name == 'Urgent' || prio_name == 'High') {
				arr['high'].count += 1;
				arr['high'].list += number + ',';
		} else {
				arr['other'].count +=1;
				arr['other'].list += number + ',';
		}

		var prio_setting;
		prio_setting = priority_check();
	//	document.getElementById("debug").innerHTML = prio_setting;
		var chart_category;
		if (prio_setting.indexOf(prio_name) >= 0) {
			arr['Total'].count += 1;
			arr['Total'].list += number + ',';
			if (stat_name.substr(0,4) == 'Open') {
				if  ( stat_name.indexOf("not assigned") >= 0) {
					if (disposition == 'Not Reviewed' || disposition == '') {
						arr['NotReviewed'].count +=1;
						arr['NotReviewed'].list += number + ',';
						chart_category = 'NotReviewed';
					} else {
						arr['Reviewed'].count += 1;
						arr['Reviewed'].list += number + ',';
						chart_category = 'Reviewed';
					}
				} else {
					arr['Processing'].count += 1;
					arr['Processing'].list += number + ',';
					chart_category = 'Processing';
				}
			} else if (stat_name.substr(0,6) == 'Closed') {
				arr['Done'].count += 1;
				arr['Done'].list += number + ',';
				chart_category = 'Done';
			} else if (stat_name.substr(0,5) == 'Fixed') {
				arr['ToBeVerified'].count += 1;
				arr['ToBeVerified'].list += number + ',';
				chart_category = 'ToBeVerified';
			} else if (stat_name.substr(0,8) == 'Released') {
				arr['ToBeVerified'].count += 1;
				arr['ToBeVerified'].list += number + ',';
				chart_category = 'ToBeVerified';
			} else if (stat_name.substr(0,14) == 'In Development') {
				arr['Processing'].count += 1;
				arr['Processing'].list += number + ',';
				chart_category = 'Processing';
			} else {
				arr['Unknown'].count += 1;
				arr['Unknown'].list += number + ',';
				chart_category = 'Unknown';
			}
		}
		rows[rx]['chart_category']=chart_category;
	}


	if (Number.isInteger(top_no) && (top_no < Object.keys(pobj).length) && (top_no > 0)) {
		var top_nr = Object.keys(pobj).sort(function(a,b) {return pobj[a]['all_total'] - pobj[b]['all_total']});
		for (var i = 0 ; i < top_nr.length - top_no ; i++) {
			delete pobj[top_nr[i]];
		}
	}

	pobj['subtotal'] = {'high':0, other:0, NotReviewed:0, Reviewed:0, Processing:0, ToBeVerified:0, Done:0, Unknown:0};
	for (var pp in pobj) {
		if (pp == 'subtotal') {continue;}
		pobj['subtotal']['high'] += pobj[pp]['high'].count;
		pobj['subtotal']['other'] += pobj[pp]['other'].count;
		pobj['subtotal']['NotReviewed'] += pobj[pp]['NotReviewed'].count;
		pobj['subtotal']['Reviewed'] += pobj[pp]['Reviewed'].count;
		pobj['subtotal']['Processing'] += pobj[pp]['Processing'].count;
		pobj['subtotal']['ToBeVerified'] += pobj[pp]['ToBeVerified'].count;
		pobj['subtotal']['Done'] += pobj[pp]['Done'].count;
		pobj['subtotal']['Unknown'] += pobj[pp]['Unknown'].count;
	}
	return pobj;
}

function drawStatus() {
  // Some raw data (not necessarily accurate)
	var lookat = '<?php echo $lookat; ?>';
  	var seq = [];
	for (var pp in product_time) {
		if (pp == 'subtotal') {continue;}
		seq.push(pp);
	}
	seq.sort();
console.log('a',seq);
	var data = new google.visualization.DataTable();
	data.addColumn('string' , 'WeekNum');
	data.addColumn('number' , 'NotReviewed (' + product_time['subtotal'].NotReviewed + ')', 'NotReviewed');
	data.addColumn('number' , 'Reviewed (' + product_time['subtotal'].Reviewed + ')', 'Reviewed');
	data.addColumn('number' , 'Processing (' + product_time['subtotal'].Processing + ')', 'Processing');
//	if (lookat != 'rd') {
	data.addColumn('number' , 'ToBeVerified (' + product_time['subtotal'].ToBeVerified + ')', 'ToBeVerified');
	data.addColumn('number' , 'Done (' + product_time['subtotal'].Done + ')', 'Done');
//	}
//	data.addColumn('number' , 'Legend' , 'Legend');
//	data.addColumn('number' , 'Unknown (' + product_time['subtotal'].Unknown + ')', 'Unknown');
	var i;
	for (i = 0 ; i < seq.length ; i++) {
//	for (var pp in product_time) {
		var pp = seq[i];
//		if (lookat != 'rd') {
		data.addRow([pp , product_time[pp]['NotReviewed'].count , product_time[pp]['Reviewed'].count , product_time[pp]['Processing'].count , product_time[pp]['ToBeVerified'].count ,product_time[pp]['Done'].count ]); // /*, product_time[pp]['Unknown'].count]*/);
//		} else {
//		data.addRow([pp , product_time[pp]['NotReviewed'].count , product_time[pp]['Reviewed'].count , product_time[pp]['Processing'].count ]); // /*, product_time[pp]['Unknown'].count]*/);
//		}
	}
	var mview = new google.visualization.DataView(data);
	//mview.hideColumns([4]);

	var chart_width = seq.length * 50 + 300;
	if (chart_width < 1200 ) {
		chart_width = 1200;
	}
	
  var options = {
	fontSize: 20,
	title : 'Recent ' + period_sel + ' <Status> by Product',
	width : chart_width,
	height : 500,
	animation: {
		duration: 1000,
		easing: 'out',
	},
	vAxis: {title: "Ticket Entered"},
	hAxis: {direction: '1',
		slantedText: true,
		slantedTextAngle: '60',
		textStyle : {   fontSize: '14' }
	},
	legend: { position: 'top', maxLines: '4', textStyle: {fontSize: 16 } },
	backgroundColor: '#d3f3d3',
	series: legend_series,
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
		lstr = product_time[data.getValue(item.row,0)][data.getColumnId(item.column)].list;
		pname = data.getValue(item.row,0);
    } else if (item.column != null) {
			//alert("column is not null-" + data.getColumnId(item.column) + "|" + data.getColumnLabel(item.column) );
		if (data.getColumnId(item.column) == 'Legend') {
			window.open('legend.html' , 'New Found Tickets Legend');
		} else {
			for (var pp in product_time) {
				if (pp == 'subtotal') {continue;}
				lstr += product_time[pp][data.getColumnId(item.column)].list;
			}
			pname = "All Products";
		}
	} else { 
      return;
    }
         
 //           alert('The user selected ' + lstr);
	document.getElementById("detail").innerHTML = l2table(f_obj ,lstr);
	detail_sorter.init('detail_tab',2);
	detail_sorter.wk(2);
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
	product_time = time_product(time_obj);
	show_statistics();
	var seq = [];
	for (var pp in product_time) {
		if (pp == 'subtotal') {continue;}
		seq.push(pp);
	}
	seq.sort();
console.log('b',seq);
	data.setColumnLabel(1 , 'NotReviewed (' + product_time['subtotal'].NotReviewed + ')');
	data.setColumnLabel(2 , 'Reviewed (' + product_time['subtotal'].Reviewed + ')');
	data.setColumnLabel(3 , 'Processing (' + product_time['subtotal'].Processing + ')');
//	if (lookat != 'rd') {
	data.setColumnLabel(4 , 'ToBeVerified (' + product_time['subtotal'].ToBeVerified + ')');
	data.setColumnLabel(5 , 'Done (' + product_time['subtotal'].Done + ')');
//	}
//	data.addColumnLabel(5 , 'Legend');
//	data.setColumnLabel(4 , 'Unknown (' + product_stat['subtotal'].Unknown + ')');
	var rowIndex;
	rowIndex = 0;
	var i;
	for (i = 0 ; i < seq.length ; i++) {
		var pp = seq[i];

		data.setValue( rowIndex , 1 , product_time[pp]['NotReviewed'].count);
		data.setValue( rowIndex , 2 , product_time[pp]['Reviewed'].count);
		data.setValue( rowIndex , 3 , product_time[pp]['Processing'].count);
//		if (lookat != 'rd') {
		data.setValue( rowIndex , 4 , product_time[pp]['ToBeVerified'].count);
		data.setValue( rowIndex , 5 , product_time[pp]['Done'].count);
//		}
//		data.setValue( rowIndex , 4 , product_time[pp]['Unknown'].count);
		rowIndex++;
//		data.addRow([pp , product_time[pp]['NotReviewed'].count , product_time[pp]['Reviewed'].count ,product_time[pp]['Processing'].count , product_stat[pp]['Done'].count , product_time[pp]['Unknown'].count]);
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
    var seq = [];
	for (var pp in product_stat) {
		if (pp == 'subtotal') {continue;}
		seq.push(pp);
	}
	seq.sort();
	var data = new google.visualization.DataTable();
	data.addColumn('string' , 'Product');

	data.addColumn('number' , 'Urgent and High (' + product_stat['subtotal'].high + ')','high');
	data.addColumn('number' , 'Medium and Low (' + product_stat['subtotal'].other + ')','other');

	var i;
	for (i = 0 ; i < seq.length ; i++) {

		var pp = seq[i];
		data.addRow([pp , product_stat[pp]['high'].count , product_stat[pp]['other'].count]);
	}


	
  var options = {
	title : 'Recent ' + period_sel + ' <Priority> by Product',
	width : 1200,
	height : 500,
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
	detail_sorter.init('detail_tab',2);
	detail_sorter.wk(2);
	var label = data.getColumnLabel(item.column);
	var label_h = label.split("(");
	document.getElementById("detail_subject").innerHTML = 'Detail List of <u>' + pname + '</u> and <i>Priority ' + label_h[0] + '</i>';

	window.location.href="#detail_list";
          
  }
	google.visualization.events.addListener(chart, 'select', selectHandler_p);  
  	chart.draw(data, options);
}

function weeknum(ttpro_obj) {
	var simple_obj = new Object();
	var header_arr = [];
	var col_list = ttpro_obj['recordlist']['columnlist']['item'];
	for (hx in col_list) {
		header_arr[hx] = col_list[hx].name;
	}

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

		rows_arr[rows_arr.length] = row_arr;
	}
	simple_obj.rows = rows_arr;
	return simple_obj;
}
function f2table(sobj) {
	var text = "";
	var number_id = -1;
	text += "<table id='detail_tab' class='sortable'><thead><tr>"; // bgcolor='#6e99d4' >";
	var col_list = sobj.header;

	for (hx in col_list) {
		text += "<td><h4>" + col_list[hx] + "</h4></td>";
		if (col_list[hx] == "Number") {
			number_id = hx;
		}
	}
	text += "</tr></thead><tbody>";

	var rows = sobj.rows;
	for (rx in rows) {
		text += "<tr>";
		var row1 = rows[rx];
		var r_color = legend_color[row1['chart_category']];
		for (cx in row1) {
			if (cx == 'chart_category') {continue;}
			if (cx == number_id) {
				text += "<td ><a href='onedefect.php?defectNumber=" + row1[cx] + "'>" + row1[cx] + "</a></td>";
			} else {
				text += "<td><font color='" + r_color + "'>" + row1[cx] + "</font></td>";
			}
		}
		text += "</tr>";
	}

	text += "</tbody></table>";
	return text;
}

function l2table(sobj,list_str) {
	var text = "";
	var number_id = -1;
	text += "<table id='detail_tab' class='sortable'><thead><tr>"; // bgcolor='#6e99d4' >";
	var col_list = sobj.header;

	for (hx in col_list) {
		text += "<td><h4>" + col_list[hx] + "</h4></td>";
		if (col_list[hx] == "Number") {
			number_id = hx;
		}
	}
	text += "</tr></thead><tbody>";
	
	var rows = sobj.rows;
	for (rx in rows) {
		var row1 = rows[rx];
		if (list_str.indexOf(row1[number_id]) >=0 ) {
			text += "<tr>";
			var r_color = legend_color[row1['chart_category']];
			for (cx in row1) {
				if (cx == 'chart_category') {continue;}
				if (cx == number_id) {
					text += "<td><a href='onedefect.php?defectNumber=" + row1[cx] + "'>" + row1[cx] + "</a></td>";
				} else {
					text += "<td><font color='" + r_color + "'>" + row1[cx] + "</font></td>";
				}
			}
			text += "</tr>";
		}
	}

	text += "</tbody></table>";
	return text;
}

function table_statistics()
{
	var sst_top = document.createElement("Table");
	sst_top.id = 'sst';
	sst_top.className = 'sortable';

	var header = sst_top.createTHead();

	var tr0 = header.insertRow();
	var hdr = ['Product' ,  'NotReviewed' , '%' , 'Reviewed' , '%' , 'Processing', '%', 'ToBeVerified', '%' , 'Done' , '%' ,'Total' ];
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
						detail_sorter.init('detail_tab',2);
						detail_sorter.wk(2);
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

</script>

</body>
</html> 
