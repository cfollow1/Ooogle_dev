<!DOCTYPE html>
<html lang="en">
<head>
<title>ORIM -- Oogle -- Ortholog Interval Mining </title>
<meta charset="utf-8">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap-theme.min.css">
<link rel="stylesheet" href="css/tooltip.css">

<script type="text/javascript">
$(document).ready(function(){
		$('[data-toggle="tooltip"]').tooltip();   
		});
</script>


</head>
<body>
<div class='container'>

<form name='oogle' class='form-inline' action='<?php echo $_SERVER['PHP_SELF'] ?>' method='post'>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'lib/main.php';
$error = " ";
$organism_query  = isset($_POST['organism_query']) ? $_POST['organism_query'] : null;
$chromosome_query = isset($_POST['chromosome_query']) ? $_POST['chromosome_query'] : null;
$organism_subject  = isset($_POST['organism_subject']) ? $_POST['organism_subject'] : null;
$chromosome_subject = isset($_POST['chromosome_subject']) ? $_POST['chromosome_subject'] : null;
$intervals_query  = isset($_POST['intervals_query']) ? $_POST['intervals_query'] : null;
$intervals_subject = isset($_POST['intervals_subject']) ? $_POST['intervals_subject'] : null;


if(isset($_POST['go'])){
	$_POST['go'] = null;
	$validate = $data->validate($_POST);
	if(isset($validate['error'])){
		$error = "<font color='red'>".$validate['error']."</font>";
	}
	else{
		retrofit();
		echo "Retrofit failed";
	}

}

function retrofit(){
	$get['boss_org'] = $_POST['organism_query'];
	$_POST['intervals_query'] = preg_replace("/-/",'..',$_POST['intervals_query']);
	$_POST['intervals_subject'] = preg_replace("/-/",'..',$_POST['intervals_subject']);

	$get['boss']=implode(",",explode("\n",$_POST['intervals_query']));
	$get[$_POST['organism_subject']] =implode(",",explode("\n",$_POST['intervals_subject']));
	$get['boss_type'] = 'none';
	$get['orgs']=$_POST['organism_subject'];
	if(isset($_POST['expect'])){
		$get['evalue'] = $_POST['expect'];
	}
	else{
		$get['evalue'] = 'none';
	}
	if(isset($_POST['rbh'])){
		$get['rbh_opt'] = 'true';

	}
	else{
		$get['rbh_opt']='false';
	}
	if(isset($_POST['whole_genome'])){
		$get['interval_opt'] = 'false';
	}
	else{
		$get['interval_opt'] = 'true';
	}
	header("Location: table.php?".http_build_query($get));



}


#echo "<img src='images/Oogle.gif'>";
echo "<a href='index.php'><h1>Ortholog Interval Miner</h1></a>";
echo "$error";
#Can turn this into a data object next time
# I wonder what the limit for a funciton should be before you pass a hash



#$tooltip = '<a href="#" data-toggle="tooltip" data-placement="top" data-original-title="IGV Format (Chr:Start-Stop)" > <button type="button" class="btn btn-default circle">?</button></a>';
$tooltip = '<a href="#" data-toggle="tooltip" data-placement="top" data-original-title="IGV Format (Chr:Start-Stop)" ><u>?</u></a>';



#QUERY
echo "<h4>Step 1: Select a subject organism's genome.</h4>";
echo "<div class='form-group'>";
echo $html->dropdown($data->getOrganisms(),'organism_query','Select Organism',$organism_query,true);
echo "</div>";
echo " ";
echo "<div class='form-group'>";
echo $html->dropdown($data->getChromosomes($organism_query),'chromosome_query','View Chromosomes',$chromosome_query);
echo "</div>";
echo "<h4>Step 2: Choose intervals on the genome: $tooltip</h4> ";
echo "<textarea class='form-control' name='intervals_query' placeholder='Chr#:start-stop' cols=50 rows=10>$intervals_query</textarea>";

#SUBJECT
echo "<h4>Step 3: Select an organism to search against.</h4>";
echo $html->dropdown($data->getOrganisms(),'organism_subject','Select Organism',$organism_subject,true);
echo " ";
echo $html->dropdown($data->getChromosomes($organism_subject),'chromosome_subject','View Chromosome',$chromosome_subject);
echo "<h4>Step 4: Choose intervals on the genome: $tooltip</h4>";
echo "<textarea class='form-control' name='intervals_subject' placeholder='Chr#:start-stop' cols=50 rows=10>$intervals_subject</textarea>";
echo"<br><br>";
?>
<?php
$rbh = isset($_POST['rbh'])?'checked':'';
$wholegenome = isset($_POST['wholegenome'])?'checked':'';
$expect =isset($_POST['expect'])?$_POST['expect']:'';

?>


<?php
$rbh = isset($_POST['rbh']) ? 'checked' : '';
$wholegenome = isset($_POST['whole_genome']) ? 'checked' : '';
$expect = isset($_POST['expect']) ? $_POST['expect'] : '';
if($expect <=4){
	$expect = 5;
}

?>



<input type='checkbox' name='rbh' <?php echo $rbh; ?>  > Show only reciprocal best hits. <br> 
<input type='checkbox' name='whole_genome' <?php echo $wholegenome; ?> > Genome-wide best hit discovery. (Search Entire Genome) <br>
1e<sup>-</sup><input type='number' name='expect' value='<?php  echo $expect; ?>' style='width:55px'> Show only hits above expect threshold.  (Default 1e<sup>-5</sup>)<br>

<br>
<input type='submit' value='Oooogle It!' name='go'>
<br>
<br>

</form>
</div>
</body>
</html>
