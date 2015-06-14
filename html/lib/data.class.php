<?php

class data{

	public $database;
	public $organisms;

	public function __construct()
	{
		$this->database= new medoo(array(
					'database_type' => 'mysql',
					'database_name' => __MYSQL_DATABASE__,
					'server' => __MYSQL_HOST__,
					'username' => __MYSQL_USER__,
					'password' => __MYSQL_PASSWORD__,
					'charset' => 'utf8',
					// optional
					//'port' => 3306,
					// driver_option for connection, read more from http://www.php.net/manual/en/pdo.setattribute.php
					'option' => array(
						PDO::ATTR_CASE => PDO::CASE_NATURAL
						)
					));

	}
	public function getChromosomes($organism){
		if(!isset($organism)){
			return array();
		}
		$chromosomes = $this->database->select("chromosomes","chromosome",array('org'=>"$organism"));
#sort($chromosomes,SORT_NATURAL);
		natsort($chromosomes);
		$new_chr;
		foreach($chromosomes as $chr){
			$stripped = $this->stripChr($chr);
			$new_chr[$chr] = $stripped == $chr ? $chr : "$stripped ($chr)";
		}	
		return isset($new_chr)? $new_chr : array();
#print "Select chromosome from chromosomes where org=$organism";

	}

	public function validate($_POST){
#print_r($_POST);
		$_POST['intervals_query'] = $this->cleanIntervals($_POST['intervals_query']);
		$_POST['intervals_subject'] = $this->cleanIntervals($_POST['intervals_subject']);
		$validate = array();
		if(! $this->validate_helper($_POST['organism_query'])){
			return array('error'=>'Please select a query organism');
		}

		if(! $this->validate_helper($_POST['intervals_query'])){
			return array('error'=>"Please select intervals on the organism for <b> {$_POST["organism_query"]} </b>");
		}
		else{
			$valid_intervals = $this->validate_intervals($_POST['organism_query'],$_POST['intervals_query']);
			if($valid_intervals){
				$error = "Invalid intervals entered for {$_POST['organism_query']} ({$valid_intervals['error']})";
				return array('error'=>$error);
			}
		}


		if(! $this->validate_helper($_POST['organism_subject'])){
			return array('error'=>'Please select a subject organism');
		}
		if($_POST['organism_query'] == $_POST['organism_subject']){
			return array('error'=>'Please select two different organisms');
		}
		if(!isset($_POST['whole_genome'])){
			if(! $this->validate_helper($_POST['intervals_subject'])){
				return array('error'=>'Please select an interval on <b>' . $_POST['organism_subject'] . '</b> or <b><i>search entire genome</i></b>');
			}
			else{
				$valid_intervals = $this->validate_intervals($_POST['organism_subject'],$_POST['intervals_subject']);
				if($valid_intervals){
					$error = "Invalid intervals entered for <b> {$_POST['organism_subject']} </b> ({$valid_intervals['error']}) ";
					return array('error'=>$error);
				}
			}
		}	
		if(isset($_POST['expect']) && is_int($_POST['expect'])){
			return array('error'=>'Please select an integer for e-value ');
		}
#	$valid_chr = $this->validateChromosomes($_POST);
#	if(isset($valid_chr['error'])){
#		return array('error'=>$valid_chr['error']);
#	}	

	}
	#Should probably store this so dont call DB again and again
	public function validateChromosomes($organism,$chr){
		$chromosomes = $this->getChromosomes($organism);
		$prepends = array('','0','chromosome','chr','Chr','gm','Gm','A','a','bd','Bd');
		foreach($prepends as $prefix){
			if(isset($chromosomes[$prefix . $chr])){
				return ($prefix . $chr);
			}
		}
		return false;
	}
	


	private function validateChromosomes2($_POST){
		if(isset($_POST['organism_query']) && isset($_POST['intervals_query'])){
			$query_chromosomes = $this->getChromosomes($_POST['organism_query']);
			foreach(explode("\n",$_POST['intervals_query']) as $interval){
#;
			}
		}
		if(isset($_POST['organism_subject']) && isset($POST['organism_subject'])){
			$subject_chromosomes = $this->getChromosomes($_POST['organism_subject']);
			foreach(explode("\n",$_POST['intervals_subject']) as $interval){
#;
			}
		}

	}

	private function cleanIntervals($intervals){
		if(!isset($intervals)){
			return;
		}
		$intervals = str_replace(" ","\n",$intervals);
		$intervals = str_replace(",","\n",$intervals);
		$intervals = str_replace("\r","\n",$intervals);
		return $intervals;	
	}


	private function validate_intervals($organism,$intervals){
		$intervals = explode("\n",$intervals);
		foreach($intervals as $interval){
			if(strlen($interval) == 0){
				continue;
			}
			if(strpos($interval,':')===false || strpos($interval,'-')===false){
				return array('error'=>"$interval does not have : or -");
			}
			if(substr_count($interval,':') > 1){
					return array('error'=>"$interval has too many ':'");

			}
			$chr_interval = explode(":",$interval);
			print_r($chr_interval);
			$valid_chromosome = $this->validateChromosomes($organism,$chr_interval[0]);
			if(!$valid_chromosome){
				return array('error'=>$chr_interval[0] . " is not a valid chromsome ");
			}
			$error = $this->validateIntervalsHelper($chr_interval[1]);
			if(isset($error)){
				return $error;
			}

		}
		return null;
	}

	private function validateIntervalsHelper($interval_length){
		if(substr_count($interval_length,'-') > 1){
			return array('error'=>"Interval $interval_length has too many '-'");
		}
		if(preg_match('/[^0-9-]/',$interval_length)){
			return array('error'=>"Interval $interval_length has non numeric characters");
		}
		$start_stop = explode("-",$interval_length);
		if($start_stop[0] >= $start_stop[1]){
			return array('error'=>"Interval $interval_length [{$start_stop[0]} >= {$start_stop[1]}] ");

		}
		return null;

	}

	private function validate_helper($variable){
		return (isset($variable) && strlen($variable) > 0 && $variable !='Select Organism');
}


	#These are order dependent
	function stripChr($chr){
		$chr = preg_replace('/chromosome/i','',"$chr");
		$chr = preg_replace('/chr/i','',"$chr");
		$chr = preg_replace('/gm([0-9]{2})/i',"$1","$chr");
		$chr = preg_replace('/A([0-9]{2})/i',"$1","$chr");
		$chr = preg_replace('/bd([0-9]{1})/i',"$1","$chr");
		$chr = preg_replace('/^[0]/','',"$chr");

#Delete scaffolds and contigs?
		return $chr;

	}

#Returns a list of Key Value Pairs of organisms
	public function getOrganisms(){
		$organismsInfo =  $this->database->select("Organism_info", "*");;
		$organisms = array();
		foreach ($organismsInfo as $row){
			$name = $row['name'];
			$type = $row['type'];
			$common = $row['common'];
			$latin = $row['latin'];
			$organisms[$name] = "$latin ($common) ($type)";
		}
		array_multisort($organisms);
		return $organisms;
	}




}


?>


