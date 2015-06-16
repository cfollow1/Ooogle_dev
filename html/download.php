<html>
<head>
<script type="text/javascript" src="http://jqueryjs.googlecode.com/files/jquery-1.3.1.min.js" > </script> 
<script type="text/javascript" src="http://www.kunalbabre.com/projects/table2CSV.js" > </script> 


<script type='text/javascript'>
 $(document).ready(function () {
    $('table').each(function () {
        var $table = $(this);

        var $button = $("<button type='button'>");
        $button.text("Export to spreadsheet");
        $button.insertAfter($table);

        $button.click(function () {
            var csv = $table.table2CSV({
                delivery: 'value'
            });
            window.location.href = 'data:text/csv;charset=UTF-8,' 
            + encodeURIComponent(csv);
        });
    });
})
</script>
</head>
<body>



<?php


$json = json_decode($_POST['json'],$assoc = true);



$header = null;
$rows = array();

$html;
foreach (array_keys($json) as $interval){
	foreach (array_keys($json[$interval]) as $org){
		foreach( array_keys($json[$interval][$org]) as $boss_gene){
			$html .= "<tr>";
			foreach( array_keys($json[$interval][$org][$boss_gene]) as $title){
				$attributes_arr = $json[$interval][$org][$boss_gene][$title];
				if(! isset($header)){
					$header = array_keys($attributes_arr);
				}
				foreach ($attributes_arr as $key=>$value){
					if(is_array($value)){
						$html .="<td>";
						foreach($value as $k => $v){
							$html .= "[$k:$v]";

						}
						$html .="</td>";
					}
					else{
						$html .= "<td>$value</td>";

					}
				}
			}
			$html .= "</tr>";
		}
	}
}
$heading = "<tr>";
foreach($header as $head){
	$heading .= "<td>$head</td>";
}
$heading .= "</tr>";
print "<table border=1>";
print $heading;
print $html;
print "</table>";
foreach ($rows as $row){


}
?>
</body>
</html>
