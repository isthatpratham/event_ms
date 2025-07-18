<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "eveflow_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$email = $password = "";
$error = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = trim($_POST['password']); // Now stored in plaintext
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Prepare SQL statement
        $stmt = $conn->prepare("SELECT adminid, email, password FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            
            // Direct comparison (no hashing)
            if ($password === $row['password']) {
                // Password is correct
                $_SESSION['admin_id'] = $row['adminid'];
                $_SESSION['admin_email'] = $row['email'];
                $_SESSION['admin_logged_in'] = true;
                
                // Update last login
                $update = $conn->prepare("UPDATE admin SET last_login = NOW() WHERE adminid = ?");
                $update->bind_param("i", $row['adminid']);
                $update->execute();
                $update->close();
                
                header("Location: adminpanel/admin.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Login</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="//fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css" type="text/css" media="all" />
    <style>
        body {
            background: #656c74;
        }
        header#site-header.fixed-top {
            padding-top: 100px;
        }
        header a {
            text-decoration: none;
        }
        .navbar-brand {
            font-size: 32px;
            font-weight: bold;
            color: white !important;
        }
        .error-message {
            color: #ff6b6b;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="signinform">
        <header id="site-header" class="fixed-top">
            <div class="container">
                <nav class="navbar navbar-expand-lg stroke px-0 pt-lg-0">
                    <h1>
                        <a class="navbar-brand" href="adminlogin.php">
                            EveFlow
                        </a>
                    </h1>
                </nav>
            </div>
        </header>

        <div class="container">
            <div class="w3l-form-info">
                <div class="w3_info">
                    <h2>Administrator Login</h2>
                    <p>Secure access for administrative personnel only</p>
                    
                    <?php if (!empty($error)): ?>
                        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form action="adminlogin.php" method="post">
                        <div class="input-group">
                            <span><i class="fas fa-user" aria-hidden="true"></i></span>
                            <input type="email" name="email" placeholder="Admin Email" required value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                        <div class="input-group">
                            <span><i class="fas fa-key" aria-hidden="true"></i></span>
                            <input type="password" name="password" placeholder="Admin Password" required>
                        </div>
                        <div class="form-row bottom">
                            <div class="form-check">
                                <input type="checkbox" id="remember" name="remember" value="remember">
                                <label for="remember"> Remember me?</label>
                            </div>
                            <a href="admin-password-reset.php" class="forgot">Forgot password?</a>
                        </div>
                        <button class="btn btn-primary btn-block" type="submit">Admin Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="js/fontawesome.js"></script>
</body>
</html>