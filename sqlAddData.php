<!-- Use this to view data 
	Put this in C:/xampp/htdocs-->
<!DOCTYPE html>
<html>

<head>
    <title>Add MySQL Data</title>
</head>

<body>

    <h1>Add Data Page</h1>
    <p>Computer Engineering Essentials 2016<br>Group 26</p>

    <?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "iot_data";
    $tablename = "accel_data";
    $factor = (16 / 4096) * 0.001 * 9.8; //lsb to m/s^2 for 13-bit 16g
    // TODO How to convert data 
    // TODO How to Calibrate Data
    
    date_default_timezone_set("Asia/Bangkok");
    
    // setup mySQL connection
    $connection = new mysqli($servername, $username, $password, $dbname);
    if($connection->connect_error){
        die("Connection failed: ".$connection->connect_error);
    }
    
    //read data from php link
    // /sqlAddData.php?ax=...&&ay=...&&az=...
    $data = new StdClass;
    $data->timestamp = date("Y-m-d H:i:s");
    $data->acc_x = checkInput(isset($_REQUEST['ax']) ? $_REQUEST['ax'] : NULL);
    $data->acc_y = checkInput(isset($_REQUEST['ay']) ? $_REQUEST['ay'] : NULL);
    $data->acc_z = checkInput(isset($_REQUEST['az']) ? $_REQUEST['az'] : NULL);
    
    $sql = "SELECT ID FROM sleep_round ORDER BY ID DESC LIMIT 1";
    $result = $connection->query($sql);
    if ($result === FALSE){
        die("Error: " . $sql . "<br>" . $connection->error);
    }
    $row = $result->fetch_assoc();
    $sleep_round = $row["ID"];
    
    $result = $connection->query("SELECT start_time FROM sleep_round WHERE ID=$sleep_round");
    if(!$result){
        die("<b>Error</b>: No Sleep Round found!\nAborting...");
    }
    $row = $result->fetch_assoc();
    if ($row["start_time"] === NULL){
        $connection->query("UPDATE `sleep_round` SET start_time=TIMESTAMP(\"$data->timestamp\") WHERE ID=$sleep_round");
    }  
    $connection->query("UPDATE `sleep_round` SET end_time=TIMESTAMP(\"$data->timestamp\") WHERE ID=$sleep_round");
    
    
    echo "<p>Sleep Round =  ".$sleep_round."<br></p>";
    echo "<p>X Acceleration = ".$data->acc_x." LSB<br></p>";
    echo "<p>Y Acceleration = ".$data->acc_y." LSB<br></p>";
    echo "<p>Z Acceleration = ".$data->acc_z." LSB<br></p>";
    
    $sql = "INSERT INTO $tablename (`cilent`, `raw_acc_x`, `raw_acc_y`, `raw_acc_z`, `cal_acc_x`, `cal_acc_y`, `cal_acc_z`, `sleep_id`) VALUES ('{$_SERVER['REMOTE_ADDR']}', $data->acc_x, $data->acc_y, $data->acc_z, $factor * $data->acc_x, $factor * $data->acc_y, $factor * $data->acc_z, $sleep_round);";
    
    if ($connection->query($sql) === TRUE) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $connection->error;
    }
    
    $connection->close();

    function checkInput($string) {
        if (preg_match("/(^-?\d+$)/", $string)) {
            return (int) $string;
        } elseif ($string == null){
            echo ("Invalid Input : null");
            die();
            return 0;
        } else {
            echo ("Invalid Input \"".$string."\"<br>");
            die();
            return 0;
        }
    }

    ?>

</body>

</html>
