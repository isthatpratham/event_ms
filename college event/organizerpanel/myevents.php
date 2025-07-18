<?php
// Start session and set security headers
session_start();

// Prevent caching of authenticated pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Strict session validation
if (!isset($_SESSION['organizer_id']) || empty($_SESSION['organizer_id'])) {
    header("Location: /college event/organizerlogin.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "eveflow_db";

// Create connection with error handling
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get current system date for comparison
$current_date = date("Y-m-d");

// Function to determine status based on event date
function determineStatus($event_date, $current_date) {
    if ($event_date > $current_date) {
        return "Pending";
    } elseif ($event_date == $current_date) {
        return "Ongoing";
    } else {
        return "Completed";
    }
}

// Update event statuses based on system date for the current organizer
$organizerId = $_SESSION['organizer_id'];
try {
    $updateQuery = "SELECT event_id, event_date, status FROM events WHERE organizer_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->execute([$organizerId]);
    $eventsToUpdate = $updateStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($eventsToUpdate as $event) {
        $event_id = $event['event_id'];
        $event_date = $event['event_date'];
        $current_status = $event['status'];
        
        $new_status = determineStatus($event_date, $current_date);
        
        if ($current_status != $new_status) {
            $updateSql = "UPDATE events SET status = ? WHERE event_id = ? AND organizer_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->execute([$new_status, $event_id, $organizerId]);
        }
    }
} catch(PDOException $e) {
    $_SESSION['error_message'] = "Error updating event statuses: " . $e->getMessage();
}

// Get organizer details for display
$organizerName = "Organizer";
$organizerDetails = [];

try {
    $organizerQuery = "SELECT organizer_id, organizer_name, email, phone, department, semester, role FROM organizers WHERE organizer_id = ?";
    $stmt = $conn->prepare($organizerQuery);
    $stmt->execute([$organizerId]);
    
    if ($stmt->rowCount() > 0) {
        $organizerDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        $nameParts = explode(' ', $organizerDetails['organizer_name']);
        $organizerName = $nameParts[0];
    }
} catch(PDOException $e) {
    // Handle error
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['event_name'])) {
        // Validate organizer_id exists in organizers table first
        $organizerId = $_SESSION['organizer_id'];
        
        try {
            $stmt = $conn->prepare("SELECT organizer_id FROM organizers WHERE organizer_id = ?");
            $stmt->execute([$organizerId]);
            
            if ($stmt->rowCount() == 0) {
                $_SESSION['error_message'] = "Invalid organizer account!";
                header("Location: myevents.php");
                exit();
            }
            
            // Add/Edit Event
            if (isset($_POST['event_id']) && !empty($_POST['event_id'])) {
                // Verify the event belongs to this organizer before editing
                $verifyStmt = $conn->prepare("SELECT event_id FROM events WHERE event_id = ? AND organizer_id = ?");
                $verifyStmt->execute([$_POST['event_id'], $organizerId]);
                
                if ($verifyStmt->rowCount() == 0) {
                    $_SESSION['error_message'] = "You can only edit your own events!";
                    header("Location: myevents.php");
                    exit();
                }
                
                // Edit existing event (status will be updated automatically on page load)
                $stmt = $conn->prepare("UPDATE events SET event_name=?, event_date=?, event_type=? WHERE event_id=? AND organizer_id=?");
                $stmt->execute([
                    $_POST['event_name'],
                    $_POST['event_date'],
                    $_POST['event_type'],
                    $_POST['event_id'],
                    $organizerId
                ]);
            } else {
                // Add new event with NULL venue_id (status will be updated automatically on page load)
                $stmt = $conn->prepare("INSERT INTO events (event_name, event_date, event_type, organizer_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['event_name'],
                    $_POST['event_date'],
                    $_POST['event_type'],
                    $organizerId
                ]);
            }
            
            $_SESSION['success_message'] = "Event saved successfully!";
        } catch(PDOException $e) {
            $_SESSION['error_message'] = "Error saving event: " . $e->getMessage();
        }
        
        header("Location: myevents.php");
        exit();
    }
}

// Handle event deletion
if (isset($_GET['delete_id'])) {
    $organizerId = $_SESSION['organizer_id'];
    $eventId = $_GET['delete_id'];
    
    try {
        // Verify the event belongs to this organizer before deleting
        $verifyStmt = $conn->prepare("SELECT event_id FROM events WHERE event_id = ? AND organizer_id = ?");
        $verifyStmt->execute([$eventId, $organizerId]);
        
        if ($verifyStmt->rowCount() == 0) {
            $_SESSION['error_message'] = "You can only delete your own events!";
            header("Location: myevents.php");
            exit();
        }
        
        // Delete the event
        $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ? AND organizer_id = ?");
        $stmt->execute([$eventId, $organizerId]);
        
        $_SESSION['success_message'] = "Event deleted successfully!";
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Error deleting event: " . $e->getMessage();
    }
    
    header("Location: myevents.php");
    exit();
}

// Get event statistics
$totalEvents = $ongoingEvents = $pendingEvents = $completedEvents = 0;

try {
    $statsQuery = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Ongoing' THEN 1 ELSE 0 END) as ongoing,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
                  FROM events 
                  WHERE organizer_id = ?";
    $stmt = $conn->prepare($statsQuery);
    $stmt->execute([$organizerId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalEvents = $stats['total'];
    $ongoingEvents = $stats['ongoing'];
    $pendingEvents = $stats['pending'];
    $completedEvents = $stats['completed'];
} catch(PDOException $e) {
    // Handle error
}

// Get events for the table
$events = [];
try {
    $eventsQuery = "SELECT e.*, v.venue_name, v.location, v.venue_type 
                    FROM events e
                    LEFT JOIN venue v ON e.venue_id = v.venue_id
                    WHERE e.organizer_id = ? 
                    ORDER BY e.event_date DESC";
    $stmt = $conn->prepare($eventsQuery);
    $stmt->execute([$organizerId]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Handle error
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
        <title>Events</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <style>
            /* Modal styling */
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
            .badge-completed {
                background-color: #6c757d;
            }
            .badge-ongoing {
                background-color: #198754;
            }
            .badge-pending {
                background-color: #ffc107;
                color: #212529;
            }
            /* Custom style for blocked input feedback */
            .invalid-input {
                border-color: #dc3545 !important;
                background-color: #fff5f5 !important;
            }
            .input-feedback {
                color: #dc3545;
                font-size: 0.875rem;
                display: none;
                margin-top: 5px;
            }
        </style>
    </head>
    <body class="sb-nav-fixed">
        <!-- Success/Error Messages (will auto-hide) -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 1100;">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 1100;">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

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
            <!-- Navbar Brand-->
            <a class="navbar-brand ps-3" href="organizer.php">EveFlow</a>
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
            <!-- Navbar Search-->
            <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
                <div class="input-group">
                    <input class="form-control" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
                    <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
                </div>
            </form>
            <!-- Navbar-->
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
                            <h1>Events</h1>
                            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                                <i class="fas fa-plus"></i> Add New Event
                            </a>
                        </div>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="organizer.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Events</li>
                        </ol>
                        
                        <div class="row">
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-primary text-white mb-4">
                                    <div class="card-body d-flex justify-content-between align-items-center">
                                        <div>Total Events</div>
                                        <div class="card-value"><?php echo $totalEvents; ?></div>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="#">View Details</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-success text-white mb-4">
                                    <div class="card-body d-flex justify-content-between align-items-center">
                                        <div>Ongoing Events</div>
                                        <div class="card-value"><?php echo $ongoingEvents; ?></div>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="#">View Details</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-warning text-white mb-4">
                                    <div class="card-body d-flex justify-content-between align-items-center">
                                        <div>Pending Events</div>
                                        <div class="card-value"><?php echo $pendingEvents; ?></div>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="#">View Details</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-secondary text-white mb-4">
                                    <div class="card-body d-flex justify-content-between align-items-center">
                                        <div>Completed Events</div>
                                        <div class="card-value"><?php echo $completedEvents; ?></div>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="#">View Details</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-table me-1"></i>
                                        Events To Schedule
                                    </div>
                                    <div class="ms-auto">
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="eventFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                Filter Events
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="eventFilterDropdown">
                                                <li><a class="dropdown-item" href="#" onclick="filterEvents('All')">All Events</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="filterEvents('Ongoing')">Ongoing</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="filterEvents('Pending')">Pending</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="filterEvents('Completed')">Completed</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (count($events) > 0): ?>
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th>Id</th>
                                            <th>Event Name</th>
                                            <th>Date</th>
                                            <th>Venue</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Id</th>
                                            <th>Event Name</th>
                                            <th>Date</th>
                                            <th>Venue</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </tfoot>
                                    <tbody>
                                        <?php foreach ($events as $event): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($event['event_id']); ?></td>
                                            <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                            <td><?php echo htmlspecialchars($event['event_date']); ?></td>
                                            <td>
                                                <?php if (!empty($event['venue_name'])): ?>
                                                    <?php echo htmlspecialchars($event['venue_name']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No venue assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php echo $event['status'] == 'Ongoing' ? 'bg-success' : 
                                                          ($event['status'] == 'Pending' ? 'bg-warning' : 'bg-secondary'); ?>">
                                                    <?php echo htmlspecialchars($event['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editEvent(
                                                    '<?php echo $event['event_id']; ?>',
                                                    '<?php echo addslashes($event['event_name']); ?>',
                                                    '<?php echo $event['event_date']; ?>',
                                                    '<?php echo $event['event_type']; ?>'
                                                )">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $event['event_id']; ?>)">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                <div class="alert alert-info" id="noEventsAlert">
                                    No events found. <a href="#" class="alert-link" data-bs-toggle="modal" data-bs-target="#addEventModal">Create your first event</a>.
                                </div>
                                <?php endif; ?>
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

        <!-- Add Event Modal -->
        <div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addEventModalLabel">Add New Event</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="eventName" class="form-label">Event Name</label>
                                <input type="text" class="form-control" id="eventName" name="event_name" required>
                                <div id="eventNameFeedback" class="input-feedback">Numbers are not allowed in the event name.</div>
                            </div>
                            <div class="mb-3">
                                <label for="eventDate" class="form-label">Event Date</label>
                                <input type="date" class="form-control" id="eventDate" name="event_date" required>
                            </div>
                            <div class="mb-3">
                                <label for="eventStatus" class="form-label">Status</label>
                                <input type="text" class="form-control" id="eventStatus" value="Automatically set based on event date" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="eventType" class="form-label">Type</label>
                                <select class="form-control" id="eventType" name="event_type" required>
                                    <option value="">Select Event Type</option>
                                    <option value="Individual">Individual</option>
                                    <option value="Team">Team</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Event</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Event Modal -->
        <div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editEventModalLabel">Edit Event</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="">
                        <div class="modal-body">
                            <input type="hidden" id="editEventId" name="event_id">
                            <div class="mb-3">
                                <label for="editEventName" class="form-label">Event Name</label>
                                <input type="text" class="form-control" id="editEventName" name="event_name" required>
                                <div id="editEventNameFeedback" class="input-feedback">Numbers are not allowed in the event name.</div>
                            </div>
                            <div class="mb-3">
                                <label for="editEventDate" class="form-label">Event Date</label>
                                <input type="date" class="form-control" id="editEventDate" name="event_date" required>
                            </div>
                            <div class="mb-3">
                                <label for="editEventStatus" class="form-label">Status</label>
                                <input type="text" class="form-control" id="editEventStatus" value="Automatically set based on event date" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="editEventType" class="form-label">Type</label>
                                <select class="form-control" id="editEventType" name="event_type" required>
                                    <option value="">Select Event Type</option>
                                    <option value="Individual">Individual</option>
                                    <option value="Team">Team</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            // Initialize the datatable (if table exists)
            let dataTable;
            if (document.getElementById('datatablesSimple')) {
                dataTable = new simpleDatatables.DataTable("#datatablesSimple");
            }

            // For edit modal - populate with event data
            function editEvent(eventId, eventName, eventDate, eventType) {
                document.getElementById('editEventId').value = eventId;
                document.getElementById('editEventName').value = eventName;
                document.getElementById('editEventDate').value = eventDate;
                document.getElementById('editEventType').value = eventType;
                
                // Show the modal
                var editModal = new bootstrap.Modal(document.getElementById('editEventModal'));
                editModal.show();
            }
            
            function confirmDelete(eventId) {
                if (confirm("Are you sure you want to delete this event?")) {
                    window.location.href = 'myevents.php?delete_id=' + eventId;
                }
            }
            
            // Filter events based on status
            function filterEvents(status) {
                console.log('Filtering for status:', status); // Debug log
                const rows = document.querySelectorAll('#datatablesSimple tbody tr');
                if (rows.length === 0) {
                    console.log('No rows found in table');
                    return;
                }
                rows.forEach(row => {
                    // Get the status from the 6th column (index 5)
                    const statusCell = row.cells[5].querySelector('.badge').textContent.trim();
                    console.log('Row status:', statusCell); // Debug log
                    if (status === 'All' || statusCell === status) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            // Prevent dropdown menu from closing on item click
            document.querySelectorAll('.dropdown-menu a').forEach(item => {
                item.addEventListener('click', event => {
                    event.stopPropagation();
                    event.preventDefault(); // Prevent href="#" navigation
                });
            });
            
            // Auto-hide alerts after 5 seconds (except #noEventsAlert)
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert:not(#noEventsAlert)');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);

            // Restrict event name to strings (block numbers)
            document.getElementById('eventName').addEventListener('input', function(e) {
                const input = e.target.value;
                const feedback = document.getElementById('eventNameFeedback');
                
                if (/\d/.test(input)) {
                    e.target.value = input.replace(/\d/g, '');
                    e.target.classList.add('invalid-input');
                    feedback.style.display = 'block';
                } else {
                    e.target.classList.remove('invalid-input');
                    feedback.style.display = 'none';
                }
            });

            document.getElementById('editEventName').addEventListener('input', function(e) {
                const input = e.target.value;
                const feedback = document.getElementById('editEventNameFeedback');
                
                if (/\d/.test(input)) {
                    e.target.value = input.replace(/\d/g, '');
                    e.target.classList.add('invalid-input');
                    feedback.style.display = 'block';
                } else {
                    e.target.classList.remove('invalid-input');
                    feedback.style.display = 'none';
                }
            });
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
    </body>
</html>

<?php 
// Close connection
$conn = null;
?>