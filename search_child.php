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

    // Get basic child information with the exact column names from your table
    $childInfoSql = "SELECT 
                        child_id,
                        first_name,
                        last_name,
                        date_of_birth,
                        gender,
                        admission_date,
                        guardian_contact,
                        profile_picture
                     FROM children 
                     WHERE child_id = ?";
    
    $stmt = $conn->prepare($childInfoSql);
    $stmt->bind_param("i", $child_id); // Changed to integer binding since child_id is int(11)
    $stmt->execute();
    $childResult = $stmt->get_result();

    // Get education records
    $eduSql = "SELECT * FROM education_records WHERE child_id = ?";
    $eduStmt = $conn->prepare($eduSql);
    $eduStmt->bind_param("i", $child_id); // Changed to integer binding
    $eduStmt->execute();
    $eduResult = $eduStmt->get_result();

    if ($childResult->num_rows > 0) {
        $childData = $childResult->fetch_assoc();
        
        // Calculate age if we have a birth date
        $age = null;
        if (!empty($childData['date_of_birth'])) {
            $birthDate = new DateTime($childData['date_of_birth']);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
        }

        // Format full name
        $fullName = trim($childData['first_name'] . ' ' . $childData['last_name']);

        // Prepare education data if available
        $educationData = null;
        if ($eduResult->num_rows > 0) {
            $eduData = $eduResult->fetch_assoc();
            $educationData = [
                'child_condition' => $eduData['child_condition'] ?? null,
                'condition_details' => $eduData['special_needs_support'] ?? null,
                'school_name' => $eduData['school_name'] ?? null,
                'grade_level' => $eduData['grade_level'] ?? null,
                'performance_summary' => $eduData['performance_summary'] ?? null,
                'attendance_rate' => $eduData['attendance_rate'] ?? null
            ];
        }

        // Format profile picture URL
        $profilePicture = !empty($childData['profile_picture']) 
            ? $childData['profile_picture']
            : 'default-profile.jpg'; // You can change this default image path

        // Combine all data
        echo json_encode([
            'found' => true,
            'child_info' => [
                'child_id' => $childData['child_id'],
                'full_name' => $fullName,
                'first_name' => $childData['first_name'],
                'last_name' => $childData['last_name'],
                'age' => $age ?? 'Not available',
                'date_of_birth' => $childData['date_of_birth'] ?? 'Not available',
                'gender' => $childData['gender'] ?? 'Not available',
                'admission_date' => $childData['admission_date'] ?? 'Not available',
                'guardian_contact' => $childData['guardian_contact'] ?? 'Not available',
                'profile_picture' => $profilePicture
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