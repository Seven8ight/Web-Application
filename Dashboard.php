<?php
// Database configuration
$host = 'localhost';
$dbname = 'transport app';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch rides from database
function fetchRides($pdo, $rider_id = null) {
    $sql = "SELECT ride_id, rider_id, passenger_id, destination, amount, pickup_point
            FROM rides";

    if ($rider_id) {
        $sql .= " WHERE rider_id = :rider_id";
    }

    $sql .= " ORDER BY ride_id DESC";

    $stmt = $pdo->prepare($sql);
    if ($rider_id) {
        $stmt->bindParam(':rider_id', $rider_id);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get passenger name by ID (you'll need a passengers table)
function getPassengerName($pdo, $passenger_id) {
    $stmt = $pdo->prepare("SELECT name FROM user WHERE userid = :passenger_id");
    $stmt->bindParam(':passenger_id', $passenger_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['name'] : 'Unknown Passenger';
}

// Get ride statistics
function getRideStats($pdo, $rider_id = null) {
    $sql = "SELECT
                COUNT(*) as total_rides,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount
            FROM rides";

    if ($rider_id) {
        $sql .= " WHERE rider_id = :rider_id";
    }

    $stmt = $pdo->prepare($sql);
    if ($rider_id) {
        $stmt->bindParam(':rider_id', $rider_id);
    }
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Insert new ride
function insertRide($pdo, $rider_id, $passenger_id, $destination, $amount, $pickup_point = null) {
    $sql = "INSERT INTO rides (rider_id, passenger_id, destination, amount, pickup_point)
            VALUES (:rider_id, :passenger_id, :destination, :amount, :pickup_point)";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':rider_id', $rider_id);
    $stmt->bindParam(':passenger_id', $passenger_id);
    $stmt->bindParam(':destination', $destination);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':pickup_point', $pickup_point);

    return $stmt->execute();
}

// Delete ride
function deleteRide($pdo, $ride_id, $rider_id = null) {
    $sql = "DELETE FROM rides WHERE ride_id = :ride_id";

    if ($rider_id) {
        $sql .= " AND rider_id = :rider_id";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':ride_id', $ride_id);

    if ($rider_id) {
        $stmt->bindParam(':rider_id', $rider_id);
    }

    return $stmt->execute();
}

// Example: Get current user's rides (you'll need to implement proper session management)
session_start();
$current_rider_id = $_SESSION['rider_id'] ?? 1; // Default to rider_id 1 for demo

$rides = fetchRides($pdo, $current_rider_id);
$stats = getRideStats($pdo, $current_rider_id);

// Handle ride deletion
if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['ride_id'])) {
    $ride_id = $_POST['ride_id'];
    deleteRide($pdo, $ride_id, $current_rider_id);

    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle new ride booking
if (isset($_POST['action']) && $_POST['action'] == 'book_ride') {
    $passenger_id = $_POST['passenger_id'];
    $destination = $_POST['destination'];
    $amount = $_POST['amount'];
    $pickup_point = $_POST['pickup_point'] ?? null;

    if (insertRide($pdo, $current_rider_id, $passenger_id, $destination, $amount, $pickup_point)) {
        $success_message = "Ride booked successfully!";
        // Refresh data
        $rides = fetchRides($pdo, $current_rider_id);
        $stats = getRideStats($pdo, $current_rider_id);
    } else {
        $error_message = "Failed to book ride. Please try again.";
    }
}

// Handle profile update
if (isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    // Add profile update logic here
    $success_message = "Profile updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #7fb3a3;
            min-height: 100vh;
            display: flex;
            padding: 20px;
        }

        .sidebar {
            width: 80px;
            background-color: #e8e8e8;
            border-radius: 10px;
            padding: 20px 0;
            margin-right: 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            align-items: center;
            height: fit-content;
        }

        .sidebar-icon {
            width: 50px;
            height: 50px;
            background-color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-decoration: none;
        }

        .sidebar-icon:hover, .sidebar-icon.active {
            background-color: #7fb3a3;
            transform: translateY(-2px);
        }

        .sidebar-icon:hover svg, .sidebar-icon.active svg {
            fill: white;
        }

        .sidebar-icon svg {
            width: 24px;
            height: 24px;
            fill: #333;
        }

        .main-content {
            flex: 1;
            background-color: #d4d4d4;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }

        .tab-buttons {
            display: flex;
            gap: 10px;
        }

        .tab-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background-color: white;
            color: #333;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .tab-btn.active {
            background-color: #7fb3a3;
            color: white;
        }

        .tab-btn:hover {
            transform: translateY(-1px);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        /* Rides Tab Styles */
        .rides-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .rides-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            flex: 1;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #7fb3a3;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .rides-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .ride-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.3s;
        }

        .ride-card:hover {
            transform: translateY(-2px);
        }

        .ride-info {
            flex: 1;
        }

        .ride-route {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .ride-details {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .ride-amount {
            font-size: 14px;
            color: #7fb3a3;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .ride-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }

        .btn-view {
            background-color: #7fb3a3;
            color: white;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-1px);
        }

        /* Book Ride Form */
        .book-ride-form {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1;
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #7fb3a3;
        }

        .book-btn {
            background-color: #7fb3a3;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .book-btn:hover {
            background-color: #6a9d8a;
        }

        /* Profile Tab Styles */
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .profile-section {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }

        .save-btn {
            width: 100%;
            padding: 15px;
            background-color: #7fb3a3;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .save-btn:hover {
            background-color: #6a9d8a;
        }

        .no-rides {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
                margin-right: 15px;
            }

            .main-content {
                padding: 20px;
            }

            .header {
                flex-direction: column;
                gap: 20px;
            }

            .rides-stats {
                flex-direction: column;
            }

            .profile-container {
                grid-template-columns: 1fr;
            }

            .ride-card {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .ride-actions {
                justify-content: center;
            }

            .form-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="dashboard.php" class="sidebar-icon active">
            <svg viewBox="0 0 24 24">
                <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
            </svg>
        </a>
        <a href="selection.php" class="sidebar-icon">
            <svg viewBox="0 0 24 24">
                <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 7V9C15 10.1 15.9 11 17 11V20H19V11C20.1 11 21 10.1 21 9ZM1 9C1 10.1 1.9 11 3 11V20H5V11C6.1 11 7 10.1 7 9V7H1V9Z"/>
            </svg>
        </a>
        <a href="user.php" class="sidebar-icon">
            <svg viewBox="0 0 24 24">
                <path d="M12 2C13.09 2 14 2.91 14 4C14 5.09 13.09 6 12 6C10.91 6 10 5.09 10 4C10 2.91 10.91 2 12 2ZM12 20C10.91 20 10 19.09 10 18C10 16.91 10.91 16 12 16C13.09 16 14 16.91 14 18C14 19.09 13.09 20 12 20ZM12 14C10.91 14 10 13.09 10 12C10 10.91 10.91 10 12 10C13.09 10 14 10.91 14 12C14 13.09 13.09 14 12 14Z"/>
            </svg>
        </a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1 class="page-title">Dashboard</h1>
            <div class="tab-buttons">
                <button class="tab-btn active" onclick="switchTab('rides')">My Rides</button>
                <button class="tab-btn" onclick="switchTab('profile')">Profile</button>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
        <div class="success-message">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <!-- Rides Tab -->
        <div id="rides-tab" class="tab-content active">
            <!-- Book New Ride Form -->
            <div class="book-ride-form">
                <h3 style="margin-bottom: 20px; color: #333;">Book New Ride</h3>
                <form method="post">
                    <input type="hidden" name="action" value="book_ride">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Passenger ID</label>
                            <input type="number" name="passenger_id" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pickup Point</label>
                            <input type="text" name="pickup_point" class="form-input" placeholder="Enter pickup point">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Destination</label>
                            <input type="text" name="destination" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Amount ($)</label>
                            <input type="number" name="amount" class="form-input" step="0.01" min="0" required>
                        </div>
                    </div>
                    <button type="submit" class="book-btn">Book Ride</button>
                </form>
            </div>

            <div class="rides-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_rides']; ?></div>
                    <div class="stat-label">Total Rides</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($stats['total_amount'], 2); ?></div>
                    <div class="stat-label">Total Amount</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($stats['avg_amount'], 2); ?></div>
                    <div class="stat-label">Average Amount</div>
                </div>
            </div>

            <div class="rides-list">
                <?php if (empty($rides)): ?>
                    <div class="no-rides">
                        <p>No rides found. Book your first ride!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($rides as $ride): ?>
                    <div class="ride-card">
                        <div class="ride-info">
                            <div class="ride-route">
                                <?php echo htmlspecialchars($ride['pickup_point'] ?? 'Pickup Point'); ?> →
                                <?php echo htmlspecialchars($ride['destination']); ?>
                            </div>
                            <div class="ride-details">
                                Ride ID: <?php echo htmlspecialchars($ride['ride_id']); ?> •
                                Passenger ID: <?php echo htmlspecialchars($ride['passenger_id']); ?> •
                                Rider ID: <?php echo htmlspecialchars($ride['rider_id']); ?>
                            </div>
                            <div class="ride-amount">
                                $<?php echo number_format($ride['amount'], 2); ?>
                            </div>
                        </div>
                        <div class="ride-actions">
                            <button class="action-btn btn-view" onclick="viewRide(<?php echo $ride['ride_id']; ?>)">View</button>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="ride_id" value="<?php echo $ride['ride_id']; ?>">
                                <button type="submit" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this ride?')">Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Profile Tab -->
        <div id="profile-tab" class="tab-content">
            <div class="profile-container">
                <div class="profile-section">
                    <h2 class="section-title">Personal Information</h2>
                    <form method="post" id="personalForm">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-input" value="John Doe" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-input" value="john.doe@email.com" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-input" value="+1 (555) 123-4567" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" class="form-input" value="1990-01-15" required>
                        </div>
                        <button type="submit" class="save-btn">Save Personal Info</button>
                    </form>
                </div>

                <div class="profile-section">
                    <h2 class="section-title">Account Settings</h2>
                    <form method="post" id="accountForm">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label class="form-label">ID Number/Passport</label>
                            <input type="text" name="id_number" class="form-input" value="ID123456789" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-input" value="123 Main Street, City" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Emergency Contact</label>
                            <input type="text" name="emergency_contact" class="form-input" value="Jane Doe - +1 (555) 987-6543" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Preferred Language</label>
                            <select name="language" class="form-input" required>
                                <option value="en" selected>English</option>
                                <option value="es">Spanish</option>
                                <option value="fr">French</option>
                                <option value="sw">Swahili</option>
                            </select>
                        </div>
                        <button type="submit" class="save-btn">Save Account Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab and activate button
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }

        function viewRide(rideId) {
            // You can redirect to a detailed ride view page
            window.location.href = `ride_details.php?ride_id=${rideId}`;
        }
    </script>
</body>
</html>