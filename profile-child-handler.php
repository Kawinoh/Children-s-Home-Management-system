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
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle file upload for profile picture
$upload_dir = 'uploads/';
$profile_picture_name = basename($_FILES['profile_picture']['name']);
$target_file = $upload_dir . $profile_picture_name;

// Ensure the uploads directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Check if the profile picture is uploaded
if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
    die('Error uploading profile picture.');
}

// Insert basic child info into `children` table
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$date_of_birth = $_POST['date_of_birth'];
$gender = $_POST['gender'];
$admission_date = $_POST['admission_date'];
$guardian_contact = $_POST['guardian_contact'];

try {
    $pdo->beginTransaction();

    // Insert child data
    $insert_child = $pdo->prepare("INSERT INTO children 
        (first_name, last_name, date_of_birth, gender, admission_date, guardian_contact, profile_picture) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insert_child->execute([$first_name, $last_name, $date_of_birth, $gender, $admission_date, $guardian_contact, $target_file]);

    // Get the inserted child ID and store it in session for health and education forms
    $_SESSION['child_id'] = $pdo->lastInsertId(); 

    $pdo->commit();

    // Redirect to children profile page
    echo 'Data saved successfully. Redirecting...';
header('Refresh: 2; URL=children_profile.php');
exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die('Error saving basic information: ' . $e->getMessage());
}
?>
