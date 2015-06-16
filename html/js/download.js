function download(){
var json = $("#downloadMe").html();
var jsonObject = $.parseJSON(json);


}



/*
//var obj = $.parseJSON($("#downloadMe").html());
//var obj2 = eval(obj);
var html = "";
for (interval in jsonObject){
	console.log(interval);
	console.log(jsonObject[interval]);
	orgObj = jsonObject[interval];
	orgName = '';
	for(name in orgObj){
		orgName=name;
	}
	console.log(orgObj[name]);	

	var fields = ['boss_org','boss','boss_location',
	'hit_org','org_interval','hit_in_intervals','hit_location','boss_e','hit_e','hit_tophit',
	'rbh','annotation1','annotation2'];	

	html += interval + "\t";
	for (i in fields){
		field = fields[i];
		html +=  orgObj[orgName][field] + "\t";;

	}
		html += "<br>";
	
//	html +=interval + name + orgObj[name]['boss_org']+"<br>";

}

header = ['Interval','Query Organism','Location',
	'Subject Organism','Subject Interval','Hit In Intervals',
	'Location','Query Expect','Subject Expect','Subject top hit','Reciprocal Best Hit','Query Annotations','Subject Annotations'].join("\t");

$("#json").html(html);

newwindow=window.open();
newdocument=newwindow.document;
newdocument.write(header + "<br>\n" + html);
newdocument.close();


}
*/
