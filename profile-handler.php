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

// Handle file upload for profile picture
$upload_dir = 'uploads/';
$profile_picture_name = basename($_FILES['profile_picture']['name']);
$target_file = $upload_dir . $profile_picture_name;

// Ensure the uploads directory exists, if not, create it
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); // Create the directory with full permissions
}

// Check if the profile picture is successfully uploaded
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
    $pdo->beginTransaction(); // Start transaction

    // Insert child data
    $insert_child = $pdo->prepare("INSERT INTO children 
        (first_name, last_name, date_of_birth, gender, admission_date, guardian_contact, profile_picture) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insert_child->execute([$first_name, $last_name, $date_of_birth, $gender, $admission_date, $guardian_contact, $target_file]);
    
    // Get the inserted child ID
    $child_id = $pdo->lastInsertId(); 

    // Insert health records into `health_records` table
    $health_check_date = $_POST['health_check_date'];
    $health_status = $_POST['health_status'];
    $vaccinations = $_POST['vaccinations'];
    $allergies = $_POST['allergies'];

    $insert_health = $pdo->prepare("INSERT INTO health_records 
        (child_id, health_check_date, health_status, vaccinations, allergies) 
        VALUES (?, ?, ?, ?, ?)");
    $insert_health->execute([$child_id, $health_check_date, $health_status, $vaccinations, $allergies]);

    // Insert educational records into `education_records` table
    $school_name = $_POST['school_name'];
    $grade_level = $_POST['grade_level'];
    $performance_summary = $_POST['performance_summary'];
    $attendance_rate = $_POST['attendance_rate'];

    $insert_education = $pdo->prepare("INSERT INTO education_records 
        (child_id, school_name, grade_level, performance_summary, attendance_rate) 
        VALUES (?, ?, ?, ?, ?)");
    $insert_education->execute([$child_id, $school_name, $grade_level, $performance_summary, $attendance_rate]);

    $pdo->commit(); // Commit transaction

    // Redirect to the children's profiles page after success
    header('Location: children_profile.php');
    exit;

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack(); // Rollback transaction in case of an error
    }
    die('Error saving data: ' . $e->getMessage());
}
?>
