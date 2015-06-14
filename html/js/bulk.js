//Populate selected div with 

function submitCSV()
{
	var formInput = ($("#csv_textarea").val()).split("\n");
	
	//Get Data
	var title;
	var boss = "";
	var orgs = new Array(); 
	var org_inputs = new Array();
	var current_org = '';
	for(var i in formInput){
		var line = formInput[i];


		if(line == '' || line.length==0){ continue ; }
		
		
		if(line.substring(0,5) != "title:"){
			line = line.replace(/^\s+|\s+$/g , "");
		}

		if(param_value = line.split(':'))
		{
			switch(param_value[0].toUpperCase())
			{
				case 'TITLE': 	title		=param_value[1]; break;
				case 'BOSS': 	
					boss		= param_value[1];
					current_org = param_value[1];
					org_inputs[current_org] = new Array();
					break;
				case 'ORG':
					orgs.push	(param_value[1]);
					current_org = param_value[1];
					org_inputs[current_org] = new Array(); 
					break;
				default: 
					if(current_org != ''){
						org_inputs[current_org].push(line); 
					}
					break;
			}
		}
	}
	//Verify integrity of data
	
	
	
	//Create and submit query
	var query = '';

	for(org in org_inputs){
		if(org == boss){
			query +="?boss_org=" + boss + "&boss=" + org_inputs[boss].join(",");
		}
		else{
			query +="&"+org+"=" + org_inputs[org].join(",");
		}	
	}
	var interval_opt = true;
	if($("#interval_opt").is(':checked')){
		interval_opt = false;
	}
//	alert(interval_opt);
	
	query += 
	"&title=" + title	
	+"&rbh_opt=" + $("#rbh_opt").is(':checked')
	+"&interval_opt=" + interval_opt
	+"&eval=none"  
	+ "&orgs=" + orgs.join(",") ;

	//This might be for markers or something?
	var boss_type = 'dicot';
	if( boss == 'Bdistachyon_192' || boss == 'Osativa_193' || 
		boss == 'Sbicolor_79' || boss == 'Sitalica_164' ||
		boss == 'Bdistachyon_192' ){
		boss_type = 'Zmays_181';
	}
	
	
	 
		
	query += "&boss_type="+boss_type;
	//alert(query);
		
	//	window.open('http://stan.cropsci.uiuc.edu/browser/table.php'+query);
	window.open('table.php'+query);
}




function checkInputCSV()
{
	var formInput = ($("#csv_textarea").val()).split("\n");
	
	//Probably could have used an associative array
	var organisms 		= new Array();
	var intervals 		= new Array();
	var chromosomes 	= new Array();
	var offset 			= new Array();
	var rbh_opt 		= new Array();
	var interval_opt 	= new Array();
	var title			;
	

	//#if input has more than one : then do something to warn
	//check for multiple boss too? or just assume first org is boss
	//sanitize the list for random chars?
	
	for(var i in formInput)
	{
		var input = formInput[i];
		var param_value;
		if(input == ''){ continue ; }
		
		
		if(param_value = input.split(':'))
		{
			
			switch(param_value[0].toUpperCase())
			{
				case 'ORG': 			organisms.push	(param_value[1]+"_peptide.fa"); break;
				case 'MIDPOINTS': 		intervals.push	(param_value[1]); break;
				case 'CHROMOSOMES':		chromosomes.push(param_value[1]); break;
				case 'OFFSET': 			offset.push		(param_value[1]); break;
				case 'RBH_OPT':			rbh_opt = 		(param_value[1]); break;
				case 'INTERVAL_OPT':	interval_opt =	(param_value[1]); break;
				case 'TITLE':			title = param_value[1]; break;
				default: alert('problem on line '+i +  ' matched ' +param_value[0] );
				return;
			}
		}
	}
	//Should sanitize input here .... todo
	//return 0 if bad
	
	var query = "";
	//BOSS
	query +=
			" -boss_filepath="  + organisms[0]  +
			" -boss_intervals=" + intervals[0]  +
			" -boss_chromosomes="		+ chromosomes[0]  +
			" -boss_offset=" 	+ offset[0] ;
	//OTHERS
	query += 
		" -organism_filepaths=" + (organisms.slice(1)).join(" ") +
		" -organism_intervals=" + (intervals.slice(1)).join(" ") +
		" -organism_chromosomes=" + (chromosomes.slice(1)).join(" ") +
		" -organism_offset=" + (offset.slice(1)).join(" ") +
		" -rbh="+ rbh_opt +
		" -interval=" + interval_opt +
		" -title=" + title;
		;
	$("#query").html("<input type='text' value='"+query+"' size='140' id='query_string'></input>"); 
	
	return 1;
}



function populateChr(div,org){
		if(org == 'none'){
			$("#"+div).html(""); 
			return;
		}
		$.get('query_db.php?type=chr&org='+org, function(data){
			 $("#"+div).html("<select id='"+org+"-chr'>"+data+"</select>"); 
		});
		
}
