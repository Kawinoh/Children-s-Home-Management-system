<?php
// volunteer_handler.php
// Database connection
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "children_home_management_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve form data
$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$skills = $_POST['skills'];

// Check for duplicate email
$checkEmailSql = "SELECT COUNT(*) FROM volunteers WHERE email = ?";
$checkStmt = $conn->prepare($checkEmailSql);
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkStmt->bind_result($emailCount);
$checkStmt->fetch();
$checkStmt->close();

// If the email already exists, show an alert and redirect
if ($emailCount > 0) {
    echo "<script>
            alert('This email is already registered. Please use a different email.');
            window.location.href = 'volunteers.html'; 
          </script>";
} else {
    // Insert data into the database
    $sql = "INSERT INTO volunteers (name, email, phone, skills, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $phone, $skills); // Match 4 variables

    try {
        // Execute the statement
        if ($stmt->execute()) {
            // Show success message and redirect back to the form
            echo "<script>
                    alert('Thank you for your application!');
                    window.location.href = 'volunteers.html'; 
                  </script>";
        }
    } catch (mysqli_sql_exception $e) {
        // Show error message and redirect back to the form
        echo "<script>
                alert('Error: " . addslashes($e->getMessage()) . "');
                setTimeout(function() {
                    window.location.href = 'volunteers.html'; 
                }, 3000); // Redirect after 3 seconds
              </script>";
    }

    $stmt->close();
}

$conn->close();
?>
