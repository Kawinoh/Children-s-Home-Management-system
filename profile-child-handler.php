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
$child_id = $_POST['child_id'];
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$date_of_birth = $_POST['date_of_birth'];
$gender = $_POST['gender'];
$admission_date = $_POST['admission_date'];
$guardian_contact = $_POST['guardian_contact'];

try {
    $pdo->beginTransaction();

    // Check if child ID exists
    $check_child = $pdo->prepare("SELECT * FROM children WHERE child_id = ?");
    $check_child->execute([$child_id]);

    if ($check_child->rowCount() > 0) {
        // Child exists, update profile picture
        $update_picture = $pdo->prepare("UPDATE children SET profile_picture = ? WHERE child_id = ?");
        $update_picture->execute([$target_file, $child_id]);

        // Store child ID in session for health and education forms
        $_SESSION['child_id'] = $child_id;
        echo 'Child profile updated. Redirecting...';
    } else {
        // Child does not exist, insert new record
        $insert_child = $pdo->prepare("INSERT INTO children 
            (child_id, first_name, last_name, date_of_birth, gender, admission_date, guardian_contact, profile_picture) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert_child->execute([$child_id, $first_name, $last_name, $date_of_birth, $gender, $admission_date, $guardian_contact, $target_file]);

        // Store the new child ID in session
        $_SESSION['child_id'] = $child_id;
        echo 'Child profile created. Redirecting...';
    }

    $pdo->commit();

    // Redirect to children profile page
    header('Refresh: 2; URL=children_profile.php');
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die('Error saving basic information: ' . $e->getMessage());
}
?>
