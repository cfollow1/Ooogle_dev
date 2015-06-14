<!DOCTYPE html >
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Results for input intervals</title>
<LINK href="./css/table.css" rel="stylesheet" type="text/css">
<LINK href="./css/tooltip.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="./js/tablesort.js"></script>
<script type="text/javascript" src="./js/jquery-1.8.2.min.js"></script>
<script type="text/javascript" src="./js/resizable-tables.js"></script>
<script type='text/javascript'>

function toggle(id){
	id = id.replace(/\./g,'\\\.');
	id = id.replace(/\:/g,'\\\:');
	$("#"+id).toggle();
}



</script>
</head>
<!-- CSS -->
<body>

<button type='button' onclick='$("#instructions").toggle()' style='margin-left:15px;margin-top:5px' >Show/Hide Legend</button>
<br>
<table id='instructions' style='' >
<thead><tr><th>Header</th><th>What does it mean?</th></tr></thead>
<tr><td>Expect</td><td>Expect value of hit found for the query sequence.</td></tr>
<tr><td>%ID</td><td>Percent identity of hit for the query sequence.</td></tr>
<tr><td>Hit Ex.</td><td>Expect value of the hit found for the query sequence's top hit. </td></tr>
<tr><td>%ID</td><td>Percent identity of the top hit of the query sequence's top hit .</td></tr>
<tr><td>RBH</td><td>Reciprocal Best Hit: Whether or not the hit's top hit is the query sequence</td></tr>
</table>

<?php
//Steps
//Get all input loci
//Get all boss genes from the input loci, and their chr,start,stop and hits
//Foreach hits, get their chr,start,stop and hits
//Filter the hits based on interval,rbh,evalue, etc
//Get the annotations
//Create the table, and export data for the HTML5 object
#SET ERROR REPORTING ON
error_reporting(E_ALL); ini_set('display_errors', 'On');
include 'iplant_db_credentials.php';


$mysqli = new mysqli("$iplant_db_host", $iplant_db_username,$iplant_db_password,"$database_name");
if ($mysqli->connect_errno) {
	echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

/*****Setup some global variables****
  settings contains a variety of elements
  settings['org_list'] csv organisms list
  settings['boss']  boss_organism name
  settings['boss_value'] boss_loci
  settings['$org_name']  loci for each organsim
  settings['rbh_opt'],['interval_opt'],['eval_opt'] various options
/******Boss Data
boss[$locus][$array_of_geneIDs]->['org']->['hit']
boss[$locus][$array_of_geneIDs]->['org']->['start']
boss[$locus][$array_of_geneIDs]->['org']->['stop']
boss[$locus][$array_of_geneIDs]->['org']->['chr']
boss[$locus][$array_of_geneIDs]->['org']->['e']
boss[$locus][$array_of_geneIDs]->['org']->['rbh']
//******Other Data
data['org'][$locus][$array_of_geneIDs]->['hit'] etc. same as above, but extra org

 */

$settings = getSettings();
$global_loci	  ; //a nice copy of the loci
$boss     ; //boss info
$hits;	  ; //location info of the boss hits
$annotations;
main();

function main(){
	global $mysqli;
	global $settings;
	global $annotations;
	global $boss;
	global $hits;
#1 Check to see that query is OK
	checkQuery();
	//DONT FORGET TO SANTIIZE EVERYTHING HERE FOR SQL INPUT
	//http://stackoverflow.com/questions/1314518/sanitizing-users-data-in-get-by-php
	//DONT FORGET TO SANITIZE HTML OUTPUT IN LATER PART

#2 Get all boss genes from the input loci, and their chr,start,stop,rbh,e,pid, and hits and put it into data
# Check to see if there are any hits!
	getBoss();
#3 Get information about the boss_hits, chr,start,stop,e,pid,
	get_org_genes_and_hits();
#4  Filter the hits based on interval,rbh,evalue, etc
	//filter()
#5 get annotations or quit if no genes found (all boss inputs were bad or nothing was found in those locations)
	if(!$annotations){
		print "<br>No annotations<br>";
		return;
	}
	getAnnotations($mysqli,$settings,$annotations);
#6 For each loci, give hits in each org
	generateTables($boss,$settings,$annotations,$hits);
	createRecentSearch();

}
/*
//Description
//Inputs: All of the arguments
//Outputs: All of the possible return values
 */
function getAnnotations(&$mysqli,&$settings,&$annotations)
{
	//Get boss genes
	$temp_annotations_keys = array();
	foreach(array_keys($annotations) as $geneID){
		$temp_annotations_keys[] = "'$geneID'";
	}
	//Get from phytozome_annot table
	$query = 
		"SELECT id,pfam,panther,kog,kegg_orth,kegg_ec,araHit,araSymbol,araDefline,riceHit,riceSymbol,riceDefline FROM phytozome_annot where id in (" . implode (",", array_values($temp_annotations_keys)) . ")";
	$result = $mysqli->query($query) or die($mysqli->error.__LINE__);
	while($row = $result->fetch_assoc()) {
		$id = $row['id']; #remove ID field from the annotatations as it is not an annotation
			unset ($row['id']);
		foreach(array_keys ($row) as $key)
		{
			$annotations[$id][$key]   = $row[$key];
		}
	}
	//Get from annotations table
	$query = 
		"SELECT id,fsf_pfam,synonyms,other_synonyms,defline FROM annotations where id in (" . implode (",", array_values($temp_annotations_keys)) . ")";
	$result = $mysqli->query($query) or die($mysqli->error.__LINE__);
	while($row = $result->fetch_assoc()) {
		$id = $row['id'];
		unset ($row['id']); #remove ID field from the annotatations as it is not an annotation
			foreach(array_keys ($row) as $key)
			{
				$annotations[$id][$key]   = $row[$key];
			}
	}
	//Get from markers table
	//Markers are assumed to be uniquely named, ie, two markers with the same name can't have the same location
	$query = 
		"SELECT * FROM markers";
	$result = $mysqli->query($query) or die($mysqli->error.__LINE__);
	while($row = $result->fetch_assoc()) {
		$org = $row['org'];
		$marker = $row['marker'];
		$chr = $row['chr'];
		$start = $row['start'];
		$stop = $row['stop'];
		$annotations[$org][$chr][$marker]['start'] = $start;
		$annotations[$org][$chr][$marker]['stop'] = $stop; 
	}
}

function generateLocusID($locus){
	$locus = str_replace(':',"-",$locus);
	return str_replace('..',"->",$locus);
}	

/*
   This function generates tables using all of the data we generated so far
   It also linkifys some of the data
   It also prepares data to be sent to other places and can one day help to create json
 */
function generateTables(&$boss,&$settings,&$annotations,&$hits){
	$rbh = $settings['rbh_opt'];
	$interval	 = $settings['interval_opt'];
	$organism_list = explode(",",$settings['orgs']);
	$boss_org = $settings['boss_org'];

#for each boss locus, generate a table for each org
	foreach(array_keys($boss) as $locus){
		foreach ($organism_list as $org){
			$locus_id = generateLocusID($locus); #rename locusid
				$table =
				"<table id='$locus_id-$org-table' border=1 class='sortme sortable-onload-0  colstyle-alt no-arrow resizeable'>".
				//Heading for the table
				"<thead><tr>
				<!-- <th class='sortable'></th> -->
				<th> $boss_org ID</th>
				<th> Hit In $org</th>
				<th> Common Annotations (Query and Hit)</th>
				<th class='sortable-numeric' >Expect (col1) </th>
				<th class='sortable-numeric'>%identity (col1) </th>
				<th class='sortable-numeric'>Expect for Hit (col2)</th>
				<th class='sortable-numeric'>%identity for Hit (col2)</th>
				<th class='sortable'>Recip. Best Hit </th>
				</tr></thead>";
			//Generate Table contents
			//Get all values, then put them in the table, row by row
			$count = 0; $rbh_count = 0; $index = 0;
			foreach(array_keys($boss[$locus]) as $boss_gene){
				$hit = $boss[$locus][$boss_gene]['hit'][$org];
				//No Hit!
				if($hit == "none\|none\|none\|NA"){
					$index++; //Still a valid gene to display? or maybe remove this

					//continue if these options are true	
					if($settings['interval_opt'] == 'true' || ($settings['rbh_opt'] == 'true') ){
						continue;
					}

					$table.= "<tr><td>$index</td><td>$boss_gene</td><td colspan='7'>No Hit Found</td></tr>";
					continue;
				}


				//Check to see if there is a hit for the hit
				//Temproary fix
				// \\\|

				$hit_array = explode ("\|", $hit); 
				$boss_id_pac = explode("|",$hit_array[0]);
				$boss_hit = $boss_id_pac[0];
				$boss_e   = $hit_array[1];
				$boss_p	  = $hit_array[2];
				$hit_e	  = $hit_array[4];
				$hit_p 	  = $hit_array[5];
				if(isset($settings['evalue'])){
				$cutoff = $settings['evalue'];
				}
				else{
					$cutoff = null;
				}
				



#Add 1 to evalues to make e10 1e10


				if(substr($boss_e,0,1) == 'e'){
					$boss_e = "1$boss_e";
				}
				if(substr($hit_e,0,1) == 'e'){
					$hit_e = "1$hit_e";
				}

#
				$number_of_pipes = substr_count($hit,"\|"); #There should be 5 pipes for a normal hit or a NEW delimiter
					if($number_of_pipes != 5)
					{
						print "<br>BADHITFOUND: $boss_gene $boss_hit $number_of_pipes</br>Note this error will  ALSO occur for orgs without a double | until database delimiters are fixed";
					}
				//determine if it is a rbh and set CSS color ($rbh)
				$possible_rbh = explode("|",$hit_array[3]);
				$possible_rbh_id = $possible_rbh[0];
				$rbh = "black";
				$rbh_result = 'false';

				//Check to see if the hit is in any intervals
				$in_intervals = getInIntervals($boss_hit,$org);

				//Check to see if in interval, otherwise skip the row
				if($settings['interval_opt'] == 'true'){
					if(count($in_intervals)==0){	continue; }
				}

				//Check to see if the hit is a reciprocal best hit, and if the RBH option is on
				//If it is not a reciprocal best hit, continue with an empty row
				if($boss_gene == $possible_rbh_id){
					$rbh = "rbh";
					$rbh_result = 'true';
					$rbh_count++;
				}
				if($settings['rbh_opt'] == 'true' && $rbh !='rbh'){ //quit if rbh opt on
					continue;
					$table.= "<tr><td>$index</td><td>$boss_gene</td><td colspan='7'>No Hit Found</td></tr>";
				}
				if(isset($cutoff)){
				if($boss_e != 0.0){
					#print "Comparing[" . $boss_e . "]" . " 1e-$cutoff" . "<br>";					
					if($boss_e < "1e-$cutoff"){
					#	print "$boss_e < 1e-$cutoff <br>";
					}else{
					#	print "$boss_e > 1e-$cutoff <br>";
						continue;
					}
				}
				}


				//
				$boss_table_id = "$locus-$boss_gene-$index";
				$hit_table_id = "$locus-$boss_hit-$index";

				//Generate annotation tabels here
				$boss_annotations_table = getAnnotationHTML($boss_gene, "$locus-boss",$boss_table_id,$boss_org);
				$hit_annotations_table  = getAnnotationHTML($boss_hit,"$locus-hit",$hit_table_id,$org);


				$boss_location = "{$boss[$locus][$boss_gene]['chr']}:{$boss[$locus][$boss_gene]['start']}..{$boss[$locus][$boss_gene]['stop']}";
				$hit_location  = "{$hits[$boss_hit]['chr']}:{$hits[$boss_hit]['start']}..{$hits[$boss_hit]['stop']}";
				$boss_alias = filterHTMLBracket(isset($annotations[$boss_gene]['synonyms']) && strlen($annotations[$boss_gene]['synonyms']) > 1 ? $annotations[$boss_gene]['synonyms']: "none");
				$hit_alias = filterHTMLBracket(isset($annotations[$boss_hit]['synonyms']) && strlen($annotations[$boss_hit]['synonyms']) > 1 ? $annotations[$boss_hit]['synonyms']: "none");
				$boss_length = $boss[$locus][$boss_gene]['stop'] - $boss[$locus][$boss_gene]['start'] ;
				$hit_length =  $hits[$boss_hit]['stop']  - $hits[$boss_hit]['start'];



				$optional_intervals_row ="";

				if( count($in_intervals) > 0){
					$optional_intervals_row = "<tr><td>intervals</td><td><span style='color:green'>" .implode("<br>",$in_intervals). "</span></td>";
				}

				//Else skip it
				//print_r ($hit_array);
				$boss_gene_link = $boss_gene;
				$boss_hit_link = $boss_hit;
				//Boss outlink
				if($boss_org == 'Athaliana_167'){
					$boss_gene_link = "<a href='http://www.arabidopsis.org/servlets/Search?type=general&amp;search_action=detail&amp;method=1&amp;show_obsolete=F&amp;name=$boss_gene&amp;sub_type=gene&amp;SEARCH_EXACT=4&amp;SEARCH_CONTAINS=1'>$boss_gene</a>";
				}
				else if($boss_org == 'Zmays_181'){
					$boss_gene_link = "<a href='http://www.maizegdb.org/cgi-bin/displaygmresults.cgi?term=$boss_gene'>$boss_gene</a>";
				}
				//Hit outlink
				if($org == 'Athaliana_167'){
					$boss_hit_link = "<a href='http://www.arabidopsis.org/servlets/Search?type=general&amp;search_action=detail&amp;method=1&amp;show_obsolete=F&amp;name=$boss_hit&amp;sub_type=gene&amp;SEARCH_EXACT=4&amp;SEARCH_CONTAINS=1'>$boss_hit</a>";
				}
				else if($org == 'Zmays_181'){
					$boss_hit_link = "<a href='http://www.maizegdb.org/cgi-bin/displaygmresults.cgi?term=$boss_hit'>$boss_hit</a>";
				}

				$boss_phytozome = "http://www.phytozome.net/results.php?method=0&amp;search=1&amp;sbut=Submit+keyword+search&amp;searchText=peptidename:$boss_gene";
				$hit_phytozome = "http://www.phytozome.net/results.php?method=0&amp;search=1&amp;sbut=Submit+keyword+search&amp;searchText=peptidename:$boss_hit";

				$boss_markers  = 	getMarkers($boss[$locus][$boss_gene]['chr'],$boss[$locus][$boss_gene]['start'],$boss[$locus][$boss_gene]['stop'],$boss_org);
				$hit_markers  = getMarkers ($hits[$boss_hit]['chr'],$hits[$boss_hit]['start'],$hits[$boss_hit]['stop'],$org);

				if(strlen($boss_markers) ==0){
					$boss_markers = "";
				}
				else{
					$boss_markers = "<tr><td>markers</td><td>$boss_markers</td></tr>";
				}
				if(strlen($hit_markers) ==0){
					$hit_markers = "";
				}
				else{
					$hit_markers = "<tr><td>markers</td><td>$hit_markers</td></tr>";
				}

				$boss_hit_table =
					"<table class='innertable ' >
					<tr><td style='background:#F0F0F0;' colspan='2'><b>LOCUS GENE ID</b> $boss_gene_link </td></tr>
					<tr><td>alias</td><td>{$boss_alias[0]}</td></tr>
					<tr><td>Phytozome</td><td><a href='$boss_phytozome'>Link</a></td></tr>
					<tr><td>location</td><td>$boss_location ($boss_length bp) </td></tr>
					<tr><td>rbh</td><td><span class='$rbh'>$rbh_result</span></td></tr>
					<!-- $boss_markers -->
					<tr><td colspan='2' style='background:none'><button type='button' onClick='toggle(\"$boss_table_id\");' class='button1'>$boss_gene Annotations</button></td></tr>
					<tr><td colspan='2' style='background:none'>$boss_annotations_table</td></tr>
					</table>";
				$hit_table =
					"<table class='innertable' >
					<tr><td  style='background:#F0F0F0' colspan='2' class='$rbh'>HIT ID $boss_hit_link</td></tr>
					<tr><td>alias</td><td>{$hit_alias[0]}</td></tr>
					<tr><td>Phytozome</td><td><a href='$hit_phytozome'>Link</a></td></tr>
					<tr><td>location</td><td>$hit_location ($hit_length bp)</td></tr>
					<tr><td>hit</td><td><span class='$rbh'>$possible_rbh_id</span></td></tr>
					<!-- $hit_markers -->
					$optional_intervals_row
					<tr><td colspan='2' style='background:none'><button type='button' onClick='toggle(\"$hit_table_id\");' class='button1'>$boss_hit Annotations</button></td></tr>
					<tr><td colspan='2' style='background:none'>$hit_annotations_table</td></tr>
					</table>";



				//style='width:300px;height:300px'/
				$table.=
					"<tr>
					<!-- <td style='min-width:30px' ><label><input type='checkbox' value='$locus-$org-$index' style='align:center'>$index</label></td> -->
					<td style='min-width:200px'>$boss_hit_table<br></td>
					<td style='min-width:200px'>$hit_table<br><br></td>
					<td>
					<table>
					<tr>
					<td style='background:#F0F0F0;'><b>type</b></td>
					<td style='background:#F0F0F0;'><b>value</b></td>
					</tr>".
					getCommonAnnotations($boss_gene,$boss_hit).
					"</table>
					</td>
					<td>{$boss_e}</td>
					<td>{$boss_p}</td>
					<td>$hit_e</td>
					<td>$hit_p</td>
					<td><span class='$rbh'>$rbh_result</span></td>
					</tr>";
				//We made it! Its a hit
				$count++;
				$index++;

			}
			$table .= "</table>";

			$interval_or_genome = " (Intervals) ";
			if($_GET['interval_opt'] == 'false'){
				$interval_or_genome = "(Entire Genome)";
			}
			

			print "
				<div id='$locus-$org-div' class='table-holder' style='border:solid 1px;float:left'>
				<div class='heading' style='margin:10px;'>
				<h3 class='red'>Table for $locus for $boss_org v $org $interval_or_genome </h3 >
				Genes Found: $count  <br> Reciprocal Best Hits Found: $rbh_count
				</div>
				$table
				</div>";
			//unless no hits? do that earlier!

		}

	}
}


function changeCHR($chr,$org){


	// ;
#    $stop = $annotations[$org][$chr][$marker]['stop'];
}



function getMarkers($chr,$start,$stop,$org){
	global $annotations;
	$chr = preg_replace('/[\D]/','',$chr);
	if( !$chr || strlen($chr) == 0 ){
		return "";
	}
	if(!isset($annotations[$org][$chr])){
		return "";
	}

	$annotations[$org][$chr]['bob-testgene']['start'] = 22327580; 

	$potential_in_gene = array();
	$potential_10000 = array();
	$potential_100000 = array();

	foreach(array_keys($annotations[$org][$chr]) as $marker){
		$m_start =  $annotations[$org][$chr][$marker]['start'];
		$m_stop =  $annotations[$org][$chr][$marker]['start'];

		if($m_start >= $start && $m_start <= $stop){
			$potential_in_gene[] = "$marker:$m_start..$m_stop";
		}
		else if($m_stop >= $start && $m_stop <= $stop){
			$potential_in_gene[]= "$marker:$m_start..$m_stop";
		}
		else if($m_stop >= ($start - 10000) || $m_start >= ($stop + 10000)){
			$potential_10000[] = "$marker:$m_start..$m_stop";
		}
		else if($m_stop >= ($start - 100000) || $m_start >= ($stop + 100000)){
			$potential_100000[] = "$marker:$m_start..$m_stop";
		}
	}
	if(!$potential_in_gene && !$potential_10000 && !$potential_100000){
		return "";
	}
	else{
		$return_string = "";
		if($potential_in_gene){
			$return_string .= "Within Gene<select>";
			foreach ($potential_in_gene as $opt){
				$return_string .= "<option>$opt</option>";
			}
			$return_string .= "</select><br>";
		}
		if($potential_10000){
			$return_string .= "Within 10,000 BP<select>";
			foreach ($potential_10000 as $opt){
				$return_string .= "<option>$opt</option>";
			}
			$return_string .= "</select><br>";
		}
		if($potential_100000){
			$return_string .= "Within 100,000 BP<select>";
			foreach ($potential_100000 as $opt){
				$return_string .= "<option>$opt</option>";
			}
			$return_string .= "</select>";
		}
		return $return_string;
	}
}


//Checks to see if the given non boss 
// geneID for the $org matches an $org loci given 
function getInIntervals($geneID,$org)
{
	global $hits;
	global $global_loci;
	global $settings;
	$loci_list = array();

	$geneChr = $hits[$geneID]['chr'];
	$geneStart = $hits[$geneID]['start'];
	$geneStop = $hits[$geneID]['stop'];

	if( $global_loci[$org] == null ){
		return $loci_list;
	}
	foreach(array_keys($global_loci[$org]) as $locus){
	
		$chr 	= $global_loci[$org][$locus]['chr'];
		$start 	= $global_loci[$org][$locus]['start'];
		$stop	= $global_loci[$org][$locus]['stop'];

		if($chr == $geneChr){
			if(  
					($geneStart >= $start && $geneStart <= $stop) ||
					($geneStop >= $stop && $geneStop <= $start) ||
					($geneStart <= $start && $geneStop >= $stop) ){
				$loci_list[] = $locus;
			}
		}

	}


	/*	
		if($geneChr != $chr){
		continue;
		} 
		if(  ($geneStart <= $interval_start && $geneStop >= $interval_start ) || //Check left side of interval
		($geneStart >= $interval_start && $geneStart <= $interval_stop ) || //Check right side of interval
		($geneStart <= $interval_start && $geneStop >= $interval_stop)
		){
		$loci_list[] = $locus;
		}
		}
	 */
/*
   print "Checking locus $locus <br>";
   print "GeneStart:{$geneStart} < {$interval_start}" ;
   $left = ($geneStart < $interval_start)?'true':'false' ;
   $right = ($geneStart >= $interval_start && $geneStart <= $interval_stop)?'true':'false' ;
   $middle= ($geneStart <= $interval_start && $geneStop >= $interval_stop)?'true':'false' ;



   print $left . "<br>" . $right . "<br>" . $middle ."<br>";

 */
/*	$in_range_start = "start >= $left  AND start <= $right" ; //if($start >= $left && $start < $right);
	$in_range_stop 	= "stop  <= $right AND stop  >= $left"  ; //if($stop <= $right && $stop >= $left);
	$overlap  		= "start <= $left  AND stop  >= $right" ; //overlap entire interval
 */
/*if($chr == $geneChr){
  if( 
  ($geneStart >= $start && $geneStart <= $stop) ||
  ($geneStop >= $stop && $geneStop <= $start) ||
  ($geneStart <= $start && $geneStop >= $stop) ){
  $loci_list[] = $locus;
  }
  } 
 */



return $loci_list;
}





//Returns a string with the annotations
function getAnnotationHTML($id,$locus,$table_id,$org)
{
	global $annotations;
	//Types of annotations
	//FSF_PFAM, synonyms
	//pfam panther kogg kegg_orth 
	//$fsf_pfam = explode ("|", $annotations[$id]['fsf_pfam']);

	//<table style='margin:0px;padding:0px;display:block'>

	$html = "
		<table id='$table_id' class='innertable' style='display:none'>
		<tr>
		<td style='background:#F0F0F0;'><b>type</b></td>
		<td style='background:#F0F0F0;'><b>value</b></td>
		</tr>
		";

	//sort($annotations[$id]);
	ksort($annotations[$id]);
	//arsort($annotations[$id]);

	foreach((array_keys($annotations[$id])) as $key){
		if(!($annotations[$id][$key]) || strlen(($annotations[$id][$key])) < 1 || $annotations[$id][$key] == 'null'){ continue; };
		if($key == 'fsf_pfam' ){

		};
		$value = preg_replace("/</", "(", $annotations[$id][$key]);
		$value = preg_replace("/>/", ")<br>", $value);
		$value = linkify($key,$value);
		//$value = str_replace("N/A","",$value);

		if($org =='Athaliana_167' && $key =='araHit'){
			$key = 'RiceHit';
		}

		$html .=  
			"<tr>
			<td><b>".$key."</b></td>
			<td> $value</td>
			</tr>";
	}

	return "$html</table>" ; //. "kegg orth" . $annotations[$id]['kegg_orth'];;
}	

//This function makes the inputted values into a link!
function linkify($type,$value,$org = null,$style=null)
{
	$target = '';#'target=\'_blank\'';
	if(isset($org) || isset($style)){
		//Must be a special case
	}
	else{
		switch($type){
			case 'fsf_pfam':
				$fsf_pfam = explode ("|", $value);
				if($fsf_pfam[0] != 'no FSF assignment'){
					$fsf_pfam[0] = "<a href='http://supfam.org/SUPERFAMILY/cgi-bin/search.cgi?search_field=$fsf_pfam[0]' $target" . 
						"onclick=\"NewWindow(this.href,'name','600','800','yes'); return false\">$fsf_pfam[0]</a>";
				}
				else{
					$fsf_pfam[0] = '';
				}
				if(isset($fsf_pfam[1])){
					foreach(explode(",",$fsf_pfam[1]) as $val){
						$pfam[] = "<a href='http://pfam.sanger.ac.uk/family/$val' $target >$val</a>";
					}
					return  "FSF: " . $fsf_pfam[0] . "<br>PFAM:<br> " . implode("<br>",$pfam);				
				}
				else{ return '';};
			case 'pfam':
				$pfam = array();
				foreach(explode("\|",$value) as $val){
					$pfam[] = "<a href='http://pfam.sanger.ac.uk/family/$val' $target >$val</a>";
				}
				return implode (" ",$pfam);
			case 'fsf':
				return "<a href='http://supfam.org/SUPERFAMILY/cgi-bin/search.cgi?search_field=$value' $target" .
					" onclick=\"NewWindow(this.href,'name','600','800','yes');return false\">$value</a>";
			case 'kegg_ec':
				/*	$return = "<a href='http://www.genome.jp/dbget-bin/www_bget?ec:$value'><br>";
					$annotation =  file_get_contents("http://rest.kegg.jp/list/ec:$value");
					$annotations = explode(";", $annotation);
					if(count($annotations) > 1){
					$annotation = $annotations[0];
					}					
					if(strlen($annotation) == 0) {
					return "$value"; #$annotation = $value;
					}
					return "$return$annotation</a
				 */ return $value;
			case 'kegg_orth':
				/*	$html = file_get_contents("http://rest.kegg.jp/list/ko:$value");
					return "<a href='http://www.genome.jp/dbget-bin/www_bget?ko:$value'><br>". $html. "</a>";
				 */ 
				return "<a href='http://www.genome.jp/dbget-bin/www_bget?ko:$value'>$value</a>";
			case 'panther':
				return "<a href='http://www.pantherdb.org/panther/family.do?clsAccession=$value' $target>$value</a>"; break;
			case 'kog':
				return "<a href='http://www.ncbi.nlm.nih.gov/COG/grace/shokog.cgi?$value' $target onclick=\"window.open(this.href,'name','height=500,width=900');return false\">$value</a>"; break;
			case 'synonyms':
			case 'other_synonyms':
			case 'araDefline':
			case 'riceDefline':
			case 'defline':
			case 'riceSymbol':
			case 'araSymbol':
				return $value;
			case 'araHit':
			case 'riceHit':
				return "<a href='http://www.phytozome.net/results.php?method=0&amp;search=1&amp;sbut=Submit+keyword+search&amp;searchText=peptidename:$value'>$value</a>";


				default;
				return "<br>something went wrong with $type $value<br>";
				break;
		}

	}

}


function addNBSP($string,$desiredLength){
	while(strlen($string) < $desiredLength){
		$string .= '_';
	}
	return $string;
}
//FilterHTMLBracket
//Removes < > and converts to list based on right >
//Removes /\ as well
function filterHTMLBracket($text)
{
	$list = explode (">", $text);
	foreach($list as &$li){
		$li = str_replace("<","",$li);
	}
	unset($li);
	return $list;
}




/*
   Sanitizes inputs
   Creates an array entry at settings
   $settings['loci']['org'] = ['chr'],['start'],['stop']
 */
function checkQuery()
{
	include 'lib/main.php';
	global $settings;
	global $global_loci;
	$required_inputs = array('boss_org','boss','boss_type','orgs');
	$all_used_inputs = array('boss_org','boss','boss_type','orgs','rbh_opt','interval_opt','opposite_type');//and various list of orgnames

	//Make sure all inputs defined!
	//Exit if not defined!
	$not_defined = 0;
	foreach($all_used_inputs as $key){
		if(!isset($settings[$key])){
			print "$key not defined!<br>";
			$not_defined++;
		}
	}
	if($not_defined){exit();}
	//Make sure boss_inputs well defined
	$boss_inputs = explode(",",$settings['boss']);
	$number_of_inputs = count($boss_inputs);
	$successful_inputs = 0;
	$bad_loci = array();
	foreach($boss_inputs as $input){
		//Attempt to fix sticky finger caused errors 
		if(strlen($input) ==0){
		continue;
		}
		$temp = (explode(":",$input));
		
		$start_stop = explode("..",$temp[1]);
		$chr = $temp[0];
		$start = $start_stop[0];
		$stop = $start_stop[1];

		if(isset($chr) && isset($start) && isset($stop) && checkValidLocus($chr,$start,$stop) )	{
			$successful_inputs++;
			$org = $settings['boss_org'];
			#print "Chr for $org = BEFORE[$chr] AFTER";
			$chr = $data->validateChromosomes($org,$chr);
		#	print $chr."<br>";
			$global_loci['boss'][$input]['chr'] = $chr;
			$global_loci['boss'][$input]['start'] = $start;
			$global_loci['boss'][$input]['stop'] = $stop;
		}
		else{
			print "<br>Problem with inputs for $input";

			//unset the bad loci
			//push bad loci
		}

	}
	if($number_of_inputs != $successful_inputs){
		#print "<br>removed boss loci";
		//print_r($bad_loci);
	}
	$orgs = explode(",",($settings['orgs']));
	foreach($orgs as $org){///work in progress
		foreach(explode (",",$settings[$org]) as $org_locus){
			$temp = (explode(":",$org_locus));
			$start_stop = "";
			if(count($temp) > 1){ #Blank intervals
				$start_stop = explode("..",$temp[1]);
				$chr = $temp[0];
				$start = $start_stop[0];
				$stop = $start_stop[1];
			}

			if(isset($chr) && isset($start) && isset($stop) && checkValidLocus($chr,$start,$stop) )	{
				//$successful_inputs++;
			#	print "Chr for $org = BEFORE[$chr] AFTER";
				$chr = $data->validateChromosomes($org,$chr);
			#	print $chr."<br>";
				$global_loci[$org][$org_locus]['chr'] = $chr;
				$global_loci[$org][$org_locus]['start'] = $start;
				$global_loci[$org][$org_locus]['stop'] = $stop;
			}
			else{
				print "<br>Problem with inputs for $input";
				//	print "isset chr = " . isset($chr);
				//print "isset start = " . isset($start);
				//print "isset stop = " . isset($stop);
				//	print "isset checkvalid = " . checkValidLocus($chr,$start,$stop);

				//unset the bad loci
				//push bad loci
			}
		}


	}


}	


//Helper function for...
//Input:
//Output: true/false
function checkValidLocus($chr,$start,$stop){
	if( preg_match('/[^-\da-zA-Z_]/',$chr) ){
		print "<br>Problem with chr: $chr";
		return 0;
	}
	if( preg_match('/[^\d]/',$start) ){
		print "<br>Problem with start: $start";
		return 0;
	}
	if( preg_match('/[^\d]/',$stop) ){
		print "<br>Problem with stop: $stop";
		return 0;
	}
	if($start > $stop){
		print "<br>Problem with $start..$stop , $stop > $start";
		return 0;
	}
	return 1;
}


//Settings to check
//and 
//boss
//list of orgs
//boss_type
//interval_opt
//orgs

#Step 1, Sanitize all inputs for SQL
//http://rosettacode.org/wiki/Parametrized_SQL_statement


//	if(!rbh_opt

//[0] => boss_org [1] => boss [2] => Zmays_181 [3] => boss_type [4] => orgs [5] => rbh_opt [6] => interval_opt [7] => opposite_type )
//global $settings;
//print_r( array_keys($settings));
//Make sure that there are at least 2 orgs
//Remove duplicate inputs
//Make sure the loci inputs are in the correct format, and have boundary conditions
//Make sure the orgs match available orgs?
//Make sure rbh_opt, interval_opt, and eval_opt are there / in right format


//Gets common annotations and returns a table containing them
//Makes sure the common annotation table is not blank , but skip where common = none/null
function getCommonAnnotations($geneID,$hitID){
	global $annotations;
	foreach(array_keys($annotations[$geneID]) as $key){ //header
		if(isset($annotations[$geneID][$key]) && isset($annotations[$hitID][$key])){
			$boss_value = $annotations[$geneID][$key];
			$hit_value = $annotations[$hitID][$key];
			//Skip these keys as nothing to compare!
			if(strlen($boss_value) == 0 || strlen($hit_value) ==0 ){
				continue;
			}
			if($key == 'fsf_pfam'){			
				$boss_explode	=  explode ("|",$boss_value);
				$hit_explode	=  explode ("|",$hit_value);
				$boss_fsf = $boss_pfam = $hit_fsf = $hit_pfam = array();



				$boss_fsf		= explode (",", $boss_explode[0]);
				$hit_fsf	   	= explode (",", $hit_explode[0]);

				if(isset($boss_explode[1])){
					$boss_pfam		= explode (",",	$boss_explode[1]);
					}	
				if(isset($hit_explode[1])){
					$hit_pfam		= explode (",", $hit_explode[1]);
				}
				$fsf_intersect = array_intersect($boss_fsf,$hit_fsf );
				$pfam_intersect = array_intersect($boss_pfam,$hit_pfam );

				//Remove no FSF or no PFAM
				if (($i = array_search('no FSF assignment', $fsf_intersect)) !== false) {
					unset($fsf_intersect[$i]);
				}
				if (($i = array_search('no PFAM assignment', $fsf_intersect)) !== false) {
					unset($pfam_intersect[$i]);
				}
				$common_annotation = 'FSF: ';
				foreach(array_values($fsf_intersect) as $val){
					if(strlen($val) > 1){
						$common_annotation .= linkify('fsf',$val);
					}
				}	
				$common_annotation .= '<br>PFAM: ';		

				foreach(array_values($pfam_intersect) as $val){
					if(strlen($val) > 1){
						$common_annotation .= "<br>".linkify('pfam',$val);
					}
				}
#	print "boss_fsf: "  . implode(",",$boss_fsf)  . " hit_fsf:   ". implode(",",$hit_fsf)  . " fsf_intersect: " . implode(",",$fsf_intersect) . "<br>";
#	print "boss_pfam: " . implode(",",$boss_pfam) . " hit_pfam: ". implode(",",$hit_pfam) . " pfam_intersect:" . implode(",",$pfam_intersect) . "<br>";
				$common_annotations[$key] = $common_annotation;
			}
			else{
				$boss_value_array = explode(",",$annotations[$geneID][$key]); 
				foreach($boss_value_array as $boss_value){
					if($key == 'fsf_pfam'){

					}
					else{
						if($boss_value && $boss_value == $annotations[$hitID][$key]){
							$common_annotations[$key] = linkify($key,$boss_value);
						}
					}
				}
			}
		}
	}
	if(!isset($common_annotations)){
		return '</tr colspan=2><td>none<td></tr>';
		return array('none');

	}
	else{
		$common = array();
		foreach(array_keys($common_annotations) as $key){
			if($common_annotations[$key] == 'null' || $common_annotations[$key] == 'none'){ 
				continue;
			} 
			$common[] = "<tr><td>$key:</td><td>{$common_annotations[$key]}</td></tr>";
		}

		return implode("",$common);
	}
}


//Load boss genes and their hits
function getBoss(){
	global $settings;
	global $global_loci;
	foreach(explode(',',$settings['boss']) as $locus){
		//Find genes in the locus, get their hits, update the $boss_var and $Boss_annot
		//Check to see if empty locus?
		if(isset($global_loci['boss'][$locus])){
			get_boss_genes_and_hits($locus);
		}
		else{
			#print "<br>Skipping bad locus $locus";
		}

	}
}

function get_org_genes_and_hits(){
	global $boss;
	global $settings;
	$type = $settings['opposite_type'];

	//Check to see if any boss loci passed or fail
	if(!$boss){
	print "<br><br><br> No Genes found in any input boss loci. Check to make sure you are using the correct chromosome/scaffold (eg. Chr1 vs 1 vs Scaff_1)";
		exit()	;
	}
#Store all genes into geneIDs, ALL GENEIDS must be unique!
	foreach(array_keys($boss) as $loci){
		foreach(array_keys($boss[$loci]) as $gene){
			foreach(array_keys($boss[$loci][$gene]['hit']) as $org){
				$hit = $boss[$loci][$gene]['hit'][$org];
				$hit_array_split = explode ("\|",$hit);
				$id_pac = explode("|",$hit_array_split[0]);
				$geneIDs[$id_pac[0]] = null; //Get list of hit_geneIDs
			}
		}
	}
	//So in the above section we could store them as genes[org][id] 
	//but hopefully all ids are unique across organisms! If not, just add an extra organism key
#Get all geneID locations,
	getHitLocations($geneIDs,$type);

}
//Get list of CHR, START, STOP based on the gene UNIQUEIDS for the boss_hits
//Store them into the '$hits' array and into the '$annotations' array
function getHitLocations($geneIDs,$type)
{
	global $mysqli;
	global $hits;
	global $annotations;
	//Encapsulate each string with apostrophes
	foreach(array_keys($geneIDs) as $gene){
		$genes2[]  = "'$gene'";
	}
	//Execute query;
	$query = "SELECT id,chr,start,stop FROM ".$type."_blast WHERE id IN (". (implode(",",$genes2)) . ")";
	$results = array();
	$result = $mysqli->query($query) or die($mysqli->error.__LINE__);
	while($row = $result->fetch_assoc()) {
		$id = $row['id'];
		$hits[$id]['chr'] 	= $row['chr'];
		$hits[$id]['start']	= $row['start'];
		$hits[$id]['stop'] 	= $row['stop'];
		$annotations[$id]	= NULL;
	}
}
//Function only for boss
function get_boss_genes_and_hits($locus){
	//Setup vars
	global $settings;
	global $mysqli;
	global $boss;
	global $annotations;

	$boss_name = $settings['boss_org'];
	$type =	$settings['boss_type'];
	list($chr,$start_stop) = (explode(":",$locus));
	list($left,$right) = (explode ("..",$start_stop));
	$in_range_start = "start >= $left  AND start <= $right" ; //if($start >= $left && $start < $right);
	$in_range_stop 	= "stop  <= $right AND stop  >= $left"  ; //if($stop <= $right && $stop >= $left);
	$overlap  		= "start <= $left  AND stop  >= $right" ; //overlap entire interval
	$org_list 		= $settings['orgs'];

	$query = "SELECT id,chr,start,stop,$org_list FROM blast WHERE (chr='$chr' AND org='$boss_name') AND".
		"( ($in_range_start) OR ($in_range_stop) OR ($overlap) ) ORDER BY START";
	//Execute query;
	$results = array();
	$result = $mysqli->query($query) or die($mysqli->error.__LINE__);

	//Store id,chr,start,stop, of boss org, and boss[locus][id][hit][org] = hit
	//The reason it has the extra hit name is in case an organism is named chr or start or stop or hit, etc
	while($row = $result->fetch_assoc()) {
		if(!isset($row)){
			print "No Genes found in the interval $locus for $boss_name<br>";
		}
		$id = $row['id'];
		$boss[$locus][$id]['chr'] 	= $row['chr'];
		$boss[$locus][$id]['start'] = $row['start'];
		$boss[$locus][$id]['stop'] 	= $row['stop'];
		foreach ($row as $org => $hit){
			if(array_search($org, array('id','chr','start','stop')) !== FALSE)  { continue; }; //Skip known keys
			$boss[$locus][$id]['hit'][$org] = $hit;
			$annotations[$id] = NULL; //Build up a list of genes
		}
	}	
	//printBossLocus($locus);
	//return $results;
}

//Unused debugging function
function printBossLocus($locus){
	global $boss;
	print "$locus<br>";
	foreach (array_keys($boss[$locus]) as $geneID)
	{
		$chr	= $boss[$locus][$geneID]['chr'];
		$start 	= $boss[$locus][$geneID]['start'];
		$stop 	= $boss[$locus][$geneID]['stop'];

		print "$geneID $chr:$start..$stop<br>";
	}
	print "<br>";
}


#Connect to DB and get some organisms

//generateHits(getSettings());

function generateHits($settings){
	$boss_org = $settings['boss_org'];
	foreach(explode(",",$settings['boss']) as $boss_locus){
		$gene_locus = getGenesInInterval($boss_org,$boss_locus,$settings['boss_type']);
		generateTable($gene_locus);
	}
}

function generateTable($gene_locus)	{

	foreach($gene_locus as $key => $value){
		$geneID = $value['id'];
		print $geneID . ' ';
		foreach(array_keys($value) as $hits){
			if($hits == 'id') { continue ;}

			$hits_array  = explode ("|", $value[$hits]);
			$hit_id = $hits_array[0];
			$location = getLocation($hits_array[0]);
			$chr 	= $location['chr'];
			$start 	= $location['start'];
			$stop   = $location['stop'];
			print "$hit_id $chr:..$stop";
		}
		print "<br>";
	}

}
function getLocation($geneID){
	global $mysqli; global $settings; 
	//	$query = "SELECT chr,start,stop from $settings['opposite_type'] where id ='$geneID'";
	$query = "SELECT chr,start,stop from ".$settings['opposite_type']."_blast where id='$geneID'";
	$result = $mysqli->query($query) or die($mysqli->error.__LINE__);
	$results = array();
	$row = $result->fetch_assoc();
	return $row;
}



//*******************************************************
//**Return gene ids of genes in the interval and their hits
//********
function getGenesInInterval($org_name,$locus,$type){
	//Setup vars
	global $settings;
	global $mysqli;
	list($chr,$start_stop) = (explode(":",$locus));
	list($left,$right) = (explode ("..",$start_stop));
	$in_range_start = "start >= $left  AND start <= $right" ; //if($start >= $left && $start < $right);
	$in_range_stop 	= "stop  <= $right AND stop  >= $left"  ; //if($stop <= $right && $stop >= $left);
	$overlap  		= "start <= $left  AND stop  >= $right" ; //overlap entire interval
	$org_list 		= $settings['orgs'];

	$query = "SELECT id,$org_list FROM ".$type."_blast WHERE (chr='$chr' AND organism='$org_name') AND ".
		"( ($in_range_start) OR ($in_range_stop) OR ($overlap) ) ORDER BY START";
	//Execute query;
	$results = array();
	$result = $mysqli->query($query) or die($mysqli->error.__LINE__);
	while($row = $result->fetch_assoc()) {
		$results[] = $row;

	}			
	return $results;
}


function getSettings()
{
	foreach($_GET as $var=>$val){
		$val = trim($val);	
		$_GET[$var] = $val;
	}
	$settings = $_GET;
	$type = $settings['boss_type'];
	if($type == 'monocot'){
		$settings['opposite_type'] = 'dicot';
	}
	else{
		$settings['opposite_type'] = 'monocot';
	}

	return $settings;
}

function createRecentSearch(){
	global $mysqli;
	$title ='undefined';

#	$url = 'http://stan.cropsci.uiuc.edu/browser/table.php?typeOfRequest="get"';
	$url = 'table.php?typeOfRequest="get"';
	foreach(($_GET) as $get_string => $value){
		$url .= "&$get_string=" . $value ; 
		if($get_string =='title'){
			$title = $value;
		}
	}
	date_default_timezone_set('US/Central') ;
	$date =  date('Y-m-d H:i:s');

	$query = "INSERT IGNORE INTO recent VALUES ('$url' , '$date') ";
	$result = $mysqli->query($query) or die($mysqli->error.__LINE__);

}

?>

</body>
</html>
