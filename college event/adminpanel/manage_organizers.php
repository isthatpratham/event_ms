<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../adminlogin.php");
    exit();
}

// Database connection
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

// Delete Organizer
if (isset($_GET['delete'])) {
    $id = trim($_GET['delete'] ?? '');
    if (empty($id) || !preg_match("/^org-[0-9]+$/", $id)) {
        error_log("Delete Organizer Failed: Invalid organizer_id format");
        echo "<script>alert('Error: Invalid Organizer ID format for deletion.'); console.log('Error: Invalid organizer_id');</script>";
    } else {
        $sql = "DELETE FROM organizers WHERE organizer_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Delete prepare failed: " . $conn->error);
            echo "<script>alert('Error preparing delete: " . addslashes($conn->error) . "');</script>";
        } else {
            $stmt->bind_param("s", $id);
            if ($stmt->execute()) {
                echo "<script>alert('Organizer deleted successfully.'); window.location.href='manage_organizers.php';</script>";
            } else {
                error_log("Delete failed: " . $stmt->error);
                echo "<script>alert('Error deleting organizer: " . addslashes($stmt->error) . "');</script>";
            }
            $stmt->close();
        }
    }
}

// Update Organizer
if (isset($_POST['edit_organizer'])) {
    // Sanitize inputs by trimming whitespace
    $id = trim($_POST['organizer_id'] ?? '');
    $name = trim($_POST['organizer_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $dept = trim($_POST['dept'] ?? '');
    $semester = $_POST['semester'] ?? '';
    $updated_at = date("Y-m-d H:i:s");

    // Log form data for debugging
    $form_data_log = "Edit Organizer Attempt: ID=$id, Name=$name, Email=$email, Phone=$phone, Role=$role, Dept=$dept, Semester=$semester";
    error_log($form_data_log);
    echo "<script>console.log('$form_data_log');</script>";

    // Validate inputs
    // ID: Must match org-x format
    if (empty($id) || !preg_match("/^org-[0-9]+$/", $id)) {
        error_log("Edit Organizer Failed: Invalid organizer_id format");
        echo "<script>alert('Error: Invalid Organizer ID format. It should be like org-1.'); console.log('Error: Invalid organizer_id');</script>";
    }
    // Name: Only letters and spaces
    elseif (empty($name) || !preg_match("/^[a-zA-Z\s]+$/", $name)) {
        error_log("Edit Organizer Failed: Name contains invalid characters");
        echo "<script>alert('Error: Name should only contain letters and spaces.'); console.log('Error: Invalid name');</script>";
    }
    // Role: Only letters
    elseif (empty($role) || !preg_match("/^[a-zA-Z]+$/", $role)) {
        error_log("Edit Organizer Failed: Role contains invalid characters");
        echo "<script>alert('Error: Role should only contain letters.'); console.log('Error: Invalid role');</script>";
    }
    // Email: Valid format and convert to lowercase
    elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("Edit Organizer Failed: Invalid email format");
        echo "<script>alert('Error: Please enter a valid email address.'); console.log('Error: Invalid email');</script>";
    }
    // Phone: Exactly 10 digits, only numbers
    elseif (empty($phone) || !preg_match("/^[0-9]{10}$/", $phone)) {
        error_log("Edit Organizer Failed: Phone number must be exactly 10 digits");
        echo "<script>alert('Error: Phone number must be exactly 10 digits and contain only numbers.'); console.log('Error: Invalid phone');</script>";
    }
    // Semester: Must be an integer between 1 and 6
    elseif (!filter_var($semester, FILTER_VALIDATE_INT) || $semester < 1 || $semester > 6) {
        error_log("Edit Organizer Failed: Invalid semester value");
        echo "<script>alert('Error: Semester must be between 1 and 6.'); console.log('Error: Invalid semester');</script>";
    }
    // Department: Must not be empty
    elseif (empty($dept)) {
        error_log("Edit Organizer Failed: Department is empty");
        echo "<script>alert('Error: Department is required.'); console.log('Error: Missing department');</script>";
    } else {
        // Convert email to lowercase
        $email = strtolower($email);

        // Check if the organizer_id exists
        $check = $conn->prepare("SELECT organizer_id, email FROM organizers WHERE organizer_id = ?");
        $check->bind_param("s", $id);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows === 0) {
            error_log("Edit Organizer Failed: organizer_id $id does not exist");
            echo "<script>alert('Error: Organizer with ID $id does not exist.'); console.log('Error: organizer_id $id not found');</script>";
        } else {
            // Check for email uniqueness (if email has changed)
            $existing = $result->fetch_assoc();
            if ($email !== $existing['email']) {
                $email_check = $conn->prepare("SELECT organizer_id FROM organizers WHERE email = ? AND organizer_id != ?");
                $email_check->bind_param("ss", $email, $id);
                $email_check->execute();
                $email_result = $email_check->get_result();
                if ($email_result->num_rows > 0) {
                    error_log("Edit Organizer Failed: Email $email already exists");
                    echo "<script>alert('Error: Email $email is already in use by another organizer.'); console.log('Error: Email already exists');</script>";
                    $email_check->close();
                    $check->close();
                    return;
                }
                $email_check->close();
            }

            // Validate department against subjects table
            $check_subject = $conn->prepare("SELECT COUNT(*) as count FROM subjects WHERE department = ?");
            $check_subject->bind_param("s", $dept);
            $check_subject->execute();
            $subject_result = $check_subject->get_result()->fetch_assoc();
            if ($subject_result['count'] === 0) {
                error_log("Validation failed: Department=$dept not found in subjects");
                echo "<script>alert('Invalid department.'); console.log('Validation failed: Dept=$dept');</script>";
            } else {
                // Proceed with the update
                $sql = "UPDATE organizers SET organizer_name = ?, email = ?, phone = ?, role = ?, department = ?, semester = ?, updated_at = ? WHERE organizer_id = ?";
                error_log("Executing SQL: $sql with values Name=$name, Email=$email, Phone=$phone, Role=$role, Dept=$dept, Semester=$semester, UpdatedAt=$updated_at, ID=$id");
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    error_log("Update prepare failed: " . $conn->error);
                    echo "<script>alert('Error preparing update: " . addslashes($conn->error) . "'); console.log('Prepare error: " . addslashes($conn->error) . "');</script>";
                } else {
                    $stmt->bind_param("sssssiss", $name, $email, $phone, $role, $dept, $semester, $updated_at, $id);
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            echo "<script>alert('Organizer updated successfully.'); window.location.href='manage_organizers.php';</script>";
                        } else {
                            error_log("Update executed but no rows affected: ID=$id, possibly no changes made");
                            echo "<script>alert('No changes were made to the organizer. The submitted data might be the same as the existing data.'); console.log('No rows affected for ID=$id');</script>";
                        }
                    } else {
                        error_log("Update failed: " . $stmt->error);
                        echo "<script>alert('Error updating organizer: " . addslashes($stmt->error) . "'); console.log('Update error: " . addslashes($stmt->error) . "');</script>";
                    }
                    $stmt->close();
                }
            }
            $check_subject->close();
        }
        $check->close();
    }
}

// Fetch data from the organizers table
$sql = "SELECT organizer_id, email, organizer_name, phone, department, semester, role, password, created_at, updated_at FROM organizers";
$result = $conn->query($sql);
if (!$result) {
    error_log("Organizer fetch failed: " . $conn->error);
    echo "<script>alert('Error fetching organizers: " . addslashes($conn->error) . "');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Organizers</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
            <a class="navbar-brand ps-3" href="admin.php">EveFlow</a>
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
            <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
                <div class="input-group">
                    <input class="form-control" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
                    <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
                </div>
            </form>
            <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="#!">Profile</a></li>
                        <li><hr class="dropdown-divider" /></li>
                        <li><a class="dropdown-item" href="../adminlogout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <div class="sb-sidenav-menu-heading">Admin</div>
                            <a class="nav-link" href="admin.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt fa-fw"></i></div>
                                Dashboard
                            </a>
                            <a class="nav-link" href="manage_users.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-user-alt fa-fw"></i></div>
                                Manage Users
                            </a>
                            <a class="nav-link" href="manage_events.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt fa-fw"></i></div>
                                Manage Events
                            </a>
                            <a class="nav-link" href="manage_organizers.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-user-tie fa-fw"></i></div>
                                Manage Organizers
                            </a>
                            <a class="nav-link" href="manage_teams.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-users fa-fw"></i></div>
                                View Participations
                            </a>
                            <a class="nav-link" href="manage_venue.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-location-dot fa-fw"></i></div>
                                Manage Venue
                            </a>
                            <a class="nav-link" href="../adminlogout.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-sign-out fa-fw"></i></div>
                                Logout
                            </a>
                        </div>
                    </div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <h1>Manage Organizers</h1>
                        </div>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="admin.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Manage Organizers</li>
                        </ol>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i> Manage Organizers
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th>Id</th>
                                            <th>Organizer Name</th>
                                            <th>Email</th>
                                            <th>Phone No.</th>
                                            <th>Role</th>
                                            <th>Department</th>
                                            <th>Edit</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Id</th>
                                            <th>Organizer Name</th>
                                            <th>Email</th>
                                            <th>Phone No.</th>
                                            <th>Role</th>
                                            <th>Department</th>
                                            <th>Edit</th>
                                        </tr>
                                    </tfoot>
                                    <tbody>
                                        <?php
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<tr>
                                                    <td>" . htmlspecialchars($row['organizer_id']) . "</td>
                                                    <td>" . htmlspecialchars($row['organizer_name']) . "</td>
                                                    <td>" . htmlspecialchars($row['email']) . "</td>
                                                    <td>" . htmlspecialchars($row['phone']) . "</td>
                                                    <td>" . htmlspecialchars($row['role']) . "</td>
                                                    <td>" . htmlspecialchars($row['department']) . "</td>
                                                    <td>
                                                        <button class='btn btn-sm btn-primary' data-bs-toggle='modal' data-bs-target='#editOrganizerModal" . htmlspecialchars($row['organizer_id']) . "'>
                                                            <i class='fas fa-edit'></i> Edit
                                                        </button>
                                                        <a href='?delete=" . htmlspecialchars($row['organizer_id']) . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this organizer?\");'>
                                                            <i class='fas fa-trash'></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>";

                                                // Edit Organizer Modal for each organizer
                                                echo "
                                                <div class='modal fade' id='editOrganizerModal" . htmlspecialchars($row['organizer_id']) . "' tabindex='-1' aria-labelledby='editOrganizerModalLabel" . htmlspecialchars($row['organizer_id']) . "' aria-hidden='true'>
                                                    <div class='modal-dialog'>
                                                        <form method='POST' action='manage_organizers.php'>
                                                            <div class='modal-content'>
                                                                <div class='modal-header'>
                                                                    <h5 class='modal-title' id='editOrganizerModalLabel" . htmlspecialchars($row['organizer_id']) . "'>Edit Organizer</h5>
                                                                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                                                                </div>
                                                                <div class='modal-body'>
                                                                    <input type='hidden' name='organizer_id' value='" . htmlspecialchars($row['organizer_id']) . "'>
                                                                    <div class='mb-3'>
                                                                        <label for='editOrganizerName" . htmlspecialchars($row['organizer_id']) . "' class='form-label'>Name</label>
                                                                        <input type='text' class='form-control' id='editOrganizerName" . htmlspecialchars($row['organizer_id']) . "' name='organizer_name' value='" . htmlspecialchars($row['organizer_name']) . "' required onkeypress='return onlyLettersAndSpaces(event)' pattern='[a-zA-Z\s]+' title='Only letters and spaces are allowed'>
                                                                    </div>
                                                                    <div class='mb-3'>
                                                                        <label for='editOrganizerEmail" . htmlspecialchars($row['organizer_id']) . "' class='form-label'>Email</label>
                                                                        <input type='email' class='form-control' id='editOrganizerEmail" . htmlspecialchars($row['organizer_id']) . "' name='email' value='" . htmlspecialchars($row['email']) . "' required oninput='this.value = this.value.toLowerCase()'>
                                                                    </div>
                                                                    <div class='mb-3'>
                                                                        <label for='editOrganizerPhone" . htmlspecialchars($row['organizer_id']) . "' class='form-label'>Phone Number</label>
                                                                        <input type='text' class='form-control' id='editOrganizerPhone" . htmlspecialchars($row['organizer_id']) . "' name='phone' value='" . htmlspecialchars($row['phone']) . "' required onkeypress='return onlyNumbers(event)' minlength='10' maxlength='10' pattern='[0-9]{10}' title='Phone number must be exactly 10 digits'>
                                                                    </div>
                                                                    <div class='mb-3'>
                                                                        <label for='editOrganizerRole" . htmlspecialchars($row['organizer_id']) . "' class='form-label'>Role</label>
                                                                        <input type='text' class='form-control' id='editOrganizerRole" . htmlspecialchars($row['organizer_id']) . "' name='role' value='" . htmlspecialchars($row['role']) . "' required onkeypress='return onlyLetters(event)' pattern='[a-zA-Z]+' title='Only letters are allowed'>
                                                                    </div>
                                                                    <div class='mb-3'>
                                                                        <label for='editOrganizerDept" . htmlspecialchars($row['organizer_id']) . "' class='form-label'>Department</label>
                                                                        <select class='form-control' id='editOrganizerDept" . htmlspecialchars($row['organizer_id']) . "' name='dept' required>
                                                                            <option value='' disabled>Select a department</option>";

                                                // Fetch distinct departments from subjects table
                                                $dept_query = $conn->query("SELECT DISTINCT department FROM subjects ORDER BY department");
                                                if ($dept_query && $dept_query->num_rows > 0) {
                                                    while ($dept = $dept_query->fetch_assoc()) {
                                                        $selected = ($dept['department'] === $row['department']) ? 'selected' : '';
                                                        echo "<option value='" . htmlspecialchars($dept['department']) . "' $selected>" . htmlspecialchars($dept['department']) . "</option>";
                                                    }
                                                } else {
                                                    echo "<option value='' disabled>No departments available</option>";
                                                }

                                                echo "
                                                                        </select>
                                                                    </div>
                                                                    <div class='mb-3'>
                                                                        <label for='editOrganizerSemester" . htmlspecialchars($row['organizer_id']) . "' class='form-label'>Semester</label>
                                                                        <select class='form-control' id='editOrganizerSemester" . htmlspecialchars($row['organizer_id']) . "' name='semester' required>
                                                                            <option value='' disabled>Select a semester</option>";
                                                for ($i = 1; $i <= 6; $i++) {
                                                    $selected = ($row['semester'] == $i) ? 'selected' : '';
                                                    echo "<option value='$i' $selected>$i</option>";
                                                }
                                                echo "
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class='modal-footer'>
                                                                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                                                                    <button type='submit' name='edit_organizer' class='btn btn-primary'>Save Organizer</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='7'>No organizers found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
                <footer class="py-4 bg-light mt-auto">
                    <!-- Footer content -->
                </footer>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
        <script>
            // Allow only letters and spaces (for Name)
            function onlyLettersAndSpaces(event) {
                const char = String.fromCharCode(event.keyCode);
                return /[a-zA-Z\s]/.test(char) || event.keyCode === 8; // Allow backspace
            }

            // Allow only letters (for Role)
            function onlyLetters(event) {
                const char = String.fromCharCode(event.keyCode);
                return /[a-zA-Z]/.test(char) || event.keyCode === 8; // Allow backspace
            }

            // Allow only numbers (for Phone)
            function onlyNumbers(event) {
                const char = String.fromCharCode(event.keyCode);
                return /[0-9]/.test(char) || event.keyCode === 8; // Allow backspace
            }
        </script>
    </body>
</html>
<?php $conn->close(); ?>