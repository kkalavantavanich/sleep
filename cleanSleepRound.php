<!--In case anything goes wrong with sleep round, run this. -->

<?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "iot_data";
    $accel_table = "accel_data";
    $sleep_table = "sleep_round";

    $connection = new mysqli($servername, $username, $password, $dbname);
    if ($connection->connect_error){
        die("Connection Error: ".$connection.connect_error);
    }

    $sleep_ids = $connection->query("SELECT ID FROM $sleep_table");
    $sleep_id = $sleep_ids->fetch_assoc()["ID"];
    while($sleep_id){
        
        $result = $connection->query("SELECT MIN(timestamp) FROM $accel_table WHERE sleep_id=$sleep_id");
        $min = $result->fetch_array()[0];

        $result = $connection->query("SELECT MAX(timestamp) FROM $accel_table WHERE sleep_id=$sleep_id");
        $max = $result->fetch_array()[0];
        
        if(!$min  || !$max){
            echo "No record found for sleep_id=".$sleep_id."<br>";
            $connection->query("UPDATE `sleep_round` SET start_time=NULL,end_time=NULL WHERE ID=$sleep_id");
            $sleep_id = $sleep_ids->fetch_assoc()["ID"];
            continue;
        }

        $connection->query("UPDATE `sleep_round` SET start_time=TIMESTAMP(\"$min\"),end_time=TIMESTAMP(\"$max\") WHERE ID=$sleep_id");
        echo "Processed sleep_id=".$sleep_id."<br>";

        $sleep_id = $sleep_ids->fetch_assoc()["ID"];
    }
    
    $connection->close();
?>

<html>
<head>
    <title>Clean Sleep Round</title>    
</head>

</html>