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

// Initialize search variables
$search_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$search_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search_term = isset($_GET['search_term']) ? $_GET['search_term'] : '';
$search_category = isset($_GET['category']) ? $_GET['category'] : '';

// Query Building Logic for Financial Transactions
function buildFinancialQuery($conn, $search_date_from, $search_date_to, $search_term) {
    $conditions = [];
    $params = [];
    $types = '';

    if ($search_date_from && $search_date_to) {
        $conditions[] = "transaction_date BETWEEN ? AND ?";
        $params[] = $search_date_from;
        $params[] = $search_date_to;
        $types .= 'ss';
    }

    if ($search_term) {
        $conditions[] = "(description LIKE ? OR category LIKE ?)";
        $params[] = "%$search_term%";
        $params[] = "%$search_term%";
        $types .= 'ss';
    }

    $financial_query = "SELECT transaction_date, description, amount_in, amount_out FROM financial_transactions";
    if (!empty($conditions)) {
        $financial_query .= " WHERE " . implode(" AND ", $conditions);
    }
    $financial_query .= " ORDER BY transaction_date DESC";

    $stmt = $conn->prepare($financial_query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

// Function to Update Material Stock
function updateMaterialStock($conn, $category, $quantity, $description, $isUsage = true) {
    try {
        $conn->begin_transaction();
        
        // Check if category exists
        $stmt = $conn->prepare("SELECT * FROM material_inventory WHERE category = ?");
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $result = $stmt->get_result();
        $current = $result->fetch_assoc();

        // If category doesn't exist, create it
        if ($result->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO material_inventory (category, opening_stock, received, used, current_stock) VALUES (?, 0, 0, 0, 0)");
            $stmt->bind_param("s", $category);
            $stmt->execute();
            $current = ['current_stock' => 0];
        }

        // Check stock availability for usage
        if ($isUsage && $current['current_stock'] < $quantity) {
            throw new Exception("Insufficient stock available. Current stock: " . $current['current_stock']);
        }

        // Update stock values based on usage or donation
        if ($isUsage) {
            $stmt = $conn->prepare("UPDATE material_inventory SET used = COALESCE(used, 0) + ?, current_stock = current_stock - ? WHERE category = ?");
        } else {
            $stmt = $conn->prepare("UPDATE material_inventory SET received = COALESCE(received, 0) + ?, current_stock = current_stock + ? WHERE category = ?");
        }
        $stmt->bind_param("iis", $quantity, $quantity, $category);
        $stmt->execute();

        // Record transaction in material_transactions table
        $date = date('Y-m-d');
        $transaction_type = $isUsage ? 'usage' : 'received';
        $stmt = $conn->prepare("INSERT INTO material_transactions (category, quantity, transaction_type, description, transaction_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisss", $category, $quantity, $transaction_type, $description, $date);
        $stmt->execute();

        $conn->commit();
        return ["success" => true, "message" => "Stock updated successfully"];
    } catch (Exception $e) {
        $conn->rollback();
        return ["success" => false, "message" => $e->getMessage()];
    }
}

// Function to get Material Report
function getMaterialReport($conn, $dateFrom = null, $dateTo = null, $category = null) {
    $conditions = [];
    $params = [];
    $types = '';

    if ($dateFrom && $dateTo) {
        $conditions[] = "t.transaction_date BETWEEN ? AND ?";
        $params[] = $dateFrom;
        $params[] = $dateTo;
        $types .= 'ss';
    }

    if ($category) {
        $conditions[] = "t.category = ?";
        $params[] = $category;
        $types .= 's';
    }

    $query = "SELECT 
                i.category,
                i.opening_stock,
                i.received,
                i.used,
                i.current_stock,
                t.transaction_date,
                t.transaction_type,
                t.quantity,
                t.description
              FROM material_inventory i
              LEFT JOIN material_transactions t ON i.category = t.category";
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    $query .= " ORDER BY t.transaction_date DESC, i.category";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

// Handle POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['update_stock'])) {
        if (isset($_POST['material_update'])) {
            $category = $_POST['category'];
            $quantity = intval($_POST['quantity']);
            $description = $_POST['description'];
            $isUsage = $_POST['transaction_type'] === 'usage';
            $result = updateMaterialStock($conn, $category, $quantity, $description, $isUsage);
            echo "<script>alert('" . addslashes($result['message']) . "');</script>";
        } elseif (isset($_POST['financial_update'])) {
            $amount = floatval($_POST['amount']);
            $description = $_POST['description'];
            
            $stmt = $conn->prepare("INSERT INTO financial_transactions (transaction_date, description, amount_out) VALUES (CURRENT_DATE(), ?, ?)");
            $stmt->bind_param("sd", $description, $amount);
            
            if ($stmt->execute()) {
                echo "<script>alert('Financial transaction recorded successfully');</script>";
            } else {
                echo "<script>alert('Error recording financial transaction');</script>";
            }
        }
    }
}

// Get financial results for display
$financial_results = buildFinancialQuery($conn, $search_date_from, $search_date_to, $search_term);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Impact - Children's Home Management System</title>
    <link rel="stylesheet" href="reports_impact.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .stock-update-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .update-section {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .stock-update-form button {
            background: #4a90e2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .stock-update-form button:hover {
            background: #357abd;
        }

        .report-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
        }
        
        .report-table th, .report-table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        .report-table th {
            background: #4a90e2;
            color: white;
        }
        
        .report-table tr:nth-child(even) {
            background: #f5f5f5;
        }
        
        .total-row {
            font-weight: bold;
            background: #e8f4ff !important;
        }
        
        .download-btn {
            background: #4a90e2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 0;
        }
        
        .download-btn:hover {
            background: #357abd;
        }
        
        .search-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .search-form input, .search-form select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }
        
        .search-form button {
            padding: 10px;
            background: #4a90e2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .search-form button:hover {
            background: #357abd;
        }
        
        .no-results {
            padding: 20px;
            text-align: center;
            background: #f5f5f5;
            border-radius: 4px;
        }

        .highlight {
            background-color: #ffcc00;
            color: #000;
            padding: 2px;
            border-radius: 3px;
            box-shadow: 0 0 5px rgba(0,0,0,0.5);
            animation: highlight-pulse 1s infinite;
        }

        @keyframes highlight-pulse {
            0% { background-color: #ffcc00; }
            50% { background-color: #ff8800; }
            100% { background-color: #ffcc00; }
        }
        .collapsible-records {
        width: 100%;
        margin: 20px 0;
    }

    .collapsible-container {
        margin-bottom: 15px;
    }

    .collapsible-button {
        width: 100%;
        padding: 15px 20px;
        background-color: #300be6;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 16px;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }

    .collapsible-button:hover {
        background-color: #8309ed;
    }

    .collapsible-content {
        display: none;
        padding: 20px;
        background-color: white;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .collapsible-content.active {
        display: block;
    }

    .fa-chevron-down {
        transition: transform 0.3s ease;
    }

    .fa-chevron-down.rotated {
        transform: rotate(180deg);
    }
    .record-label {
      font-size: 18px; /* Adjust font size as needed */
      font-weight: bold;
      color: #333; /* Base text color */
      margin-right: 20px; /* Space between spans if they are on the same line */
  }

  .record-label i {
      margin-left: 8px; /* Space between text and icon */
      color: #dea814; /* Color for icons (e.g., blue) */
  }

  .record-label:hover {
      color: #0056b3; /* Darker shade on hover for text */
      cursor: pointer;
  }

  .record-label i:hover {
      color: #ff5733; /* Different color on hover for icon */
      transition: color 0.3s ease; /* Smooth color transition */
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

    <section>
        <h1>Reports & Impact</h1>
        
        <div class="search-section">
        <form class="search-form" method="GET">
                <div>
                    <label>From Date:</label>
                    <input type="date" name="date_from" value="<?php echo $search_date_from; ?>">
                </div>
                <div>
                    <label>To Date:</label>
                    <input type="date" name="date_to" value="<?php echo $search_date_to; ?>">
                </div>
                <div>
                    <label>Category:</label>
                    <select name="category">
                        <option value="">All Categories</option>
                        <option value="financial" <?php echo $search_category == 'financial' ? 'selected' : ''; ?>>Financial</option>
                        <option value="material" <?php echo $search_category == 'material' ? 'selected' : ''; ?>>Material</option>
                    </select>
                </div>
                <div>
                    <label>Search Term:</label>
                    <input type="text" name="search_term" placeholder="Search..." value="<?php echo $search_term; ?>">
                </div>
                <div>
                    <button type="submit">Search</button>
                    <button type="reset">Clear</button>
                </div>
            </form>
        </div>
        <div class="report-section">
            <h2>Update Stock</h2>
            <form method="POST" class="stock-update-form">
                <input type="hidden" name="update_stock" value="1">
                
                <!-- Financial Update -->
                <div class="update-section">
                    <h3>Financial Update</h3>
                    <div class="form-group">
                        <label>Amount Used:</label>
                        <input type="number" name="amount" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Description:</label>
                        <input type="text" name="description" required>
                    </div>
                    <button type="submit" name="financial_update" value="1">Update Financial Stock</button>
                </div>
                
                <!-- Material Update -->
                <div class="update-section">
                    <h3>Material Update</h3>
                    <div class="form-group">
                        <label>Category:</label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <option value="Food Supplies">Food Supplies</option>
                            <option value="Clothing">Clothing</option>
                            <option value="School Supplies">School Supplies</option>
                            <option value="Hygiene Products">Hygiene Products</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quantity:</label>
                        <input type="number" name="quantity" required>
                    </div>
                    <div class="form-group">
                        <label>Description:</label>
                        <input type="text" name="description" required>
                    </div>
                    <div class="form-group">
                        <label>Transaction Type:</label>
                        <select name="transaction_type" required>
                            <option value="usage">Usage</option>
                            <option value="donation">Donation</option>
                        </select>
                    </div>
                    <button type="submit" name="material_update" value="1">Update Material Stock</button>
                </div>
            </form>
        </div>
        <div class="report-section">
            <div class="collapsible-records">
                <!-- Financial Records Button & Content -->
                <div class="collapsible-container">
                    <button class="collapsible-button" onclick="toggleSection('financial')">
                    <span class="record-label">Financial Records <i class="fas fa-money-bill-wave" id="financial-icon"></i></span>
                    <i class="fas fa-chevron-down" id="financial-icon"></i>
                    </button>
                    <div class="collapsible-content" id="financial-section">
                    <table class="report-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount In (KSh)</th>
                            <th>Amount Out (KSh)</th>
                            <th>Balance (KSh)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($financial_results && $financial_results->num_rows > 0) {
                            $balance = 0;
                            $total_in = 0;
                            $total_out = 0;
                            
                            while ($row = $financial_results->fetch_assoc()) {
                                $amount_in = $row['amount_in'] ?? 0;
                                $amount_out = $row['amount_out'] ?? 0;
                                $balance += $amount_in - $amount_out;
                                $total_in += $amount_in;
                                $total_out += $amount_out;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['transaction_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td><?php echo number_format($amount_in); ?></td>
                                    <td><?php echo number_format($amount_out); ?></td>
                                    <td><?php echo number_format($balance); ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                            <tr class="total-row">
                                <td colspan="2">Total</td>
                                <td><?php echo number_format($total_in); ?></td>
                                <td><?php echo number_format($total_out); ?></td>
                                <td><?php echo number_format($balance); ?></td>
                            </tr>
                            <?php
                        } else {
                            ?>
                            <tr>
                                <td colspan="5" class="no-results">No financial records found</td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                    </div>
                </div>

                <!-- Material Records Button & Content -->
                <div class="collapsible-container">
                    <button class="collapsible-button" onclick="toggleSection('material')">
   <span class="record-label">Material Records <i class="fas fa-box" id="material-icon"></i></span>

                        <i class="fas fa-chevron-down" id="material-icon"></i>
                    </button>
                    <div class="collapsible-content" id="material-section">
                    <table class="report-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Transaction Type</th>
                            <th>Quantity</th>
                            <th>Description</th>
                            <th>Current Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $material_transactions = getMaterialReport($conn, $search_date_from, $search_date_to, $search_category);
                        if ($material_transactions && $material_transactions->num_rows > 0) {
                            while ($row = $material_transactions->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['transaction_date']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['transaction_type']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['current_stock']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='no-results'>No transactions found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="report-section">
            <h2>Download Reports</h2>
            <form method="POST" class="download-form">
                <input type="hidden" name="date_from" value="<?php echo $search_date_from; ?>">
                <input type="hidden" name="date_to" value="<?php echo $search_date_to; ?>">
                
                <button type="submit" name="download_report" value="financial" class="download-btn">
                    <i class="fas fa-download"></i> Financial Report (PDF)
                </button>
                
                <button type="submit" name="download_report" value="material" class="download-btn">
                    <i class="fas fa-download"></i> Material Inventory Report (PDF)
                </button>
                
                <button type="submit" name="download_report" value="complete" class="download-btn">
                    <i class="fas fa-download"></i> Complete Monthly Report (PDF)
                </button>
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
<<script>
// Add this script just before the closing </body> tag
document.addEventListener('DOMContentLoaded', function() {
    // Get form elements
    const searchForm = document.querySelector('.search-form');
    const clearButton = searchForm.querySelector('button[type="reset"]');
    const dateFrom = searchForm.querySelector('input[name="date_from"]');
    const dateTo = searchForm.querySelector('input[name="date_to"]');
    const category = searchForm.querySelector('select[name="category"]');
    const searchTerm = searchForm.querySelector('input[name="search_term"]');

    // Function to highlight text in an element
    function highlightText(element, searchText) {
        if (!searchText.trim()) return;

        const innerHTML = element.innerHTML;
        const index = innerHTML.toLowerCase().indexOf(searchText.toLowerCase());
        if (index >= 0) {
            const text = innerHTML.substring(index, index + searchText.length);
            const highlighted = innerHTML.replace(
                new RegExp(text, 'gi'),
                match => `<mark class="highlight">${match}</mark>`
            );
            element.innerHTML = highlighted;
        }
    }

    // Function to highlight all matching content in tables
    function highlightSearchResults() {
        const searchText = searchTerm.value.trim();
        if (!searchText) return;

        // Get all table cells
        const tableCells = document.querySelectorAll('.report-table td');
        
        // Highlight matching content in each cell
        tableCells.forEach(cell => {
            if (!cell.classList.contains('no-results')) {
                highlightText(cell, searchText);
            }
        });

        // Scroll to first highlight if exists
        const firstHighlight = document.querySelector('.highlight');
        if (firstHighlight) {
            firstHighlight.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    }

    // Function to clear form and refresh page
    function clearForm(e) {
        e.preventDefault();
        dateFrom.value = '';
        dateTo.value = '';
        category.value = '';
        searchTerm.value = '';
        window.location.href = window.location.pathname;
    }

    // Function to validate date range
    function validateDateRange(from, to) {
        if (from && to) {
            const fromDate = new Date(from);
            const toDate = new Date(to);
            
            if (fromDate > toDate) {
                alert('Start date must be before or equal to end date');
                return false;
            }
        }
        return true;
    }

    // Add submit handler to validate form
    searchForm.addEventListener('submit', function(e) {
        // Validate date range before submitting
        if (!validateDateRange(dateFrom.value, dateTo.value)) {
            e.preventDefault();
            return false;
        }
        
        // If search term is empty and no other filters are set, prevent submission
        if (!dateFrom.value && !dateTo.value && !category.value && !searchTerm.value.trim()) {
            e.preventDefault();
            alert('Please enter at least one search criteria');
            return false;
        }
    });

    // Add clear button handler
    clearButton.addEventListener('click', clearForm);

    // Highlight results when page loads if there's a search term
    if (searchTerm.value.trim()) {
        highlightSearchResults();
    }
});
</script>
<script>
function toggleSection(sectionId) {
    // Get the content section and icon
    const section = document.getElementById(sectionId + '-section');
    const icon = document.getElementById(sectionId + '-icon');
    
    // Toggle the active class
    section.classList.toggle('active');
    icon.classList.toggle('rotated');
    
    // Close other sections
    const allSections = document.getElementsByClassName('collapsible-content');
    const allIcons = document.getElementsByClassName('fa-chevron-down');
    
    for (let i = 0; i < allSections.length; i++) {
        if (allSections[i].id !== sectionId + '-section' && allSections[i].classList.contains('active')) {
            allSections[i].classList.remove('active');
            allIcons[i].classList.remove('rotated');
        }
    }
}

// Initialize the first section as open (optional)
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('financial-section').classList.add('active');
    document.getElementById('financial-icon').classList.add('rotated');
});
</script>
</body>
</html>