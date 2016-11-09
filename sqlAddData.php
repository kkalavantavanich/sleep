<!-- Use this to view data 
	Put this in C:/xampp/htdocs-->
<!DOCTYPE html>
<html>
<head>
    <title>SQL Add Data</title>
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
    $data->acc_x = checkInput($_REQUEST['ax']);
    $data->acc_y = checkInput($_REQUEST['ay']);
    $data->acc_z = checkInput($_REQUEST['az']);

    echo "<p>X Acceleration = ".$data->acc_x." m/s^2</p>";
    echo "<p>Y Acceleration = ".$data->acc_y." m/s^2</p>";
    echo "<p>Z Acceleration = ".$data->acc_z." m/s^2</p>";
    
    $sql = "INSERT INTO $tablename (`cilent`, `accb_x`, `accb_y`, `accb_z`) VALUES ('{$_SERVER['REMOTE_ADDR']}', $data->acc_x, $data->acc_y, $data->acc_z);";
    
    if ($connection->query($sql) === TRUE) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $connection->error;
    }
    
    $connection->close();

    function checkInput($string) {
        if (preg_match("(\d+)", $string)) {
            return (double) $string;
        } elseif ($string == null){
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
