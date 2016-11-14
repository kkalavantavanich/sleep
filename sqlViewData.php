<?php
$page = $_SERVER['PHP_SELF'];
$sec = "10";
?>
<html>

<head>
    <title>View MySQL Data</title>
    <meta http-equiv="refresh" content="<?php echo $sec?>;URL='<?php echo $page?>'">
</head>

<body>
    <h1>View Data Page</h1>
    <p>Computer Engineering Essentials 2016<br>Group 26</p>
    <?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "iot_data";
    $tablename = "accel_data";
    
    date_default_timezone_set("Asia/Bangkok");
    
    // setup mySQL connection
    $connection = new mysqli($servername, $username, $password, $dbname);
    if($connection->connect_error){
        die("Connection failed: ".$connection->connect_error);
    }  
          
    $sql = "SELECT `timestamp`,`raw_acc_x`, `raw_acc_y`, `raw_acc_z`, `cal_acc_x`,`cal_acc_y`, `cal_acc_z` FROM $tablename";
    $sleep_round = isset($_REQUEST["sleep"]) ? $_REQUEST["sleep"] : NULL;
    if($sleep_round !== NULL){
        $sql = $sql . " WHERE sleep_id=$sleep_round";
        echo "<b>Showing sleep = $sleep_round</b><br><br>";
    } else {
        echo "<b>Showing all data...</b><br><br>";
    }
    
    $result = $connection->query($sql);

    if ($result->num_rows > 0) {
        echo "<table border=1>";
        echo "<tr><th>timestamp</th><th>raw_acc_x(LSB)</th><th>raw_acc_y(LSB)</th><th>raw_acc_z(LSB)</th><th>cal_acc_x(m/s<sup>2</sup>)</th><th>cal_acc_y(m/s<sup>2</sup>)</th><th>cal_acc_z(m/s<sup>2</sup>)</th></tr>";
        // output data of each row
        while($row = $result->fetch_assoc()) {
            echo "<tr><td>".$row["timestamp"]. "</td><td>".$row["raw_acc_x"]. "</td><td>" . $row["raw_acc_y"]. "</td><td> " . $row["raw_acc_z"]. "</td><td>".$row["cal_acc_x"]. "</td><td>" . $row["cal_acc_y"]. "</td><td>" . $row["cal_acc_z"]. "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "0 results";
    }
    $connection->close();
    ?>

</body>

</html>
