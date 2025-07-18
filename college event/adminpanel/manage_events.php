<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../adminlogin.php");
    exit();
}

$host = "localhost";
$user = "root";
$password = "";
$database = "eveflow_db";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add Event
if (isset($_POST['add_event'])) {
    $event_name = htmlspecialchars(trim($_POST['event_name']));
    $event_date = $_POST['event_date'];
    $venue_id = $_POST['venue_id'];
    $event_type = $_POST['event_type'];
    $organizer_id = $_POST['organizer_id'];
    $created_at = date("Y-m-d H:i:s");

    // Validate event name (only letters and spaces)
    if (!preg_match("/^[a-zA-Z\s]+$/", $event_name)) {
        echo "<script>alert('Error: Event name must contain only letters and spaces.');</script>";
    } elseif (!in_array($event_type, ['Individual', 'Team'])) {
        echo "<script>alert('Error: Invalid event type.');</script>";
    } else {
        // Check for venue conflict (exclude completed events)
        $check_sql = "SELECT * FROM events WHERE venue_id = ? AND event_date = ? AND status != 'Completed'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $venue_id, $event_date);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('Error: This venue is already booked for another event on the same date.');</script>";
        } else {
            // Verify venue_id exists
            $venue_check_sql = "SELECT venue_id FROM venue WHERE venue_id = ?";
            $venue_check_stmt = $conn->prepare($venue_check_sql);
            $venue_check_stmt->bind_param("i", $venue_id);
            $venue_check_stmt->execute();
            $venue_result = $venue_check_stmt->get_result();

            if ($venue_result->num_rows == 0) {
                echo "<script>alert('Error: Invalid venue ID.');</script>";
            } else {
                // Verify organizer_id exists
                $org_check_sql = "SELECT organizer_id FROM organizers WHERE organizer_id = ?";
                $org_check_stmt = $conn->prepare($org_check_sql);
                $org_check_stmt->bind_param("i", $organizer_id);
                $org_check_stmt->execute();
                $org_result = $org_check_stmt->get_result();

                if ($org_result->num_rows == 0) {
                    echo "<script>alert('Error: Invalid organizer ID.');</script>";
                } else {
                    $sql = "INSERT INTO events (event_name, event_date, venue_id, event_type, organizer_id, created_at) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssisis", $event_name, $event_date, $venue_id, $event_type, $organizer_id, $created_at);
                    if ($stmt->execute()) {
                        echo "<script>alert('Event added successfully.');</script>";
                    } else {
                        echo "<script>alert('Error adding event: " . addslashes($stmt->error) . "');</script>";
                    }
                    $stmt->close();
                }
                $org_check_stmt->close();
            }
            $venue_check_stmt->close();
        }
        $check_stmt->close();
    }
}

// Delete Event
if (isset($_GET['delete'])) {
    $id = htmlspecialchars(trim($_GET['delete']));
    $sql = "DELETE FROM events WHERE event_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Event deleted successfully.');</script>";
    } else {
        echo "<script>alert('Error deleting event: " . addslashes($stmt->error) . "');</script>";
    }
    $stmt->close();
}

// Update Event
if (isset($_POST['edit_event'])) {
    $event_id = $_POST['event_id'];
    $event_name = htmlspecialchars(trim($_POST['event_name']));
    $event_date = $_POST['event_date'];
    $venue_id = $_POST['venue_id'];
    $event_type = $_POST['event_type'];
    $organizer_id = $_POST['organizer_id'];

    // Validate event name (only letters and spaces)
    if (!preg_match("/^[a-zA-Z\s]+$/", $event_name)) {
        echo "<script>alert('Error: Event name must contain only letters and spaces.');</script>";
    } elseif (!in_array($event_type, ['Individual', 'Team'])) {
        echo "<script>alert('Error: Invalid event type.');</script>";
    } else {
        // Check for venue conflict (excluding current event and completed events)
        $check_sql = "SELECT * FROM events WHERE venue_id = ? AND event_date = ? AND event_id != ? AND status != 'Completed'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("isi", $venue_id, $event_date, $event_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('Error: This venue is already booked for another event on the same date.');</script>";
        } else {
            // Verify venue_id exists
            $venue_check_sql = "SELECT venue_id FROM venue WHERE venue_id = ?";
            $venue_check_stmt = $conn->prepare($venue_check_sql);
            $venue_check_stmt->bind_param("i", $venue_id);
            $venue_check_stmt->execute();
            $venue_result = $venue_check_stmt->get_result();

            if ($venue_result->num_rows == 0) {
                echo "<script>alert('Error: Invalid venue ID.');</script>";
            } else {
                // Verify organizer_id exists
                $org_check_sql = "SELECT organizer_id FROM organizers WHERE organizer_id = ?";
                $org_check_stmt = $conn->prepare($org_check_sql);
                $org_check_stmt->bind_param("i", $organizer_id);
                $org_check_stmt->execute();
                $org_result = $org_check_stmt->get_result();

                if ($org_result->num_rows == 0) {
                    echo "<script>alert('Error: Invalid organizer ID.');</script>";
                } else {
                    $sql = "UPDATE events SET event_name=?, event_date=?, venue_id=?, event_type=? WHERE event_id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssisi", $event_name, $event_date, $venue_id, $event_type, $event_id);
                    if ($stmt->execute()) {
                        echo "<script>alert('Event updated successfully.');</script>";
                    } else {
                        echo "<script>alert('Error updating event: " . addslashes($stmt->error) . "');</script>";
                    }
                    $stmt->close();
                }
                $org_check_stmt->close();
            }
            $venue_check_stmt->close();
        }
        $check_stmt->close();
    }
}

// Fetch all events with venue and organizer names
$events_query = "SELECT e.*, v.venue_name, o.organizer_name 
                 FROM events e 
                 LEFT JOIN venue v ON e.venue_id = v.venue_id 
                 LEFT JOIN organizers o ON e.organizer_id = o.organizer_id 
                 ORDER BY e.created_at DESC";
$events = $conn->query($events_query);

// Fetch all venues for dropdown
$venue_query = $conn->query("SELECT * FROM venue");
$venue_options = "";
$has_venues = false;
if ($venue_query && $venue_query->num_rows > 0) {
    $has_venues = true;
    while ($venue = $venue_query->fetch_assoc()) {
        $venue_options .= "<option value='{$venue['venue_id']}'>" . htmlspecialchars($venue['venue_name']) . "</option>";
    }
} else {
    $venue_options = "<option value='' disabled selected>No venues available</option>";
}

// Fetch all organizers for dropdown
$organizer_query = $conn->query("SELECT organizer_id, organizer_name FROM organizers");
$organizer_options = "";
$has_organizers = false;
if ($organizer_query && $organizer_query->num_rows > 0) {
    $has_organizers = true;
    while ($organizer = $organizer_query->fetch_assoc()) {
        $organizer_options .= "<option value='{$organizer['organizer_id']}'>" . htmlspecialchars($organizer['organizer_name']) . "</option>";
    }
} else {
    $organizer_options = "<option value='' disabled selected>No organizers available</option>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Events</title>
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
                        <h1>Manage Events</h1>
                        <button class="btn btn-primary <?php echo (!$has_venues || !$has_organizers) ? 'disabled' : ''; ?>" data-bs-toggle="modal" data-bs-target="#addEventModal">Add Event</button>
                    </div>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="admin.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Manage Events</li>
                    </ol>
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Events Table
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Date</th>
                                        <th>Venue</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Organizer</th>
                                        <th>Edit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $events->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $row['event_id'] ?></td>
                                            <td><?= htmlspecialchars($row['event_name']) ?></td>
                                            <td><?= $row['event_date'] ?></td>
                                            <td><?= htmlspecialchars($row['venue_name']) ?: 'N/A' ?></td>
                                            <td><?= htmlspecialchars($row['event_type']) ?></td>
                                            <td>
                                                <?php
                                                $badge_class = '';
                                                switch ($row['status']) {
                                                    case 'Pending':
                                                        $badge_class = 'bg-warning text-dark';
                                                        break;
                                                    case 'Ongoing':
                                                        $badge_class = 'bg-success text-white';
                                                        break;
                                                    case 'Completed':
                                                        $badge_class = 'bg-secondary text-white';
                                                        break;
                                                    default:
                                                        $badge_class = 'bg-secondary text-white';
                                                }
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?= htmlspecialchars($row['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($row['organizer_name']) ?: 'N/A' ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary <?php echo (!$has_venues) ? 'disabled' : ''; ?>" data-bs-toggle="modal" data-bs-target="#editEventModal<?= $row['event_id'] ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <a href="?delete=<?= $row['event_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this event?');">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editEventModal<?= $row['event_id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <form method="POST">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Event</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="event_id" value="<?= $row['event_id'] ?>">
                                                            <input type="hidden" name="organizer_id" value="<?= $row['organizer_id'] ?>">
                                                            <div class="mb-3">
                                                                <label>Event Name</label>
                                                                <input type="text" name="event_name" class="form-control" value="<?= htmlspecialchars($row['event_name']) ?>" required pattern="[A-Za-z\s]+" title="Only letters and spaces are allowed" onkeypress="return /^[A-Za-z\s]$/.test(String.fromCharCode(event.keyCode || event.which))">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label>Event Date</label>
                                                                <input type="date" name="event_date" class="form-control" value="<?= $row['event_date'] ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label>Venue</label>
                                                                <select name="venue_id" class="form-control" required>
                                                                    <option value="" disabled>Select a venue</option>
                                                                    <?php
                                                                    $venue_query->data_seek(0);
                                                                    while ($venue = $venue_query->fetch_assoc()) {
                                                                        $selected = $venue['venue_id'] == $row['venue_id'] ? "selected" : "";
                                                                        echo "<option value='{$venue['venue_id']}' $selected>" . htmlspecialchars($venue['venue_name']) . "</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label>Event Type</label>
                                                                <select name="event_type" class="form-control" required>
                                                                    <option value="Individual" <?php echo $row['event_type'] == 'Individual' ? 'selected' : ''; ?>>Individual</option>
                                                                    <option value="Team" <?php echo $row['event_type'] == 'Team' ? 'selected' : ''; ?>>Team</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label>Organizer</label>
                                                                <input type="text" class="form-control" value="<?= htmlspecialchars($row['organizer_id']) . ' - ' . htmlspecialchars($row['organizer_name']) ?>" readonly>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" name="edit_event" class="btn btn-primary">Save</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Event</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Event Name</label>
                            <input type="text" name="event_name" class="form-control" required pattern="[A-Za-z\s]+" title="Only letters and spaces are allowed" onkeypress="return /^[A-Za-z\s]$/.test(String.fromCharCode(event.keyCode || event.which))">
                        </div>
                        <div class="mb-3">
                            <label>Event Date</label>
                            <input type="date" name="event_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Venue</label>
                            <select name="venue_id" class="form-control" required>
                                <option value="" disabled selected>Select a venue</option>
                                <?= $venue_options ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Event Type</label>
                            <select name="event_type" class="form-control" required>
                                <option value="" disabled selected>Select event type</option>
                                <option value="Individual">Individual</option>
                                <option value="Team">Team</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Organizer</label>
                            <select name="organizer_id" class="form-control" required>
                                <option value="" disabled selected>Select an organizer</option>
                                <?= $organizer_options ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add_event" class="btn btn-primary">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', event => {
            new simpleDatatables.DataTable("#datatablesSimple");
        });

        // Client-side validation for event name input
        document.querySelectorAll('input[name="event_name"]').forEach(input => {
            input.addEventListener('input', function(e) {
                const value = e.target.value;
                if (!/^[A-Za-z\s]*$/.test(value)) {
                    e.target.value = value.replace(/[^A-Za-z\s]/g, '');
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>