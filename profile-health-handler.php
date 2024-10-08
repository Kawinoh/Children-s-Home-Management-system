<?php
session_start();

// Ensure the child ID is in session
if (!isset($_SESSION['child_id'])) {
    die('Child ID not found. Please fill in the basic information first.');
}

$child_id = $_SESSION['child_id'];

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "children_home_management_system";

// Create connection using PDO
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Capture form data
$health_status = $_POST['health_status'];
$health_condition = isset($_POST['health_condition']) && !empty($_POST['health_condition']) ? $_POST['health_condition'] : 'None'; // Default to 'None' if no condition is specified
$health_check_date = $_POST['health_check_date'];
$vaccinations = $_POST['vaccinations'];
$allergies = $_POST['allergies'];

try {
    // Insert health records into `health_records` table
    $insert_health = $pdo->prepare("INSERT INTO health_records 
    (child_id, health_check_date, health_status, health_condition, vaccinations, allergies) 
    VALUES (?, ?, ?, ?, ?, ?)");
$insert_health->execute([$child_id, $health_check_date, $health_status, $health_condition, $vaccinations, $allergies]);


    // Redirect to children profile page
    echo 'Data saved successfully. Redirecting...';
    header('Refresh: 2; URL=children_profile.php');
    exit;
} catch (Exception $e) {
    die('Error saving health records: ' . $e->getMessage());
}
?>
