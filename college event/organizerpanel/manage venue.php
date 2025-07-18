<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Venue</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>
    <body class="sb-nav-fixed">
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
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="#!">Profile</a></li>
                        <li><hr class="dropdown-divider" /></li>
                        <li><a class="dropdown-item" href="#!">Logout</a></li>
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
                            <a class="nav-link" href="manage venue.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-location-dot fa-fw"></i></div>
                                Manage Venue
                            </a>
                            <a class="nav-link" href="/college event/logout.php">
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
                            <h1>Venue</h1>
                            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVenueModal">
                                <i class="fas fa-plus"></i> Add New Venue
                            </a>
                        </div>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="organizer.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Venue</li>
                        </ol>
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-table me-1"></i>
                                        Manage Participants
                                    </div>
                                    <div class="ms-auto"> <!-- Use ms-auto to push the dropdown to the right -->
                                        <!-- Dropdown for venue Filter -->
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="venueFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                Filter
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="venueFilterDropdown">
                                                <li><a class="dropdown-item" href="#">All Venue</a></li>
                                                <li><a class="dropdown-item" href="#">Indoor</a></li>
                                                <li><a class="dropdown-item" href="#">Outdoor</a></li>
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
                                            <th>Venue Name</th>
                                            <th>Location</th>
                                            <th>Venue Type</th>
                                            <th>Status</th>
                                            <th>Edit</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Id</th>
                                            <th>Venue Name</th>
                                            <th>Location</th>
                                            <th>Venue Type</th>
                                            <th>Status</th>
                                            <th>Edit</th>
                                        </tr>
                                    </tfoot>
                                    <tbody>
                                        <tr>
                                            <td>1</td>
                                            <td>Venue 1</td>
                                            <td>Nearest</td>
                                            <td>Indoor</td>
                                            <td>Active</td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editVenueModal">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="confirmDelete()">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>2</td>
                                            <td>Venue 2</td>
                                            <td>Farthest</td>
                                            <td>Outdoor</td>
                                            <td>Active</td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editVenueModal">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="confirmDelete()">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>                    
                    </div>
                </main>
                <footer class="py-4 bg-light mt-auto">
                    <div class="container-fluid px-4">
                        <div class="d-flex align-items-center justify-content-between small">
                            <div class="text-muted">Copyright &copy; Your Website 2023</div>
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
        <div class="modal fade" id="addVenueModal" tabindex="-1" aria-labelledby="addVenueModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addVenueModalLabel">Add New Venue</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="mb-3">
                                <label for="venueName" class="form-label">Venue Name</label>
                                <input type="text" class="form-control" id="venueName" required>
                            </div>
                            <div class="mb-3">
                                <label for="venueLocation" class="form-label">Location</label>
                                <input type="text" class="form-control" id="venuetype" required>
                            </div>
                            <div class="mb-3">
                                <label for="venueType" class="form-label">Venue Type</label>
                                <input type="number" class="form-control" id="venueType" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Save Venue</button>
                    </div>
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
                    <div class="modal-body">
                        <form>
                            <div class="mb-3">
                                <label for="editVenueName" class="form-label">Venue Name</label>
                                <input type="text" class="form-control" id="editVenueName" required>
                            </div>
                            <div class="mb-3">
                                <label for="editLocation" class="form-label">Location</label>
                                <input type="text" class="form-control" id="editLocation" required>
                            </div>
                            <div class="mb-3">
                                <label for="editVenueType" class="form-label">Venue Type</label>
                                <input type="number" class="form-control" id="editVenueType" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Script -->
        <script>
            function confirmDelete() {
                if (confirm("Are you sure you want to delete this Venue?")) {
                    // Add logic to delete the Venue
                    alert("Venue deleted!");
                }
            }
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
    </body>
</html>
