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

if ( $last_period != 'Week' and $last_period != 'BiWeek' and $last_period != 'Month' and $last_period != 'Year') {
	$last_period = 'BiWeek';
}

	$arr = ttpro::newfound_eng('Newly Found ' . substr($last_period,0,1));

//	krumo($arr);
?>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <title>Submitter Classification</title>
    <meta name="author" content="Arnold Ho">
<script src="https://www.google.com/jsapi"></script>
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
<body onload="show_header('<?php echo $_COOKIE[db_mediawikiUserName] ?>' ,'<?php echo $_SERVER[REMOTE_ADDR] ?>' )">

<table width="100%"  ><tr><td rowspan="2" valigh="bottom"><h1>Submitter Classification</h1></td>
<td valign=bottom align=right>
<b>Duration: </b><input type=radio name=period style='color:green' onClick="location.href='byfae.php?last=Week'" value="Week" <?php if ($last_period == "Week") echo "checked" ?> >Week
<input type=radio name=period style='color:green' onClick="location.href='byfae.php?last=BiWeek'" value="BiWeek" <?php if ($last_period == "BiWeek") echo "checked" ?> >BiWeek
<input type=radio name=period style='color:blue' onClick="location.href='byfae.php?last=Month'" value="Month" <?php if ($last_period == "Month") echo "checked" ?>>Month
<input type=radio name=period style='color:black' onClick="location.href='byfae.php?last=Year'" value="Year" <?php if ($last_period == "Year") echo "checked" ?>>Year
<!---<input type=radio name=period style='color:black' onClick="period_chg();location.href='newfound_t.php?last=W'" value="N">None --->
</td></tr>
<tr><td valign=bottom align=right>
<label for="datepicker">Date Entered From:</label>
    <input type="button" id="date-b" >
<label for="datepicker">To:</label>
    <input type="button" id="date-e" >
</td></tr></table>
<div id='debug'></div>
<table><tr><td valign=bottom align=right bgcolor='#d3f3d3'><b>Priority:</b>&nbsp;
<input type=checkbox name=Urgent id="Urgent" checked>Urgent
<input type=checkbox name=High id="High" checked >High
<input type=checkbox name=Medium id="Medium" >Medium
<input type=checkbox name=Low id="Low" >Low
<input type=checkbox name=not_set id="not_set" >Not Set
</td><td bgcolor='#d3f3d3'><button bgcolor='#d3f3d3' id="change_prio" onClick="priority_redraw()" >Redraw</button></td>
<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
<td ><button bgcolor='#d3f3d3' onClick="display_legend()">Legend</button></td>
<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
<td ><button bgcolor='#d3f3d3' onClick="window.location.href='#st_list';">Table</button></td>
<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
<td><button bgcolor="#d3f3d3" onclick="display_groups();">Groups</button></td></tr></table>
<div id="chart_status" ></div><br>
<br><div><a name='st_list'></a></div>
<h4 align='center'>[&nbsp;&nbsp;&nbsp;[&nbsp;&nbsp;[&nbsp;Click on the head row to sort, click on cell to show detail list&nbsp;]&nbsp;&nbsp;]&nbsp;&nbsp;&nbsp;]</h4><br>
<p id="statistics"></p><br>

<hr>
<div id='detail_list'><a name='detail_list'></a></div> 
<h3 id='detail_subject'>Detail List in Duration</h3>
<p id="detail"></p><br>
<a name='grouping_tag'></a><div id='grouping'></div>


	<script src="../sweetalert-master/dist/sweetalert.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../sweetalert-master/dist/sweetalert.css">
    <script src="../Pikaday-1.3.2/pikaday.js"></script>

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
function display_groups()
{
	window.location.href='#grouping_tag';
	var msg_txt = 'Group information is from email aliasing<br>';
	msg_txt += 'Conversion: apps --> FAE, eng --> R&D<br>';
	msg_txt += 'Precedence: SALES > FAE > R&D<br>';
	msg_txt += 'Precedence: Region > Product<br>'
	swal({
		title: 'Grouping Rules',
		text: msg_txt,
		html:true
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

	var emp_role = define_role();
	groups_table();
	var ttpro = <?php echo json_encode($arr); ?>;

	var s_obj = weeknum(ttpro);
	var date_b = new Date(document.getElementById('date-b').value);
	var date_e = new Date(document.getElementById('date-e').value);
	var f_obj = filter_by_date(s_obj , date_b , date_e);
	

	var product_stat = collect_product(f_obj);
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
//document.getElementById("statistics").innerHTML = text;
	document.getElementById("detail").innerHTML = f2table(f_obj );
	detail_sorter.init('detail_tab',5);
	detail_sorter.wk(5);

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



function groups_table()
{
    var d_roles = {};
    var abc = '<br><br><br><hr><table style="font face=Arial" border=1>';
    for (var rr in emp_role) {
 //   console.log('<' , rr , '>');
        var rl = emp_role[rr];
        if ( !(d_roles[rl]) ) {
		    d_roles[rl] = rr;
		} else {
		    d_roles[rl] += "|" + rr;
		}
	}
	var seq = [];
	var dd;
	var i;
	for (var dd in d_roles) {
		seq.push(dd);
	}
	seq.sort();

	for (i = 0 ; i < seq.length ; i++) {
		dd = seq[i];
	    var roles = d_roles[dd];
	    var members = roles.split("|");
	    abc += "<tr>";
	    abc += "<td  rowspan=" + Math.ceil(members.length/5) + "><font face='serif'>" + dd + "(" + members.length + ")</font></td>";
	    var ii,jj;
	    for (ii = 0 ,jj=0; ii < members.length; ii++,jj++) {
	        if ( ii > 0 && jj == 0) {
	            abc += "<tr>";
	        }
	        abc += "<td>" + members[ii] + "</td>";
	        if (jj == 4) {
	            abc += "</tr>";
	            jj = -1;
	        }
        }
	}
	abc += "</tr></table>";

    //alert(abc);
    document.getElementById("grouping").innerHTML = abc;
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

function collect_product(sobj)
{
	var pobj = new Object();
	var col_list = sobj.header;
	var product_id = -1;
	var priority_id = -1;
	var status_id = -1;
	var number_id = -1;
	var disposition_id = -1;
	var entered_by_id = -1;

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
		if (col_list[hx] == "Entered by") {
			entered_by_id = hx;
		}
	}

	pobj['subtotal'] = {high:0, other:0, NotReviewed:0, Reviewed:0, Processing:0, ToBeVerified:0, Done:0, Unknown:0};
	var rows = sobj.rows;
	for (rx in rows) {
		var prod_name = rows[rx][product_id];
		var entered_by_name = rows[rx][entered_by_id];
		var prio_name = rows[rx][priority_id];
		var stat_name = rows[rx][status_id];
		var number = rows[rx][number_id];
		var disposition = rows[rx][disposition_id];

		if (prio_name == '') {
			prio_name = 'Not_set';
		}
		
	// emp_role[name] to get its role
	//	console.log('<',entered_by_name , '>');

		var fae_group = emp_role[entered_by_name];
		

		if (!(pobj[fae_group])) {
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
			pobj[fae_group] = arr;
		}
		var arr = pobj[fae_group];
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

		var chart_category;
		if (prio_setting.indexOf(prio_name) >= 0) {
			arr['Total'].count += 1;
			arr['Total'].list += number + ',';
		if (stat_name.substr(0,4) == 'Open') {
			if  ( stat_name.indexOf("not assigned") >= 0) {
				if (disposition == 'Not Reviewed' || disposition == '') {
					arr['NotReviewed'].count +=1;
					arr['NotReviewed'].list += number + ',';
					pobj['subtotal'].NotReviewed += 1;
					chart_category = 'NotReviewed';
				} else {
					arr['Reviewed'].count += 1;
					arr['Reviewed'].list += number + ',';
					pobj['subtotal'].Reviewed += 1;
					chart_category = 'Reviewed';
				}
			} else {
				arr['Processing'].count += 1;
				arr['Processing'].list += number + ',';
				pobj['subtotal'].Processing += 1;
				chart_category = 'Processing';
			}
		} else if (stat_name.substr(0,6) == 'Closed') {
			arr['Done'].count += 1;
			arr['Done'].list += number + ',';
			pobj['subtotal'].Done += 1;
				chart_category = 'Done';
		} else if (stat_name.substr(0,5) == 'Fixed') {
			arr['ToBeVerified'].count += 1;
			arr['ToBeVerified'].list += number + ',';
			pobj['subtotal'].ToBeVerified += 1;
				chart_category = 'ToBeVerified';
		} else if (stat_name.substr(0,8) == 'Released') {
			arr['ToBeVerified'].count += 1;
			arr['ToBeVerified'].list += number + ',';
			pobj['subtotal'].ToBeVerified += 1;
				chart_category = 'ToBeVerified';
		} else if (stat_name.substr(0,14) == 'In Development') {
			arr['Processing'].count += 1;
			arr['Processing'].list += number + ',';
			pobj['subtotal'].Processing += 1;
				chart_category = 'Processing';
		} else {
			arr['Unknown'].count += 1;
			arr['Unknown'].list += number + ',';
			pobj['subtotal'].Unknown += 1;
				chart_category = 'Unknown';
		}
		}
		rows[rx]['chart_category']=chart_category;
	}
	return pobj;
}

function drawStatus() {
  // Some raw data (not necessarily accurate)
  
	var seq = [];
	for (var pp in product_stat) {
		if (pp == 'subtotal') {continue;}
		seq.push(pp);
	}
	seq.sort();
	var data = new google.visualization.DataTable();
	data.addColumn('string' , 'Submitter');
	data.addColumn('number' , 'NotReviewed (' + product_stat['subtotal'].NotReviewed + ')', 'NotReviewed');
	data.addColumn('number' , 'Reviewed (' + product_stat['subtotal'].Reviewed + ')', 'Reviewed');
	data.addColumn('number' , 'Processing (' + product_stat['subtotal'].Processing + ')', 'Processing');
	data.addColumn('number' , 'ToBeVerified (' + product_stat['subtotal'].ToBeVerified + ')', 'ToBeVerified');
	data.addColumn('number' , 'Done (' + product_stat['subtotal'].Done + ')', 'Done');
	var i;
	for (i = 0 ; i < seq.length ; i++) {
//	for (var pp in product_stat) {
		var pp = seq[i];
//console.log(pp);
		data.addRow([pp , product_stat[pp]['NotReviewed'].count , product_stat[pp]['Reviewed'].count , product_stat[pp]['Processing'].count , product_stat[pp]['ToBeVerified'].count ,product_stat[pp]['Done'].count ]); // /*, product_stat[pp]['Unknown'].count]*/);
	}
	var mview = new google.visualization.DataView(data);
	//mview.hideColumns([4]);

	var chart_width = seq.length * 50 + 300;
	if (chart_width < 1200 ) {
		chart_width = 1200;
	}

  var options = {
	fontSize: 20,
	title : 'Recent ' + period_sel + ' <Status> by Submitter',
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
		lstr = product_stat[data.getValue(item.row,0)][data.getColumnId(item.column)].list;
		pname = data.getValue(item.row,0);
    } else if (item.column != null) {
			//alert("column is not null-" + data.getColumnId(item.column) + "|" + data.getColumnLabel(item.column) );
		
		for (var pp in product_stat) {
			if (pp == 'subtotal') {continue;}
			lstr += product_stat[pp][data.getColumnId(item.column)].list;
		}
		pname = "All Products";
	} else { 
      return;
    }
         
 //           alert('The user selected ' + lstr);
	document.getElementById("detail").innerHTML = l2table(f_obj ,lstr);
	detail_sorter.init('detail_tab',5);
	detail_sorter.wk(5);
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
		rowIndex++;
//		data.addRow([pp , product_stat[pp]['Not_Assigned'].count , product_stat[pp]['Processing'].count , product_stat[pp]['Done'].count , product_stat[pp]['Unknown'].count]);
	}
	drawChart();
  }
	drawChart();
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
	var hdr = ['Group' ,  'NotReviewed' , '%' , 'Reviewed' , '%' , 'Processing', '%', 'ToBeVerified', '%' , 'Done' , '%' ,'Total' ];
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
						detail_sorter.init('detail_tab',5);
						detail_sorter.wk(5);
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
function define_role()
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
"Liu, Frank" : "tw_apps" ,
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
"Lee, Hung-Li" : "ca_eng" ,
"Jannaty, Pooya" : "ca_eng, tcad_eng"
}

	var emp = {};

	for ( var entered_by_name in emp0) {
		var e_roles = emp0[entered_by_name].toUpperCase();

		var fae_group;
		if (e_roles == '') {
			fae_group = entered_by_name;
		} else {
			var groups = e_roles.split(",");
			var priority = 0; // sales: 90 apps:70 highest priority, eng:50 second high
			var index;
			fae_group = groups[0];
			for (index=0; index < groups.length ; ++index) {
				var re_grp = groups[index].split('_');
				var re0_region = 0;
				if (re_grp.length == 1 && (priority <= 0)) {
					fae_group = re_grp[0];
					continue;
				}

				if (re_grp[0] in {CA:1, EU:1, JP:1, KR:1, MA:1, SG:1, TW:1, TX:1}) {
					re0_region = 9;
				}
				if ((re_grp[1] == 'SALES') && (priority < (90 + re0_region))) {
					fae_group = 'SALES ' + re_grp[0];
					priority = 90 + re0_region;
				} else if ((re_grp[1] == 'APPS') && (priority < (70 + re0_region))) {
					fae_group = 'FAE ' + re_grp[0];
					priority = 70 + re0_region;
				} else if ((re_grp[1] == 'ENG') && (priority < (50 + re0_region))) {
					fae_group = 'R&D ' + re_grp[0];
					priority = 50 + re0_region;
				} else if (priority <=0) {
					fae_group = re_grp[1] + ' ' + re_grp[0];
					priority = 1;
				}

			}
		}


		emp[entered_by_name] = fae_group;

	}


	return emp;
}
</script>

</body>
</html> 
