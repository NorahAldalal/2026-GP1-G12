<?php
$conn = new mysqli("localhost", "root", "root", "siraj", 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$lamp_id     = $_POST['lamp_id'];
$lux         = $_POST['lux'];
$status      = $_POST['status'];
$node_health = $_POST['node_health'];
$lat         = $_POST['lat'];
$lng         = $_POST['lng'];

// هنا يتأكد إن Lux_Value تتحدث فعلًا
$sql = "UPDATE Lamp 
        SET Lux_Value = '$lux', 
            Status = '$status',
            offset_lat = '$lat',
            offset_lng = '$lng'
        WHERE LampID = '$lamp_id'";

if ($conn->query($sql) === TRUE) {
    echo "Lamp updated OK";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>