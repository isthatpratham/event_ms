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

// Handle withdrawal request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['withdraw_participation'])) {
    $pt_id = $_POST['pt_id'];
    
    $status_check = $conn->prepare("SELECT TRIM(e.status) FROM participations p 
                                   JOIN events e ON p.event_name = e.event_name 
                                   WHERE p.pt_id = ? AND p.st_id = ?");
    $status_check->bind_param("is", $pt_id, $_SESSION['st_id']);
    $status_check->execute();
    $status_check->bind_result($event_status);
    $status_check->fetch();
    $status_check->close();
    
    if (strtolower($event_status) === 'pending') {
        $cert_stmt = $conn->prepare("SELECT certificate_path FROM participations WHERE pt_id = ? AND st_id = ?");
        $cert_stmt->bind_param("is", $pt_id, $_SESSION['st_id']);
        $cert_stmt->execute();
        $cert_stmt->bind_result($certificate_path);
        $cert_stmt->fetch();
        $cert_stmt->close();
        
        if ($certificate_path && file_exists($certificate_path)) {
            unlink($certificate_path);
        }
        
        $stmt = $conn->prepare("DELETE FROM participations WHERE pt_id = ? AND st_id = ?");
        $stmt->bind_param("is", $pt_id, $_SESSION['st_id']);
        
        if ($stmt->execute()) {
            $_SESSION['upload_message'] = "Participation withdrawn successfully!";
            $_SESSION['upload_status'] = "success";
        } else {
            $_SESSION['upload_message'] = "Error withdrawing participation: " . $stmt->error;
            $_SESSION['upload_status'] = "danger";
        }
        
        $stmt->close();
    } else {
        $_SESSION['upload_message'] = "Can only withdraw from pending events (Current status: " . $event_status . ")";
        $_SESSION['upload_status'] = "danger";
    }
    
    header("Location: participation.php");
    exit();
}

// Fetch student information with prepared statement
$student_id = $_SESSION['st_id'];
$student_query = "SELECT * FROM users WHERE st_id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();

// Check if student data exists
if ($student_result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    session_destroy();
    header("Location: login.php");
    exit();
}
$student_data = $student_result->fetch_assoc();
$stmt->close();

// Handle filters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where_clause = "WHERE p.st_id = ?";

switch($filter) {
    case 'ongoing':
        $where_clause .= " AND e.event_date >= CURDATE() AND e.status = 'ongoing'";
        break;
    case 'pending':
        $where_clause .= " AND e.status = 'pending'";
        break;
    case 'completed':
        $where_clause .= " AND e.event_date < CURDATE() AND e.status = 'completed'";
        break;
    default:
        break;
}

// Fetch participations with event details using prepared statement
$query = "SELECT p.*, e.event_date, TRIM(e.status) as event_status 
          FROM participations p
          JOIN events e ON p.event_name = e.event_name
          $where_clause
          ORDER BY e.event_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

// Count total entries for pagination
$total_query = "SELECT COUNT(*) as total FROM participations p
                JOIN events e ON p.event_name = e.event_name
                WHERE p.st_id = ?";
$total_stmt = $conn->prepare($total_query);
$total_stmt->bind_param("s", $student_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_entries = $total_row['total'];
$total_stmt->close();

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
    <title>My Participations | EveFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/applystyle.css" type="text/css" media="all" />
    <style>
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 50rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-approved {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .badge-pending {
            background-color: #fff3cd;
            color: #664d03;
        }
        .badge-completed {
            background-color: #cfe2ff;
            color: #084298;
        }
        .badge-ongoing {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-winner {
            background-color: #28a745;
            color: white;
        }
        .badge-runner-up-1 {
            background-color: #17a2b8;
            color: white;
        }
        .badge-runner-up-2 {
            background-color: #6c757d;
            color: white;
        }
        .badge-participated {
            background-color: #6c757d;
            color: white;
        }
        .filter-container {
            margin-bottom: 1.5rem;
        }
        .filter-btn {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .table-title {
            margin-bottom: 1rem;
        }
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
        .action-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
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
        .btn-disabled {
            opacity: 0.65;
            pointer-events: none;
        }
        .modal-dialog.modal-xl {
            max-width: 90vw;
            width: 1200px;
        }
        #certificateFrame {
            width: 100%;
            height: 70vh;
            border: none;
        }
        .alert-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1100;
            width: 350px;
        }
        .btn-withdraw {
            background-color: #dc3545;
            color: white;
            border: none;
        }
        .btn-withdraw:hover {
            background-color: #bb2d3b;
            color: white;
        }
        .btn-withdraw:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        .certificate-buttons {
            display: flex;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="alert-container">
        <?php if (isset($_SESSION['upload_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['upload_status']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['upload_message']; ?>
            <!-- Debug status -->
            <?php if (isset($_SESSION['debug_status'])): ?>
                <br>Debug: Status was '<?php echo $_SESSION['debug_status']; ?>'
                <?php unset($_SESSION['debug_status']); ?>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
            unset($_SESSION['upload_message']);
            unset($_SESSION['upload_status']);
        endif; 
        ?>
    </div>
    
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

    <div class="modal fade" id="certificateModal" tabindex="-1" aria-labelledby="certificateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="certificateModalLabel">View Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe id="certificateFrame" src=""></iframe>
                </div>
                <div class="modal-footer">
                    <a id="downloadCertificate" href="#" class="btn btn-primary" download>
                        <i class="bi bi-download"></i> Download
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <div class="d-flex align-items-center">
                <button class="burger-menu me-3">
                    <i class="bi bi-list"></i>
                </button>
                <h1>My Participations</h1>
            </div>
            <div class="user-info">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student_data['student_name']); ?>&background=random" alt="User">
                <span><?php echo htmlspecialchars($student_data['student_name']); ?></span>
            </div>
        </div>
        
        <div class="filter-container">
            <a href="participation.php?filter=all" class="btn btn-outline-primary filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
            <a href="participation.php?filter=ongoing" class="btn btn-outline-primary filter-btn <?php echo $filter == 'ongoing' ? 'active' : ''; ?>">Ongoing</a>
            <a href="participation.php?filter=pending" class="btn btn-outline-primary filter-btn <?php echo $filter == 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="participation.php?filter=completed" class="btn btn-outline-primary filter-btn <?php echo $filter == 'completed' ? 'active' : ''; ?>">Completed</a>
        </div>
        
        <div class="table-container">
            <div class="table-title">
                <h4><i class="bi bi-list-task me-2"></i>My Event Participations</h4>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Result</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($row['event_name']); ?></strong>
                                            <div class="text-muted small">ID: <?php echo htmlspecialchars($row['p_id']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($row['event_date'])); ?></td>
                                    <td><?php echo ucfirst($row['participation_type']); ?></td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        switch($row['event_status']) {
                                            case 'approved':
                                                $status_class = 'badge-approved';
                                                break;
                                            case 'pending':
                                                $status_class = 'badge-pending';
                                                break;
                                            case ' caressing':
                                                $status_class = 'badge-completed';
                                                break;
                                            case 'ongoing':
                                                $status_class = 'badge-ongoing';
                                                break;
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst($row['event_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($row['result'])) {
                                            $result_class = '';
                                            switch(strtolower($row['result'])) {
                                                case 'winner':
                                                    $result_class = 'badge-winner';
                                                    break;
                                                case '1st runner up':
                                                    $result_class = 'badge-runner-up-1';
                                                    break;
                                                case '2nd runner up':
                                                    $result_class = 'badge-runner-up-2';
                                                    break;
                                                case 'participated':
                                                    $result_class = 'badge-participated';
                                                    break;
                                                default:
                                                    $result_class = 'badge-secondary';
                                            }
                                            echo '<span class="status-badge '.$result_class.'">'.htmlspecialchars($row['result']).'</span>';
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <div class="certificate-buttons">
                                                <?php 
                                                if (!empty($row['certificate_path'])) {
                                                    $cert_path = 'organizerpanel/uploads/certificates/' . basename($row['certificate_path']);
                                                    if (file_exists($cert_path)): ?>
                                                        <button class="btn btn-sm btn-outline-primary action-btn view-certificate" 
                                                                data-certificate="<?php echo $cert_path; ?>">
                                                            <i class="bi bi-eye"></i> View
                                                        </button>
                                                        <a href="<?php echo $cert_path; ?>" 
                                                           class="btn btn-sm btn-outline-secondary action-btn" 
                                                           download="<?php echo basename($row['certificate_path']); ?>">
                                                            <i class="bi bi-download"></i> Download
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-outline-primary action-btn btn-disabled" title="Certificate file missing">
                                                            <i class="bi bi-eye"></i> View
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-secondary action-btn btn-disabled" title="Certificate file missing">
                                                            <i class="bi bi-download"></i> Download
                                                        </button>
                                                    <?php endif;
                                                } else { ?>
                                                    <button class="btn btn-sm btn-outline-primary action-btn btn-disabled" title="Certificate not available">
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary action-btn btn-disabled" title="Certificate not available">
                                                        <i class="bi bi-download"></i> Download
                                                    </button>
                                                <?php } ?>
                                            </div>
                                            <?php if (strtolower($row['event_status']) === 'pending'): ?>
                                                <button class="btn btn-sm btn-withdraw action-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#withdrawModal<?php echo $row['pt_id']; ?>">
                                                    <i class="bi bi-x-circle"></i> Withdraw
                                                </button>
                                                
                                                <div class="modal fade" id="withdrawModal<?php echo $row['pt_id']; ?>" tabindex="-1" aria-labelledby="withdrawModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="withdrawModalLabel">Confirm Withdrawal</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Are you sure you want to withdraw from "<strong><?php echo htmlspecialchars($row['event_name']); ?></strong>"?</p>
                                                                <p class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> This action cannot be undone.</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                                <form method="POST" action="participation.php">
                                                                    <input type="hidden" name="pt_id" value="<?php echo $row['pt_id']; ?>">
                                                                    <button type="submit" name="withdraw_participation" class="btn btn-danger btn-sm">
                                                                        <i class="bi bi-trash"></i> Confirm Withdrawal
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-withdraw action-btn" disabled
                                                        title="Can only withdraw from pending events (Current status: <?php echo $row['event_status']; ?>)">
                                                    <i class="bi bi-x-circle"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No participations found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination-container">
                <div class="entries-info">Showing <?php echo $result->num_rows; ?> of <?php echo $total_entries; ?> entries</div>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
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
            
            const certificateModal = new bootstrap.Modal(document.getElementById('certificateModal'));
            const certificateFrame = document.getElementById('certificateFrame');
            const downloadBtn = document.getElementById('downloadCertificate');
            
            document.querySelectorAll('.view-certificate').forEach(btn => {
                btn.addEventListener('click', function() {
                    const certificateUrl = this.getAttribute('data-certificate');
                    certificateFrame.src = certificateUrl;
                    downloadBtn.href = certificateUrl;
                    downloadBtn.setAttribute('download', certificateUrl.split('/').pop());
                    certificateModal.show();
                });
            });
            
            document.getElementById('certificateModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    certificateModal.hide();
                }
            });

            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                });
            }, 5000);

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