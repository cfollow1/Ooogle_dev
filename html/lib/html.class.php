<?php
class html{
	//Returns a dropdown
	function dropdown($elements,$id=null,$title=null,$select=null,$onchange=null){
		$options = "";
		if(isset($onchange)){
			$onchange= "onchange='this.form.submit()'";
		}
		if(isset($title)){
			$options .= "<option>$title</option>";
		}
		if(isset($elements)){
			$selected = "";
			foreach($elements as $key=>$value){
				$default = $key == $select? 'selected': '';
				
				$options .= "<option $default value='$key'>$value</option>";
			}
		}
		return "<select id='$id' name='$id' class='form-control' $onchange> $options </select>";
	}


}
?>
