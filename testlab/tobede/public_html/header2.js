function nl2br (str, is_xhtml)
{
	var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
	return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}
function GetURLParameter(sParam)
{
	var sPageURL = window.location.search.substring(1);
	var sURLVariables = sPageURL.split('&');
	for (var i = 0; i < sURLVariables.length; i++) 
	{
		var sParameterName = sURLVariables[i].split('=');
		if (sParameterName[0] == sParam) {
			return sParameterName[1];
		}
	}
	return "";
}
function datestr2local(dstr, offset)
{
	var dt_str;
	var dt = new Date(dstr);
	if (dstr.indexOf("T") <0) {
		dt = new Date(dt.getTime() - (offset + 60*dt.getTimezoneOffset())*1000)
	}
	dt_str = (dt.getMonth()+1) + "/" +
				dt.getDate() +"/" +
				dt.getFullYear() + " " +
				dt.getHours() + ":" +
				dt.getMinutes();

	return dt_str;
}
