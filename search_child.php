<?php
// search_child.php
header('Cache-Control: no-cache, must-revalidate');
header('Content-Type: application/json');

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "children_home_management_system";

try {
    // Create connection using PDO for better security
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get and validate the child ID
    $child_id = isset($_GET['child_id']) ? trim($_GET['child_id']) : '';
    
    if (empty($child_id)) {
        throw new Exception("Child ID is required");
    }

    // Prepare and execute the query
    $stmt = $pdo->prepare("SELECT 
        child_id,
        first_name,
        last_name,
        date_of_birth,
        gender,
        admission_date,
        guardian_contact
        FROM children 
        WHERE child_id = ?");
    
    $stmt->execute([$child_id]);
    
    if ($stmt->rowCount() > 0) {
        $childData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate age
        $age = null;
        if (!empty($childData['date_of_birth'])) {
            $birthDate = new DateTime($childData['date_of_birth']);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
        }

        // Format response data
        $response = [
            'found' => true,
            'child_info' => [
                'child_id' => $childData['child_id'],
                'full_name' => trim($childData['first_name'] . ' ' . $childData['last_name']),
                'first_name' => $childData['first_name'],
                'last_name' => $childData['last_name'],
                'age' => $age ?? 'Not available',
                'date_of_birth' => $childData['date_of_birth'] ?? 'Not available',
                'gender' => $childData['gender'] ?? 'Not available',
                'admission_date' => $childData['admission_date'] ?? 'Not available',
                'guardian_contact' => $childData['guardian_contact'] ?? 'Not available'
            ]
        ];

        // Get existing health records if any
        $healthStmt = $pdo->prepare("SELECT * FROM health_records WHERE child_id = ? ORDER BY health_check_date DESC LIMIT 1");
        $healthStmt->execute([$child_id]);
        
        if ($healthStmt->rowCount() > 0) {
            $healthData = $healthStmt->fetch(PDO::FETCH_ASSOC);
            $response['health_records'] = $healthData;
        }

        echo json_encode($response);
    } else {
        echo json_encode([
            'found' => false,
            'message' => 'No child found with this ID'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}