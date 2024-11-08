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
$child_id = $_POST['child_id'];
$child_condition = $_POST['child_condition'];
$school_name = $_POST['school_name'] ?? null;
$grade_level = $_POST['grade_level'] ?? null;
$performance_summary = $_POST['performance_summary'] ?? null;
$attendance_rate = $_POST['attendance_rate'] ?? null;
$special_needs_support = ($_POST['child_condition'] === "Other") ? $_POST['condition_details'] ?? null : null;

try {
    // Ensure the child ID exists in the children table
    $check_child = $pdo->prepare("SELECT * FROM children WHERE child_id = ?");
    $check_child->execute([$child_id]);

    if ($check_child->rowCount() > 0) {
        // Check if an education record for this child ID already exists
        $check_record = $pdo->prepare("SELECT * FROM education_records WHERE child_id = ?");
        $check_record->execute([$child_id]);

        if ($check_record->rowCount() > 0) {
            // Record exists, update it
            $update_education = $pdo->prepare("UPDATE education_records 
                SET child_condition = ?, school_name = ?, grade_level = ?, 
                    performance_summary = ?, attendance_rate = ?, 
                    special_needs_support = ? 
                WHERE child_id = ?");
            $update_education->execute([
                $child_condition, $school_name, $grade_level, 
                $performance_summary, $attendance_rate, $special_needs_support, $child_id
            ]);
            echo 'Education records updated successfully. Redirecting...';
        } else {
            // Record does not exist, insert a new record
            $insert_education = $pdo->prepare("INSERT INTO education_records 
                (child_id, child_condition, school_name, grade_level, 
                performance_summary, attendance_rate, special_needs_support) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert_education->execute([
                $child_id, $child_condition, $school_name, $grade_level, 
                $performance_summary, $attendance_rate, $special_needs_support
            ]);
            echo 'Education records saved successfully. Redirecting...';
        }

        // Redirect to the children's profile page
        header('Refresh: 2; URL=children_profile.php');
        exit;
    } else {
        // Child ID not found in children table
        echo 'Error: Child ID not found. Please ensure the child ID is correct.';
    }
} catch (Exception $e) {
    die('Error saving educational records: ' . $e->getMessage());
}
?>
