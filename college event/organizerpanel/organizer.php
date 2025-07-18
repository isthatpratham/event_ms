<?php
// Start session with strict security settings
session_start();

// Prevent caching of authenticated pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login if not authenticated
if (!isset($_SESSION['organizer_id']) || empty($_SESSION['organizer_id'])) {
    header("Location: /college event/organizerlogin.php");
    exit();
}

// Regenerate session ID periodically
if (!isset($_SESSION['created'])) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "eveflow_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get organizer details
$organizerId = $_SESSION['organizer_id'];
$organizerName = "Organizer";
$organizerDetails = [];

$organizerQuery = "SELECT organizer_id, organizer_name, email, phone, department, semester, role FROM organizers WHERE organizer_id = ?";
$stmt = $conn->prepare($organizerQuery);
$stmt->bind_param("s", $organizerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $organizerDetails = $result->fetch_assoc();
    $nameParts = explode(' ', $organizerDetails['organizer_name']);
    $organizerName = $nameParts[0];
}
$stmt->close();

// Get event statistics
$totalEvents = $ongoingEvents = $pendingTasks = 0;

$statsQuery = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Ongoing' THEN 1 ELSE 0 END) as ongoing,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
              FROM events 
              WHERE organizer_id = ?";

$stmt = $conn->prepare($statsQuery);
$stmt->bind_param("s", $organizerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stats = $result->fetch_assoc();
    $totalEvents = $stats['total'];
    $ongoingEvents = $stats['ongoing'];
    $pendingTasks = $stats['pending'];
}
$stmt->close();
// Update event status based on today's date
$today = date("Y-m-d");

$updateOngoing = $conn->prepare("UPDATE events SET status = 'Ongoing' WHERE event_date = ? AND status != 'Ongoing'");
$updateOngoing->bind_param("s", $today);
$updateOngoing->execute();
$updateOngoing->close();

$updateCompleted = $conn->prepare("UPDATE events SET status = 'Completed' WHERE event_date < ? AND status != 'Completed'");
$updateCompleted->bind_param("s", $today);
$updateCompleted->execute();
$updateCompleted->close();


// Get events for datatable (now with venue join)
$eventsQuery = "SELECT e.*, v.venue_name 
                FROM events e 
                LEFT JOIN venue v ON e.venue_id = v.venue_id 
                WHERE e.organizer_id = ? 
                ORDER BY e.event_date DESC";
$stmt = $conn->prepare($eventsQuery);
$stmt->bind_param("s", $organizerId);
$stmt->execute();
$eventsResult = $stmt->get_result();
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Organizer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .modal-content {
            border-radius: 0.5rem;
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
        .badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        .bg-success {
            background-color: #198754!important;
        }
        .bg-warning {
            background-color: #ffc107!important;
            color: #212529;
        }
        .bg-secondary {
            background-color: #6c757d!important;
        }
        .bg-info {
            background-color: #0dcaf0!important;
            color: #212529;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <!-- Profile Modal -->
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
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($organizerDetails['organizer_name'] ?? ''); ?>&background=random" class="profile-img" alt="Profile Image">
                                <h4><?php echo htmlspecialchars($organizerDetails['organizer_name'] ?? 'Organizer'); ?></h4>
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

    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="organizer.php">EveFlow</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
            <div class="input-group">
                <input class="form-control" type="text" placeholder="Search..." />
                <button class="btn btn-primary" type="button"><i class="fas fa-search"></i></button>
            </div>
        </form>
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user fa-fw"></i>
                    <span class="ms-1 d-none d-lg-inline"><?php echo htmlspecialchars($organizerName); ?></span>
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
                        <h1>Organizer Dashboard</h1>
                    </div>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                    
                    <div class="row">
                        <!-- Total Events Card -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>Total Events</div>
                                    <div class="card-value"><?php echo $totalEvents; ?></div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="myevents.php">View Details</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ongoing Events Card -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>Ongoing Events</div>
                                    <div class="card-value"><?php echo $ongoingEvents; ?></div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="myevents.php">View Details</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pending Tasks Card -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-4">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>Pending Tasks</div>
                                    <div class="card-value"><?php echo $pendingTasks; ?></div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="myevents.php">View Details</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Your Events
                        </div>
                        <div class="card-body">
                            <?php if ($eventsResult->num_rows > 0): ?>
                            <table id="datatablesSimple" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Event Name</th>
                                        <th>Date</th>
                                        <th>Venue</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($event = $eventsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['event_id']); ?></td>
                                        <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                        <td><?php echo htmlspecialchars($event['event_date']); ?></td>
                                        <td><?php echo htmlspecialchars($event['venue_name'] ?? 'Not specified'); ?></td>
                                        <td><?php echo htmlspecialchars($event['event_type'] ?? 'Not specified'); ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                switch($event['status']) {
                                                    case 'Ongoing': echo 'bg-success'; break;
                                                    case 'Pending': echo 'bg-warning'; break;
                                                    case 'Completed': echo 'bg-secondary'; break;
                                                    default: echo 'bg-info';
                                                }
                                                ?>">
                                                <?php echo htmlspecialchars($event['status'] ?? 'Unknown'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="alert alert-info">
                                No events found. <a href="myevents.php" class="alert-link">Create your first event</a>.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; EveFlow <?php echo date('Y'); ?></div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dataTable = new simpleDatatables.DataTable("#datatablesSimple");
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
