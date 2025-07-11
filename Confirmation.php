<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Function to get booking data from cookie
function getBookingFromCookie() {
    if (isset($_COOKIE['booking'])) {
        $cookie_data = json_decode($_COOKIE['booking'], true);
        // Debug: Check if JSON decoding was successful
        if (json_last_error() === JSON_ERROR_NONE) {
            return $cookie_data;
        } else {
            error_log("JSON decode error: " . json_last_error_msg());
            return null;
        }
    }
    return null;
}

// Function to insert confirmed booking into rides table
function insertConfirmedBooking($pdo, $booking_data) {
    try {
        $sql = "INSERT INTO rides (rider_id, passenger_id, destination, pickup_point, amount, status) 
                VALUES (:rider_id, :passenger_id, :destination, :pickup_point, :amount, :status)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':rider_id', $booking_data['rider_id']);
        $stmt->bindParam(':passenger_id', $booking_data['passenger_id']);
        $stmt->bindParam(':destination', $booking_data['destination']);
        $stmt->bindParam(':pickup_point', $booking_data['pickup_point']);
        $stmt->bindParam(':amount', $booking_data['amount']);
        $stmt->bindParam(':status', $booking_data['status']);
        
        return $stmt->execute();
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to clear booking cookie after confirmation
function clearBookingCookie() {
    setcookie('booking', '', time() - 3600, '/');
}

// Initialize variables
$booking_data = null;
$confirmation_message = '';
$booking_confirmed = false;
$debug_info = '';

// Debug: Check if cookies are available
if (empty($_COOKIE)) {
    $debug_info .= "No cookies found. ";
} else {
    $debug_info .= "Available cookies: " . implode(', ', array_keys($_COOKIE)) . ". ";
}

// Get booking data from cookie
$booking_data = getBookingFromCookie();

// Debug: Log cookie retrieval
if ($booking_data === null) {
    $debug_info .= "No booking cookie found or invalid JSON. ";
    if (isset($_COOKIE['booking'])) {
        $debug_info .= "Cookie exists but JSON is invalid. Raw cookie: " . substr($_COOKIE['booking'], 0, 100) . "... ";
    }
} else {
    $debug_info .= "Booking cookie retrieved successfully. ";
}

// Handle booking confirmation
if (isset($_POST['confirm_booking']) && $booking_data) {
    // Set default values and status
    $booking_data['status'] = 'confirmed';
    
    // Insert into database
    if (insertConfirmedBooking($pdo, $booking_data)) {
        $booking_confirmed = true;
        $confirmation_message = 'Booking confirmed successfully!';
        clearBookingCookie(); // Clear the cookie after successful confirmation
    } else {
        $confirmation_message = 'Error confirming booking. Please try again.';
    }
}

// If no booking data found, redirect or show error
if (!$booking_data && !$booking_confirmed) {
    $confirmation_message = 'No booking data found. Please start a new booking.';
}

// Debug function to safely display data
function safeDisplay($data, $key, $default = 'N/A') {
    return isset($data[$key]) ? htmlspecialchars($data[$key]) : $default;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Booking</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            display: flex;
        }
        
        .sidebar {
            width: 80px;
            background-color: #333;
            height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 20px 0;
        }
        
        .sidebar-icon {
            width: 60px;
            height: 60px;
            margin: 10px auto;
            background-color: #555;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .sidebar-icon:hover {
            background-color: #666;
        }
        
        .sidebar-icon svg {
            width: 24px;
            height: 24px;
            fill: white;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
        }
        
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .confirmation-title {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2em;
        }
        
        .booking-details {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .bus-icon {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .bus-icon svg {
            width: 50px;
            height: 50px;
            fill: #007bff;
        }
        
        .trip-info {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .trip-title {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .trip-details {
            color: #666;
            line-height: 1.6;
        }
        
        .form-section {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .form-display {
            background-color: #f8f9fa;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin: 0;
        }
        
        .driver-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .section-title {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        
        .driver-details {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        
        .driver-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .driver-info-row:last-child {
            border-bottom: none;
        }
        
        .driver-label {
            font-weight: bold;
            color: #555;
        }
        
        .driver-value {
            color: #333;
        }
        
        .driver-price {
            color: #28a745;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .confirm-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            transition: background-color 0.3s;
        }
        
        .confirm-button:hover {
            background-color: #0056b3;
        }
        
        .message-container {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message-link {
            text-decoration: none;
            font-weight: bold;
        }
        
        .debug-info {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-icon">
            <svg viewBox="0 0 24 24">
                <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
            </svg>
        </div>
        <div class="sidebar-icon">
            <svg viewBox="0 0 24 24">
                <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 7V9C15 10.1 15.9 11 17 11V20H19V11C20.1 11 21 10.1 21 9ZM1 9C1 10.1 1.9 11 3 11V20H5V11C6.1 11 7 10.1 7 9V7H1V9Z"/>
            </svg>
        </div>
        <div class="sidebar-icon">
            <svg viewBox="0 0 24 24">
                <path d="M12 2C13.09 2 14 2.91 14 4C14 5.09 13.09 6 12 6C10.91 6 10 5.09 10 4C10 2.91 10.91 2 12 2ZM12 20C10.91 20 10 19.09 10 18C10 16.91 10.91 16 12 16C13.09 16 14 16.91 14 18C14 19.09 13.09 20 12 20ZM12 14C10.91 14 10 13.09 10 12C10 10.91 10.91 10 12 10C13.09 10 14 10.91 14 12C14 13.09 13.09 14 12 14Z"/>
            </svg>
        </div>
    </div>

    <div class="main-content">
        <div class="confirmation-container">
            <h1 class="confirmation-title">Confirm Booking</h1>
            
            <!-- Debug Information -->
            <?php if (!empty($debug_info)): ?>
                <div class="debug-info">
                    <strong>Debug Info:</strong> <?php echo htmlspecialchars($debug_info); ?>
                    <?php if (isset($_COOKIE['booking'])): ?>
                        <br><strong>Raw Cookie Data:</strong> <?php echo htmlspecialchars(substr($_COOKIE['booking'], 0, 200)); ?>...
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="booking-details">
                <div class="bus-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 16c0 .88.39 1.67 1 2.22V20c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h8v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1.78c.61-.55 1-1.34 1-2.22V6c0-3.5-3.58-4-8-4s-8 .5-8 4v10zm3.5 1c-.83 0-1.5-.67-1.5-1.5S6.67 14 7.5 14s1.5.67 1.5 1.5S8.33 17 7.5 17zm9 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm1.5-6H6V6h12v5z"/>
                    </svg>
                </div>
                
                <div class="trip-info">
                    <div class="trip-title"><?php echo safeDisplay($booking_data, 'trip_type', 'Express Bus'); ?></div>
                    <div class="trip-details">
                        Time: <?php echo safeDisplay($booking_data, 'trip_time', '10:00 A.M - 13:30 P.M'); ?><br>
                        Route: <?php echo safeDisplay($booking_data, 'pickup_point', 'A') . ' → ' . safeDisplay($booking_data, 'destination', 'B'); ?>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <p class="form-display"><?php echo safeDisplay($booking_data, 'passenger_name', 'John Doe'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <p class="form-display"><?php echo safeDisplay($booking_data, 'passenger_email', 'john.doe@email.com'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <p class="form-display"><?php echo safeDisplay($booking_data, 'passenger_phone', '+1 (555) 123-4567'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">ID Number/Passport</label>
                        <p class="form-display"><?php echo safeDisplay($booking_data, 'passenger_id_number', 'ID123456789'); ?></p>
                    </div>
                </div>
            </div>

            <div class="driver-section">
                <h3 class="section-title">Selected Driver</h3>
                <div class="driver-details">
                    <div class="driver-info-row">
                        <span class="driver-label">Driver Name:</span>
                        <span class="driver-value"><?php echo safeDisplay($booking_data, 'rider_name', 'John Smith'); ?></span>
                    </div>
                    <div class="driver-info-row">
                        <span class="driver-label">Rating:</span>
                        <span class="driver-value">⭐ <?php echo safeDisplay($booking_data, 'rating', '4.8'); ?> (<?php echo safeDisplay($booking_data, 'total_rides', '127'); ?> rides)</span>
                    </div>
                    <div class="driver-info-row">
                        <span class="driver-label">Vehicle:</span>
                        <span class="driver-value"><?php echo safeDisplay($booking_data, 'vehicle', 'Toyota Camry') . ' - ' . safeDisplay($booking_data, 'license_plate', 'ABC 123'); ?></span>
                    </div>
                    <div class="driver-info-row">
                        <span class="driver-label">Price:</span>
                        <span class="driver-value driver-price">$<?php echo safeDisplay($booking_data, 'amount', '25'); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($booking_confirmed): ?>
                <div class="message-container success-message">
                    <strong><?php echo htmlspecialchars($confirmation_message); ?></strong>
                    <br>
                    <a href="Dashboard.php" class="message-link" style="color: #155724;">Go to Dashboard</a>
                </div>
            <?php elseif ($booking_data): ?>
                <form method="POST" action="">
                    <button type="submit" name="confirm_booking" class="confirm-button">Confirm Booking</button>
                </form>
            <?php else: ?>
                <div class="message-container error-message">
                    <strong><?php echo htmlspecialchars($confirmation_message); ?></strong>
                    <br>
                    <a href="Selection.php" class="message-link" style="color: #721c24;">Start New Booking</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add some interactivity to sidebar icons
        document.querySelectorAll('.sidebar-icon').forEach(icon => {
            icon.addEventListener('click', function() {
                // You can implement navigation functionality here
                alert('Navigation feature - would redirect to respective page');
                window.location.href = 'Dashboard.php'; // Example redirect
            });
        });

        // Show confirmation message if booking was successful
        <?php if ($booking_confirmed): ?>
            // Optional: Add any client-side success animations or effects here
            console.log('Booking confirmed successfully!');
        <?php endif; ?>
        
        // Debug: Log booking data to console
        <?php if ($booking_data): ?>
            console.log('Booking data:', <?php echo json_encode($booking_data); ?>);
        <?php else: ?>
            console.log('No booking data found');
        <?php endif; ?>
    </script>
</body>
</html>