<?php
$servername = "localhost";
$username = "root";
$password ="";
$databasename = "search";
// create connection 
$connection = mysqli_connect($servername, $username, $password,$databasename);

// check connection
if ($connection->connect_error){
    die("connection failed: " .$connection->connect_error);
}
?>