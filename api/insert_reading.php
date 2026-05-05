<?php
$conn = new mysqli("localhost", "root", "root", "siraj", 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$lamp_id        = $_POST['lamp_id'];
$ambientLight   = $_POST['ambientLight'];
$motionDetected = $_POST['motionDetected'];

$sql = "INSERT INTO LampReading (LampID, ambientLight, motionDetected, readingTime)
        VALUES ('$lamp_id', '$ambientLight', '$motionDetected', NOW())";

if ($conn->query($sql) === TRUE) {
    echo "Reading inserted OK";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>