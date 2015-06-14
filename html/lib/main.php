
<?php
set_include_path(get_include_path() . ':.');

include_once '../settings.php';
include_once 'medoo.php';
include_once 'lib/medoo.php';
function __autoload($class_name) {
	$filename = "lib/$class_name.class.php";
	#$filename = "lib/$class_name.class.php";
	if(file_exists($filename)) {
                require_once $filename;
        }
}
date_default_timezone_set(__TIMEZONE__);

$data = new data();
$html = new html();
?>

