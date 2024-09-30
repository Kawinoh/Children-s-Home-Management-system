<?php
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

// Get the form data
$firstname = $_POST['firstname'];
$lastname = $_POST['lastname'];
$user = $_POST['username'];
$pass = $_POST['password'];
$email = $_POST['email']; // Assuming you added this in the registration form

// To prevent SQL injection
$firstname = $conn->real_escape_string($firstname);
$lastname = $conn->real_escape_string($lastname);
$user = $conn->real_escape_string($user);
$pass = $conn->real_escape_string($pass);
$email = $conn->real_escape_string($email);

// Hash the password
$hashed_password = password_hash($pass, PASSWORD_BCRYPT);

// Insert the new user into the users table
$sql = "INSERT INTO users (firstname, lastname, username, password, email) VALUES ('$firstname', '$lastname', '$user', '$hashed_password', '$email')";

if ($conn->query($sql) === TRUE) {
    // Registration successful, redirect to login page
    header("Location: login.html");
} else {
    // Registration failed, display an error message
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
