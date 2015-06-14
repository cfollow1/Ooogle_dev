
function createDiv(divID)
{
  
    

}



function display(type)
{
	//Hide multiple select box in right frame
	if(type == 'monocot'){
		alert('hiding monocot'+type);
		$("#monocot-dropdown-div").hide();
		$("#dicot-dropdown-div").show();
	}
	else if(type == 'dicot'){
		alert('hiding dicot'+type);
		$("#dicot-dropdown-div").hide();
		$("#monocot-dropdown-div").show();
	}
	//Hide all divs that are of other type

	
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
function removeField(divID)
{
	
}
