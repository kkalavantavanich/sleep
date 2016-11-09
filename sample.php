<!-- Use this to test 
Put this in C:/xampp/htdocs -->
<?php

$fp = fopen("sampleData.txt",'a') or die("Unable to open file!");

date_default_timezone_set("Asia/Bangkok");
$data = new StdClass;
$data->timestamp = date("Y-m-d H:i:s");
$data->cilent = $_SERVER['REMOTE_ADDR'];
$data->value = $_REQUEST['value'];
print json_encode($data);

fwrite($fp, json_encode($data));
fwrite($fp, "\r\n");
fclose($fp);

?>