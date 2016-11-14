<!-- Create New Sleep Round -->

<html>
<head>
    <title>Start Sleep Round</title>
</head>
<body>
<?php  
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "iot_data";
    $tablename = "sleep_round";

    $connection = new mysqli($servername, $username, $password, $dbname);
    if($connection->connect_error){
        die("Connection Error : ".$connection->connect_error);
    }
    $sql = "ALTER TABLE $tablename AUTO_INCREMENT = 1";
    $connection->query($sql);
    $sql = "INSERT INTO $tablename VALUES ()";  
    if($connection->query($sql) === TRUE){
        $sql = "SELECT ID FROM $tablename ORDER BY ID DESC LIMIT 1";
        $result = $connection->query($sql);
        if($result !== FALSE){
            $row = $result->fetch_assoc();
            echo "Sleep_round = ".$row["ID"]. " added.";
        } else {
            echo "Error adding sleep round.";
        }
    } else {
        echo "Error adding sleep round.";
    }
    $connection->close()
      
?>
    </body>
</html>