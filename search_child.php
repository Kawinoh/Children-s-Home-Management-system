<?php
// Disable caching
header('Cache-Control: no-cache, must-revalidate');
header('Content-Type: application/json');

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "children_home_management_system";

try {
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get and sanitize the child ID
    $child_id = isset($_GET['child_id']) ? $conn->real_escape_string($_GET['child_id']) : '';
    
    if (empty($child_id)) {
        throw new Exception("Child ID is required");
    }

    // Get basic child information
    $childInfoSql = "SELECT c.*, 
                            COALESCE(c.first_name, c.child_name, c.fullname) as child_name,
                            COALESCE(c.dob, c.date_of_birth, c.birth_date) as date_of_birth,
                            COALESCE(c.gender, c.sex) as gender
                     FROM children c 
                     WHERE c.child_id = ?";
    
    $stmt = $conn->prepare($childInfoSql);
    $stmt->bind_param("s", $child_id);
    $stmt->execute();
    $childResult = $stmt->get_result();

    // Get education records
    $eduSql = "SELECT * FROM education_records WHERE child_id = ?";
    $eduStmt = $conn->prepare($eduSql);
    $eduStmt->bind_param("s", $child_id);
    $eduStmt->execute();
    $eduResult = $eduStmt->get_result();

    if ($childResult->num_rows > 0) {
        $childData = $childResult->fetch_assoc();
        
        // Calculate age if we have a birth date
        $age = null;
        if (isset($childData['date_of_birth'])) {
            $birthDate = new DateTime($childData['date_of_birth']);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
        }

        // Prepare education data if available
        $educationData = null;
        if ($eduResult->num_rows > 0) {
            $eduData = $eduResult->fetch_assoc();
            $educationData = [
                'child_condition' => $eduData['child_condition'],
                'condition_details' => $eduData['special_needs_support'],
                'school_name' => $eduData['school_name'],
                'grade_level' => $eduData['grade_level'],
                'performance_summary' => $eduData['performance_summary'],
                'attendance_rate' => $eduData['attendance_rate']
            ];
        }

        // Combine all data
        echo json_encode([
            'found' => true,
            'child_info' => [
                'name' => $childData['child_name'] ?? 'Name not available',
                'age' => $age ?? 'Age not available',
                'gender' => $childData['gender'] ?? 'Gender not available',
                'child_id' => $child_id
            ],
            'education_records' => $educationData ?? [
                'found' => false,
                'message' => 'No education records found'
            ]
        ]);
    } else {
        echo json_encode([
            'found' => false,
            'message' => 'No child found with this ID'
        ]);
    }

    // Close statements and connection
    $stmt->close();
    $eduStmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>