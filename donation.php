<?php
// Database connection settings
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

// Form submission handling
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Collect and validate user inputs
    $donor_name = trim($_POST['donor_name']);
    $donation_type = trim($_POST['donation_type']);
    $donation_date = trim($_POST['donation_date']);
    $description = trim($_POST['description']) ?: NULL;

    // Initialize variables for both types
    $amount = NULL;
    $category = NULL;
    $quantity = NULL;
    $unit = NULL;

    // Basic validation
    $error = false;
    $error_message = "";

    if (empty($donor_name) || empty($donation_type) || empty($donation_date)) {
        $error = true;
        $error_message = "Please fill in all required fields.";
    }

    if ($donation_type === 'financial') {
        $amount = trim($_POST['amount']);
        if (!is_numeric($amount) || $amount <= 0) {
            $error = true;
            $error_message = "Amount should be a positive number.";
        }
    } else if ($donation_type === 'material') {
        $category = trim($_POST['category']);
        $quantity = trim($_POST['quantity']);
        $unit = trim($_POST['unit']);
        
        if (empty($category) || !is_numeric($quantity) || $quantity <= 0 || empty($unit)) {
            $error = true;
            $error_message = "Please provide valid material donation details.";
        }
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $donation_date)) {
        $error = true;
        $error_message = "Invalid date format. Use YYYY-MM-DD.";
    }

    if ($error) {
        echo "<script>alert('$error_message'); window.location.href='donation.php';</script>";
        exit();
    }

    try {
        // Start transaction
        $conn->begin_transaction();

        if ($donation_type === 'financial') {
            // Insert into donations table
            $stmt = $conn->prepare("INSERT INTO donations (donor_name, donation_type, amount, donation_date, description) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("ssdss", $donor_name, $donation_type, $amount, $donation_date, $description);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            // Update financial_transactions table
            $stmt2 = $conn->prepare("INSERT INTO financial_transactions (transaction_date, description, amount_in, amount_out) VALUES (?, ?, ?, 0)");
            if (!$stmt2) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt2->bind_param("ssd", $donation_date, $description, $amount);
            
            if (!$stmt2->execute()) {
                throw new Exception("Execute failed: " . $stmt2->error);
            }
            
            $stmt2->close();
        } else if ($donation_type === 'material') {
            // First check if category exists in material_inventory
            $check_category = $conn->prepare("SELECT category FROM material_inventory WHERE category = ?");
            if (!$check_category) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $check_category->bind_param("s", $category);
            $check_category->execute();
            $category_result = $check_category->get_result();
            
            // If category doesn't exist, create it with initial values
            if ($category_result->num_rows === 0) {
                $init_inventory = $conn->prepare("INSERT INTO material_inventory (category, opening_stock, received, used, current_stock) VALUES (?, 0, 0, 0, 0)");
                if (!$init_inventory) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $init_inventory->bind_param("s", $category);
                if (!$init_inventory->execute()) {
                    throw new Exception("Failed to initialize inventory: " . $init_inventory->error);
                }
                $init_inventory->close();
            }
            $check_category->close();
            
            // Insert into donations table for material donation
            $stmt = $conn->prepare("INSERT INTO donations (donor_name, donation_type, category, quantity, unit, donation_date, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("sssisss", $donor_name, $donation_type, $category, $quantity, $unit, $donation_date, $description);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            // Update material_inventory
            $update_inventory = $conn->prepare("UPDATE material_inventory SET received = COALESCE(received, 0) + ?, current_stock = COALESCE(current_stock, 0) + ? WHERE category = ?");
            if (!$update_inventory) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $update_inventory->bind_param("iis", $quantity, $quantity, $category);
            if (!$update_inventory->execute()) {
                throw new Exception("Failed to update inventory: " . $update_inventory->error);
            }
            
            $update_inventory->close();
        }

        $stmt->close();
        $conn->commit();

        echo "<script>
            alert('Donation recorded successfully!');
            window.location.href='reports_impact.php';
        </script>";
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href='donation.php';</script>";
        error_log("Donation insertion error: " . $e->getMessage());
        exit();
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Donation Form</title>
    <link rel="stylesheet" href="donation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
       <style>
        .form-group {
            margin-bottom: 15px;
        }
        .material-fields, .financial-fields {
            display: none;
        }
        .active {
            display: block;
        }
    </style>
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
            <li><a href="volunteers.html">Volunteers</a></li>
            <li><a href="children_profile.php">Children Profiles</a></li>
            <li><a href="donation.php">Donations</a></li>
            <li><a href="reports_impact.php">Reports & Impact</a></li>
        </ul>
    </div>

    <h2>Record a Donation</h2>
    <div class="form-container">
        <form method="POST" action="">
            <div class="form-group">
                <label for="donor_name">Donor Name:</label>
                <input type="text" id="donor_name" name="donor_name" required>
            </div>

            <div class="form-group">
                <label for="donation_type">Donation Type:</label>
                <select id="donation_type" name="donation_type" required onchange="toggleDonationFields()">
                    <option value="">Select Type</option>
                    <option value="financial">Financial</option>
                    <option value="material">Material/In-Kind</option>
                </select>
            </div>

            <div class="form-group">
                <label for="donation_date">Donation Date:</label>
                <input type="date" id="donation_date" name="donation_date" required>
            </div>

            <div class="financial-fields">
                <div class="form-group">
                    <label for="amount">Amount (in KSh):</label>
                    <input type="number" id="amount" name="amount" min="1" step="0.01">
                </div>
            </div>

            <div class="material-fields">
                <div class="form-group">
                    <label for="category">Category:</label>
                    <select id="category" name="category">
                        <option value="">Select Category</option>
                        <option value="Food Supplies">Food Supplies</option>
                        <option value="Clothing">Clothing</option>
                        <option value="School Supplies">School Supplies</option>
                        <option value="Hygiene Products">Hygiene Products</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" min="1">
                </div>

                <div class="form-group">
                    <label for="unit">Unit:</label>
                    <select id="unit" name="unit">
                        <option value="">Select Unit</option>
                        <option value="pieces">Pieces</option>
                        <option value="kg">Kilograms</option>
                        <option value="sets">Sets</option>
                        <option value="boxes">Boxes</option>
                        <option value="units">Units</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea style="font-family: 'Times New Roman';" id="description" name="description" ></textarea>
            </div>

            <input type="submit" value="Submit Donation">
        </form>
    </div>

    <script>
        function toggleDonationFields() {
            const donationType = document.getElementById('donation_type').value;
            const financialFields = document.querySelector('.financial-fields');
            const materialFields = document.querySelector('.material-fields');
            
            if (donationType === 'financial') {
                financialFields.classList.add('active');
                materialFields.classList.remove('active');
                // Make financial fields required
                document.getElementById('amount').required = true;
                // Make material fields not required
                document.getElementById('category').required = false;
                document.getElementById('quantity').required = false;
                document.getElementById('unit').required = false;
            } else if (donationType === 'material') {
                materialFields.classList.add('active');
                financialFields.classList.remove('active');
                // Make material fields required
                document.getElementById('category').required = true;
                document.getElementById('quantity').required = true;
                document.getElementById('unit').required = true;
                // Make financial fields not required
                document.getElementById('amount').required = false;
            } else {
                financialFields.classList.remove('active');
                materialFields.classList.remove('active');
            }
        }
    </script>
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