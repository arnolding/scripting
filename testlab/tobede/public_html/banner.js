function show_header(username) {
		var dbody = document.getElementsByTagName('BODY')[0];

		var body_header = document.createElement("Div");
		body_header.id = 'body_header';
		body_header.className = 'topnav w3-left';
		body_header.style = 'overflow:auto;';
		var left = document.createElement("Div");
		left.className = 'subnavl';
		var right = document.createElement("Div");
		right.className = 'subnavr';

		var hdr = [ 
			{t: 'Silvaco WiKi',h:'http://svcwiki/index.php/Main_Page'},
			{t: 'BusDev', h:'busdev.html'},
			{t: 'FAE Cat', h:'byfae.php'},
			{t: 'ENG Cat', h: 'byeng.php'},
			{t: 'Release', h: 'release.php'},
			{t: 'Help', h: 'https://drive.google.com/open?id=0B5j4pyWfpIGSNEJReEp6QnZ3Y0U'} ];

		var special = [ 
			{t: 'Daily Build',h:'daily_build.php'}];

		var cur_loc = window.location.pathname;

		var seg = cur_loc.split("/");
		var loc_last = seg[seg.length - 1];
	
		for ( var i = 0 ; i < hdr.length ; i++) {
			var anchor = document.createElement("A");
			anchor.href = hdr[i].h;
			anchor.innerHTML = hdr[i].t;
			var seg2 = hdr[i].h.split("/");
			if (loc_last == seg2[seg2.length-1]) {
				anchor.className = 'active';
			}
			left.appendChild(anchor);
		}
		if (username && username == 'Arnoldh') {
			for ( var i = 0 ; i < special.length ; i++) {
				var anchor = document.createElement("A");
				anchor.href = special[i].h;
				anchor.innerHTML = special[i].t;
				var seg2 = special[i].h.split("/");
				if (loc_last == seg2[seg2.length-1]) {
					anchor.className = 'active';
				}
				right.appendChild(anchor);
			}
		}
		if (username == "") {
			var anchor = document.createElement("A");
			anchor.href = "http://svcwiki/index.php?title=Special:UserLogin&returnto=Main+Page";
			anchor.innerHTML = 'Login';
			right.appendChild(anchor);
		} else {
			var anchor = document.createElement("A");
				anchor.href = "http://svcwiki/index.php/User:" + username;
				anchor.innerHTML = username;

				right.appendChild(anchor);
		}
		

		body_header.appendChild(left);
		body_header.appendChild(right);
		
		if (dbody.hasChildNodes()) {
			var n1 = dbody.firstChild;
			dbody.insertBefore(body_header , n1);
		} else {
			dbody.appendChild(body_header);
		}
		
}


