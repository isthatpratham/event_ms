<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "eveflow_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize error message
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Validate email is lowercase on server-side
    if (preg_match('/[A-Z]/', $email)) {
        $error_message = "Email must contain only lowercase letters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT st_id, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Compare plain text passwords directly
            if ($password === $row['password']) {
                // Set session variables
                $_SESSION['st_id'] = $row['st_id'];
                $_SESSION['email'] = $email;
                $_SESSION['logged_in'] = true;
                $_SESSION['user_type'] = 'student';
                
                // Redirect to student dashboard
                header("Location: student.php");
                exit();
            } else {
                $error_message = "Invalid email or password.";
            }
        } else {
            $error_message = "Invalid email or password.";
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Login</title>
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
            font-family: 'Kumbh Sans', serif;
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
            color: red;
            text-align: center;
            margin-bottom: 15px;
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
                        <a class="navbar-brand" href="index.php">
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
                        <h2>Student Login</h2>
                        <a href="adminlogin.php" class="admin-login" title="Admin Login"><i class="fas fa-user-shield"></i></a>
                    </div>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="error-message"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <form action="" method="post">
                        <div class="input-group">
                            <span><i class="fas fa-user" aria-hidden="true"></i></span>
                            <input type="email" 
                                   name="email" 
                                   id="email" 
                                   placeholder="Email" 
                                   required 
                                   pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                   title="Please enter a valid email address with lowercase letters only">
                        </div>
                        <div class="input-group">
                            <span><i class="fas fa-key" aria-hidden="true"></i></span>
                            <input type="password" name="password" placeholder="Password" required>
                        </div>
                        <div class="form-row bottom">
                            <div class="form-check">
                                <input type="checkbox" id="remember" name="remember" value="remember">
                                <label for="remember"> Remember me?</label>
                            </div>
                            <a href="forgot-password.php" class="forgot">Forgot password?</a>
                        </div>
                        <button class="btn btn-primary btn-block" type="submit">Login</button>
                    </form>
                    <p class="account">Don't have an account? <a href="registration.php">Sign up</a></p>
                    <p class="account">Are you an Organizer? <a href="organizerlogin.php">Organizer Login</a></p>
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