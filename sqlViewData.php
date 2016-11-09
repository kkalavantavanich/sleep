<html>

<head>
    <title>View SQL Data</title>
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
    
    $sql = "SELECT `timestamp`,`accb_x`, `accb_y`, `accb_z` FROM $tablename";
    
    $result = $connection->query($sql);

    if ($result->num_rows > 0) {
        echo "<table border=1>";
        echo "<tr><th>timestamp</th><th>acc_x</th><th>acc_y</th><th>acc_z</th></tr>";
        // output data of each row
        while($row = $result->fetch_assoc()) {
            echo "<tr><td>".$row["timestamp"]. "</td><td>".$row["accb_x"]. "</td><td>" . $row["accb_y"]. "</td><td> " . $row["accb_z"]. "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "0 results";
    }
    $connection->close();
    ?>

</body>

</html>
