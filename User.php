<?php
session_start();

// Variables for messages
$message = "";
$message_type = "";

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'transport_app';

// Connects to the database
$connection = mysqli_connect($host, $username, $password, $database);

// Checks if connection failed
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Checks if form was submitted
if ($_POST) {
    $action = $_POST['action'];
    
    // SIGNUP
    if ($action == 'signup') {
        // Get form data
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $id_number = $_POST['id_number'];
        $phone = $_POST['phone'];
        
        // Simple validation
        if (empty($name) || empty($email) || empty($password)) {
            $message = "Please fill all fields!";
            $message_type = "error";
        } else {
            // Check if email already exists
            $check_email = "SELECT * FROM users WHERE email = '$email'";
            $result = mysqli_query($connection, $check_email);
            
            if (mysqli_num_rows($result) > 0) {
                $message = "Email already exists!";
                $message_type = "error";
            } else {
                // Hash password 
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Inserts users into database
                $insert_user = "INSERT INTO users (name, email, password, id_number, phone, role) 
                               VALUES ('$name', '$email', '$hashed_password', '$id_number', '$phone', 'customer')";
                
                if (mysqli_query($connection, $insert_user)) {
                    $message = "Account created successfully! You can now login.";
                    $message_type = "success";
                    header("Location: /Php/Dashboard.php");
                } else {
                    $message = "Error: " . mysqli_error($connection);
                    $message_type = "error";
                }
            }
        }
    }
    
    // LOGIN
    if ($action == 'login') {
        // Gets form data
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        // Simple validation
        if (empty($email) || empty($password)) {
            $message = "Please fill all fields!";
            $message_type = "error";
        } else {
            // Check if user exists
            $find_user = "SELECT * FROM users WHERE email = '$email'";
            $result = mysqli_query($connection, $find_user);
            
            if (mysqli_num_rows($result) == 1) {
                $user = mysqli_fetch_assoc($result);
                
                // Check password
                if (password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    $message = "Login successful! Welcome " . $user['name'];
                    $message_type = "success";
                    
                    header("Location: /Php/Dashboard.php");
                } else {
                    $message = "Wrong password!";
                    $message_type = "error";
                }
            } else {
                $message = "User not found!";
                $message_type = "error";
            }
        }
    }
}

// Closes database connection
mysqli_close($connection);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login/Signup Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #5d9cec;
            margin: 0;
            padding: 20px;
        }

        .container {
            background-color: white;
            width: 400px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            border-bottom: 2px solid transparent;
        }

        .tab.active {
            color: #5d9cec;
            border-bottom-color: #5d9cec;
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-row {
            display: flex;
            gap: 10px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .checkbox-group input {
            width: auto;
            margin-right: 8px;
        }

        .checkbox-group label {
            margin-bottom: 0;
            font-size: 12px;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #5d9cec;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: #4a8bc2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Heading</h2>
        <p style="text-align: center; color: #666; margin-bottom: 30px;">Transport App</p>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="showForm('signup')">Signup</button>
            <button class="tab" onclick="showForm('login')">Login</button>
        </div>

        <!-- Signup Form -->
        <div id="signup" class="form-section active">
            <form method="post">
                <input type="hidden" name="action" value="signup">
                
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>ID Number</label>
                        <input type="text" name="id_number" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" required>
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="terms" required>
                    <label>I agree to the Terms and Conditions</label>
                </div>

                <button type="submit" class="submit-btn">Sign Up</button>
            </form>
        </div>

        <!-- Login Form -->
        <div id="login" class="form-section">
            <form method="post">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="remember">
                    <label>Remember me</label>
                </div>

                <button type="submit" class="submit-btn">Login</button>
            </form>
        </div>
    </div>

    <script>
        function showForm(formName) {
            // Hides all forms
            var forms = document.querySelectorAll('.form-section');
            for (var i = 0; i < forms.length; i++) {
                forms[i].classList.remove('active');
            }

            // Remove active class from all tabs
            var tabs = document.querySelectorAll('.tab');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }

            // Show selected form
            document.getElementById(formName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
    </script>
</body>
</html>