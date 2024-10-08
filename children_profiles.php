<?php
session_start();

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "children_home_management_system";

// Create connection using PDO
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode to exceptions
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $child_id = $_POST['child_id'];
    $first_name = $_POST['first_name'];
    $date_of_birth = $_POST['date_of_birth'];
    $guardian_contact = $_POST['guardian_contact'];
    $admission_date = $_POST['admission_date'];

    // Prepare the SQL update statement
    $query = "UPDATE children SET first_name = ?, date_of_birth= ?, guardian_contact = ?, admission_date = ? WHERE child_id = ?";
    $stmt = $pdo->prepare($query);

    // Execute the statement
    if ($stmt->execute([$first_name, $date_of_birth, $guardian_contact, $admission_date, $child_id])) {
        $_SESSION['message'] = "Profile updated successfully.";
    } else {
        $_SESSION['message'] = "Error updating profile.";
    }

    // Close the statement
    $stmt = null; // Close PDO statement
}

// Close database connection
$pdo = null; // Close PDO connection
?>