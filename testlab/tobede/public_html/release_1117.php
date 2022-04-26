<!DOCTYPE html>
<html>
<head>
<?php
include "krumo/class.krumo.php";
include "class_ttpro.php";

	$p_set = $_GET['p_set'];
	$v_set = $_GET['v_set'];
	$debug_set = $_GET['debug_set'];
	logx();

// $last_period = W for week, B for BiWeek, M for month, Y for year
	$last_period = $_GET['last'];
	if ( $last_period != 'Week' and $last_period != 'BiWeek' and $last_period != 'Month' and $last_period != 'Year') {
		$last_period = 'BiWeek';
	}

	$detail_arr = ttpro::release();

//	krumo($detail_arr);
//	echo "<hr>\n";
//	print_r($arr);
//	array2table($arr);




function logx()
{
	$date_tag = strftime("%b %d %Y, %X %Z ", time());
	$log_msg = $date_tag . "UserName: [" . $_COOKIE["db_mediawikiUserName"] . "], REMOTE_ADDR: [" . $_SERVER["REMOTE_ADDR"] . "], SCRIPT: [" . $_SERVER["SCRIPT_NAME"] . "]\n";
	file_put_contents("/main/stage/wiki/testlab/user.log" , $log_msg , FILE_APPEND);
}

?>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <title>Release of Silvaco</title>
    <meta name="author" content="Arnold Ho">

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
	<STYLE>  
 		.OpenCellClass	{
			font-family: Arial, sans-serif;
			font-size: 14px;
			COLOR: #ff0000;
		}
	</STYLE>
<link rel="stylesheet" href="../js_sorter/style.css" />
</head>
<body>
<p id="xxx"></p>
<table width="100%"  ><tr><td rowspan="2" valigh="bottom"><h1><u>Release of Silvaco</u></h1></td>
</table>
<table>
<tr id='pv_tr'><td valign=bottom align=left bgcolor='#d3f3d3'><b>Product:</b>&nbsp;</td></tr>
<tr id='pvv_tr'><td valign=bottom align=left bgcolor='#d3f3d3'><b>Version:</b>&nbsp;</td></tr>
</table>
<table>
<tr><td ><p onclick="pv_go()"><img src="../update.png" height=32 id="blinking_update"/></p></td><td id='filter_td'></td></tr>
</table><br>
<div id='timeline' ></div>




<div id='detail_list'><a name='detail_list'></a></div> 
<h2 id='detail_subject'>Detail List of Selected Release <font color="blue"><i>(Click on timeline bar above to show list of specific product/version)</i></font></h2>
<p id="detail0"></p>
<p id="detail"></p>
	
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="../js_sorter/script.js"></script>
    <script type="text/javascript">
     


	var period_sel = '<?php echo $last_period; ?>';
	var periods = ["Week" , "BiWeek" , "Month" , "Year"];



function pv_set_sel(pp, vv)
{
	if ( (( p_set == "") || ((p_set.indexOf(pp) >= 0) &&(p_set != "")) ) && ((v_set =="") || ((v_set.indexOf(vv) >= 0) && (v_set != "") ) )) {
		return 1;
	} else {
		return 0;
	}
}
function pv_sel(ele_id)
{
	var pp = '';
	var p_td = document.getElementById(ele_id).children;
	for (var c=1 ; c < p_td.length; c++) {
		var p_ele = p_td[c].children[0];
//		console.log(c , ">" , "> value>" , p_ele["value"] , "> type>" , p_ele["type"] , "> checked>" , p_ele["checked"]);
		if (p_ele['checked']) {
			pp += p_ele['value'] + ',';
		}
	}
	return pp;
}

function pv_go() {
	p_set = pv_sel('pv_tr');
	v_set = pv_sel('pvv_tr');

//	console.log('p_set=', p_set);
//	console.log('v_set=', v_set);
	//window.location = "release.php?p_set=" + p_set +"&v_set=" + v_set;
	var filter_str = "http://" +window.location.hostname + window.location.pathname +"?p_set=" + p_set + "&v_set=" + v_set;
	
	document.getElementById("filter_td").innerHTML = "<a href='" + filter_str + "'>" + filter_str + "</a>";
	drawChart();
}


	var dt = new Date();
	

	Date.prototype.getWeek = function() {
        var onejan = new Date(this.getFullYear(), 0, 1);
        return Math.ceil((((this - onejan) / 86400000) + onejan.getDay() + 1) / 7);
    }

	var ttpro = <?php echo json_encode($detail_arr); ?>;
	var p_set = "<?php echo $p_set; ?>";
	var v_set = "<?php echo $v_set; ?>";
	var debug_set = "<?php echo $debug_set; ?>";
//alert (p_set + ":" + v_set);

	var s_obj = weeknum(ttpro);
//	var date_b = new Date(document.getElementById('date-b').value);
//	var date_e = new Date(document.getElementById('date-e').value);
//	var f_obj = filter_by_date(s_obj , date_b , date_e);
	


	 google.load("visualization", "1", {packages:["timeline"]});
      google.setOnLoadCallback(drawChart);

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


//	sorter.init("table",1);
//	drawChart();
//	document.getElementById("detail").innerHTML = f2table(s_obj);

//	var sankey_set = sankey_arrange(s_obj);
function drawChart(tline)
{
        var container = document.getElementById('timeline');
        var chart = new google.visualization.Timeline(container);
        var dataTable = new google.visualization.DataTable();

	var product_stat = collect_product(s_obj);


	var timeline_arr = new Array();

	var p_table = [];
	var v_table = [];
	for (var pp in product_stat) {	
		if (p_table.indexOf(pp) == -1) {
			p_table.push(pp);
		}
		var arrv = product_stat[pp]['version'];
		for (ii = 0 ; ii < arrv.length; ii++)  {
			var arrpv = arrv[ii];
			if (v_table.indexOf(arrpv.pv) == -1) {
				v_table.push(arrpv.pv);
			}
console.log("[" , p_set , "]");
			if ( pv_set_sel(pp , arrpv.pv) ) {
			var timeline_1 = new Array(pp+', ' + arrpv.pv , arrpv.progress , arrpv.date_b ,  arrpv.date_e/*new Date(2015,11,1)*/);

			timeline_arr.push( timeline_1);
			}
		}
	}

        dataTable.addColumn({ type: 'string', id: 'Release' });
	dataTable.addColumn({ type: 'string', id: 'Progress' });
        dataTable.addColumn({ type: 'date', id: 'Start' });
        dataTable.addColumn({ type: 'date', id: 'End' });
        dataTable.addRows(timeline_arr);
	var options = {
	    //colors: ['#cbb69d', '#603913', '#c69c6e'],
		'title': 'Release Scope, Schedule, and Progress',
//		'width': 800,
		'height': timeline_arr.length * 40 + 100,
	    timeline: { barLabelStyle: { color: '#f00f0f'} }
		  };

	function selectHandler() {
    	var selection = chart.getSelection();
		if (selection.length == 0) {
			return;
		}
		
		var lstr = "";
		var pname = "";
	    var item = selection[0];

	    if (item.row != null ) {
			pname = dataTable.getValue(item.row,0);
	    } else { 
			return;
	    }

		var pandv = pname.split(', ');
		var progress;

		var arrv = product_stat[pandv[0]]['version'];
		for (ii = 0 ; ii < arrv.length; ii++)  {
			var arrpv = arrv[ii];
			if (arrpv.pv == pandv[1]) {
				lstr = arrpv.list;
				progress = arrpv.progress;
				break;
			}
		}
//		alert(lstr);
		document.getElementById("detail").innerHTML = l2table(s_obj ,lstr);
		sorter.init("table_detail");
		document.getElementById("detail_subject").innerHTML = 'Detail List of Selected Release <font color="blue"><i>' + pname + '&nbsp' + progress + '</i></font>';
		window.location.href="#detail_list";
	}

		document.getElementById("detail").innerHTML = l2table(s_obj ,'all');
		sorter.init("table_detail");
		google.visualization.events.addListener(chart, 'select', selectHandler);  
        chart.draw(dataTable, options);
		
		pv_list(p_table , v_table);
}	

function pv_list(p_tbl , v_tbl)
{
	var selection = document.getElementById('pv_tr');

	while (selection.childNodes.length > 1) {
		selection.removeChild(selection.lastChild);	
	//	console.log(selection.childNodes.length);
	}
	for (var count in p_tbl) {
		var td = document.createElement("td");
		var txt = document.createTextNode(p_tbl[count]);
		var pwork = document.createElement("input");
		pwork.value = (p_tbl[count]);
		pwork.type = "checkbox";
		if (p_set.indexOf(p_tbl[count]) >= 0) {
			pwork.checked = 1;
		}
		pwork.id = "p" + count;
		pwork.onclick = function() {
			var update_img = document.getElementById('blinking_update');
			var count = 0;
			var interval = window.setInterval(function() {
				if (update_img.style.visibility == 'hidden') {
					update_img.style.visibility = 'visible';
					count++;
					if (count >= 6) {
						window.clearInterval(interval);
					}
				} else {
					update_img.style.visibility = 'hidden';
					count++;
				}
			}, 1000);
		}
		td.appendChild(pwork);
		td.appendChild(txt);
		selection.appendChild(td);	
	}

	var vsel = document.getElementById('pvv_tr');
	while (vsel.childNodes.length > 1) {
		vsel.removeChild(vsel.lastChild);	
	}

	for (var count in v_tbl) {
		var td = document.createElement("td");
		var txt = document.createTextNode(v_tbl[count]);
		var pwork = document.createElement("input");
		pwork.value = (v_tbl[count]);
		pwork.type = "checkbox";
		if (v_set.indexOf(v_tbl[count]) >= 0) {
			pwork.checked = 1;
		}
		pwork.id = "v" + count;
		pwork.onclick = function() {
			var update_img = document.getElementById('blinking_update');
			var count = 0;
			var interval = window.setInterval(function() {
				if (update_img.style.visibility == 'hidden') {
					update_img.style.visibility = 'visible';
					count++;
					if (count >= 6) {
						window.clearInterval(interval);
					}
				} else {
					update_img.style.visibility = 'hidden';
					count++;
				}
			}, 1000);
		}
		td.appendChild(pwork);
		td.appendChild(txt);
		vsel.appendChild(td);	
	}
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
function sankey_arrange(sobj)
{
	var sankey_arr = [];
	var col_list = sobj.header;
	var product_id = -1;
	var modified_by_id = -1;
	var status_id = -1;
	var entered_by_id = -1;

	for (hx in col_list) {
		if (col_list[hx] == "Product") {
			product_id = hx;
		}
		if (col_list[hx] == "Modified By") {
			modified_by_id = hx;
		}
		if (col_list[hx] == "Status") {
			status_id = hx;
		}
		if (col_list[hx] == "Entered By") {
			entered_by_id = hx;
		}
	}
	var prod_set = {};
	var stat_set = {};
	var m_set = {}; // for modified by
	var e_set = {}; // for entered by
	var rows = sobj.rows;
	for (rx in rows) {
		var prod_name = rows[rx][product_id];
		var stat_name = rows[rx][status_id];
		var m_name = rows[rx][modified_by_id];
		var e_name = rows[rx][entered_by_id];
		if ( !(prod_name in prod_set)) {prod_set[prod_name] = 1;}
		if ( !(stat_name in stat_set)) {stat_set[stat_name] = 1;}
		if ( !(m_name in m_set)) { m_set[m_name] = 1;}
		if ( !(e_name in e_set)) { e_set[e_name] = 1;}
	}

var str1 = "=================================<br>";
	for ( var p in prod_set.keys() )
		for ( var s in stat_set.keys() ) {
	str1 += "<br>123456 " + prod_set[p] + "<br>";
	str1 += "<br>abc" + stat_set[s] + "<br>";
			var p2s = [prod_set[p] , stat_set[s] , 1] ;
			sankey_arr[sankey_arr.length] = p2s;
		}

document.getElementById("detail0").innerHTML = str1;
	for ( var s in stat_set.keys() ) 
		for ( var m in m_set.keys() ) {
			var p2s = [stat_set[s] , m_set[m] , 1] ;
			sankey_arr[sankey_arr.length] = p2s;
		}

	return sankey_arr;
}
function collect_product(sobj)
{
	var pobj = new Object();
	var col_list = sobj.header;
	var product_id = -1;
	var priority_id = -1;
	var status_id = -1;
	var number_id = -1;
	var planned_version_id = -1;
	var dm_id = -1;
	var dt = new Date();
	var date_id_array = [];
	var date_name_array = ['Current Assignment Date', 'Fix Date', 'Release to Testing Date', 'Verify Date', 'Release to Customer Testing Date', 'Force Close Date'];

	for (hx in col_list) {
		if (col_list[hx] == "Product") {
			product_id = hx;
		}
		if (col_list[hx] == "Planned Version") {
			planned_version_id = hx;
		}
		if (col_list[hx] == 'Date Modified') {
			dm_id = hx;
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
		if (date_name_array.indexOf(col_list[hx]) >= 0 ) {
			date_id_array.push(hx);
		}
	}




	var rows = sobj.rows;
	for (rx in rows) {
		var prod_name = rows[rx][product_id];
		var pv = rows[rx][planned_version_id];
		var d_str = rows[rx][dm_id];
		var d_arr = d_str.split(" ");
		var d_mod = new Date(d_arr[0]);
		var prio_name = rows[rx][priority_id];
		var stat_name = rows[rx][status_id];
		var number = rows[rx][number_id];
//alert('number:' + number + ' stat_name:' + stat_name);
		
		var d_min = 0;
		var d_max = 0;

		for (dx in date_id_array) {
			var d_str = rows[rx][date_id_array[dx]];
			var d_arr = d_str.split(" ");
			var dd;
			if (d_str != "" ) {
				d_arr = d_str.split(" ");
				dd = new Date(d_arr[0]);

				if (d_min == 0) {
					d_min = dd;
				} else {
					if (d_min > dd) {
						d_min = dd;
					}
				}
				if (d_max == 0) {
					d_max = dd;
				} else {
					if (d_max < dd) {
						d_max = dd;
					}
				}
			}
		}

		if (d_min == 0) {
			d_min = new Date();
		}
		if (d_max == 0) {
			d_max = new Date();
		}




		if (prio_name == '') {
			prio_name = 'Not_set';
		}


		
		if (!(pobj[prod_name])) {
			var arr = new Object();
			var arrv = new Array();
//			arr['total'] = 0; 
//			arr['high'] = {count: 0, list:''};
//			arr['other'] = {count: 0, list:''};
//			arr['Not_Assigned'] = {count: 0, list:''};  // open, not assigned
//			arr['Processing'] = {count: 0, list:''}; // open, assigned or In Development
//			arr['Done'] = {count: 0, list:''}; // Fixed, Closed, Released to Testing
//			arr['Unknown'] = {count: 0, list:''}; // status unknown or not classified
			arr['version'] = arrv;
			pobj[prod_name] = arr;
		}

		var simple_stat = simple_state_class(stat_name);

		var arr = pobj[prod_name];
		var arrv = arr['version'];
		var pv_ii = -1;
		for (ii = 0 ; ii < arrv.length ; ii++) {
			if (arrv[ii].pv == pv) {
				pv_ii = ii;
				break;
			}			
		}
		if ( pv_ii == -1 ) {
			arrpv = new Object();
			arrpv.pv = pv;
			arrpv.date_b = d_min;
			arrpv.date_e = d_max;
			arrpv.date_mb = d_mod;
			arrpv.date_me = d_mod;
			arrpv.open = 0;
			arrpv.close = 0;
			arrpv.unknown = 0;
			if (simple_stat == 'O') {
				arrpv.open += 1;
			} else if (simple_stat == 'C') {
				arrpv.close += 1;
			} else {
				arrpv.unknown += 1;
			}
			arrpv.list = number;
			arrv[arrv.length] = arrpv;
		} else {
			if (arrv[pv_ii].date_b > d_min) {
				arrv[pv_ii].date_b = d_min;
			}
			if (arrv[pv_ii].date_e < d_max) {
				arrv[pv_ii].date_e = d_max;
			}
			arrv[pv_ii].list += ',' + number;
			if (simple_stat == 'O') {
				arrv[pv_ii].open += 1;
			} else if (simple_stat == 'C') {
				arrv[pv_ii].close += 1;
			} else {
				arrv[pv_ii].unknown += 1;
			}
		}
//alert('2number2:' + number + ' stat_name:' + stat_name);
	}


	for (var pp in pobj) {	
		var arrv = pobj[pp]['version'];
		for (ii = 0 ; ii < arrv.length; ii++)  {
			var arrpv = arrv[ii];
			
			if (arrpv.date_b === undefined) {
				arrpv.date_b = arrpv.date_mb;
				alert('tell arnold arrpv.dateb undefined ==>' +pp+', ' + arrpv.pv);
			}
			if (arrpv.date_e === undefined) {
				arrpv.date_e = arrpv.date_mb;
				alert('tell arnold arrpv.dateb undefined ==>' +pp+', ' + arrpv.pv);
			}

			if (  (Math.abs(arrpv.date_e.getTime() - arrpv.date_b.getTime())/(1000*3600*24) ) > 365 ) {
				arrpv.date_b.setTime( arrpv.date_e.getTime() - 365*1000*3600*24);
			}

			if (  (Math.abs(arrpv.date_e.getTime() - arrpv.date_b.getTime())/(1000*3600*24) ) <=1.0 ) {
				arrpv.date_e = new Date();
				arrpv.date_e.setTime( arrpv.date_b.getTime() + 1000*3600*24);
			}

			var progress_str = 'progress: Open/Close/All ';
			var total = arrpv.open + arrpv.close + arrpv.unknown;
			progress_str +=  arrpv.open.toString() + '/' + arrpv.close.toString() + '/' + total.toString();
			arrpv.progress = progress_str
		}
	}



	return pobj;
}
function simple_state_class(stat_name) {
	var result = 'U';
	if (stat_name.substr(0,4) == 'Open') {
		result = 'O';
	} else if (stat_name.substr(0,6) == 'Closed') {
		result = 'C';
	} else if (stat_name.substr(0,5) == 'Fixed') {
		result = 'C';
	} else if (stat_name.substr(0,8) == 'Released') {
		result = 'C';
	} else if (stat_name.substr(0,14) == 'In Development') {
		result = 'O';
	} else {
		result = 'U';
	}
	return result;
}
function state_class(stat_name) {
	var result = 'U';
	if (stat_name.substr(0,4) == 'Open') {
		result = 'Open';
	} else if (stat_name.substr(0,6) == 'Closed') {
		result = 'Closed';
	} else if (stat_name.substr(0,5) == 'Fixed') {
		result = 'Fixed';
	} else if (stat_name.substr(0,8) == 'Released') {
		result = 'Released';
	} else if (stat_name.substr(0,14) == 'In Development') {
		result = 'Developing';
	} else {
		result = 'Unknown';
	}
	return result;
}

function weeknum(ttpro_obj) {
	var simple_obj = new Object();
	var header_arr = [];
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
	var product_id = -1;
	var planned_version_id = -1;
	var number_id = -1;
	var status_id = -1;
	text += "<table id='table_detail'  class='sortable' ><thead><tr >";
// style='width:100%;border:0;cellpadding:5;cellspacing:0;margin=15px 0px;'
	var col_list = sobj.header;

	for (hx in col_list) {
		if (hx == -1) {
			text += "<th class = 'nosort'>";
		} else {
			text += "<th>";
		}
		text += '<h3>' + col_list[hx] + "</h3></th>";
		if (col_list[hx] == "Product") {
			product_id = hx;
		}
		if (col_list[hx] == "Planned Version") {
			planned_version_id = hx;
		}
		if (col_list[hx] == "Number") {
			number_id = hx;
		}
		if (col_list[hx] == "Status") {
			status_id = hx;
		}
		if (debug_set == "" && hx >= 9) {
			break;
		}
	}
	text += "</tr></thead><tbody>";
	
	var rows = sobj.rows;
	var even=true;
	for (rx in rows) {
		var row1 = rows[rx];
		var status = row1[status_id];
		var pp = row1[product_id];
		var vv = row1[planned_version_id];
		status = status.split(',')[0]
		if ( (list_str == 'all' || list_str.indexOf(row1[number_id]) >=0 ) && pv_set_sel(pp, vv)){
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
					text += "<td ><a href='onedefect.php?defectNumber=" + row1[cx] + "'>" + row1[cx] + "</a></td>";
				} else {
					text += "<td >" + row1[cx] + "</td>";
				}
				if (debug_set == "" && cx >= 9) {
					break;
				}
			}
			text += "</tr>";
		}
	}

	text += "</tbody></table>";
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
