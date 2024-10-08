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

// Insert educational records into `education_records` table
$school_name = $_POST['school_name'];
$grade_level = $_POST['grade_level'];
$performance_summary = $_POST['performance_summary'];
$attendance_rate = $_POST['attendance_rate'];

try {
    $insert_education = $pdo->prepare("INSERT INTO education_records 
        (child_id, school_name, grade_level, performance_summary, attendance_rate) 
        VALUES (?, ?, ?, ?, ?)");
    $insert_education->execute([$child_id, $school_name, $grade_level, $performance_summary, $attendance_rate]);

    // Redirect to children profile page
    echo 'Data saved successfully. Redirecting...';
    header('Refresh: 2; URL=children_profile.php');
    exit;
} catch (Exception $e) {
    die('Error saving educational records: ' . $e->getMessage());
}
?>
