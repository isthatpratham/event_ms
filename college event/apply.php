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

// Create participations table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS participations (
    pt_id INT AUTO_INCREMENT PRIMARY KEY,
    p_id VARCHAR(20) NOT NULL,
    st_id VARCHAR(20) NOT NULL,
    event_id INT(11) NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    participation_type VARCHAR(50) NOT NULL,
    team_name VARCHAR(255),
    team_members TEXT,
    result VARCHAR(20) NOT NULL DEFAULT 'pending',
    certificate_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (st_id) REFERENCES users(st_id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    UNIQUE KEY unique_participation (st_id, event_id),
    INDEX (p_id),
    INDEX (st_id),
    INDEX (event_id)
)";

if (!$conn->query($create_table_sql)) {
    die("Error creating table: " . $conn->error);
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

// Generate or retrieve participant ID
$participant_id = "prt-" . $student_id;
$existing_pid_query = "SELECT p_id FROM participations WHERE st_id = ?";
$existing_pid_stmt = $conn->prepare($existing_pid_query);
$existing_pid_stmt->bind_param("s", $student_id);
$existing_pid_stmt->execute();
$existing_pid_result = $existing_pid_stmt->get_result();

if ($existing_pid_result->num_rows > 0) {
    $participant_id = $existing_pid_result->fetch_assoc()['p_id'];
}
$existing_pid_stmt->close();

// Get registered events using prepared statement
$registered_events = [];
$registered_query = "SELECT p.event_id, e.event_name 
                    FROM participations p
                    JOIN events e ON p.event_id = e.event_id
                    WHERE p.st_id = ?";
$registered_stmt = $conn->prepare($registered_query);
$registered_stmt->bind_param("s", $student_id);
$registered_stmt->execute();
$registered_result = $registered_stmt->get_result();

while ($row = $registered_result->fetch_assoc()) {
    $registered_events[$row['event_id']] = $row['event_name'];
}
$registered_stmt->close();

// Handle form submission with CSRF protection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['events']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $errors = [];
    
    foreach ($_POST['events'] as $event) {
        if (!isset($event['event_id']) || empty($event['event_id'])) {
            continue;
        }
        
        // Verify event exists and student isn't already registered
        $event_check_query = "SELECT e.event_name, e.event_type 
                             FROM events e
                             LEFT JOIN participations p ON e.event_id = p.event_id AND p.st_id = ?
                             WHERE e.event_id = ? AND p.event_id IS NULL";
        $event_check_stmt = $conn->prepare($event_check_query);
        $event_check_stmt->bind_param("si", $student_id, $event['event_id']);
        $event_check_stmt->execute();
        $event_check_result = $event_check_stmt->get_result();
        
        if ($event_check_result->num_rows == 0) {
            $errors[] = "Event not found or already registered";
            $event_check_stmt->close();
            continue;
        }
        
        $event_data = $event_check_result->fetch_assoc();
        $event_check_stmt->close();
        
        $participation_type = $conn->real_escape_string($event['participation_type']);
        $team_name = isset($event['team_name']) ? $conn->real_escape_string($event['team_name']) : '';
        $team_members = isset($event['team_members']) ? $conn->real_escape_string($event['team_members']) : '';
        
        $insert_query = "INSERT INTO participations (
            p_id, st_id, event_id, student_name,
            event_name, participation_type, team_name, team_members, result
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param(
            "ssisssss",
            $participant_id,
            $student_data['st_id'],
            $event['event_id'],
            $student_data['student_name'],
            $event_data['event_name'],
            $participation_type,
            $team_name,
            $team_members
        );
        
        if (!$insert_stmt->execute()) {
            $errors[] = "Error registering for event: " . $insert_stmt->error;
        }
        $insert_stmt->close();
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: apply.php");
        exit();
    } else {
        header("Location: participation.php?success=1");
        exit();
    }
}

// Fetch available events using prepared statement
$events_query = "SELECT e.event_id, e.event_name, e.event_type 
                FROM events e
                LEFT JOIN participations p ON e.event_id = p.event_id AND p.st_id = ?
                WHERE e.event_date >= CURDATE() 
                AND e.status != 'completed'
                AND p.event_id IS NULL
                ORDER BY e.event_date ASC";
$events_stmt = $conn->prepare($events_query);
$events_stmt->bind_param("s", $student_id);
$events_stmt->execute();
$events_result = $events_stmt->get_result();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
    <title>Event Participation | EveFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/pstyle.css" type="text/css" media="all" />
    <style>
        .form-control:disabled, .form-control[readonly] {
            background-color: #f8f9fa;
            opacity: 1;
        }
        .event-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        .event-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .event-title {
            margin: 0;
            font-size: 1.25rem;
        }
        .remove-event {
            background: none;
            border: none;
            color: #dc3545;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0;
            transition: color 0.2s;
        }
        .remove-event:hover {
            color: #bb2d3b;
        }
        .add-event-btn {
            margin-bottom: 20px;
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
        .team-fields {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #dee2e6;
        }
        .already-registered {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 5px;
            display: none;
        }
        /* Ensure sticky header doesn’t overlap content */
        .content {
            padding-top: 70px; /* Adjust based on header height */
        }
        .alert {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1100;
            width: 350px;
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
        <a href="apply.php" class="active"><i class="bi bi-people"></i> <span>Participate</span></a>
        <a href="participation.php"><i class="bi bi-trophy"></i> <span>My Participations</span></a>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a>
    </div>

    <div class="content">
        <div class="header">
            <div class="d-flex align-items-center">
                <button class="burger-menu me-3">
                    <i class="bi bi-list"></i>
                </button>
                <h1>Event Participation</h1>
            </div>
            <div class="user-info">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student_data['student_name']); ?>&background=random" alt="User">
                <span><?php echo htmlspecialchars($student_data['student_name']); ?></span>
            </div>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="form-card card">
            <div class="card-header">
                <i class="bi bi-pencil-square me-2"></i>Participation Form
            </div>
            <div class="card-body">
                <form id="participationForm" method="POST" action="apply.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <!-- Personal Information Section -->
                    <h5 class="mb-4"><i class="bi bi-person-circle me-2"></i>Personal Information</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="participantId" class="form-label">Participant ID</label>
                            <input type="text" class="form-control" id="participantId" 
                                   value="<?php echo htmlspecialchars($participant_id); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="studentId" class="form-label">Student ID</label>
                            <input type="text" class="form-control" id="studentId" 
                                   value="<?php echo htmlspecialchars($student_data['st_id']); ?>" readonly>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="fullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="fullName" 
                                   value="<?php echo htmlspecialchars($student_data['student_name']); ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" 
                                   value="<?php echo htmlspecialchars($student_data['email']); ?>" readonly>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="course" class="form-label">Course</label>
                            <input type="text" class="form-control" id="course" 
                                   value="<?php echo htmlspecialchars($student_data['course']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="semester" class="form-label">Semester</label>
                            <input type="text" class="form-control" id="semester" 
                                   value="<?php echo htmlspecialchars($student_data['semester']); ?> Semester" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" class="form-control" id="department" 
                               value="<?php echo htmlspecialchars($student_data['dept']); ?>" readonly>
                    </div>
                    
                    <!-- Events Section -->
                    <h5 class="mb-4"><i class="bi bi-calendar3 me-2"></i>Events Participation</h5>
                    <div id="eventsContainer">
                        <!-- Event cards will be added here dynamically -->
                    </div>
                    
                    <div class="add-event-btn">
                        <button type="button" class="btn btn-outline-primary" id="addEventBtn">
                            <i class="bi bi-plus-circle me-2"></i>Add Another Event
                        </button>
                    </div>
                    
                    <!-- Terms and Submit -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="termsCheck" required>
                        <label class="form-check-label" for="termsCheck">
                            I agree to the <a href="#">terms and conditions</a> of participation
                        </label>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-3">
                        <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='student.php'">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send-fill me-2"></i>Submit All Participations
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="footer">
            <p>Copyright © 2025 EveFlow. All rights reserved.</p>
        </div>
    </div>

    <!-- Event Template -->
    <template id="eventTemplate">
        <div class="event-card">
            <div class="event-card-header">
                <h6 class="event-title">Event <span class="event-number">1</span></h6>
                <button type="button" class="remove-event" title="Remove this event">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Select Event</label>
                    <select class="form-select event-select" name="events[][event_id]" required>
                        <option value="" selected disabled>Choose an event</option>
                        <?php 
                        if ($events_result->num_rows > 0) {
                            $events_result->data_seek(0);
                            while($event = $events_result->fetch_assoc()): 
                        ?>
                            <option value="<?php echo htmlspecialchars($event['event_id']); ?>" 
                                    data-event-type="<?php echo htmlspecialchars($event['event_type']); ?>"
                                    data-event-name="<?php echo htmlspecialchars($event['event_name']); ?>">
                                <?php echo htmlspecialchars($event['event_name']); ?>
                            </option>
                        <?php 
                            endwhile;
                        } else {
                            echo '<option value="" disabled>No available events at this time</option>';
                        }
                        ?>
                    </select>
                    <input type="hidden" class="event-name" name="events[][event_name]">
                    <div class="already-registered">
                        <i class="bi bi-exclamation-circle"></i> You're already registered for this event
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Participation Type</label>
                    <input type="text" class="form-control participation-type-display" readonly>
                    <input type="hidden" class="participation-type" name="events[][participation_type]">
                </div>
            </div>
            <div class="team-fields" style="display: none;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Team Name</label>
                        <input type="text" class="form-control team-name" name="events[][team_name]" placeholder="Enter your team name">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Team Members</label>
                        <textarea class="form-control team-members" name="events[][team_members]" rows="2" placeholder="Enter team member names and IDs (comma separated)"></textarea>
                    </div>
                </div>
                <div class="form-text">For team events only</div>
            </div>
        </div>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar functionality
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
            
            // Event participation functionality
            const eventsContainer = document.getElementById('eventsContainer');
            const addEventBtn = document.getElementById('addEventBtn');
            const eventTemplate = document.getElementById('eventTemplate');
            const registeredEvents = <?php echo json_encode(array_keys($registered_events)); ?>;
            let eventCount = 0;
            
            // Add first event by default
            addEvent();
            
            // Add event button click handler
            addEventBtn.addEventListener('click', function() {
                if (eventsContainer.children.length >= 5) {
                    alert('You can register for a maximum of 5 events at once');
                    return;
                }
                addEvent();
            });
            
            function addEvent() {
                eventCount++;
                const eventClone = eventTemplate.content.cloneNode(true);
                const eventCard = eventClone.querySelector('.event-card');
                const eventNumber = eventClone.querySelector('.event-number');
                const removeBtn = eventClone.querySelector('.remove-event');
                const eventSelect = eventClone.querySelector('.event-select');
                const participationTypeDisplay = eventClone.querySelector('.participation-type-display');
                const participationTypeHidden = eventClone.querySelector('.participation-type');
                const teamFields = eventClone.querySelector('.team-fields');
                const teamNameInput = eventClone.querySelector('.team-name');
                const teamMembersTextarea = eventClone.querySelector('.team-members');
                const alreadyRegisteredMsg = eventClone.querySelector('.already-registered');
                const eventNameInput = eventClone.querySelector('.event-name');
                
                eventNumber.textContent = eventCount;
                eventCard.dataset.eventId = eventCount;
                
                // Update array indices in form names
                const inputs = eventClone.querySelectorAll('[name^="events"]');
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    input.setAttribute('name', name.replace('[]', `[${eventCount-1}]`));
                });
                
                // Handle event selection change
                eventSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const eventType = selectedOption.dataset.eventType;
                    const eventName = selectedOption.dataset.eventName;
                    const eventId = this.value;
                    const normalizedType = eventType.toLowerCase();
                    
                    // Set the event name in the hidden input
                    eventNameInput.value = eventName;
                    
                    // Check if already registered
                    if (registeredEvents.includes(eventId)) {
                        alreadyRegisteredMsg.style.display = 'block';
                        this.setCustomValidity("You're already registered for this event");
                    } else {
                        alreadyRegisteredMsg.style.display = 'none';
                        this.setCustomValidity("");
                    }
                    
                    // Set participation type based on event type
                    participationTypeDisplay.value = eventType;
                    participationTypeHidden.value = normalizedType;
                    
                    // Show/hide team fields
                    if (normalizedType === 'team') {
                        teamFields.style.display = 'block';
                        teamNameInput.required = true;
                        teamMembersTextarea.required = true;
                    } else {
                        teamFields.style.display = 'none';
                        teamNameInput.required = false;
                        teamMembersTextarea.required = false;
                        teamNameInput.value = '';
                        teamMembersTextarea.value = '';
                    }
                });
                
                // Add remove event functionality
                removeBtn.addEventListener('click', function() {
                    if (eventsContainer.children.length > 1) {
                        eventCard.remove();
                        renumberEvents();
                    } else {
                        alert('You must participate in at least one event.');
                    }
                });
                
                eventsContainer.appendChild(eventClone);
            }
            
            function renumberEvents() {
                const eventCards = document.querySelectorAll('.event-card');
                eventCards.forEach((card, index) => {
                    card.querySelector('.event-number').textContent = index + 1;
                    
                    // Update array indices in form names
                    const inputs = card.querySelectorAll('[name^="events"]');
                    inputs.forEach(input => {
                        const name = input.getAttribute('name');
                        input.setAttribute('name', name.replace(/\[\d+\]/g, `[${index}]`));
                    });
                });
                eventCount = eventCards.length;
            }
            
            // Prevent form submission if any duplicate events are detected
            document.getElementById('participationForm').addEventListener('submit', function(e) {
                const selects = document.querySelectorAll('.event-select');
                let hasDuplicate = false;
                const selectedEvents = [];
                
                selects.forEach(select => {
                    if (select.value) {
                        // Check if already registered
                        if (registeredEvents.includes(select.value)) {
                            hasDuplicate = true;
                            const msg = select.parentElement.querySelector('.already-registered');
                            msg.style.display = 'block';
                            select.setCustomValidity("You're already registered for this event");
                        }
                        
                        // Check for duplicates in current form
                        if (selectedEvents.includes(select.value)) {
                            hasDuplicate = true;
                            alert('You cannot register for the same event multiple times in one form.');
                        } else {
                            selectedEvents.push(select.value);
                        }
                    }
                });
                
                if (hasDuplicate) {
                    e.preventDefault();
                }
            });

            // Prevent back button from showing cached page
            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });

            // Auto-dismiss alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                        alert.classList.remove('show');
                    }, 500);
                });
            }, 5000);
        });
    </script>
</body>
</html>