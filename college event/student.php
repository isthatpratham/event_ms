<?php
session_start();

// Prevent caching of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in
if (!isset($_SESSION['st_id'])) {
    header("Location: login.php");
    exit();
}

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

// Sanitize student_id to prevent SQL injection
$student_id = mysqli_real_escape_string($conn, $_SESSION['st_id']);
$student_query = "SELECT * FROM users WHERE st_id = '$student_id'";
$student_result = $conn->query($student_query);

// Check if student data exists
if ($student_result->num_rows === 0) {
    session_destroy();
    header("Location: login.php");
    exit();
}
$student_data = $student_result->fetch_assoc();

// Fetch all events with status 'ongoing' or 'pending' and join with venues table
$events_query = "SELECT e.event_id, e.event_name, e.event_date, 
                        v.venue_name, e.event_type, e.status 
                 FROM events e
                 LEFT JOIN venue v ON e.venue_id = v.venue_id
                 WHERE e.status IN ('ongoing', 'pending') 
                 ORDER BY e.event_date ASC";
$events_result = $conn->query($events_query);

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Student Profile | EveFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/userstyle.css" type="text/css" media="all" />
    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-completed {
            background-color: #28a745;
            color: white;
        }
        .badge-ongoing {
            background-color: #ffc107;
            color: #000;
        }
        .badge-pending {
            background-color: #17a2b8;
            color: white;
        }
        .sidebar a:hover:not(.no-transform-hover), 
        .sidebar a.active:not(.no-transform-hover) {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }
        .sidebar-header a.no-transform-hover:hover {
            transform: none !important;
            color: inherit;
            background-color: transparent;
            cursor: default;
        }
        .participate-btn-container {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay"></div>
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="no-transform-hover"><h3><span>EveFlow</span></h3></a>
        </div>
        <a href="student.php"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a>
        <a href="apply.php"><i class="bi bi-people"></i> <span>Participate</span></a>
        <a href="participation.php"><i class="bi bi-trophy"></i> <span>My Participations</span></a>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a>
    </div>

    <div class="content">
        <div class="header">
            <div class="d-flex align-items-center">
                <button class="burger-menu me-3">
                    <i class="bi bi-list"></i>
                </button>
                <h1>Student Profile</h1>
            </div>
            <div class="user-info">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student_data['student_name']); ?>&background=random" alt="User">
                <span><?php echo htmlspecialchars($student_data['student_name']); ?></span>
            </div>
        </div>
        
        <div class="profile-card card">
            <div class="card-header">
                <i class="bi bi-person-circle me-2"></i>Personal Information
            </div>
            <div class="card-body py-4">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                        <div class="profile-badge mx-auto mb-3"><?php echo substr($student_data['student_name'], 0, 2); ?></div>
                        <div class="profile-name"><?php echo htmlspecialchars($student_data['student_name']); ?></div>
                        <div class="profile-subtitle"><?php echo htmlspecialchars($student_data['dept']); ?></div>
                    </div>
                    <div class="col-md-9">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="profile-details">
                                    <div class="detail-row mb-3">
                                        <span class="detail-label">Student Id:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($student_data['st_id']); ?></span>
                                    </div>
                                    <div class="detail-row mb-3">
                                        <span class="detail-label">Email:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($student_data['email']); ?></span>
                                    </div>
                                    <div class="detail-row mb-3">
                                        <span class="detail-label">Course</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($student_data['course']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-details">
                                    <div class="detail-row mb-3">
                                        <span class="detail-label">Department:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($student_data['dept']); ?></span>
                                    </div>
                                    <div class="detail-row mb-3">
                                        <span class="detail-label">Semester:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($student_data['semester']); ?> Semester</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-title">
                <h4><i class="bi bi-list-task me-2"></i>Ongoing Events</h4>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="id-column">S.No.</th>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($events_result->num_rows > 0): ?>
                            <?php $serialNumber = 1; // Initialize serial number counter ?>
                            <?php while($event = $events_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="id-column"><?php echo $serialNumber++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <strong><?php echo htmlspecialchars($event['event_name']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['event_date']); ?></td>
                                    <td><?php echo htmlspecialchars($event['venue_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo ($event['status'] == 'completed') ? 'badge-completed' : (($event['status'] == 'ongoing') ? 'badge-ongoing' : 'badge-pending'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($event['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No ongoing events found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($events_result->num_rows > 0): ?>
            <br><br>
            <div class="participate-btn-container">
                <a href="apply.php" class="btn btn-primary">Participate Now</a>
            </div>
            <?php endif; ?>
            
            <div class="pagination-container">
                <div class="entries-info">
                    Showing <?php echo $events_result->num_rows; ?> entries
                </div>
                <?php if ($events_result->num_rows > 10): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="footer">
            <p>Copyright © 2025 EveFlow. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const burgerMenu = document.querySelector('.burger-menu');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            burgerMenu.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
            
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
            
            const sidebarLinks = document.querySelectorAll('.sidebar a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 992) {
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                    }
                });
            });

            // Prevent back button from showing cached page
            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });
        });
    </script>
</body>
</html>