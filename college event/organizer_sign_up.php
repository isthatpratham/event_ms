<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'eveflow_db';

// Initialize variables
$message = '';
$messageClass = '';
$email = $organizer_name = $phone = $department = $semester = $role = '';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
if ($conn->query($sql) === TRUE) {
    // Select the database
    $conn->select_db($db_name);
    
    // Create organizers table with custom ID format
    $sql = "CREATE TABLE IF NOT EXISTS organizers (
        organizer_id VARCHAR(20) PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        organizer_name VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        department VARCHAR(255) NOT NULL,
        semester VARCHAR(255) NOT NULL,
        role VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($sql)) {
        $message = "Error creating table: " . $conn->error;
        $messageClass = "error-message";
    }
    
    // Create subjects table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS subjects (
        sid INT AUTO_INCREMENT PRIMARY KEY,
        department VARCHAR(255) NOT NULL,
        course VARCHAR(255) NOT NULL
    )";
    $conn->query($sql);
} else {
    $message = "Error creating database: " . $conn->error;
    $messageClass = "error-message";
}

// Get all departments from subjects table
$departments = [];
$dept_query = $conn->query("SELECT DISTINCT department FROM subjects");
if ($dept_query) {
    while ($row = $dept_query->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// Function to generate organizer ID (org-1, org-2, etc.)
function generateOrganizerId($conn) {
    // Get the highest existing ID
    $result = $conn->query("SELECT organizer_id FROM organizers ORDER BY organizer_id DESC LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_id = $row['organizer_id'];
        // Extract the numeric part
        $last_num = (int) str_replace('org-', '', $last_id);
        $next_num = $last_num + 1;
    } else {
        // First organizer
        $next_num = 1;
    }
    
    return 'org-' . $next_num;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $organizer_name = htmlspecialchars($_POST['organizer_name']);
    $phone = htmlspecialchars($_POST['phone']);
    $department = htmlspecialchars($_POST['department']);
    $semester = htmlspecialchars($_POST['semester']);
    $role = htmlspecialchars($_POST['role']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate email (lowercase only)
    if (preg_match('/[A-Z]/', $email)) {
        $message = "Email must contain only lowercase letters";
        $messageClass = "error-message";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format";
        $messageClass = "error-message";
    } 
    // Validate name (letters only)
    elseif (!preg_match('/^[A-Za-z\s]+$/', $organizer_name)) {
        $message = "Name can only contain letters and spaces";
        $messageClass = "error-message";
    }
    // Validate role (letters only)
    elseif (!preg_match('/^[A-Za-z\s]+$/', $role)) {
        $message = "Role can only contain letters and spaces";
        $messageClass = "error-message";
    }
    // Validate phone number (exactly 10 digits)
    elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $message = "Phone number must be exactly 10 digits";
        $messageClass = "error-message";
    }
    // Check if passwords match
    elseif ($password !== $confirm_password) {
        $message = "Passwords do not match";
        $messageClass = "error-message";
    } 
    // Validate password strength
    else {
        $password_errors = [];
        
        // Check password length
        if (strlen($password) < 8) {
            $password_errors[] = "Password must be at least 8 characters long";
        }
        
        // Check for both letters and numbers
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $password_errors[] = "Password must contain both letters and numbers";
        }
        
        // Check for at least one capital letter
        if (!preg_match('/[A-Z]/', $password)) {
            $password_errors[] = "Password must contain at least one capital letter";
        }
        
        // Check for at least one special character
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $password_errors[] = "Password must contain at least one special character";
        }
        
        if (!empty($password_errors)) {
            $message = implode("<br>", $password_errors);
            $messageClass = "error-message";
        }
    }
    
    // Check department is not empty
    if (empty($department) && empty($message)) {
        $message = "Department is required";
        $messageClass = "error-message";
    }
    // Check semester is not empty
    elseif (empty($semester) && empty($message)) {
        $message = "Semester is required";
        $messageClass = "error-message";
    } 
    
    // Proceed with registration if no errors
    if (empty($message)) {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT organizer_id FROM organizers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $message = "Email already registered";
            $messageClass = "error-message";
        } else {
            // Generate the custom organizer ID
            $organizer_id = generateOrganizerId($conn);
            
            // Store password without hashing
            $plain_password = $password;
            
            // Insert new organizer with custom ID
            $stmt = $conn->prepare("INSERT INTO organizers (organizer_id, email, organizer_name, phone, department, semester, role, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $organizer_id, $email, $organizer_name, $phone, $department, $semester, $role, $plain_password);
            
            if ($stmt->execute()) {
                $message = "Registration successful! <a href='organizerlogin.php'>Click here to login</a>";
                $messageClass = "success-message";
                
                // Clear form fields
                $email = $organizer_name = $phone = $department = $semester = $role = '';
            } else {
                $message = "Error: " . $stmt->error;
                $messageClass = "error-message";
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Organizer Registration</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="//fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css" type="text/css" media="all" />
    <style>
        body {
            background: #656c74
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
        .alert-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            z-index: 1000;
            animation: slideIn 0.5s;
        }
        @keyframes slideIn {
            from {right: -300px; opacity: 0;}
            to {right: 20px; opacity: 1;}
        }
        @keyframes fadeOut {
            from {opacity: 1;}
            to {opacity: 0;}
        }
        .error-message {
            background-color: #ff3333;
        }
        .success-message {
            background-color: #008000;
        }
        .success-message a {
            color: white;
            text-decoration: underline;
        }
        .info-message {
            background-color: #0066cc;
        }
        .logo em {
            font-style: normal;
            color: #2a2a2a;
            font-weight: 900;
        }
        .invalid-input {
            border: 2px solid #ff3333;
        }
    </style>
</head>
<body>
    <?php if(!empty($message)): ?>
    <div class="alert-message <?php echo $messageClass; ?>">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <div class="signinform">
        <header id="site-header" class="fixed-top">
            <div class="container">
                <nav class="navbar navbar-expand-lg stroke px-0 pt-lg-0">
                    <h1>
                        <a class="navbar-brand" href="registration.php">
                            EveFlow
                        </a>
                    </h1>
                </nav>
            </div>
        </header>
        <div class="container">
            <div class="w3l-form-info">
                <div class="w3_info">
                    <h2>Create Organizer Account</h2>
                    <form action="organizer_sign_up.php" method="post" id="registrationForm">
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
                            <span><i class="fas fa-user" aria-hidden="true"></i></span>
                            <input type="text" 
                                   name="organizer_name" 
                                   placeholder="Name" 
                                   required 
                                   value="<?php echo htmlspecialchars($organizer_name); ?>"
                                   pattern="[A-Za-z\s]+" 
                                   title="Only letters and spaces are allowed"
                                   onkeypress="return onlyLetters(event)">
                        </div>
                        <div class="input-group">
                            <span><i class="fas fa-phone" aria-hidden="true"></i></span>
                            <input type="tel" 
                                   name="phone" 
                                   id="phone" 
                                   placeholder="Phone Number" 
                                   required 
                                   maxlength="10"
                                   value="<?php echo htmlspecialchars($phone); ?>"
                                   onkeypress="return onlyNumbers(event)"
                                   oninput="restrictPhoneLength(this)">
                        </div>
                        <div class="input-group">
                            <span><i class="fas fa-graduation-cap" aria-hidden="true"></i></span>
                            <select name="department" required>
                                <option value="" disabled selected>Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($department === $dept) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <span><i class="fas fa-id-card" aria-hidden="true"></i></span>
                            <select name="semester" required>
                                <option value="" disabled selected>Select Semester</option>
                                <option value="1" <?php echo ($semester === '1') ? 'selected' : ''; ?>>Semester 1</option>
                                <option value="2" <?php echo ($semester === '2') ? 'selected' : ''; ?>>Semester 2</option>
                                <option value="3" <?php echo ($semester === '3') ? 'selected' : ''; ?>>Semester 3</option>
                                <option value="4" <?php echo ($semester === '4') ? 'selected' : ''; ?>>Semester 4</option>
                                <option value="5" <?php echo ($semester === '5') ? 'selected' : ''; ?>>Semester 5</option>
                                <option value="6" <?php echo ($semester === '6') ? 'selected' : ''; ?>>Semester 6</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <span><i class="fas fa-user-tag" aria-hidden="true"></i></span>
                            <input type="text" 
                                   name="role" 
                                   placeholder="Role" 
                                   required 
                                   value="<?php echo htmlspecialchars($role); ?>"
                                   pattern="[A-Za-z\s]+"
                                   title="Only letters and spaces are allowed"
                                   onkeypress="return onlyLetters(event)">
                        </div>
                        <div class="input-group">
                            <span><i class="fas fa-key" aria-hidden="true"></i></span>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   placeholder="Password" 
                                   required
                                   pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}$"
                                   onfocus="showPasswordAlert()">
                        </div>
                        <div class="input-group">
                            <span><i class="fas fa-key" aria-hidden="true"></i></span>
                            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                        </div>
                        <button class="btn btn-primary btn-block" type="submit">Create Account</button>
                    </form>
                    
                    <p class="account">Already have an account? <a href="organizerlogin.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
    <!-- fontawesome v5-->
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

        // Phone number handling
        const phoneInput = document.getElementById('phone');
        
        // Function to allow only numbers
        function onlyNumbers(event) {
            const charCode = event.which ? event.which : event.keyCode;
            if (charCode < 48 || charCode > 57) { // 48-57 are the char codes for 0-9
                return false;
            }
            return true;
        }

        // Restrict phone number length and validate on input
        function restrictPhoneLength(input) {
            // Remove any non-numeric characters
            input.value = input.value.replace(/[^0-9]/g, '');
            
            // Enforce max length of 10
            if (input.value.length > 10) {
                input.value = input.value.slice(0, 10);
            }
            
            // Check if length is exactly 10 to remove any existing alerts
            if (input.value.length === 10) {
                const existingPhoneAlerts = document.querySelectorAll('.alert-message.phone-error');
                existingPhoneAlerts.forEach(alert => alert.remove());
                input.classList.remove('invalid-input');
            }
        }

        // Validate phone number on blur
        phoneInput.addEventListener('blur', function(e) {
            const phone = e.target.value;
            if (phone.length > 0 && phone.length < 10) {
                // Show alert if less than 10 digits
                showPhoneErrorAlert("Phone number must be exactly 10 digits");
                e.target.classList.add('invalid-input');
            } else if (phone.length === 0) {
                // Remove any existing alerts if field is empty
                const existingPhoneAlerts = document.querySelectorAll('.alert-message.phone-error');
                existingPhoneAlerts.forEach(alert => alert.remove());
            }
        });

        // Prevent pasting non-numeric characters
        phoneInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const numbersOnly = pastedText.replace(/[^0-9]/g, '');
            const truncatedText = numbersOnly.slice(0, 10 - this.value.length);
            document.execCommand('insertText', false, truncatedText);
        });

        function showPhoneErrorAlert(message) {
            // Remove any existing phone error alerts
            const existingPhoneAlerts = document.querySelectorAll('.alert-message.phone-error');
            existingPhoneAlerts.forEach(alert => alert.remove());
            
            // Create new alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert-message error-message phone-error';
            alertDiv.innerHTML = message;
            document.body.appendChild(alertDiv);
        }

        function showPasswordAlert() {
            showAlert("Password requirements:<br>- At least 8 characters<br>- Both letters and numbers<br>- At least one capital letter<br>- At least one special character");
        }
        
        function showAlert(message) {
            // Remove any existing info alerts
            const existingAlerts = document.querySelectorAll('.alert-message.info-message');
            existingAlerts.forEach(alert => alert.remove());
            
            // Create new alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert-message info-message';
            alertDiv.innerHTML = message;
            document.body.appendChild(alertDiv);
            
            // Remove after 10 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 10000);
        }
        
        // Function to allow only letters and spaces
        function onlyLetters(event) {
            const charCode = event.which ? event.which : event.keyCode;
            if (charCode === 32) return true; // Allow space
            if (charCode >= 65 && charCode <= 90) return true; // A-Z
            if (charCode >= 97 && charCode <= 122) return true; // a-z
            return false;
        }
        
        // Prevent pasting non-letter characters
        document.addEventListener('DOMContentLoaded', function() {
            const nameField = document.querySelector('input[name="organizer_name"]');
            const roleField = document.querySelector('input[name="role"]');
            
            [nameField, roleField].forEach(field => {
                field.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                    const lettersOnly = pastedText.replace(/[^A-Za-z\s]/g, '');
                    document.execCommand('insertText', false, lettersOnly);
                });
                
                field.addEventListener('input', function() {
                    this.value = this.value.replace(/[^A-Za-z\s]/g, '');
                });
            });
        });
    </script>
</body>
</html>