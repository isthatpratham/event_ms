<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Registration</title>
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
            animation: slideIn 0.5s, fadeOut 0.5s 9.5s forwards;
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
        .info-message {
            background-color: #0066cc;
        }
        .logo em {
            font-style: normal;
            color: #2a2a2a;
            font-weight: 900;
        }
        .password-requirements {
            display: none;
            font-size: 12px;
            color: #666;
            margin-top: -10px;
            margin-bottom: 15px;
        }
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 3px;
        }
        .requirement .icon {
            margin-right: 5px;
            font-size: 14px;
        }
        .valid {
            color: green;
        }
        .invalid {
            color: red;
        }
        .invalid-input {
            border: 2px solid #ff3333;
        }
    </style>
</head>
<body>
<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $message = "";
    $messageClass = "";

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "eveflow_db";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $table_check = $conn->query("SHOW TABLES LIKE 'users'");
    if ($table_check->num_rows == 0) {
        $create_table_sql = "CREATE TABLE users (
            st_id VARCHAR(20) PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            student_name VARCHAR(255) NOT NULL,
            course VARCHAR(255) NOT NULL,
            dept VARCHAR(255) NOT NULL,
            semester VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($create_table_sql) === TRUE) {
            $message = "Users table created successfully!";
            $messageClass = "success-message";
        } else {
            $message = "Error creating table: " . $conn->error;
            $messageClass = "error-message";
        }
    }

    $subjects_table_check = $conn->query("SHOW TABLES LIKE 'subjects'");
    if ($subjects_table_check->num_rows == 0) {
        $message = "Subjects table does not exist. Please create it first.";
        $messageClass = "error-message";
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];
        $student_name = $_POST['student_name'];
        $course = $_POST['course'];
        $dept = $_POST['dept'];
        $semester = $_POST['semester'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        $password_errors = [];
        if (strlen($password) < 8) {
            $password_errors[] = "Password must be at least 8 characters long.";
        }
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $password_errors[] = "Password must contain both letters and digits.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $password_errors[] = "Password must contain at least one capital letter.";
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $password_errors[] = "Password must contain at least one special character.";
        }

        if (!preg_match('/^[A-Za-z\s]+$/', $student_name)) {
            $message = "Name must contain only letters and spaces.";
            $messageClass = "error-message";
        }
        elseif (preg_match('/[A-Z]/', $email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Email must be a valid address with only lowercase letters.";
            $messageClass = "error-message";
        } else {
            $result = $conn->query("SELECT COUNT(*) as count FROM users");
            $row = $result->fetch_assoc();
            $next_id = "std-" . ($row['count'] + 1);

            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $message = "Email already registered.";
                $messageClass = "error-message";
            } else if (!empty($password_errors)) {
                $message = "Password requirements not met:<br>" . implode("<br>", $password_errors);
                $messageClass = "error-message";
            } else {
                if ($password != $confirm_password) {
                    $message = "Passwords do not match.";
                    $messageClass = "error-message";
                } else {
                    $plain_password = $password;

                    $stmt = $conn->prepare("INSERT INTO users (st_id, email, student_name, course, dept, semester, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $next_id, $email, $student_name, $course, $dept, $semester, $plain_password);                
                    if ($stmt->execute()) {
                        $message = "Account created successfully! Your Student ID is: $next_id";
                        $messageClass = "success-message";
                    } else {
                        $message = "Error: " . $stmt->error;
                        $messageClass = "error-message";
                    }
                }
            }
            $stmt->close();
        }
    }
    $conn->close();
?>

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
                    <h2>Create Account</h2>
                    <form action="registration.php" method="post" id="registrationForm">
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
                            <span><i class="fas fa-user" aria-hidden="true"></i></span>
                            <input type="text" 
                                   name="student_name" 
                                   id="student_name" 
                                   placeholder="Name" 
                                   required 
                                   pattern="[A-Za-z\s]+"
                                   title="Name must contain only letters and spaces">
                        </div>
                        <div class="input-group">
                            <span><i class="fas fa-id-card" aria-hidden="true"></i></span>
                            <select name="semester" required>
                                <option value="" disabled selected>Select Semester</option>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                                <option value="3">Semester 3</option>
                                <option value="4">Semester 4</option>
                                <option value="5">Semester 5</option>
                                <option value="6">Semester 6</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <span><i class="fas fa-graduation-cap" aria-hidden="true"></i></span>
                            <select name="dept" id="dept" required>
                                <option value="" disabled selected>Select Department</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <span><i class="fas fa-graduation-cap" aria-hidden="true"></i></span>
                            <select name="course" id="course" required>
                                <option value="" disabled selected>Select Course</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <span><i class="fas fa-key" aria-hidden="true"></i></span>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   placeholder="Password" 
                                   required 
                                   pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}$"
                                   title="Password must be at least 8 characters with at least one capital letter, one number, and one special character">
                        </div>
                        <div class="password-requirements" id="passwordRequirements">
                            <div class="requirement">
                                <span class="icon" id="length-icon">✗</span>
                                <span>At least 8 characters</span>
                            </div>
                            <div class="requirement">
                                <span class="icon" id="letter-number-icon">✗</span>
                                <span>Contains both letters and numbers</span>
                            </div>
                            <div class="requirement">
                                <span class="icon" id="capital-icon">✗</span>
                                <span>At least one capital letter</span>
                            </div>
                            <div class="requirement">
                                <span class="icon" id="special-icon">✗</span>
                                <span>At least one special character</span>
                            </div>
                        </div>
                        <div class="input-group">
                            <span><i class="fas fa-key" aria-hidden="true"></i></span>
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                        </div>
                        <button class="btn btn-primary btn-block" type="submit">Create</button>
                    </form>
                    <p class="account">Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
    <script src="js/fontawesome.js"></script>
    
    <script>
        // Restrict name input to letters and spaces only
        document.getElementById('student_name').addEventListener('input', function(e) {
            const value = e.target.value;
            const validValue = value.replace(/[^A-Za-z\s]/g, '');
            if (value !== validValue) {
                e.target.value = validValue;
                e.target.classList.add('invalid-input');
                setTimeout(() => e.target.classList.remove('invalid-input'), 500);
            }
        });

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

        function showPasswordRequirements() {
            const existingAlerts = document.querySelectorAll('.password-req-alert');
            existingAlerts.forEach(alert => alert.remove());
            
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert-message info-message password-req-alert';
            alertDiv.innerHTML = `
                <div class="requirement">
                    <span class="icon">✗</span>
                    <span>At least 8 characters</span>
                </div>
                <div class="requirement">
                    <span class="icon">✗</span>
                    <span>Contains both letters and numbers</span>
                </div>
                <div class="requirement">
                    <span class="icon">✗</span>
                    <span>At least one capital letter</span>
                </div>
                <div class="requirement">
                    <span class="icon">✗</span>
                    <span>At least one special character</span>
                </div>
            `;
            document.body.appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 10000);
        }
        
        function fetchDepartmentsAndCourses() {
            fetch('get_subjects.php')
                .then(response => response.json())
                .then(data => {
                    const deptSelect = document.getElementById('dept');
                    const uniqueDepts = [...new Set(data.map(item => item.department))];
                    uniqueDepts.forEach(dept => {
                        deptSelect.innerHTML += `<option value="${dept}">${dept}</option>`;
                    });
                    
                    const courseSelect = document.getElementById('course');
                    const uniqueCourses = [...new Set(data.map(item => item.course))];
                    uniqueCourses.forEach(course => {
                        courseSelect.innerHTML += `<option value="${course}">${course}</option>`;
                    });
                })
                .catch(error => console.error('Error:', error));
        }
        
        function validatePassword(password) {
            const lengthValid = password.length >= 8;
            document.getElementById('length-icon').textContent = lengthValid ? '✓' : '✗';
            document.getElementById('length-icon').className = lengthValid ? 'icon valid' : 'icon invalid';
            
            const hasLetter = /[A-Za-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const letterNumberValid = hasLetter && hasNumber;
            document.getElementById('letter-number-icon').textContent = letterNumberValid ? '✓' : '✗';
            document.getElementById('letter-number-icon').className = letterNumberValid ? 'icon valid' : 'icon invalid';
            
            const capitalValid = /[A-Z]/.test(password);
            document.getElementById('capital-icon').textContent = capitalValid ? '✓' : '✗';
            document.getElementById('capital-icon').className = capitalValid ? 'icon valid' : 'icon invalid';
            
            const specialValid = /[^A-Za-z0-9]/.test(password);
            document.getElementById('special-icon').textContent = specialValid ? '✓' : '✗';
            document.getElementById('special-icon').className = specialValid ? 'icon valid' : 'icon invalid';
            
            return lengthValid && letterNumberValid && capitalValid && specialValid;
        }
        
        document.getElementById('password').addEventListener('focus', showPasswordRequirements);
        document.getElementById('password').addEventListener('input', function() {
            validatePassword(this.value);
        });
        
        document.addEventListener('DOMContentLoaded', fetchDepartmentsAndCourses);
    </script>
</body>
</html>