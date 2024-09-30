<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "children_home_management_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the form data
$support_type = $_POST['support_type'];
$message = $_POST['message'];
$user_id = $_SESSION['user_id']; // Assuming the user is logged in

// To prevent SQL injection
$support_type = $conn->real_escape_string($support_type);
$message = $conn->real_escape_string($message);

// Insert data into support_requests table
$sql = "INSERT INTO support_requests (user_id, support_type, message) VALUES ('$user_id', '$support_type', '$message')";

if ($conn->query($sql) === TRUE) {
    // Redirect to contact page with a success message
    header("Location: contact.html?success=1");
} else {
    // Redirect to contact page with an error message
    header("Location: contact.html?error=1");
}

$conn->close();
?>
