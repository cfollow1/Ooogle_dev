/* Used by index.php
1) Grabs form data
2) Does a bit of verification / sanitation of input
3) Creates a query and submits it to table.php
#Plugins/Libraries Used
Jquery
Dropdown in step #1 uses Chosen
FCBKautocomplete used for autocomplete of chromosomes
#Ajax:
Get Chromosomes and markers for popup
Get chromosomes for chr inputs
*/
autocomplete = new Array();
/*
//Floating marker related 
*/
function hideFloatingMarker(){
	$("#floating_marker").hide();
}


 


//Adds marker to input field based on input mode (interval,midpt,express)
//Markers avail for Athaliana Rice zmays
//Current have to manually edit the CHR for athaliana and osativa
function addMarker(div,org){
	//Get input mode and marker contents
	var input_mode = $("#"+div+"_input_div :checked").attr("data-type"); 
	if($("#popup_marker").val()){
		var marker = $("#popup_marker").val().split("\t"); //chr start stop
	}
	else{
		return; // user clicked too fast!
	}
	var markerName = marker[0];
	var chr = marker[1];
	if(org == 'Athaliana_167' || org == 'Osativa_193'){
		chr = "Chr" + chr;
	}
	var start_stop = marker[2] + ".." + marker[3];
	var location;
	
	
	//express
	if(input_mode == 'csv'){		
		div = div+"_input_textarea";
		if($("#"+div).val().length > 0){
				$("#"+div).val($("#"+div).val() + "," + chr + ":" + start_stop );
			}
		else{
			$("#"+div).val($("#"+div).val() + chr + ":" + start_stop );
		}
		
	}
	else{
		//ADD CHROMOSOME
		$("#"+div+"-chr").trigger("addItem",[{"title": chr, "value": chr}]);
	/*	
		if (option.hasClass("selected")) {
            var id = addItem(option.text(), option.val(), true, option.hasClass("locked"));
            temp_elem.append('<option value="'+option.val()+'" selected="selected" id="opt_'+id+'"class="selected">'+option.text()+'</option>');
          }
		*/
		
		//ADD MIDPOINT OR INTERVAL
		if(input_mode == 'interval'){
			location = start_stop;
		}
		else if(input_mode =='midpoint'){
			location  = Math.floor( (parseInt(marker[2])  + parseInt(marker[3]))  / 2 );
			var offset = (marker[3] - marker[2]) + 1;
			//Add offset if applicable
			if($("#"+div+"-offset").val().length > 0){
				$("#"+div+"-offset").val($("#"+div+"-offset").val() + ',' +  offset);
			}
			else{
				$("#"+div+"-offset").val($("#"+div+"-offset").val() +  offset);
			}
			
		}
		//Add midpoint or interval
		if($("#"+div+"-interval").val() && $("#"+div+"-interval").val().length > 0){
			$("#"+div+"-interval").val($("#"+div+"-interval").val() + ',' +  location);
		}
		else{
			$("#"+div+"-interval").val($("#"+div+"-interval").val() +  location);
		}
		
	}
}
//Function creates a marker, loads up CHR dropdown and marker dropdown
//Then it updates the funcitonality of the submit marker button
function markerpopup(org,div){

	//Hide marker popup if switching between organisms
	$("#floating_marker").hide();
	$("#addMarker").attr("onClick","addMarker('"+div+"','"+org+"')");
	//alert(org + div + input_mode);
	//alert("div is "+div + "orgname=" +orgname);
	$("#marker_org_title").html("Showing markers for <b>" + org + "</b><hr>");
	//populate select
	$.get('query_db.php?type=chr&org='+org, function(chrDATA){
		$("#available_chr").html("<select id='popup_chr'>"+chrDATA+"</select>"); 
	});
	
	//populate marker
	$.get('query_db.php?type=marker&org='+org, function(markerDATA){
		if(markerDATA == undefined || markerDATA.length == 0){
			$("#available_markers").html("<select id='popup_marker'><option>none</option></select>"); 
			$("#addMarker").attr("onClick","alert('Sorry no markers for ' + '"+org+"')");
		}
		else{
			$("#available_markers").html("<select id='popup_marker'>"+markerDATA+"</select>"); 
		}
		
	});
	$("#floating_marker").show();
}

//Returns chromosome data for FCBKplugin w ajax
function getChrJson(div,org){
		$.get('query_db.php?type=chr&return_type=json&org='+org, function(chrDATA){
			return chrData;
		});
}

//Updates chromosome list for each organsim when switching between boss organisms
//Also used to create a new chromosome list upon adding organsisms
function updateCHRLIST(div,org){
	div = div+"-chr";
	var url		= "query_db.php?type=chr&org="+org+"&return_type=fcbk";
	//var url		= "query_db.php?type=chr&org="+org+"&return_type=jtoken";
	
	$("#"+div).trigger("destroy");
	$("#"+div+"-td").html('');
	$("#"+div+"-td").html("<input type='text' id='"+div+"'>");
	
		$("#"+div).fcbkcomplete({
		json_url: url,
		cache: true,
		filter_case: false,
		filter_hide: true,
		newel: true,
		width: 241,  
		addontab: true,
		addoncomma: true,
		height:6,
		input_min_size:0,
		firstselected: true
	});
}



function populateChr(div,org){
		
		$.get('query_db.php?type=chr&org='+org, function(data){
			 $("#"+div).html("<select id='"+org+"-chr' multiple class='chosen_textinput'>"+data+"</select>"); 
			 $("#"+div+" select").attr("data-placeholder","Chr or Scaffolds").chosen();
			//alert(data);
		});
}


function checkInputs()
{
	//validateInput()
	//submitInpit()
	createQuery();
}

function createQuery()
{
	//Get Boss  , type of input, selected organisms
	var boss 		= $('#select_org').val(); //BOSS ORG NAME
	var type 		= $('#'+boss+"-bossinput").attr('title'); //Monocot or Dicot
	var input_type  = $('#left_container [name="interval"]:checked').attr('data-type'); //Interval Midpoint or CSV
	var opposite_type; //OPPOSITE OF DICOT OR MONOCOT
	
	if(type == 'monocot'){
		opposite_type = 'dicot';
	}
	else{
		opposite_type = 'monocot';
	}
	//Get selected comparison organisms
	var selected_organisms = new Array;
	var selected =	$("#"+opposite_type+"-dropdown-div :checked").get();
	for(index in selected){
		selected_organisms.push(selected[index].value);
	}
	//
	if(selected_organisms.length == 0){
		return alert('No comparison organisms selected.');
	}
	
	var get = "?boss_org="+boss; //So we know which org the boss is
	selected_organisms.unshift('boss')
	//Get inputs for selected monocots or dicots
	for(index in selected_organisms){
		var org 			= selected_organisms[index];
		var orgInputType 	= $('#'+org+"_input_div :checked").attr('data-type');
		var loci			= getLoci(org,orgInputType);
		get		+= "&"+org+"=" + loci.join();
		if(loci[0] == 'fail')
		{
			alert(loci[1]);
			return;
		}
	}
	selected_organisms.shift(); //remove boss from organisms
	selected_organisms_string = selected_organisms.join();

	//Get extra settings	
	var settings = new Array();
	settings[0] = "&rbh_opt="+$("#rbh_option").is(':checked');
	settings[1] = "&interval_opt="+$("#interval_option").is(':checked');
	var left_number = $("#left-number").val() ;
	var right_number = $("#right-number").val() ;
	if(left_number.match(/\D/)){
		return alert('Only digits allowed in evalue');
	}
	if(right_number.match(/[^\d-]/)){
		return alert('Only numbers and - allowed in evalue');
	}
	settings[2] =  "&eval="+left_number + "e" + right_number;
	settings[3] = "&title="+ $("#job_title").val() ;	
	
	window.open('table.php'+get+"&boss_type="+type+"&orgs="+selected_organisms.join()+settings.join(""),"_blank");

}

//This function goes through the form and gets the values of selected organisms and does some error checking
//It also converts midpoints to length 
//It expects intervals in start...stop format
//It expects inputs delimited by commas

function expressMode(textarea_div){


}

function getCSV(textarea_div)
{
	var input_lines = $(textarea_div).val().split("\n");
	var inputs = new Object();	
	for(var i in input_lines ){
		var split = input_lines[i].split(':');
		var header = split[0];	var value = split[1];
		inputs[header] = value;
	}
	for(param in inputs ){
		alert(param + inputs[param]);
	
	}
	
	

}

//Reads in the interval midpoint or express
function getLoci(org,input_type)
{
	var loci			= new Array();
	var fail			= ['fail','failed input for '+org + 'default'];
	var interval		= $('#'+org+"-interval").val(); //Interval or Midpoint
	//var chr 			= $('#'+org+"-chr").val().trim(); //withut fbck
	var chr 			= '';
	$('#'+org+"-chr :selected").each(function(){
		chr += (this.value+",");
	});	
	chr = chr.slice(0, -1);
	
	var offset	 		= $('#'+org+"-offset").val().trim(); 
	var interval_array 	= new Array(), chr_array = new Array(), offset_array = new Array();
	
	
	if(input_type == 'csv')
	{	
		var textarea = $('#'+org+"_input_textarea").val();
		if(textarea.indexOf(',') == -1 && textarea.length > 6){
			return [textarea];
		}
		temp_loci = textarea.split(",");
		if(temp_loci.length <=1 ){ 
			return ['fail','failed input for express csv for '+org];
		}
		else{ 
			var loci = new Array();
			for(i in temp_loci){
				var locus = temp_loci[i];
				if(locus.length > 6){
					loci.push(locus);
				}
			}
			return loci;
		}
		//return getCSV('#'+org+"_input_textarea");
	}
	else
	{
		if(interval && chr){
			interval_array	= (interval.replace(/ /g,/,/)).split(/,/);
			chr_array		= (chr.replace(/ /g,/,/)).split(/,/);
			if(interval_array.length != chr_array.length){
				fail[2] += ' please input same amount of CHR and Intervals';
				return fail;
			} //Correct # of inputs
			//If interval
			if(input_type =='interval'){
				for(var i=0; i < interval_array.length; i++){
					loci.push(chr_array[i]+':'+interval_array[i]);
				}
			}
			//If midpoints are entered
			else if(input_type = 'midpoint'){
				if(offset){
					offset_array = (offset.replace(/ /g,/,/)).split(/,/);
					if(offset_array.length != chr_array.length){return fail;} //Check correct # of inputs
					for(var i=0; i < interval_array.length; i++){
						var start = (parseInt(interval_array[i])-parseInt(offset_array[i]));
						var stop  = (parseInt(interval_array[i])+parseInt(offset_array[i]));
						start     = start < 0 ? 0 : start; // IS start less than 0?
						loci.push(chr_array[i]+':'+ start +".."+ stop);
					}
				}
				else{return fail;} // midpoint undef
			}
			
		}
		else{
			fail[2] += 'undefined';
			return fail;
		} //interval || chr undef
	}
	
	return loci; //probably can just return the string
}



function parseUserInput(org,type,interval,chr,offset,csv){
	//returns an array of loci CHR:Start...Stop,
	//interval format StartBP - End BP then space or comma
	//midpoitn format enter midpoint and area around the midpoint
	var interval_array 	= new Array;
	var midpoint_array 	= new Array;
	var chr_array 		= new Array;
	var offset_array 	= new Array;
	var loci			= new Array;
	var fail 			= ['Fail'];
	//Interval case EG 100..500,200..300, Chr5,chr6
	if(type =='interval'){ 
		interval_array 	= interval.replace("/ /g",",").split("/,");
		chr_array 		= chr.replace("/ /g",",").split("/,");
		//Probably need to check that Intervals are in Start..STop format
		if(interval_array.length == chr_array.length){
			for(var i = 0; i < interval_array.length; i++){
				var locus = chr_array[i]+":"+interval_array[i];
				loci.push(locus);
			}
		}
		else{
			return fail;
		}
	}
	else if(type =='midpoint'){	
		midpoint_array 	= interval.split("/ |,/");
		chr_array 		= chr.split("/ |,/");
		offset_array	= offset.split("/ |,/");
		if(interval_array.length == chr_array.length && chr_array.length == offset_array.length){
			var i = midpoint_array.length;
			while(i--){
				var start = midpoint_array[i]; - offset_array[i];;
				var stop = midpoint_array[i]; + offset_array[i];;
				loci.push(chr_array[i]+":"+start+".."+stop)
			}
		}
		else{
			return 'Fail';
		}
	}
	else if(type =='csv'){ //multiple input types, work on this later
	
	
	}

	return loci;
}








function addOrg(checked,title,value)
{
	//Remove div
	if(!checked)
	{
		$("#"+value+"-chr").trigger("destroy");
		$("#"+value+"_input_div").remove();
		
		
	//	$("#"+div).trigger("destroy");
	
	}
	else
	{
		$("#"+title+"_inputs").append(generate_org_input(title,value));
		updateCHRLIST(value,value)
	}

}

function generate_org_input(title,org_name)
{
	var header = '<div name="title" class="org_input_contents">Input physical location on genome for <br>' +org_name+ '</div><br>';
	var input_options =
	"\tInterval<input type='radio' 	id='"+org_name+"_interval_option' name='"+org_name+"_interval' onchange='intervalMode(this.value)'	value='"+org_name+"' data-type='interval' checked>\
     \tMidpoint<input type='radio'  id='"+org_name+"_midpoint_option' name='"+org_name+"_interval' onchange='midpointMode(this.value)'	value='"+org_name+"' data-type='midpoint'>\
     \tExpress<input type='radio'   id='"+org_name+"_csv_option' 	  name='"+org_name+"_interval' onchange='csvMode(this.value)'		value='"+org_name+"' data-type='csv'>\
	 <button type='button' onClick='markerpopup(\""+ org_name +'","'+org_name+"\")'>Marker</button>";
     var inputs =
	"<table id='"+org_name+"_input_table'>\
        <tr>\
            <td><span id='"+org_name+"-interval-txt' class='label'>INTERVAL</span></td>\
            <td><input type='text' id='"+org_name+"-interval' ></td>\
        </tr>\
        <tr>\
            <td><span id='"+org_name+"-chr-txt' class='label'>CHR</span></td>\
            <td><input type='text' id='"+org_name+"-chr' placeholder='first' /></td>\
        </tr>\
        <tr>\
            <td><span id='"+org_name+"-offset-txt' class='label'>OFFSET</span></td>\
            <td><input type='text' disabled='disabled' id='"+org_name+"-offset'></td>\
        </tr>\
        </table>\
		<div id='"+org_name+"_input_textarea-div' class='ta-wrapper' style='display:none;' >\
		<br><textarea id='"+org_name+"_input_textarea' rows=5 cols=41 placeholder='Example: Chr6:205..300,Chr6:300..400' ></textarea>    \
        </div>";	 
	
	var div  = "<div id='"+org_name+"_input_div' class='org_input_div bluebox padding_bottom'>";
	div += header + input_options + inputs + "</div>";
	return (div);

}


function display(type)
{
   //This is currently disabled as we now show both monocots and dicots
   return;
	populateChr('boss-chr-div',$("#select_org").val());
	//Hide multiple select box in right frame
	if(type == 'monocot'){
		//Checkbox div
		$("#monocot-dropdown-div").hide();
		$("#dicot-dropdown-div").fadeIn();
		$("#type-label").html('List of available dicots');
		//Step 3 Div
		$("#moncot_inputs").hide();
		if( $("#dicot_inputs").css('display') == 'none'){
		//show only if switching from dicot to monocot
			$("#dicot_inputs").show();
		}
	}
	else if(type == 'dicot'){
		//Checkbox div
		$("#dicot-dropdown-div").hide();
		$("#monocot-dropdown-div").fadeIn();
		$("#type-label").html('List of available monocots');
		//Step 3 Div hidden states
		$("#dicot_inputs").hide();
		if( $("#monocot_inputs").css('display') == 'none'){
			//show only if switching from monocot to dicot
			$("#monocot_inputs").show();
		}
	}
}
//Add and remove organism inputs
function addRemove(checkboxID,checked,type){
	var org_name = checkboxID.replace("-checkbox","");
	if(!checked){ // Read this as 'if unchecked'
		$("#"+org_name+"-div").remove();
	}
	else{
		add_field(org_name,checked,type);
	}
}


function add_field(name,type){
	var createFields =
	"<div id='"+name+"-div'>";	
	var title =
	"";
	
	var table =
	"<table id='"+name+"-table'>\
        <tr>\
            <td><span id='"+name+"-label1' class='label'>INTERVAL</span></td>\
            <td><div id='"+name+"-input1' class='input'><input type='text'></div></td>\
        </tr>\
        <tr>\
            <td><span id='"+name+"-label2' class='label'>CHR</span></td>\
            <td><div id='"+name+"-input2' class='input'><input type='text'></div></td>\
        </tr>\
        <tr>\
            <td><span id='"+name+"-label3' class='label'>OFFSET</span></td>\
            <td><div id='"+name+"-input3' class='input'><input type='text' disabled></div></td>\
        </tr>\
        </table>\
	";
	var html = createFields + title + table +"</div>";
	$("#bottom").append(html);
	//Append
}

//Functions for changing the mode of input
function csvMode(divID){
	$("#"+divID+"_input_table").hide();
	$("#"+divID+"_input_textarea-div").show();
}
function intervalMode(divID){
	//hide divs
	$("#"+divID+"_input_textarea-div").hide();
	$("#"+divID+"_input_table").show();
	//disable offset
	$("#"+divID+"-offset").attr("disabled","false");
	
}
function midpointMode(divID){
	//hide divs
	$("#"+divID+"_input_textarea-div").hide();
	$("#"+divID+"_input_table").show();
	//enable offset
	$("#"+divID+"-offset").removeAttr("disabled");
}




function attachToolTips() //idea, create a data object and have this funciton appended to all, and only load up the data objects
{
$("#options_tooltip").hover(function () {
    $(this).append('<div class="tooltip"><p>Select only reciprocal best hits instead of partial hitsSelect if we are looking at  interval or entire org <br> eval</p></div>');
  }, function () {
    $("div.tooltip").remove();
  });

 //now we attached these to all toolips, we can loa dup the data objects with a .each, storing the data as hash keys 
}


//unused

function enableTabs()
{
	$('#tabs').each(function(){
		// For each set of tabs, we want to keep track of
		// which tab is active and it's associated content
		var $active, $content, $links = $(this).find('a');

		// If the location.hash matches one of the links, use that as the active tab.
		// If no match is found, use the first link as the initial active tab.
		$active = $($links.filter('[href="'+location.hash+'"]')[0] || $links[0]);
		$active.addClass('active');
		$content = $($active.attr('href'));

		// Hide the remaining content
		$links.not($active).each(function () {
			$($(this).attr('href')).hide();
		});

		// Bind the click event handler
		$(this).on('click', 'a', function(e){
			// Make the old tab inactive.
			$active.removeClass('active');
			$content.hide();

			// Update the variables with the new link and content
			$active = $(this);
			$content = $($(this).attr('href'));

			// Make the tab active.
			$active.addClass('active');
			$content.show();

			// Prevent the anchor's default click action
			e.preventDefault();
		});
	});
}

