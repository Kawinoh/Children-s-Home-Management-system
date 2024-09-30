<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

if (isset($_POST['login'])) {
    // Get the form data
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // To prevent SQL injection
    $user = $conn->real_escape_string($user);

    // Check if user exists
    $sql = "SELECT * FROM users WHERE username='$user'";
    $result = $conn->query($sql);

    if (!$result) {
        // Query failed
        echo "Error executing query: " . $conn->error;
    } else {
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Verify password
            if (password_verify($pass, $row['password'])) {
                // Password is correct, set session and redirect to home page
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                header("Location: home.html");
                exit();
            } else {
                // Invalid password, redirect to register page
                $_SESSION['register_message'] = "Invalid username or password. Please register.";
                header("Location: login.html");
                exit();
            }
        } else {
            // User not found, redirect to register page
            $_SESSION['register_message'] = "User not found. Please register.";
            header("Location: register.html");
            exit();
        }
    }


  $conn->close();
}
?>
