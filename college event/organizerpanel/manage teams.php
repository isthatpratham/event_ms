<?php
session_start();

// Check if organizer is logged in
if (!isset($_SESSION['organizer_loggedin']) || $_SESSION['organizer_loggedin'] !== true) {
    header("Location: ../organizerlogin.php");
    exit();
}

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'eveflow_db';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get organizer details for display
$organizerDetails = [];
try {
    $organizerQuery = "SELECT organizer_id, organizer_name, email, phone, department, semester, role FROM organizers WHERE organizer_id = ?";
    $stmt = $conn->prepare($organizerQuery);
    $stmt->bind_param("s", $_SESSION['organizer_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $organizerDetails = $result->fetch_assoc();
    $stmt->close();
} catch(Exception $e) {
    // Handle error if needed
}

// Extract first name from organizer name
$organizerName = $organizerDetails['organizer_name'] ?? 'Organizer';
$firstName = explode(' ', $organizerName)[0];

// Handle result update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_result'])) {
        $pt_id = $_POST['pt_id'];
        $result = $_POST['result'];
        $event_name = '';
        
        // Get the event name for this participation
        $stmt = $conn->prepare("SELECT event_name FROM participations WHERE pt_id = ?");
        $stmt->bind_param("i", $pt_id);
        $stmt->execute();
        $stmt->bind_result($event_name);
        $stmt->fetch();
        $stmt->close();
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update the selected participant's result
            $stmt = $conn->prepare("UPDATE participations SET result = ? WHERE pt_id = ?");
            $stmt->bind_param("si", $result, $pt_id);
            $stmt->execute();
            $stmt->close();
            
            // Check if all three positions are filled for this event
            if (in_array($result, ['winner', '1st runner up', '2nd runner up'])) {
                // Count current positions filled
                $count_stmt = $conn->prepare("SELECT 
                    SUM(CASE WHEN result = 'winner' THEN 1 ELSE 0 END) as winner_count,
                    SUM(CASE WHEN result = '1st runner up' THEN 1 ELSE 0 END) as first_runner_count,
                    SUM(CASE WHEN result = '2nd runner up' THEN 1 ELSE 0 END) as second_runner_count
                    FROM participations WHERE event_name = ?");
                $count_stmt->bind_param("s", $event_name);
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                $counts = $count_result->fetch_assoc();
                $count_stmt->close();
                
                // Check if all positions are filled (including the current update)
                $has_winner = ($result === 'winner' || $counts['winner_count'] > 0);
                $has_first = ($result === '1st runner up' || $counts['first_runner_count'] > 0);
                $has_second = ($result === '2nd runner up' || $counts['second_runner_count'] > 0);
                
                if ($has_winner && $has_first && $has_second) {
                    // Only update to 'participated' when all positions are filled
                    $update_stmt = $conn->prepare("UPDATE participations 
                                                SET result = 'participated' 
                                                WHERE event_name = ? 
                                                AND pt_id != ? 
                                                AND result = 'pending'");
                    $update_stmt->bind_param("si", $event_name, $pt_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $_SESSION['upload_message'] = "Result updated successfully!";
            $_SESSION['upload_status'] = "success";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $_SESSION['upload_message'] = "Error updating results: " . $e->getMessage();
            $_SESSION['upload_status'] = "danger";
        }
        
        header("Location: manage teams.php");
        exit();
    }
    // Handle PDF upload (unchanged)
    elseif (isset($_FILES['certificate'])) {
        $pt_id = $_POST['upload_pt_id'];
        $file = $_FILES['certificate'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            if ($file['size'] > 5 * 1024 * 1024) {
                $_SESSION['upload_message'] = "File size exceeds 5MB limit";
                $_SESSION['upload_status'] = "danger";
                header("Location: manage teams.php");
                exit();
            }
            
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $file_type = $finfo->file($file['tmp_name']);
            
            if ($file_type == 'application/pdf') {
                $upload_dir = 'uploads/certificates/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $filename = uniqid('cert_') . '.pdf';
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $stmt = $conn->prepare("UPDATE participations SET certificate_path = ? WHERE pt_id = ?");
                    $stmt->bind_param("si", $filepath, $pt_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['upload_message'] = "Certificate uploaded successfully!";
                        $_SESSION['upload_status'] = "success";
                    } else {
                        $_SESSION['upload_message'] = "Error saving certificate: " . $stmt->error;
                        $_SESSION['upload_status'] = "danger";
                        unlink($filepath);
                    }
                    $stmt->close();
                } else {
                    $_SESSION['upload_message'] = "Error moving uploaded file";
                    $_SESSION['upload_status'] = "danger";
                }
            } else {
                $_SESSION['upload_message'] = "Only PDF files are allowed!";
                $_SESSION['upload_status'] = "danger";
            }
        } else {
            $_SESSION['upload_message'] = "Error uploading file: " . $file['error'];
            $_SESSION['upload_status'] = "danger";
        }
        
        header("Location: manage teams.php");
        exit();
    }
}

// Handle participant deletion (unchanged)
if (isset($_GET['delete_id'])) {
    $pt_id = $_GET['delete_id'];
    
    $stmt = $conn->prepare("SELECT certificate_path FROM participations WHERE pt_id = ?");
    $stmt->bind_param("i", $pt_id);
    $stmt->execute();
    $stmt->bind_result($certificate_path);
    $stmt->fetch();
    $stmt->close();
    
    if ($certificate_path && file_exists($certificate_path)) {
        unlink($certificate_path);
    }
    
    $stmt = $conn->prepare("DELETE FROM participations WHERE pt_id = ?");
    $stmt->bind_param("i", $pt_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: manage teams.php");
    exit();
}

// Filter by participation type if specified (unchanged)
$filter = "";
if (isset($_GET['type'])) {
    $type = $_GET['type'];
    $filter = " WHERE participation_type = '$type'";
}

// Fetch all participations with optional filter (unchanged)
$query = "
    SELECT p.pt_id, p.p_id, p.student_name, p.event_name, p.participation_type, 
           p.team_name, p.result, p.certificate_path
    FROM participations p
    INNER JOIN events e ON p.event_name = e.event_name
    WHERE e.organizer_id = ?
    " . ($filter ? " AND p.participation_type = ?" : "") . " 
    ORDER BY p.event_name, p.result
";

$stmt = $conn->prepare($query);
if ($filter) {
    $stmt->bind_param("ss", $_SESSION['organizer_id'], $type);
} else {
    $stmt->bind_param("s", $_SESSION['organizer_id']);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Participants</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <style>
            .alert-container {
                position: fixed;
                top: 80px;
                right: 20px;
                z-index: 1100;
                width: 350px;
            }
            .alert {
                animation: slideIn 0.3s ease-out;
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            .sb-topnav {
                z-index: 1000;
                position: fixed;
                width: 100%;
                top: 0;
            }
            .badge {
                font-size: 0.9em;
                padding: 0.35em 0.65em;
            }
            .bg-winner {
                background-color: #28a745 !important;
            }
            .bg-runner-up-1 {
                background-color: #007bff !important;
            }
            .bg-runner-up-2 {
                background-color: #17a2b8 !important;
            }
            .bg-pending {
                background-color: #ffc107 !important;
                color: #212529;
            }
            .bg-participated {
                background-color: #6c757d !important;
            }
            .btn-group-sm .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
            .profile-img {
                width: 120px;
                height: 120px;
                object-fit: cover;
                border-radius: 50%;
                margin: 0 auto 20px;
                display: block;
                border: 5px solid #f8f9fa;
            }
            .profile-detail {
                margin-bottom: 15px;
            }
            .profile-detail label {
                font-weight: 600;
                color: #6c757d;
                display: block;
                margin-bottom: 5px;
            }
            .profile-detail span {
                font-size: 1.1rem;
                color: #212529;
            }
        </style>
    </head>
    <body class="sb-nav-fixed">
        <!-- Profile Modal (unchanged) -->
        <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="profileModalLabel">Organizer Profile</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="container">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($organizerName); ?>&background=random" class="profile-img" alt="Profile Image">
                                    <h4><?php echo htmlspecialchars($organizerName); ?></h4>
                                    <p class="text-muted"><?php echo htmlspecialchars($organizerDetails['role'] ?? ''); ?></p>
                                </div>
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-6 profile-detail">
                                            <label>Organizer ID</label>
                                            <span><?php echo htmlspecialchars($organizerDetails['organizer_id'] ?? ''); ?></span>
                                        </div>
                                        <div class="col-md-6 profile-detail">
                                            <label>Email</label>
                                            <span><?php echo htmlspecialchars($organizerDetails['email'] ?? ''); ?></span>
                                        </div>
                                        <div class="col-md-6 profile-detail">
                                            <label>Phone</label>
                                            <span><?php echo htmlspecialchars($organizerDetails['phone'] ?? ''); ?></span>
                                        </div>
                                        <div class="col-md-6 profile-detail">
                                            <label>Department</label>
                                            <span><?php echo htmlspecialchars($organizerDetails['department'] ?? ''); ?></span>
                                        </div>
                                        <div class="col-md-6 profile-detail">
                                            <label>Semester</label>
                                            <span><?php echo htmlspecialchars($organizerDetails['semester'] ?? ''); ?></span>
                                        </div>
                                        <div class="col-md-6 profile-detail">
                                            <label>Role</label>
                                            <span><?php echo htmlspecialchars($organizerDetails['role'] ?? ''); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Container (unchanged) -->
        <div class="alert-container">
            <?php if (isset($_SESSION['upload_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['upload_status']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['upload_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php 
                unset($_SESSION['upload_message']);
                unset($_SESSION['upload_status']);
            endif; 
            ?>
        </div>
        
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
            <a class="navbar-brand ps-3" href="organizer.php">EveFlow</a>
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
            <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
                <div class="input-group">
                    <input class="form-control" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
                    <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
                </div>
            </form>
            <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i>
                        <span class="ms-1 d-none d-lg-inline"><?php echo htmlspecialchars($firstName); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">Profile</a></li>
                        <li><hr class="dropdown-divider" /></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <div class="sb-sidenav-menu-heading">Organizer</div>
                            <a class="nav-link" href="organizer.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt fa-fw"></i></div>
                                Dashboard
                            </a>
                            <a class="nav-link" href="myevents.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt fa-fw"></i></div>
                                Events
                            </a>
                            <a class="nav-link" href="manage teams.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-users fa-fw"></i></div>
                                Manage Participants
                            </a>
                            <a class="nav-link" href="logout.php">
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
                            <h1>Participants</h1>
                        </div>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="organizer.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Participants</li>
                        </ol>
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-table me-1"></i>
                                        Manage Participants
                                    </div>
                                    <div class="ms-auto">
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="playerFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                Filter
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="playerFilterDropdown">
                                                <li><a class="dropdown-item" href="manage teams.php">All Participants</a></li>
                                                <li><a class="dropdown-item" href="manage teams.php?type=individual">Individual</a></li>
                                                <li><a class="dropdown-item" href="manage teams.php?type=team">Team</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Participant ID</th>
                                            <th>Participant Name</th>
                                            <th>Event</th>
                                            <th>Type</th>
                                            <th>Team Name</th>
                                            <th>Result</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['pt_id']; ?></td>
                                            <td><?php echo $row['p_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['event_name']); ?></td>
                                            <td><?php echo ucfirst($row['participation_type']); ?></td>
                                            <td>
                                                <?php 
                                                // Only display team_name if participation_type is 'team'
                                                echo ($row['participation_type'] === 'team' && !empty($row['team_name'])) 
                                                    ? htmlspecialchars($row['team_name']) 
                                                    : ''; 
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                switch($row['result']) {
                                                    case 'winner':
                                                        echo '<span class="badge bg-winner">Winner</span>';
                                                        break;
                                                    case '1st runner up':
                                                        echo '<span class="badge bg-runner-up-1">1st Runner Up</span>';
                                                        break;
                                                    case '2nd runner up':
                                                        echo '<span class="badge bg-runner-up-2">2nd Runner Up</span>';
                                                        break;
                                                    case 'pending':
                                                        echo '<span class="badge bg-pending">Pending</span>';
                                                        break;
                                                    case 'participated':
                                                        echo '<span class="badge bg-participated">Participated</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge bg-secondary">'.htmlspecialchars($row['result']).'</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editResultModal<?php echo $row['pt_id']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#uploadModal<?php echo $row['pt_id']; ?>">
                                                        <i class="fas fa-upload"></i>
                                                    </button>
                                                    <?php if ($row['certificate_path']): ?>
                                                    <a href="<?php echo $row['certificate_path']; ?>" class="btn btn-success" target="_blank">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <button class="btn btn-danger" onclick="confirmDelete(<?php echo $row['pt_id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Edit Result Modal -->
                                        <div class="modal fade" id="editResultModal<?php echo $row['pt_id']; ?>" tabindex="-1" aria-labelledby="editResultModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editResultModalLabel">Update Result</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="POST" action="manage teams.php">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="pt_id" value="<?php echo $row['pt_id']; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Participant: <?php echo htmlspecialchars($row['student_name']); ?></label>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Event: <?php echo htmlspecialchars($row['event_name']); ?></label>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="result<?php echo $row['pt_id']; ?>" class="form-label">Select Result</label>
                                                                <select class="form-select" id="result<?php echo $row['pt_id']; ?>" name="result" required>
                                                                    <option value="pending" <?php echo $row['result'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                    <option value="winner" <?php echo $row['result'] == 'winner' ? 'selected' : ''; ?>>Winner</option>
                                                                    <option value="1st runner up" <?php echo $row['result'] == '1st runner up' ? 'selected' : ''; ?>>1st Runner Up</option>
                                                                    <option value="2nd runner up" <?php echo $row['result'] == '2nd runner up' ? 'selected' : ''; ?>>2nd Runner Up</option>
                                                                    <option value="participated" <?php echo $row['result'] == 'participated' ? 'selected' : ''; ?>>Participated</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" name="update_result" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Upload Certificate Modal (unchanged) -->
                                        <div class="modal fade" id="uploadModal<?php echo $row['pt_id']; ?>" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="uploadModalLabel">Upload Certificate</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="POST" action="manage teams.php" enctype="multipart/form-data">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="upload_pt_id" value="<?php echo $row['pt_id']; ?>">
                                                            <div class="mb-3">
                                                                <label for="certificate<?php echo $row['pt_id']; ?>" class="form-label">Select PDF Certificate</label>
                                                                <input class="form-control" type="file" id="certificate<?php echo $row['pt_id']; ?>" name="certificate" accept=".pdf" required>
                                                                <div class="form-text">Only PDF files are allowed (max 5MB)</div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Upload</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
                <footer class="py-4 bg-light mt-auto">
                    <div class="container-fluid px-4">
                        <div class="d-flex align-items-center justify-content-between small">
                            <div class="text-muted">Copyright © EveFlow <?php echo date('Y'); ?></div>
                            <div>
                                <a href="#">Privacy Policy</a>
                                ·
                                <a href="#">Terms & Conditions</a>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
        <script>
            function confirmDelete(pt_id) {
                if (confirm("Are you sure you want to delete this participant?")) {
                    window.location.href = 'manage teams.php?delete_id=' + pt_id;
                }
            }
            
            window.setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                });
            }, 5000);
        </script>
    </body>
</html>
<?php $conn->close(); ?>