<?php
session_start();

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'eveflow_db';

// Initialize variables
$message = '';
$email = '';
$remember = false;

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate email (lowercase only)
    if (preg_match('/[A-Z]/', $email)) {
        $message = "Email must contain only lowercase letters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format";
    } else {
        // Create connection
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT organizer_id, organizer_name, password FROM organizers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $organizer = $result->fetch_assoc();
            
            // Verify password (direct comparison since passwords are stored in plain text)
            if ($password === $organizer['password']) {
                // Set all required session variables
                $_SESSION['organizer_loggedin'] = true;
                $_SESSION['organizer_id'] = $organizer['organizer_id'];
                $_SESSION['organizer_name'] = $organizer['organizer_name'];
                $_SESSION['organizer_email'] = $email;
                
                // Remember me functionality
                if ($remember) {
                    $cookie_value = base64_encode($organizer['organizer_id'] . ':' . $password); // Store plain password in cookie
                    setcookie('remember_organizer', $cookie_value, time() + (86400 * 30), "/"); // 30 days
                }
                
                // Redirect to organizer panel
                header("Location: organizerpanel/organizer.php");
                exit();
            } else {
                $message = "Invalid email or password";
            }
        } else {
            $message = "Invalid email or password";
        }
        
        $stmt->close();
        $conn->close();
    }
}

// Check for remember me cookie
if (empty($_SESSION['organizer_loggedin']) && isset($_COOKIE['remember_organizer'])) {
    $cookie_data = base64_decode($_COOKIE['remember_organizer']);
    list($organizer_id, $password) = explode(':', $cookie_data);
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $stmt = $conn->prepare("SELECT organizer_id, organizer_name, email, password FROM organizers WHERE organizer_id = ? AND password = ?");
    $stmt->bind_param("ss", $organizer_id, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $organizer = $result->fetch_assoc();
        
        // Set all required session variables
        $_SESSION['organizer_loggedin'] = true;
        $_SESSION['organizer_id'] = $organizer['organizer_id'];
        $_SESSION['organizer_name'] = $organizer['organizer_name'];
        $_SESSION['organizer_email'] = $organizer['email'];
        
        // Refresh cookie
        $cookie_value = base64_encode($organizer['organizer_id'] . ':' . $organizer['password']);
        setcookie('remember_organizer', $cookie_value, time() + (86400 * 30), "/");
        
        header("Location: organizerpanel/organizer.php");
        exit();
    }
    
    // Invalid cookie
    setcookie('remember_organizer', '', time() - 3600, "/");
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Organizer Login</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="//fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css" type="text/css" media="all" />
    <style>
        body {
            background: #656c74;
        }
        .navbar-brand {
            font-family: 'Kumbh Sans', sans-serif;
            font-size: 32px;
            font-weight: bold;
            color: white !important;
        }
        header#site-header.fixed-top {
            padding-top: 100px;
        }
        header a {
            text-decoration: none;
        }
        .login-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-bottom: 20px; 
        }
        .login-header h2 {
            margin: 0; 
        }
        .admin-login {
            font-size: 20px;
            color: #3b3663;
            text-decoration: none;
        }
        .error-message {
            color: #ff3333;
            margin-bottom: 15px;
            text-align: center;
            padding: 10px;
            background-color: rgba(255, 0, 0, 0.1);
            border-radius: 5px;
        }
        .invalid-input {
            border: 2px solid #ff3333;
        }
    </style>
</head>
<body>
    <div class="signinform">
        <header id="site-header" class="fixed-top">
            <div class="container">
                <nav class="navbar navbar-expand-lg stroke px-0 pt-lg-0">
                    <h1>
                        <a class="navbar-brand" href="login.php">
                            EveFlow
                        </a>
                    </h1>
                </nav>
            </div>
        </header>
        <div class="container">
            <div class="w3l-form-info">
                <div class="w3_info">
                    <div class="login-header">
                        <h2>Organizer Login</h2>
                        <a href="adminlogin.php" class="admin-login" title="Admin Login"><i class="fas fa-user-shield"></i></a>
                    </div>
                    <?php if (!empty($message)): ?>
                        <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <form action="organizerlogin.php" method="post">
                        <div class="input-group">
                            <span><i class="fas fa-user" aria-hidden="true"></i></span>
                            <input type="email" 
                                   name="email" 
                                   id="email" 
                                   placeholder="Email" 
                                   required 
                                   pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                   title="Please enter a valid email address with lowercase letters only" 
                                   value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                        <div class="input-group">
                            <span><i class="fas fa-key" aria-hidden="true"></i></span>
                            <input type="password" name="password" placeholder="Password" required>
                        </div>
                        <div class="form-row bottom">
                            <div class="form-check">
                                <input type="checkbox" id="remember" name="remember" <?php echo $remember ? 'checked' : ''; ?>>
                                <label for="remember"> Remember me?</label>
                            </div>
                            <a href="forgot-password.php" class="forgot">Forgot password?</a>
                        </div>
                        <button class="btn btn-primary btn-block" type="submit">Login</button>
                    </form>
                    <p class="account">Don't have an account? <a href="organizer_sign_up.php">Organizer Sign Up</a></p>
                </div>
            </div>
        </div>
    </div>
    <script src="js/fontawesome.js"></script>
    <script>
        // Email handling: Convert to lowercase on input, validate on blur
        const emailInput = document.getElementById('email');
        
        emailInput.addEventListener('input', function(e) {
            // Convert to lowercase as user types
            e.target.value = e.target.value.toLowerCase();
        });

        emailInput.addEventListener('blur', function(e) {
            const email = e.target.value;
            const emailRegex = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/;
            if (!emailRegex.test(email) && email.length > 0) {
                e.target.classList.add('invalid-input');
            } else {
                e.target.classList.remove('invalid-input');
            }
        });
    </script>
</body>
</html>