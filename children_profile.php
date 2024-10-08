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
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode to exceptions
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize variables for child profile
$updatedProfile = [];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $child_id = $_POST['child_id'];
    $first_name = $_POST['first_name'];
    $date_of_birth = $_POST['date_of_birth'];
    $guardian_contact = $_POST['guardian_contact'];
    $admission_date = $_POST['admission_date'];

    // Prepare the SQL update statement
    $query = "UPDATE children SET first_name = ?, date_of_birth = ?, guardian_contact = ?, admission_date = ? WHERE child_id = ?";
    $stmt = $pdo->prepare($query);

    // Execute the statement
    if ($stmt->execute([$first_name, $date_of_birth, $guardian_contact, $admission_date, $child_id])) {
        $_SESSION['message'] = "Profile updated successfully."; // Set success message

        // Store updated profile data for immediate display
        $updatedProfile = [
            'first_name' => $first_name,
            'date_of_birth' => $date_of_birth,
            'guardian_contact' => $guardian_contact,
            'admission_date' => $admission_date,
            'child_id' => $child_id
        ];
    } else {
        $_SESSION['message'] = "Error updating profile."; // Set error message
    }

    // Close the statement
    $stmt = null; // Close PDO statement
}

// Fetching child profiles from the database
$query = "SELECT * FROM children"; // Adjust table name if needed
$stmt = $pdo->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Children Profiles - Children's Home Management System</title>
    <link rel="stylesheet" href="children_profiles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        // Show alert if there is a message in the session and redirect after delay
        window.onload = function() {
            <?php if (isset($_SESSION['message'])): ?>
                alert("<?php echo $_SESSION['message']; ?>");
                <?php unset($_SESSION['message']); // Clear message after showing it ?>
                setTimeout(function() {
                    window.location.href = 'profile.html'; // Redirect after alert
                }, 2000); // 2-second delay before redirecting
            <?php endif; ?>
        };
    </script>
</head>
<body>
    <div class="navbar">
        <a href="home.html" class="logo-link">
            <img src="logo/cmhs logo.png" alt="CHMS Logo" class="logo">
        </a>
        <ul>
            <li><a href="home.html">Home</a></li>
            <li><a href="about.html">About</a></li>
            <li><a href="profile.html">Profile</a></li>
            <li><a href="contact.html" class="contact-link">Contact</a></li>
            <li><a href="gallery.html">Gallery</a></li>
            <li><a href="events.html">Events</a></li>
            <li><a href="programs.html">Programs</a></li>
            <li><a href="volunteers.html">Volunteers</a></li>
            <li><a href="children_profile.php">Children Profiles</a></li>
            <li><a href="reports_impact.html">Reports & Impact</a></li>
        </ul>
    </div>

    <section>
        <h1>Children Profiles</h1>
        <p>Below are the profiles of the children under our care, including basic information, health records, and educational progress.</p>

        <table>
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Date of Birth</th>
                    <th>Guardian Contact</th>
                    <th>Admission Date</th>
                    <th>More Details</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Check for results
                if ($stmt->rowCount() > 0) {
                    // Output data for each child
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        // Use null coalescing operator to avoid undefined index notice
                        $first_name = $row['first_name'] ?? 'N/A';
                        $date_of_birth = $row['date_of_birth'] ?? 'N/A';
                        $guardian_contact = $row['guardian_contact'] ?? 'N/A';
                        $admission_date  = $row['admission_date'] ?? 'N/A';

                        // Check if the profile was just updated
                        if ($updatedProfile && $row['child_id'] == $updatedProfile['child_id']) {
                            $first_name = $updatedProfile['first_name'];
                            $date_of_birth = $updatedProfile['date_of_birth'];
                            $guardian_contact = $updatedProfile['guardian_contact'];
                            $admission_date = $updatedProfile['admission_date'];
                        }

                        echo "<tr>
                                <td>{$first_name}</td>
                                <td>{$date_of_birth}</td>
                                <td>{$guardian_contact}</td>
                                <td>{$admission_date}</td>
                                <td><a href='children_profile.php?child_id={$row['child_id']}'>View Details</a></td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No profiles found.</td></tr>";
                }

                // Close database connection
                $pdo = null; // Close PDO connection
                ?>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Update Child Profile</h2>
        <div class="form-container">
            <form action="children_profile.php" method="POST">
                <label for="child_id">Child ID:</label>
                <input type="text" id="child_id" name="child_id" required>

                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" required>

                <label for="date_of_birth">Date of Birth:</label>
                <input type="date" id="date_of_birth" name="date_of_birth" required>

                <label for="guardian_contact">Guardian Contact:</label>
                <input type="text" id="guardian_contact" name="guardian_contact" required>

                <label for="admission_date">Admission Date:</label>
                <input type="date" id="admission_date" name="admission_date" required>

                <button type="submit">Update Profile</button>
            </form>
        </div>
    </section>

    <footer>
        <div class="footer-content">
            <p>Contact us: 
                <a href="mailto:info@childrenhomesystem.org" target="_blank">
                    <i class="fas fa-envelope"></i> info@theangelschildrenhomesystem.org
                </a> 
                | Phone: +254-707-332-850
            </p>
            <p>Follow us: 
                <a href="https://facebook.com/theangelschildrenhomesystem" target="_blank">
                    <i class="fab fa-facebook"></i>
                </a>
                <a href="https://twitter.com/theangelschildrenhomesystem" target="_blank">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://instagram.com/theangelschildrenhomesystem" target="_blank">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://linkedin.com/company/theangelschildrenhomesystem" target="_blank">
                    <i class="fab fa-linkedin"></i>
                </a>
            </p>
        </div>
    </footer>
</body>
</html>
