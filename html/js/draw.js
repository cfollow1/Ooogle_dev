

function getData(){

	var data = new Object;
	
	dataLocations = ['Chr4:7254049..7256406','Chr4:7256538..7261180']
	data.canvas_size = '100';
	data.boss_name   = 'boss_name';
	data.divID = 'canvas';
	var paperHeight = $("#"+data.divID).height();
	var paperWidth  = $("#"+data.divID).width();
	data.paperHeight = paperHeight;
	data.paperWidth = paperWidth;
	data.bossLocations = dataLocations;
	data.boundary_left = '7254049';
	data.boundary_right = '7261180';
	drawcanvas(data);
	//Populate canvas
}


function drawcanvas(data){

	//var interval_length = interval_max-interval_min;
	var colour 		= 'red';
	var paperHeight 	= data.paperHeight;
	var paperWidth  	= data.paperWidth;
	var chrWidth 		= paperWidth * .8 ;
	var paper 		= Raphael(document.getElementById('canvas'), paperWidth, paperHeight);
	var left		= data.boundary_left;
	var right		= data.boundary_right;
	//paper.setViewBox(0,0,paper.width,paper.height);		
	
	
	var chromosome  	= paper.rect(100,paperHeight/6, chrWidth, 100, 12);  
	var c 				= createAttributesObject(chromosome);
	
	var line			= paper.rect(100,(paperHeight/2), chrWidth, 1, 12).attr({'fill': 'black'});
	var min				= paper.text(c.x-5,c.y-9, left);
	var max		 		= paper.text(c.x+5 + c.w ,c.y-9, right);

	
}


//Returns an object with x,y,h,w
function createAttributesObject(object){
	var obj = new Object();
	obj.x = object.attr('x');
	obj.y = object.attr('y');
	obj.h = object.attr('height');
	obj.w = object.attr('width');
	return obj;
}



/*
function loadChromosomes()
{

		$("div.chromosome").each(function(){
			var divID		 = this.id;
			var json		 = jQuery.parseJSON($(this).text());
			if(!json['boss_min']) { return ; };
			var interval_min = json['left'];
			var interval_max = json['right'];
			var genes		 = json['boss_headers'];
			var coords 		 = json['coords'];
			
			generateChromosome(divID,interval_min,interval_max,genes,coords);
		});
			
}

function setView()
{
	Paper.setViewBox(x, y, w, h, fit)
	
}


function CanvasClick(e) {
    if (e.target.nodeName == "svg")
    {
		//alert(can
       //This will only occur if the actual canvas area is clicked, not any other drawn elements
    }
}


function generateChromosome(divID,interval_min,interval_max,genes,coords){
		var interval_length = interval_max-interval_min;
		var colour 		= 'red';
		var paperHeight = 150;
		var paperWidth  = 1000;
		var chrWidth 	= 800;
		var paper 		= Raphael(divID, paperWidth, paperHeight);
		paper.setViewBox(0,0,paper.width,paper.height);		
		
		
		var chromosome  	= paper.rect(100,paperHeight/6, chrWidth, 100, 12);  
		var line			= paper.rect(100,(paperHeight/2), chrWidth, 1, 12).attr({'fill': 'black'});
		var t_min			= paper.text(50,(paperHeight/2), interval_min);
		var t_max		 	= paper.text(paperWidth-50,(paperHeight/2), interval_max);
		var geneShape		= new Array();
		var geneShapeText	= new Array();
		var yTop			= 0;
		var scale 			= 2;
		var zoom		    = paper.rect(0,0, 20, 20).attr({'fill': 'black'});
		zoom.click(function() {  
			paper.setViewBox(0,0,paper.width/2,paper.height/2);	
		});
		var zoom2		    = paper.rect(20,0, 20, 20).attr({'fill': 'red'});
		zoom2.click(function() {  
			paper.setViewBox(0,0,paper.width,paper.height);	
		});
		
		for(var i = 0; i < genes.length ; i++)
		{
			var yPos; 
			var geneID= genes[i];
			var geneName= geneID.split('|')[0];
			var xPosLeft		 = coords[i].split("|")[0], xPosRight = coords[i].split("|")[1];
			var geneLength		 = ((xPosRight - xPosLeft) / 800);
			var xPos 			 = Math.floor((xPosLeft / interval_max) * 800) ;			
			if(yTop == 0)
			{
				yTop++;
				yPos = (paperHeight/2) - 24;
				geneShapeText[i]	 =  paper.text(xPos,yPos-5,geneName);
			}
			else
			{
				yTop = 0;
				yPos = (paperHeight/2) + 5;
				geneShapeText[i]	 =  paper.text(xPos-((geneName.length)*3.5),yPos+50,geneName);
			}
			geneShape[i] 		 = paper.rect(xPos,yPos, geneLength * scale, 20, 12).attr({'fill': 'white'}).attr({'title': geneID});
			geneShape[i].data	("name" , geneID	);
			geneShape[i].data	("start", xPosLeft	);
			geneShape[i].data	("stop" , xPosRight);
			//geneShape[i].node.onclick = function () {   c.attr("fill", "red");};
			
		
	}
}

*/