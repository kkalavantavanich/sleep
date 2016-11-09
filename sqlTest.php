<html>

<head>
    <title>Connecting MySQL Server</title>
</head>

<body>
    <p><b>This is an SQL test.</b></p>
    
    <?php
   $dbhost = 'localhost:3306';
   $dbuser = 'root';
   $dbpassword = '';
   $conn = new mysqli($dbhost, $dbuser, $dbpassword);
   if ($conn->connect_error){
     die('Could not connect: ' . $conn->connect_error);
   }
   echo 'Connected successfully';
   $conn->close();
?>
</body>

</html>
