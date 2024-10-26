<?php
session_start();

// Ensure the child ID is in session
if (!isset($_SESSION['child_id'])) {
    die('Child ID not found. Please fill in the basic information first.');
}

$child_id = $_SESSION['child_id'];

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

// Fetch child profile
$query = "SELECT * FROM children WHERE child_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$child_id]);
$childProfile = $stmt->fetch(PDO::FETCH_ASSOC);

// If no child profile is found
if (!$childProfile) {
    die("No child profile found for the given Child ID.");
}

// Fetch health records for the child
$query_health = "SELECT * FROM health_records WHERE child_id = ?";
$stmt_health = $pdo->prepare($query_health);
$stmt_health->execute([$child_id]);
$healthRecords = $stmt_health->fetchAll(PDO::FETCH_ASSOC);

// Fetch education records for the child
$query_education = "SELECT * FROM education_records WHERE child_id = ?";
$stmt_education = $pdo->prepare($query_education);
$stmt_education->execute([$child_id]);
$educationRecords = $stmt_education->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child Profile - Children's Home Management System</title>
    <link rel="stylesheet" href="children_profiles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            <li><a href="health_records.html">Health Records</a></li>
            <li><a href="education_records.html">Educational Records</a></li>
            <li><a href="events.html">Events</a></li>
            <li><a href="programs.html">Programs</a></li>
            <li><a href="volunteers.html">Volunteers</a></li>
            <li><a href="children_profile.php">Children Profiles</a></li>
            <li><a href="reports_impact.html">Reports & Impact</a></li>
        </ul>
    </div>

    <section>
        <h1>Child Profile</h1>
        <table>
            <thead>
                <tr>
                    <th>Child Id</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Date of Birth</th>
                    <th>Gender</th>
                    <th>Admission Date</th>
                    <th>Guardian Contact</th>
                    <th>Profile Picture</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Display child profile data
                echo "<tr>
                    <td>" . htmlspecialchars($childProfile['child_id']) . "</td>
                    <td>" . htmlspecialchars($childProfile['first_name']) . "</td>
                    <td>" . htmlspecialchars($childProfile['last_name']) . "</td>
                    <td>" . htmlspecialchars($childProfile['date_of_birth']) . "</td>
                    <td>" . htmlspecialchars($childProfile['gender']) . "</td>
                    <td>" . htmlspecialchars($childProfile['admission_date']) . "</td>
                    <td>" . htmlspecialchars($childProfile['guardian_contact']) . "</td>
                    <td><img src='" . htmlspecialchars($childProfile['profile_picture']) . "' alt='Profile Picture' width='100'></td>
                </tr>";
                ?>
            </tbody>
        </table>
    </section>

   <!-- Health Records Section -->
<section>
    <h2>Health Records</h2>
    <table>
        <thead>
            <tr>
                <th>Child Id</th>
                <th>Health Check Date</th>
                <th>Health Status</th>
                <th>Health Condition</th>
                <th>Diagnosis</th>
                <th>Vaccinations</th>
                <th>Allergies</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (count($healthRecords) > 0) {
                foreach ($healthRecords as $record) {
                    echo "<tr>
                        <td>" . htmlspecialchars($childProfile['child_id']) . "</td>
                        <td>" . htmlspecialchars($record['health_check_date']) . "</td>
                        <td>" . htmlspecialchars($record['health_status']) . "</td>
                        <td>" . (isset($record['health_condition']) ? htmlspecialchars($record['health_condition']) : 'N/A') . "</td>
                        <td>" . (isset($record['diagnosis']) ? htmlspecialchars($record['diagnosis']) : 'N/A') . "</td>
                        <td>" . (isset($record['vaccinations']) ? htmlspecialchars($record['vaccinations']) : 'N/A') . "</td>
                        <td>" . (isset($record['allergies']) ? htmlspecialchars($record['allergies']) : 'N/A') . "</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='7'>No health records found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</section>

<!-- Educational Records Section -->
<section>
    <h2>Educational Records</h2>
    <table>
        <thead>
            <tr>
                <th>Child Id</th>
                <th>Child Condition</th>
                <th>School Name</th>
                <th>Grade Level</th>
                <th>Performance Summary</th>
                <th>Attendance Rate</th>
                <th>Special Needs Support Required</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (count($educationRecords) > 0) {
                foreach ($educationRecords as $record) {
                    echo "<tr>
                        <td>" . htmlspecialchars($childProfile['child_id']) . "</td>
                        <td>" . (isset($childProfile['child_condition']) ? htmlspecialchars($childProfile['child_condition']) : 'N/A') . "</td>
                        <td>" . htmlspecialchars($record['school_name']) . "</td>
                        <td>" . htmlspecialchars($record['grade_level']) . "</td>
                        <td>" . htmlspecialchars($record['performance_summary']) . "</td>
                        <td>" . (isset($record['attendance_rate']) ? htmlspecialchars($record['attendance_rate']) . "%" : 'N/A') . "</td>
                        <td>" . (isset($record['special_needs_support']) ? htmlspecialchars($record['special_needs_support']) : 'N/A') . "</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='7'>No educational records found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
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
