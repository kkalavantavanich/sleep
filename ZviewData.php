<!--Use this to add Data
Put this in C:/xampp/htdocs  -->

<!DOCTYPE html>
<html>
<body>

<h1>My first PHP page</h1>

<?php
echo "Hello World!";
$data = new StdClass;
$fp = fopen("importantFile.txt",'a') or die("Unable to open file!");;

$data->timestamp = date("Y-m-d H:i:s");
$data->acc_x = $_REQUEST['ax'];
$data->acc_y = $_REQUEST['ay'];
$data->acc_z = $_REQUEST['az'];

$array = json_decode(json_encode($data), True);
echo "<p>X Acceleration = ".$array["acc_x"]." m/s^2</p>";
echo "<p>Y Acceleration = ".$array["acc_y"]." m/s^2</p>";
echo "<p>Z Acceleration = ".$array["acc_z"]." m/s^2</p>";

fwrite($fp, json_encode($array));
fwrite($fp, '\n');
fclose($fp);

//print json_encode($data);

?>

</body>
</html>