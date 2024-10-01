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

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit();
}

// Get the form data
$support_type = $_POST['support_type'];
$message = $_POST['message'];
$user_id = $_SESSION['user_id']; // Assuming the user is logged in

// Sanitize input to prevent SQL injection
$support_type = $conn->real_escape_string($support_type);
$message = $conn->real_escape_string($message);

// Check if this is a financial or material support request
$amount = NULL;
$material_description = NULL;

if ($support_type == 'financial') {
    $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : NULL;
} elseif ($support_type == 'material') {
    $material_description = isset($_POST['material_description']) ? $conn->real_escape_string($_POST['material_description']) : NULL;
}

// Prepare the SQL statement with placeholders
$stmt = $conn->prepare("INSERT INTO support_requests (user_id, support_type, amount, material_description, message) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issds", $user_id, $support_type, $amount, $material_description, $message);

// Execute the statement
if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Support request submitted successfully!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error submitting support request. Please try again.']);
}

// Close connection
$stmt->close();
$conn->close();
?>
