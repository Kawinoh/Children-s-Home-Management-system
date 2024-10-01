<?php
require_once 'db.php'; // Include the database connection

// Handle file upload for profile picture
if (isset($_FILES['profile_picture'])) {
    $upload_dir = 'uploads/';
    $profile_picture_name = basename($_FILES['profile_picture']['name']);
    $target_file = $upload_dir . $profile_picture_name;

    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
        // Profile picture successfully uploaded
    } else {
        die('Error uploading profile picture.');
    }
}

// Insert basic child info into `children` table
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$date_of_birth = $_POST['date_of_birth'];
$gender = $_POST['gender'];
$admission_date = $_POST['admission_date'];
$guardian_contact = $_POST['guardian_contact'];

$insert_child = $pdo->prepare("INSERT INTO children (first_name, last_name, date_of_birth, gender, admission_date, guardian_contact, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?)");
$insert_child->execute([$first_name, $last_name, $date_of_birth, $gender, $admission_date, $guardian_contact, $target_file]);

$child_id = $pdo->lastInsertId(); // Get the inserted child ID

// Insert health records into `health_records` table
$health_check_date = $_POST['health_check_date'];
$health_status = $_POST['health_status'];
$vaccinations = $_POST['vaccinations'];
$allergies = $_POST['allergies'];

$insert_health = $pdo->prepare("INSERT INTO health_records (child_id, health_check_date, health_status, vaccinations, allergies) VALUES (?, ?, ?, ?, ?)");
$insert_health->execute([$child_id, $health_check_date, $health_status, $vaccinations, $allergies]);

// Insert educational records into `education_records` table
$school_name = $_POST['school_name'];
$grade_level = $_POST['grade_level'];
$performance_summary = $_POST['performance_summary'];
$attendance_rate = $_POST['attendance_rate'];

$insert_education = $pdo->prepare("INSERT INTO education_records (child_id, school_name, grade_level, performance_summary, attendance_rate) VALUES (?, ?, ?, ?, ?)");
$insert_education->execute([$child_id, $school_name, $grade_level, $performance_summary, $attendance_rate]);

// Redirect to success page or display a success message
header('Location: success.html');
exit;
?>
