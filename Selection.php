<?php
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

// Cookie management functions
function createOrderCookie($rider_data) {
    session_start();
    $passenger_id = $_SESSION['user_id'] ?? 1; 
    $destination = "Destination A"; 
    $pickup_point = "Pickup Point B"; 
    
    $order_id = 'order_' . time() . '_' . rand(1000, 9999);
    $order_data = array(
        'order_id' => $order_id,
        'rider_id' => $rider_data['rider_id'],
        'rider_name' => $rider_data['rider_name'],
        'amount' => $rider_data['amount'],
        'rating' => $rider_data['rating'],
        'vehicle' => $rider_data['vehicle'],
        'vehicle_type' => $rider_data['vehicle_type'],
        'phone' => $rider_data['phone'], 
        'license_plate' => $rider_data['license_plate'],
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
        'passenger_id' => $passenger_id, 
        'destination' => $destination, 
        'pickup_point' => $pickup_point, 
    );
    
    $cookie_value = json_encode($order_data);
    
    $cookie_set = setcookie(
        'booking', 
        $cookie_value, 
        [
            'expires' => time() + (24 * 60 * 60),
            'path' => '/',
            'domain' => '', 
            'secure' => false, 
            'httponly' => true, 
            'samesite' => 'Lax'
        ]
    );
    
    if (!$cookie_set) {
        error_log("Failed to set cookie: " . $order_id);
        return false;
    }
    
    return $order_id;
}

function getOrderFromCookie($order_id) {
    if (isset($_COOKIE[$order_id])) {
        $decoded = json_decode($_COOKIE[$order_id], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        } else {
            error_log("JSON decode error for cookie: " . $order_id);
        }
    }
    return null;
}

function fetchRiders($pdo, $search = '', $sort_by = 'rating', $sort_order = 'DESC') {
    $sql = "SELECT 
        r.rider_id, 
        r.name as rider_name,
        r.amount,
        r.vehicle,
        r.vehicle_type,
        r.phone,
        r.license_plate,
        r.rating,
        r.availability_status,
        COUNT(COALESCE(ride.ride_id, 0)) as total_rides
    FROM riders r
    LEFT JOIN rides ride ON r.rider_id = ride.rider_id
    WHERE 1=1";
    
    if (!empty($search)) {
        $sql .= " AND (r.name LIKE :search OR r.vehicle LIKE :search OR r.vehicle_type LIKE :search)";
    }
    
    $sql .= " GROUP BY r.rider_id, r.name, r.amount, r.vehicle, r.vehicle_type, r.phone, r.license_plate, r.rating, r.availability_status";
    
    $allowed_sort = ['rating', 'amount', 'rider_name', 'total_rides'];
    if (in_array($sort_by, $allowed_sort)) {
        $order = ($sort_order == 'ASC') ? 'ASC' : 'DESC';
        if ($sort_by == 'rider_name') {
            $sql .= " ORDER BY r.name " . $order;
        } else {
            $sql .= " ORDER BY " . $sort_by . " " . $order;
        }
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        
        if (!empty($search)) {
            $search_param = '%' . $search . '%';
            $stmt->bindParam(':search', $search_param);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error in fetchRiders: " . $e->getMessage());
        return [];
    }
}

function getRiderStats($pdo) {
    try {
        $sql = "SELECT 
                    COUNT(*) as total_riders,
                    COUNT(CASE WHEN availability_status = 'available' THEN 1 END) as available_riders,
                    AVG(rating) as avg_rating,
                    AVG(amount) as avg_amount
                FROM riders";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error in getRiderStats: " . $e->getMessage());
        return [
            'total_riders' => 0,
            'available_riders' => 0,
            'avg_rating' => 0,
            'avg_amount' => 0
        ];
    }
}

session_start();
$current_user_id = $_SESSION['user_id'] ?? 1; 

// Initialize variables
$success_message = '';
$error_message = '';

$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '';
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_SANITIZE_STRING) ?? 'rating';
$sort_order = filter_input(INPUT_GET, 'sort_order', FILTER_SANITIZE_STRING) ?? 'DESC';

// Validate sort parameters
$allowed_sort = ['rating', 'amount', 'rider_name', 'total_rides'];
if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'rating';
}
if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

// Fetch riders and statistics
$riders = fetchRiders($pdo, $search, $sort_by, $sort_order);
$stats = getRiderStats($pdo);

// Handle rider selection and order creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'select_rider') {
    
    // Check if rider_id is set and is numeric
    if (isset($_POST['rider_id']) && is_numeric($_POST['rider_id'])) {
        $rider_id = (int)$_POST['rider_id'];
        
        try {
            // Get rider details from database
            $stmt = $pdo->prepare("SELECT * FROM riders WHERE rider_id = :rider_id AND availability_status = 'available'");
            $stmt->bindParam(':rider_id', $rider_id, PDO::PARAM_INT);
            $stmt->execute();
            $rider_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($rider_data) {
                $order_id = createOrderCookie($rider_data);
                
                if ($order_id) {
                    $_SESSION['current_order_id'] = $order_id;
                    $update_stmt = $pdo->prepare("UPDATE riders SET availability_status = 'busy' WHERE rider_id = :rider_id");
                    $update_stmt->bindParam(':rider_id', $rider_id, PDO::PARAM_INT);
                    $update_stmt->execute();
                    header("Location: Confirmation.php"); 
                    exit();
                } else {
                    $error_message = "Failed to create order. Please try again.";
                }
            } else {
                $error_message = "Rider not found or unavailable.";
            }
        } catch(PDOException $e) {
            error_log("Database error in rider selection: " . $e->getMessage());
            $error_message = "Database error occurred. Please try again.";
        }
    } else {
        $error_message = "Invalid rider selection.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Rider</title>
    <style>
        /* ... (Your existing CSS code) ... */
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

        .search-sort-container {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .search-box {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            background-color: white;
        }

        .search-box:focus {
            outline: none;
            border-color: #7fb3a3;
        }

        .sort-select {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background-color: white;
            font-size: 16px;
            cursor: pointer;
        }

        .search-btn {
            padding: 12px 24px;
            background-color: #7fb3a3;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-btn:hover {
            background-color: #6a9d8a;
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

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

        .riders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .rider-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }

        .rider-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .rider-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .rider-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #7fb3a3;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .rider-info {
            flex: 1;
        }

        .rider-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .rider-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            color: #666;
        }

        .rating-stars {
            color: #ffc107;
        }

        .rider-details {
            margin-bottom: 15px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            color: #333;
            font-weight: bold;
        }

        .vehicle-info {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .vehicle-type {
            font-size: 16px;
            font-weight: bold;
            color: #7fb3a3;
            margin-bottom: 5px;
        }

        .vehicle-details {
            font-size: 14px;
            color: #666;
        }

        .availability-status {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-available {
            background-color: #d4edda;
            color: #155724;
        }

        .status-busy {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-offline {
            background-color: #e2e3e5;
            color: #6c757d;
        }

        .amount-display {
            font-size: 20px;
            font-weight: bold;
            color: #28a745;
            text-align: center;
            margin-bottom: 15px;
        }

        .select-btn {
            width: 100%;
            padding: 12px;
            background-color: #7fb3a3;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .select-btn:hover {
            background-color: #6a9d8a;
        }

        .select-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .no-riders {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
            grid-column: 1 / -1;
        }

        .order-info {
            background-color: #e8f5e8;
            border: 1px solid #28a745;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #155724;
        }
        
        /* New CSS rule to hide labels for accessibility without inline styles */
        .visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
                margin-right: 15px;
            }

            .main-content {
                padding: 20px;
            }

            .search-sort-container {
                flex-direction: column;
            }

            .riders-grid {
                grid-template-columns: 1fr;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="dashboard.php" class="sidebar-icon" aria-label="Dashboard">
            <svg viewBox="0 0 24 24">
                <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
            </svg>
        </a>
        <a href="selection.php" class="sidebar-icon active" aria-label="Select Rider">
            <svg viewBox="0 0 24 24">
                <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 7V9C15 10.1 15.9 11 17 11V20H19V11C20.1 11 21 10.1 21 9ZM1 9C1 10.1 1.9 11 3 11V20H5V11C6.1 11 7 10.1 7 9V7H1V9Z"/>
            </svg>
        </a>
        <a href="user.php" class="sidebar-icon" aria-label="User Profile">
            <svg viewBox="0 0 24 24">
                <path d="M12 2C13.09 2 14 2.91 14 4C14 5.09 13.09 6 12 6C10.91 6 10 5.09 10 4C10 2.91 10.91 2 12 2ZM12 20C10.91 20 10 19.09 10 18C10 16.91 10.91 16 12 16C13.09 16 14 16.91 14 18C14 19.09 13.09 20 12 20ZM12 14C10.91 14 10 13.09 10 12C10 10.91 10.91 10 12 10C13.09 10 14 10.91 14 12C14 13.09 13.09 14 12 14Z"/>
            </svg>
        </a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1 class="page-title">Select Rider</h1>
        </div>

        <?php if (!empty($success_message)): ?>
        <div class="success-message">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <div class="order-info">
            <strong>Order Process:</strong> Select a rider to create an order cookie and proceed to the confirmation page.
        </div>

        <form method="GET" class="search-sort-container">
            <input type="text" 
                   name="search" 
                   class="search-box" 
                   placeholder="Search riders by name, vehicle, or type..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            
            <label for="sort-by" class="visually-hidden">Sort By:</label>
            <select name="sort_by" id="sort-by" class="sort-select">
                <option value="rating" <?php echo $sort_by == 'rating' ? 'selected' : ''; ?>>Rating</option>
                <option value="amount" <?php echo $sort_by == 'amount' ? 'selected' : ''; ?>>Amount</option>
                <option value="rider_name" <?php echo $sort_by == 'rider_name' ? 'selected' : ''; ?>>Name</option>
                <option value="total_rides" <?php echo $sort_by == 'total_rides' ? 'selected' : ''; ?>>Total Rides</option>
            </select>
            
            <label for="sort-order" class="visually-hidden">Sort Order:</label>
            <select name="sort_order" id="sort-order" class="sort-select">
                <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>High to Low</option>
                <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Low to High</option>
            </select>
            
            <button type="submit" class="search-btn">Search</button>
        </form>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_riders']; ?></div>
                <div class="stat-label">Total Riders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['available_riders']; ?></div>
                <div class="stat-label">Available Riders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['avg_rating'], 1); ?></div>
                <div class="stat-label">Average Rating</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($stats['avg_amount'], 2); ?></div>
                <div class="stat-label">Average Amount</div>
            </div>
        </div>

        <div class="riders-grid">
            <?php if (empty($riders)): ?>
                <div class="no-riders">
                    <p>No riders found. Try adjusting your search criteria.</p>
                </div>
            <?php else: ?>
                <?php foreach ($riders as $rider): ?>
                <div class="rider-card">
                    <div class="availability-status status-<?php echo $rider['availability_status']; ?>">
                        <?php echo ucfirst($rider['availability_status']); ?>
                    </div>
                    
                    <div class="rider-header">
                        <div class="rider-avatar">
                            <?php echo strtoupper(substr($rider['rider_name'], 0, 2)); ?>
                        </div>
                        <div class="rider-info">
                            <div class="rider-name"><?php echo htmlspecialchars($rider['rider_name']); ?></div>
                            <div class="rider-rating">
                                <span class="rating-stars">★★★★★</span>
                                <span><?php echo number_format($rider['rating'], 1); ?></span>
                                <span>(<?php echo $rider['total_rides']; ?> rides)</span>
                            </div>
                        </div>
                    </div>

                    <div class="vehicle-info">
                        <div class="vehicle-type"><?php echo htmlspecialchars($rider['vehicle_type']); ?></div>
                        <div class="vehicle-details">
                            <?php echo htmlspecialchars($rider['vehicle']); ?>
                            <?php if (!empty($rider['license_plate'])): ?>
                                • <?php echo htmlspecialchars($rider['license_plate']); ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="rider-details">
                        <div class="detail-row">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($rider['phone']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Total Rides:</span>
                            <span class="detail-value"><?php echo $rider['total_rides']; ?></span>
                        </div>
                    </div>

                    <div class="amount-display">
                        $<?php echo number_format($rider['amount'], 2); ?>
                    </div>

                    <form method="post" action="selection.php">
                        <input type="hidden" name="action" value="select_rider">
                        <button type="submit" 
                                name="rider_id" 
                                value="<?php echo htmlspecialchars($rider['rider_id']); ?>" 
                                class="select-btn" 
                                <?php echo $rider['availability_status'] != 'available' ? 'disabled' : ''; ?>>
                            <?php echo $rider['availability_status'] == 'available' ? 'Select Rider' : 'Unavailable'; ?>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>