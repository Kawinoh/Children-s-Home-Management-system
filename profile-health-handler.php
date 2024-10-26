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

// Capture form data
$child_id = $_POST['child_id']; // Child ID entered by the user
$health_status = $_POST['health_status'];
$health_condition = isset($_POST['health_condition']) && !empty($_POST['health_condition']) ? $_POST['health_condition'] : 'None'; // Default to 'None' if no condition is specified
$diagnosis = isset($_POST['diagnosis']) ? $_POST['diagnosis'] : ''; // Diagnosis field, if applicable
$health_check_date = $_POST['health_check_date'];
$vaccinations = $_POST['vaccinations'];
$allergies = $_POST['allergies'];

try {
    // Ensure the child ID exists in the children table
    $check_child = $pdo->prepare("SELECT * FROM children WHERE child_id = ?");
    $check_child->execute([$child_id]);

    if ($check_child->rowCount() > 0) {
        // Check if a health record for this child ID already exists
        $check_record = $pdo->prepare("SELECT * FROM health_records WHERE child_id = ?");
        $check_record->execute([$child_id]);

        if ($check_record->rowCount() > 0) {
            // Record exists, update it
            $update_health = $pdo->prepare("UPDATE health_records 
                SET health_check_date = ?, health_status = ?, health_condition = ?, diagnosis = ?, vaccinations = ?, allergies = ? 
                WHERE child_id = ?");
            $update_health->execute([$health_check_date, $health_status, $health_condition, $diagnosis, $vaccinations, $allergies, $child_id]);
            echo 'Health records updated successfully. Redirecting...';
        } else {
            // Record does not exist, insert a new record
            $insert_health = $pdo->prepare("INSERT INTO health_records 
                (child_id, health_check_date, health_status, health_condition, diagnosis, vaccinations, allergies) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert_health->execute([$child_id, $health_check_date, $health_status, $health_condition, $diagnosis, $vaccinations, $allergies]);
            echo 'Health records saved successfully. Redirecting...';
        }

        // Redirect to the children's profile page
        header('Refresh: 2; URL=children_profile.php');
        exit;
    } else {
        // Child ID not found in children table
        echo 'Error: Child ID not found. Please ensure the child ID is correct.';
    }
} catch (Exception $e) {
    die('Error saving health records: ' . $e->getMessage());
}
?>
