<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #656c74;
            --primary-hover: #7c838b;
            --text-color: #585858;
            --light-bg: #f5f7fa;
            --input-bg: rgba(0, 0, 0, 0.12);
            --white: #ffffff;
            --border-radius: 0px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            background-color: var(--light-bg);
        }
        
        .sidebar {
            background-color: var(--secondary-color);
            color: var(--white);
            height: 100vh;
            position: fixed;
            padding-top: 20px;
        }
        
        .sidebar .nav-link {
            color: var(--white);
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: var(--border-radius);
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link.active {
            background-color: var(--primary-color);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .header {
            background-color: var(--white);
            padding: 15px 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: var(--white);
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: var(--border-radius);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }
        
        .stat-card {
            background-color: var(--white);
            padding: 20px;
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card h3 {
            color: var(--secondary-color);
            font-weight: bold;
        }
        
        .stat-card i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .search-input {
            background-color: var(--input-bg);
            border: none;
            border-radius: var(--border-radius);
            padding: 8px 15px;
        }
        
        .event-item {
            border-left: 3px solid var(--primary-color);
            padding: 10px 15px;
            margin-bottom: 10px;
            background-color: var(--white);
        }
        
        .event-item .event-date {
            color: var(--primary-color);
            font-weight: bold;
        }
        
        table {
            background-color: var(--white);
        }
        
        table th {
            background-color: var(--primary-color);
            color: var(--white);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="text-center mb-4">
                    <img src="https://ui-avatars.com/api/?name=Student+Name&background=random" class="profile-img" alt="Profile">
                    <h5 class="mt-2">John Doe</h5>
                    <p class="text-muted">Computer Science</p>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-calendar-alt"></i> My Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-users"></i> Teams
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-book"></i> Courses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <a class="nav-link" href="#">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="header">
                    <h4>Student Dashboard</h4>
                    <div class="d-flex align-items-center">
                        <input type="text" class="search-input me-3" placeholder="Search...">
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="https://ui-avatars.com/api/?name=Student+Name&background=random" class="profile-img me-2">
                                <span>John Doe</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUser">
                                <li><a class="dropdown-item" href="#">Profile</a></li>
                                <li><a class="dropdown-item" href="#">Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#">Sign out</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="fas fa-calendar-check"></i>
                            <h3>5</h3>
                            <p>Registered Events</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="fas fa-trophy"></i>
                            <h3>2</h3>
                            <p>Won Competitions</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="fas fa-users"></i>
                            <h3>3</h3>
                            <p>Team Members</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="fas fa-book"></i>
                            <h3>4</h3>
                            <p>Active Courses</p>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events and Recent Activity -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Upcoming Events</h5>
                            </div>
                            <div class="card-body">
                                <div class="event-item">
                                    <div class="d-flex justify-content-between">
                                        <h6>Tech Symposium</h6>
                                        <span class="event-date">May 15</span>
                                    </div>
                                    <p class="mb-1">Computer Science Department</p>
                                    <small class="text-muted">Main Auditorium</small>
                                </div>
                                <div class="event-item">
                                    <div class="d-flex justify-content-between">
                                        <h6>Hackathon Finals</h6>
                                        <span class="event-date">May 20</span>
                                    </div>
                                    <p class="mb-1">Engineering Department</p>
                                    <small class="text-muted">Tech Building Room 205</small>
                                </div>
                                <div class="event-item">
                                    <div class="d-flex justify-content-between">
                                        <h6>Career Fair</h6>
                                        <span class="event-date">May 25</span>
                                    </div>
                                    <p class="mb-1">University Placement Cell</p>
                                    <small class="text-muted">Student Center</small>
                                </div>
                                <a href="#" class="btn btn-primary btn-sm mt-2">View All Events</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Courses Table -->
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">My Courses</h5>
                        <button class="btn btn-primary btn-sm">Enroll in New Course</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Instructor</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>CS101</td>
                                        <td>Introduction to Programming</td>
                                        <td>Dr. Smith</td>
                                        <td>Mon/Wed 10:00-11:30</td>
                                        <td><span class="badge bg-success">Active</span></td>
                                        <td><button class="btn btn-sm btn-primary">View</button></td>
                                    </tr>
                                    <tr>
                                        <td>CS201</td>
                                        <td>Data Structures</td>
                                        <td>Prof. Johnson</td>
                                        <td>Tue/Thu 13:00-14:30</td>
                                        <td><span class="badge bg-success">Active</span></td>
                                        <td><button class="btn btn-sm btn-primary">View</button></td>
                                    </tr>
                                    <tr>
                                        <td>MATH202</td>
                                        <td>Discrete Mathematics</td>
                                        <td>Dr. Williams</td>
                                        <td>Fri 9:00-12:00</td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                        <td><button class="btn btn-sm btn-primary">View</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>