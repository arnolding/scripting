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
