<?php
include iplant_db_credentials.php;

//error_reporting(E_ALL); ini_set('display_errors', 'On');
include 'iplant_db_credentials.php';
$mysqli = new mysqli("", $iplant_db_username,$iplant_db_password,"iplant");
if ($mysqli->connect_errno) {
	echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
query_direct($mysqli);

	echo "chr";

function query_direct(){
	$type 	= $_GET['type'];
	$value 	= $_GET['value'];
	$org 	= $_GET['org'];

	if($type='chr'){
	echo "chr";
		$chrList;
		foreach(getChrList($org) as $chr){
			chop($chr); 
			echo "<option>$chr</option>\n";
		};
		
	}
	
}

//Returns a list of chromosomes
function getChrList($org){
	global $mysqli;
	$query = "SELECT chromosome FROM chromosomes WHERE org ='" . mysql_real_escape_string($org) . "'";
	$result = $mysqli->query($query) or die($mysqli->error.__LINE__);
	while($row = $result->fetch_assoc()) {
		$data[] = $row['chromosome'];
	}
	return $data;
	
}




?>
