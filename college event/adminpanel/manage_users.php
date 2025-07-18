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

$host = "localhost";
$user = "root";
$password = "";
$database = "eveflow_db";

// Create connection
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add User (kept for compatibility, not triggered)
if (isset($_POST['add_user'])) {
    $name = htmlspecialchars(trim($_POST['student_name']));
    $email = strtolower(trim($_POST['email']));
    $course = htmlspecialchars(trim($_POST['course']));
    $dept = htmlspecialchars(trim($_POST['dept']));
    $semester = (int)trim($_POST['semester']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $created_at = date("Y-m-d H:i:s");

    $sql = "INSERT INTO users (student_name, email, course, dept, semester, password, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Add user prepare failed: " . $conn->error);
        echo "<script>alert('Error preparing insert: " . addslashes($conn->error) . "');</script>";
    } else {
        $stmt->bind_param("ssssiss", $name, $email, $course, $dept, $semester, $password, $created_at);
        if ($stmt->execute()) {
            echo "<script>alert('User added successfully.'); window.location.href='manage_users.php';</script>";
        } else {
            error_log("Add user failed: " . $stmt->error);
            echo "<script>alert('Error adding user: " . addslashes($stmt->error) . "');</script>";
        }
        $stmt->close();
    }
}

// Delete User
if (isset($_GET['delete'])) {
    $id = htmlspecialchars(trim($_GET['delete']));
    $sql = "DELETE FROM users WHERE st_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Delete prepare failed: " . $conn->error);
        echo "<script>alert('Error preparing delete: " . addslashes($conn->error) . "');</script>";
    } else {
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            echo "<script>alert('User deleted successfully.'); window.location.href='manage_users.php';</script>";
        } else {
            error_log("Delete failed: " . $stmt->error);
            echo "<script>alert('Error deleting user: " . addslashes($stmt->error) . "');</script>";
        }
        $stmt->close();
    }
}

// Update User
if (isset($_POST['edit_user'])) {
    $id = htmlspecialchars(trim($_POST['st_id'] ?? ''));
    $name = htmlspecialchars(trim($_POST['student_name'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? ''));
    $course = htmlspecialchars(trim($_POST['course'] ?? ''));
    $dept = htmlspecialchars(trim($_POST['dept'] ?? ''));
    $semester = (int)trim($_POST['semester'] ?? '');

    // Log form data for debugging
    $form_data_log = "Edit User Attempt: ID=$id, Name=$name, Email=$email, Course=$course, Dept=$dept, Semester=$semester";
    error_log($form_data_log);
    echo "<script>console.log('$form_data_log');</script>";

    // Validation
    if (empty($id) || empty($name) || empty($email) || empty($course) || empty($dept) || $semester === '') {
        error_log("Edit User Failed: One or more fields are empty");
        echo "<script>alert('Error: All fields are required.'); console.log('Error: Missing fields');</script>";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        error_log("Edit User Failed: Name contains invalid characters");
        echo "<script>alert('Error: Name must contain only letters and spaces.'); console.log('Error: Invalid name');</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("Edit User Failed: Invalid email format");
        echo "<script>alert('Error: Invalid email format.'); console.log('Error: Invalid email');</script>";
    } elseif (!in_array($semester, range(1, 6))) {
        error_log("Edit User Failed: Invalid semester");
        echo "<script>alert('Error: Semester must be between 1 and 6.'); console.log('Error: Invalid semester');</script>";
    } else {
        // Check if the st_id exists
        $check = $conn->prepare("SELECT st_id, email FROM users WHERE st_id = ?");
        $check->bind_param("s", $id);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows === 0) {
            error_log("Edit User Failed: st_id $id does not exist");
            echo "<script>alert('Error: User with ID $id does not exist.'); console.log('Error: st_id $id not found');</script>";
        } else {
            // Check for email uniqueness (if email has changed)
            $existing = $result->fetch_assoc();
            if ($email !== $existing['email']) {
                $email_check = $conn->prepare("SELECT st_id FROM users WHERE email = ? AND st_id != ?");
                $email_check->bind_param("ss", $email, $id);
                $email_check->execute();
                $email_result = $email_check->get_result();
                if ($email_result->num_rows > 0) {
                    error_log("Edit User Failed: Email $email already exists");
                    echo "<script>alert('Error: Email $email is already in use by another user.'); console.log('Error: Email already exists');</script>";
                    $email_check->close();
                    $check->close();
                    return;
                }
                $email_check->close();
            }

            // Proceed with the update
            $sql = "UPDATE users SET student_name = ?, email = ?, course = ?, dept = ?, semester = ? WHERE st_id = ?";
            error_log("Executing SQL: $sql with values Name=$name, Email=$email, Course=$course, Dept=$dept, Semester=$semester, ID=$id");
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                error_log("Update prepare failed: " . $conn->error);
                echo "<script>alert('Error preparing update: " . addslashes($conn->error) . "'); console.log('Prepare error: " . addslashes($conn->error) . "');</script>";
            } else {
                $stmt->bind_param("ssssis", $name, $email, $course, $dept, $semester, $id);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        echo "<script>alert('User updated successfully.'); window.location.href='manage_users.php';</script>";
                    } else {
                        error_log("Update executed but no rows affected: ID=$id, possibly no changes made");
                        echo "<script>alert('No changes were made to the user. The submitted data might be the same as the existing data.'); console.log('No rows affected for ID=$id');</script>";
                    }
                } else {
                    error_log("Update failed: " . $stmt->error);
                    echo "<script>alert('Error updating user: " . addslashes($stmt->error) . "'); console.log('Update error: " . addslashes($stmt->error) . "');</script>";
                }
                $stmt->close();
            }
        }
        $check->close();
    }
}

// Fetch all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
if (!$users) {
    error_log("User fetch failed: " . $conn->error);
    echo "<script>alert('Error fetching users: " . addslashes($conn->error) . "');</script>";
}

// Fetch distinct departments for filter
$dept_query = $conn->query("SELECT DISTINCT department FROM subjects ORDER BY department");
$dept_options = "";
if ($dept_query && $dept_query->num_rows > 0) {
    while ($dept = $dept_query->fetch_assoc()) {
        $dept_options .= "<li><a class=\"dropdown-item\" href=\"#\" onclick=\"filterUsers('" . htmlspecialchars($dept['department']) . "')\">" . htmlspecialchars($dept['department']) . "</a></li>";
    }
} else {
    $dept_options = "<li><a class=\"dropdown-item\" href=\"#\" onclick=\"filterUsers('All')\">No departments available</a></li>";
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
        <title>Users</title>
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
                            <h1>Manage Users</h1>
                        </div>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="admin.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Manage Users</li>
                        </ol>
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-table me-1"></i>
                                        Manage Users
                                    </div>
                                    <div class="ms-auto">
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="userFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                Filter by Department
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="userFilterDropdown">
                                                <li><a class="dropdown-item" href="#" onclick="filterUsers('All')">All Departments</a></li>
                                                <?= $dept_options ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th>Id</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Course</th>
                                            <th>Department</th>
                                            <th>Semester</th>
                                            <th>Edit</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Id</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Course</th>
                                            <th>Department</th>
                                            <th>Semester</th>
                                            <th>Edit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
<?php if ($users && $users->num_rows > 0): ?>
    <?php while($row = $users->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['st_id']) ?></td>
            <td><?= htmlspecialchars($row['student_name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['course']) ?></td>
            <td><?= htmlspecialchars($row['dept']) ?></td>
            <td><?= htmlspecialchars($row['semester']) ?></td>
            <td>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?= htmlspecialchars($row['st_id']) ?>">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <a href="?delete=<?= htmlspecialchars($row['st_id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </td>
        </tr>

        <!-- Edit Modal for Each User -->
        <div class="modal fade" id="editUserModal<?= htmlspecialchars($row['st_id']) ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?= htmlspecialchars($row['st_id']) ?>" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="manage_users.php" id="editUserForm<?= htmlspecialchars($row['st_id']) ?>">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="st_id" value="<?= htmlspecialchars($row['st_id']) ?>">
                            <div class="mb-3">
                                <label>Name</label>
                                <input type="text" class="form-control" name="student_name" value="<?= htmlspecialchars($row['student_name']) ?>" required pattern="[A-Za-z\s]+" title="Only letters and spaces are allowed" onkeypress="return /^[A-Za-z\s]$/.test(String.fromCharCode(event.keyCode || event.which))">
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($row['email']) ?>" required oninput="this.value = this.value.toLowerCase()" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Please enter a valid email address">
                            </div>
                            <div class="mb-3">
                                <label>Course</label>
                                <select class="form-control" name="course" required>
                                    <option value="" disabled>Select a course</option>
                                    <?php
                                    $course_query = $conn->query("SELECT DISTINCT course FROM subjects ORDER BY course");
                                    if ($course_query && $course_query->num_rows > 0) {
                                        while ($course = $course_query->fetch_assoc()) {
                                            $selected = ($course['course'] === $row['course']) ? 'selected' : '';
                                            echo "<option value=\"" . htmlspecialchars($course['course']) . "\" $selected>" . htmlspecialchars($course['course']) . "</option>";
                                        }
                                    } else {
                                        echo "<option value='' disabled>No courses available</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Department</label>
                                <select class="form-control" name="dept" required>
                                    <option value="" disabled>Select a department</option>
                                    <?php
                                    $dept_query_modal = $conn->query("SELECT DISTINCT department FROM subjects ORDER BY department");
                                    if ($dept_query_modal && $dept_query_modal->num_rows > 0) {
                                        while ($dept = $dept_query_modal->fetch_assoc()) {
                                            $selected = ($dept['department'] === $row['dept']) ? 'selected' : '';
                                            echo "<option value=\"" . htmlspecialchars($dept['department']) . "\" $selected>" . htmlspecialchars($dept['department']) . "</option>";
                                        }
                                    } else {
                                        echo "<option value='' disabled>No departments available</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Semester</label>
                                <select class="form-control" name="semester" required>
                                    <option value="" disabled>Select a semester</option>
                                    <?php
                                    for ($i = 1; $i <= 6; $i++) {
                                        $selected = ($i == $row['semester']) ? 'selected' : '';
                                        echo "<option value=\"$i\" $selected>$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="7" class="text-center">No users found.</td></tr>
<?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <!-- Add User Modal (kept for compatibility, not triggered) -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="manage_users.php">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>Name</label>
                                <input type="text" class="form-control" name="student_name" required pattern="[A-Za-z\s]+" title="Only letters and spaces are allowed" onkeypress="return /^[A-Za-z\s]$/.test(String.fromCharCode(event.keyCode || event.which))">
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email" required oninput="this.value = this.value.toLowerCase()" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Please enter a valid email address">
                            </div>
                            <div class="mb-3">
                                <label>Course</label>
                                <select class="form-control" name="course" required>
                                    <option value="" disabled>Select a course</option>
                                    <?php
                                    $course_query = $conn->query("SELECT DISTINCT course FROM subjects ORDER BY course");
                                    if ($course_query && $course_query->num_rows > 0) {
                                        while ($course = $course_query->fetch_assoc()) {
                                            echo "<option value=\"" . htmlspecialchars($course['course']) . "\">" . htmlspecialchars($course['course']) . "</option>";
                                        }
                                    } else {
                                        echo "<option value='' disabled>No courses available</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Department</label>
                                <select class="form-control" name="dept" required>
                                    <option value="" disabled>Select a department</option>
                                    <?php
                                    $dept_query_modal = $conn->query("SELECT DISTINCT department FROM subjects ORDER BY department");
                                    if ($dept_query_modal && $dept_query_modal->num_rows > 0) {
                                        while ($dept = $dept_query_modal->fetch_assoc()) {
                                            echo "<option value=\"" . htmlspecialchars($dept['department']) . "\">" . htmlspecialchars($dept['department']) . "</option>";
                                        }
                                    } else {
                                        echo "<option value='' disabled>No departments available</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Semester</label>
                                <select class="form-control" name="semester" required>
                                    <option value="" disabled>Select a semester</option>
                                    <?php
                                    for ($i = 1; $i <= 6; $i++) {
                                        echo "<option value=\"$i\">$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="add_user" class="btn btn-success">Add User</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // Initialize the datatable (if table exists)
            let dataTable;
            if (document.getElementById('datatablesSimple')) {
                dataTable = new simpleDatatables.DataTable("#datatablesSimple");
            }

            // Filter users based on department (case-insensitive)
            function filterUsers(dept) {
                console.log('Filtering for department:', dept);
                const rows = document.querySelectorAll('#datatablesSimple tbody tr');
                if (rows.length === 0) {
                    console.log('No rows found in table');
                    return;
                }
                rows.forEach(row => {
                    const deptCell = row.cells[4].textContent.trim().toLowerCase();
                    const filterDept = dept.toLowerCase();
                    console.log('Row department:', deptCell);
                    row.style.display = (filterDept === 'all' || deptCell === filterDept) ? '' : 'none';
                });
                // Reinitialize DataTable to update pagination
                if (dataTable) {
                    dataTable.refresh();
                }
            }

            // Prevent dropdown menu from closing on item click
            document.querySelectorAll('.dropdown-menu a').forEach(item => {
                item.addEventListener('click', event => {
                    event.stopPropagation();
                    event.preventDefault();
                });
            });

            // Client-side validation for name input (redundant with onkeypress, kept for compatibility)
            document.querySelectorAll('input[name="student_name"]').forEach(input => {
                input.addEventListener('input', function(e) {
                    const value = e.target.value;
                    if (!/^[A-Za-z\s]*$/.test(value)) {
                        e.target.value = value.replace(/[^A-Za-z\s]/g, '');
                    }
                });
            });
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
    </body>
</html>
<?php $conn->close(); ?>