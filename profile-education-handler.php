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

// Function to get existing education records
function getExistingEducationRecord($pdo, $child_id) {
    $query = $pdo->prepare("SELECT * FROM education_records WHERE child_id = ?");
    $query->execute([$child_id]);
    return $query->fetch(PDO::FETCH_ASSOC);
}

// Load existing record (GET request)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['child_id'])) {
    $child_id = $_GET['child_id'];
    $existing_record = getExistingEducationRecord($pdo, $child_id);

    header('Content-Type: application/json');
    if ($existing_record) {
        echo json_encode($existing_record);
    } else {
        echo json_encode(['error' => 'No record found']);
    }
    exit();
}

// Handle form submission (POST request)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $child_id = $_POST['child_id'];
    $child_condition = $_POST['child_condition'];

    // Initialize variables
    $school_name = $grade_level = $performance_summary = $attendance_rate = $special_needs_support = null;

    if ($child_condition === "Normal" || $child_condition === "Other") {
        $school_name = $_POST['school_name'] ?? null;
        $grade_level = $_POST['grade_level'] ?? null;
        $performance_summary = $_POST['performance_summary'] ?? null;
        $attendance_rate = $_POST['attendance_rate'] ?? null;
    }

    if ($child_condition === "Other") {
        $special_needs_support = $_POST['condition_details'] ?? null;
    }

    try {
        // Verify if child exists
        $check_child = $pdo->prepare("SELECT child_id FROM children WHERE child_id = ?");
        $check_child->execute([$child_id]);

        if ($check_child->rowCount() > 0) {
            // Check if education record exists
            $check_record = $pdo->prepare("SELECT * FROM education_records WHERE child_id = ?");
            $check_record->execute([$child_id]);

            if ($check_record->rowCount() > 0) {
                // Update existing record
                $update_education = $pdo->prepare("
                    UPDATE education_records 
                    SET child_condition = ?, school_name = ?, grade_level = ?, 
                        performance_summary = ?, attendance_rate = ?, 
                        special_needs_support = ?, last_updated = CURRENT_TIMESTAMP 
                    WHERE child_id = ?
                ");
                $update_education->execute([
                    $child_condition, $school_name, $grade_level, 
                    $performance_summary, $attendance_rate, $special_needs_support, $child_id
                ]);
                $_SESSION['message'] = "Education records updated successfully!";
            } else {
                // Insert new record
                $insert_education = $pdo->prepare("
                    INSERT INTO education_records 
                    (child_id, child_condition, school_name, grade_level, 
                    performance_summary, attendance_rate, special_needs_support)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $insert_education->execute([
                    $child_id, $child_condition, $school_name, $grade_level, 
                    $performance_summary, $attendance_rate, $special_needs_support
                ]);
                $_SESSION['message'] = "Education records saved successfully!";
            }

            // Store child_id in session for the profile page
            $_SESSION['child_id'] = $child_id;

            // Return JSON response for AJAX requests
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $_SESSION['message']]);
                exit();
            }

            // Regular form submission redirect
            header("Location: children_profile.php");
            exit();
        } else {
            $_SESSION['error'] = "Error: Child ID not found. Please ensure the child ID is correct.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error saving education records: " . $e->getMessage();
    }

    // Return error for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => $_SESSION['error']]);
        exit();
    }

    // Redirect on error for non-AJAX requests
    header("Location: education_records.html");
    exit();
}
?>
