<?php
// Ensure no output is sent before session_start()
session_start();

// Check if admin is logged in (using consistent session variable 'admin_id')
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../adminlogin.php');
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new venue
    if (isset($_POST['add_venue'])) {
        $venue_name = $conn->real_escape_string($_POST['venue_name']);
        $location = $conn->real_escape_string($_POST['location']);
        $venue_type = $conn->real_escape_string($_POST['venue_type']);
        
        // Check if venue already exists
        $check_sql = "SELECT venue_id FROM venue WHERE venue_name = ? AND location = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $venue_name, $location);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $_SESSION['error'] = "This venue already exists in the system";
        } else {
            $sql = "INSERT INTO venue (venue_name, location, venue_type) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $venue_name, $location, $venue_type);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Venue added successfully";
            } else {
                $_SESSION['error'] = "Error adding venue: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
    
    // Update venue
    if (isset($_POST['edit_venue'])) {
        $venue_id = intval($_POST['venue_id']);
        $venue_name = $conn->real_escape_string($_POST['venue_name']);
        $location = $conn->real_escape_string($_POST['location']);
        $venue_type = $conn->real_escape_string($_POST['venue_type']);
        
        $sql = "UPDATE venue SET venue_name = ?, location = ?, venue_type = ? WHERE venue_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $venue_name, $location, $venue_type, $venue_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Venue updated successfully";
        } else {
            $_SESSION['error'] = "Error updating venue: " . $stmt->error;
        }
        $stmt->close();
    }
    
    header('Location: manage_venue.php');
    exit();
}

// Delete venue
if (isset($_GET['delete'])) {
    $venue_id = intval($_GET['delete']);
    $sql = "DELETE FROM venue WHERE venue_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $venue_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Venue deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting venue: " . $stmt->error;
    }
    $stmt->close();
    header('Location: manage_venue.php');
    exit();
}

// Filter venue by type if specified
$filter = "";
if (isset($_GET['type'])) {
    $type = $conn->real_escape_string($_GET['type']);
    $filter = " WHERE venue_type = ?";
}

// Fetch all venues
$venues = [];
$sql = "SELECT * FROM venue" . $filter;
if ($filter) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $venues[] = $row;
    }
}
if ($filter) {
    $stmt->close();
}

// Get venue data for editing
$edit_venue = null;
if (isset($_GET['edit'])) {
    $venue_id = intval($_GET['edit']);
    $sql = "SELECT * FROM venue WHERE venue_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $venue_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $edit_venue = $result->fetch_assoc();
    }
    $stmt->close();
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
    <title>Venue Management</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e3e6f0;
        }
        .breadcrumb {
            background-color: transparent;
            padding: 0;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
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
                        <h1>Manage Venue</h1>
                        <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVenueModal">
                            <i class="fas fa-plus"></i> Add New Venue
                        </a>
                    </div>
                    
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['message']) ?>
                            <?php unset($_SESSION['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['error']) ?>
                            <?php unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="admin.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Venue Management</li>
                    </ol>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-table me-1"></i>
                                    Venue List
                                </div>
                                <div class="ms-auto">
                                    <div class="dropdown">
                                        <button class="btn btn-primary dropdown-toggle" type="button" id="venueFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            Filter
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="venueFilterDropdown">
                                            <li><a class="dropdown-item" href="manage_venue.php">All Venues</a></li>
                                            <li><a class="dropdown-item" href="manage_venue.php?type=Indoor">Indoor</a></li>
                                            <li><a class="dropdown-item" href="manage_venue.php?type=Outdoor">Outdoor</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatablesSimple" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Venue Name</th>
                                            <th>Location</th>
                                            <th>Venue Type</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($venues as $venue): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($venue['venue_id']) ?></td>
                                            <td><?= htmlspecialchars($venue['venue_name']) ?></td>
                                            <td><?= htmlspecialchars($venue['location']) ?></td>
                                            <td><?= htmlspecialchars($venue['venue_type']) ?></td>
                                            <td>
                                                <a href="manage_venue.php?edit=<?= htmlspecialchars($venue['venue_id']) ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="manage_venue.php?delete=<?= htmlspecialchars($venue['venue_id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this venue?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright © EveFlow <?= date('Y') ?></div>
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

    <!-- Add Venue Modal -->
    <div class="modal fade" id="addVenueModal" tabindex="-1" aria-labelledby="addVenueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addVenueModalLabel">Add New Venue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="manage_venue.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="venueName" class="form-label">Venue Name</label>
                            <input type="text" class="form-control" id="venueName" name="venue_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="venueLocation" class="form-label">Location</label>
                            <input type="text" class="form-control" id="venueLocation" name="location" required>
                        </div>
                        <div class="mb-3">
                            <label for="venueType" class="form-label">Venue Type</label>
                            <select class="form-control" id="venueType" name="venue_type" required>
                                <option value="Indoor">Indoor</option>
                                <option value="Outdoor">Outdoor</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_venue" class="btn btn-primary">Save Venue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Venue Modal -->
    <div class="modal fade" id="editVenueModal" tabindex="-1" aria-labelledby="editVenueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editVenueModalLabel">Edit Venue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <?php if ($edit_venue): ?>
                <form method="POST" action="manage_venue.php">
                    <input type="hidden" name="venue_id" value="<?= htmlspecialchars($edit_venue['venue_id']) ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editVenueName" class="form-label">Venue Name</label>
                            <input type="text" class="form-control" id="editVenueName" name="venue_name" value="<?= htmlspecialchars($edit_venue['venue_name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="editLocation" class="form-label">Location</label>
                            <input type="text" class="form-control" id="editLocation" name="location" value="<?= htmlspecialchars($edit_venue['location']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="editVenueType" class="form-label">Venue Type</label>
                            <select class="form-control" id="editVenueType" name="venue_type" required>
                                <option value="Indoor" <?= $edit_venue['venue_type'] === 'Indoor' ? 'selected' : '' ?>>Indoor</option>
                                <option value="Outdoor" <?= $edit_venue['venue_type'] === 'Outdoor' ? 'selected' : '' ?>>Outdoor</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="edit_venue" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script>
        // Initialize DataTable
        window.addEventListener('DOMContentLoaded', event => {
            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                new simpleDatatables.DataTable(datatablesSimple);
            }
            
            // Auto-focus on modal inputs when shown
            const addVenueModal = document.getElementById('addVenueModal');
            if (addVenueModal) {
                addVenueModal.addEventListener('shown.bs.modal', () => {
                    document.getElementById('venueName').focus();
                });
            }
            
            // Show edit modal if URL has edit parameter
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('edit')) {
                const editModal = new bootstrap.Modal(document.getElementById('editVenueModal'));
                editModal.show();
                
                // Focus on first input when modal is shown
                editModal._element.addEventListener('shown.bs.modal', () => {
                    document.getElementById('editVenueName').focus();
                });
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>