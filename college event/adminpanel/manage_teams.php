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

// Delete Participant
if (isset($_GET['delete'])) {
    $p_id = $_GET['delete'];
    $sql = "DELETE FROM participations WHERE p_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $p_id);
    $stmt->execute();
    $stmt->close();
    // Redirect to refresh the page
    header("Location: manage_teams.php");
    exit();
}

// Update Participant
if (isset($_POST['edit_participant'])) {
    $p_id = $_POST['p_id'];
    $student_name = $_POST['name'];
    $event_id = $_POST['event_id'];
    $participation_type = $_POST['participant_type'];
    $team_name = $_POST['team_name'];

    // Validate event_id exists
    $event_check_sql = "SELECT event_id FROM events WHERE event_id = ?";
    $event_check_stmt = $conn->prepare($event_check_sql);
    $event_check_stmt->bind_param("i", $event_id);
    $event_check_stmt->execute();
    $event_result = $event_check_stmt->get_result();

    if ($event_result->num_rows == 0) {
        echo "<script>alert('Error: Invalid event ID.');</script>";
    } else {
        // Validate participation_type matches event_type
        $event_type_sql = "SELECT event_type FROM events WHERE event_id = ?";
        $event_type_stmt = $conn->prepare($event_type_sql);
        $event_type_stmt->bind_param("i", $event_id);
        $event_type_stmt->execute();
        $event_type_result = $event_type_stmt->get_result();
        $event_type_row = $event_type_result->fetch_assoc();
        $event_type = $event_type_row['event_type'];

        if ($event_type != $participation_type) {
            echo "<script>alert('Error: Participant type must match the event type ($event_type).');</script>";
        } else {
            $sql = "UPDATE participations SET student_name=?, event_id=?, participation_type=?, team_name=? WHERE p_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisi", $student_name, $event_id, $participation_type, $team_name, $p_id);
            if ($stmt->execute()) {
                echo "<script>alert('Participant updated successfully.');</script>";
            } else {
                echo "<script>alert('Error updating participant: " . $stmt->error . "');</script>";
            }
            $stmt->close();
        }
        $event_type_stmt->close();
    }
    $event_check_stmt->close();
}

// Handle Filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filter_sql = "";
if ($filter == 'individual') {
    $filter_sql = "WHERE p.participation_type = 'Individual'";
} elseif ($filter == 'team') {
    $filter_sql = "WHERE p.participation_type = 'Team'";
}

// Fetch all participations with event names
$participations_query = "SELECT p.*, e.event_name 
                      FROM participations p 
                      LEFT JOIN events e ON p.event_id = e.event_id 
                      $filter_sql 
                      ORDER BY p.created_at DESC";
$participations = $conn->query($participations_query);

// Fetch all events for dropdown
$events_query = $conn->query("SELECT event_id, event_name FROM events");
$event_options = "";
if ($events_query && $events_query->num_rows > 0) {
    while ($event = $events_query->fetch_assoc()) {
        $event_options .= "<option value='{$event['event_id']}'>" . htmlspecialchars($event['event_name']) . "</option>";
    }
} else {
    $event_options = "<option value='' disabled selected>No events available</option>";
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
        <title>Participations</title>
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
                            <h1>View Participations</h1>
                        </div>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="admin.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">View Participations</li>
                        </ol>
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-table me-1"></i>
                                        View Participations
                                    </div>
                                    <div class="ms-auto">
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="playerFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                Filter
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="playerFilterDropdown">
                                                <li><a class="dropdown-item" href="?filter=all">All Participations</a></li>
                                                <li><a class="dropdown-item" href="?filter=individual">Individual</a></li>
                                                <li><a class="dropdown-item" href="?filter=team">Team</a></li>
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
                                            <th>Participant Id</th>
                                            <th>Player Name</th>
                                            <th>Event</th>
                                            <th>Player Type</th>
                                            <th>Team Name</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Id</th>
                                            <th>Participant Id</th>
                                            <th>Player Name</th>
                                            <th>Event</th>
                                            <th>Player Type</th>
                                            <th>Team Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $participations->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['pt_id']) ?></td>
                                                <td><?= $row['p_id'] ?></td>
                                                <td><?= htmlspecialchars($row['student_name']) ?></td>
                                                <td><?= htmlspecialchars($row['event_name']) ?: 'N/A' ?></td>
                                                <td><?= htmlspecialchars($row['participation_type']) ?></td>
                                                <td><?= htmlspecialchars($row['team_name']) ?: '-' ?></td>
                                            </tr>

                                            <!-- Edit Player Modal -->
                                            <div class="modal fade" id="editPlayerModal<?= $row['p_id'] ?>" tabindex="-1" aria-labelledby="editPlayerModalLabel<?= $row['p_id'] ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editPlayerModalLabel<?= $row['p_id'] ?>">Edit Participant</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form method="POST">
                                                                <input type="hidden" name="p_id" value="<?= $row['p_id'] ?>">
                                                                <div class="mb-3">
                                                                    <label for="editParticipantType<?= $row['p_id'] ?>" class="form-label">Participant Type</label>
                                                                    <select class="form-select" id="editParticipantType<?= $row['p_id'] ?>" name="participant_type" required>
                                                                        <option value="Individual" <?= $row['participation_type'] == 'Individual' ? 'selected' : '' ?>>Individual</option>
                                                                        <option value="Team" <?= $row['participation_type'] == 'Team' ? 'selected' : '' ?>>Team</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="editParticipantName<?= $row['p_id'] ?>" class="form-label">Name</label>
                                                                    <input type="text" class="form-control" id="editParticipantName<?= $row['p_id'] ?>" name="name" value="<?= htmlspecialchars($row['student_name']) ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="editParticipantEvent<?= $row['p_id'] ?>" class="form-label">Event Name</label>
                                                                    <select class="form-select" id="editParticipantEvent<?= $row['p_id'] ?>" name="event_id" required>
                                                                        <option value="" disabled>Select Event</option>
                                                                        <?php
                                                                        $events_query->data_seek(0);
                                                                        while ($event = $events_query->fetch_assoc()) {
                                                                            $selected = $event['event_id'] == $row['event_id'] ? 'selected' : '';
                                                                            echo "<option value='{$event['event_id']}' $selected>" . htmlspecialchars($event['event_name']) . "</option>";
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="editTeamName<?= $row['p_id'] ?>" class="form-label">Team Name</label>
                                                                    <textarea class="form-control" id="editTeamName<?= $row['p_id'] ?>" name="team_name" rows="3"><?= htmlspecialchars($row['team_name']) ?></textarea>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    <button type="submit" class="btn btn-primary" name="edit_participant">Save Participant</button>
                                                                </div>
                                                            </form>
                                                        </div>
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
                            <div class="text-muted">Copyright © Your Website 2023</div>
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
    <!-- Add Player Modal (kept but not accessible) -->
        <div class="modal fade" id="addPlayerModal" tabindex="-1" aria-labelledby="addPlayerModalLabel" aria-hidden="true">
            <div classtabl="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPlayerModalLabel">Add New Participant</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="mb-3">
                                <label for="participantType" class="form-label">Participant Type</label>
                                <select class="form-select" id="participantType" required>
                                    <option value="Individual">Individual</option>
                                    <option value="Team">Team</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="participantName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="participantName" placeholder="Enter name" required>
                            </div>
                            <div class="mb-3">
                                <label for="participantEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="participantEmail" placeholder="Enter email" required>
                            </div>
                            <div class="mb-3">
                                <label for="participantPhone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="participantPhone" placeholder="Enter phone number" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="participantEvent" class="form-label">Event</label>
                                    <select class="form-select" id="participantEvent" required>
                                        <option value="" disabled selected>Select Event</option>
                                        <?= $event_options ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="participationStatus" class="form-label">Status</label>
                                    <select class="form-select" id="participationStatus" required>
                                        <option value="Confirmed">Confirmed</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Waitlisted">Waitlisted</option>
                                        <option value="Canceled">Canceled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="participantNotes" class="form-label">Notes</label>
                                <textarea class="form-control" id="participantNotes" rows="3" placeholder="Enter additional notes"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Save Participant</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function confirmDelete() {
                return confirm("Are you sure you want to delete this Participant?");
            }
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
    </body>
</html>
<?php $conn->close(); ?>