<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start session
session_start();

// Database configuration
define('BASE_URL', 'http://localhost/college_event/');
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "eveflow_db";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch upcoming events
$eventsQuery = "
    SELECT e.event_id, e.event_name, e.event_date, e.event_type, v.venue_name 
    FROM events e
    LEFT JOIN venue v ON e.venue_id = v.venue_id
    WHERE e.event_date >= CURDATE() 
    AND e.status != 'completed'
    ORDER BY e.event_date ASC";
    
$eventsResult = $conn->query($eventsQuery);

if (!$eventsResult) {
    die("Error fetching events: " . $conn->error);
}

$upcomingEvents = [];
while ($row = $eventsResult->fetch_assoc()) {
    $upcomingEvents[] = $row;
}

$isLoggedIn = isset($_SESSION['st_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="EveFlow - College Events Platform">
    <meta name="author" content="Tooplate">
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">
    <title>EveFlow - College Events Platform</title>
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <link rel="stylesheet" href="assets/css/tooplate-artxibition.css">
    <style>
        .venue-tickets .venue-item {
            width: 100%;
            max-width: 350px;
            margin: 0 auto 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .venue-tickets .venue-item:hover {
            transform: scale(1.03);
        }
        .venue-tickets .col-lg-4 {
            padding-left: 5px;
            padding-right: 5px;
        }
        .venue-tickets .venue-item .down-content {
            background: #656c74;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .venue-tickets .venue-item .right-content {
            flex: 1;
        }
        .venue-tickets .venue-item .right-content h4 {
            font-size: 22px;
            color: #fff;
            margin: 0 0 10px 0;
            text-align: left;
            font-weight: 700;
        }
        .venue-tickets .venue-item .right-content p {
            font-size: 16px;
            color: #fff;
            margin: 0 0 10px 0;
            text-align: left;
            font-weight: 300;
        }
        .venue-tickets .venue-item .right-content p span {
            font-weight: bold;
            color: #fff;
            margin-right: 5px;
        }
        .venue-tickets .venue-item .right-content .price {
            background: rgba(124,131,139,0.3);
            padding: 20px;
            font-size: 14px;
            color: #fff;
            flex: 0 0 auto;
        }
        .venue-tickets .venue-item .right-content .price span em {
            font-style: normal;
            color: #fff;
            font-weight: 700;
            font-size: 20px;
        }
        @media (max-width: 768px) {
            .venue-tickets .venue-item {
                max-width: 300px;
                margin-bottom: 15px;
            }
            .venue-tickets .venue-item .down-content {
                flex-direction: column;
                align-items: flex-start;
            }
            .venue-tickets .venue-item .right-content .price {
                text-align: left;
                width: 100%;
                margin-top: 10px;
            }
            .venue-tickets .venue-item .right-content h4 {
                font-size: 20px;
            }
            .venue-tickets .venue-item .right-content p {
                font-size: 14px;
            }
            .venue-tickets .venue-item .right-content .price span em {
                font-size: 18px;
            }
            .venue-tickets .col-lg-4 {
                padding-left: 15px;
                padding-right: 15px;
            }
        }
    </style>
</head>
<body>
    <div id="js-preloader" class="js-preloader">
        <div class="preloader-inner">
            <span class="dot"></span>
            <div class="dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>

    <div class="pre-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-sm-6">
                    <span>Participate in the sport you love the most.</span>
                </div>
                <div class="col-lg-6 col-sm-6">
                    <div class="text-button">
                        <a href="registration.php">Register Now! <i class="fa fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="index.php" class="logo">Eve<em>Flow</em></a>
                        <ul class="nav">
                            <li><a href="index.php" class="active">Home</a></li>
                            <li><a href="#upcoming-activities">Events</a></li>
                            <li><a href="student.php">Dashboard</a></li>
                        </ul>
                        <a class='menu-trigger'>
                            <span>Menu</span>
                        </a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <div class="main-banner">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="main-content">
                        <h6>Elevate Your College Sports Experience</h6>
                        <h2>Choose Your Sport and Join a Team</h2>
                        <div class="main-white-button">
                            <a href="<?php echo $isLoggedIn ? 'apply.php' : 'login.php'; ?>">Participate</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="venue-tickets" id="upcoming-activities">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-heading">
                        <h2>Upcoming Activities</h2>
                    </div>
                </div>
                <?php if (empty($upcomingEvents)): ?>
                    <div class="col-lg-12">
                        <p>No upcoming events available.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($upcomingEvents as $event): ?>
                        <div class="col-lg-4">
                            <div class="venue-item">
                                <div class="down-content">
                                    <div class="right-content">
                                        <h4><?php echo htmlspecialchars($event['event_name']); ?></h4>
                                        <p><span>Venue:</span> <?php echo htmlspecialchars($event['venue_name'] ?: 'TO BE DECLARED'); ?></p>
                                        <div class="price">
                                            <span>Date:<br><em><?php echo date("d-m-Y", strtotime($event['event_date'])); ?></em></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="coming-events">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="event-item">
                        <div class="down-content">
                            <a href="registration.php"><h4>Easy Registration</h4></a>
                            <p>Register for multiple sports with just a few clicks. Our streamlined process makes participation simple.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="event-item">
                        <div class="down-content">
                            <a href="event-details.php"><h4>Event Tracking</h4></a>
                            <p>Stay updated with upcoming matches, practices, and tournaments with our real-time notifications.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="event-item">
                        <div class="down-content">
                            <a href="event-details.php"><h4>Team Management</h4></a>
                            <p>Connect with teammates, share resources, and coordinate practices all in one platform.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="address">
                        <h4>DHS KANOI COLLEGE</h4>
                        <span>K.C. Gogoi Path, Dibrugarh<br>Assam PIN-786001</span>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="links">
                        <h4>Useful Links</h4>
                        <ul>
                            <li><a href="#">Info</a></li>
                            <li><a href="#">Venues</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="hours">
                        <h4>Open Hours</h4>
                        <ul>
                            <li>Mon to Fri: 10:00 AM to 8:00 PM</li>
                            <li>Sat - Sun: 11:00 AM to 4:00 PM</li>
                            <li>Holidays: Closed</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="under-footer">
                        <div class="row">
                            <div class="col-lg-6 col-sm-6">
                                <p>INDIA</p>
                            </div>
                            <div class="col-lg-6 col-sm-6">
                                <p class="copyright">Copyright 2025 EVEFLOW 
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="sub-footer">
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="logo"><span>Eve<em>Flow</em></span></div>
                            </div>
                            <div class="col-lg-6">
                                <div class="menu">
                                    <ul>
                                        <li><a href="index.php" class="active">Home</a></li>
                                        <li><a href="#upcoming-activities">Events</a></li>
                                        <li><a href="<?php echo $isLoggedIn ? 'logout.php' : 'login.php'; ?>"><?php echo $isLoggedIn ? 'Logout' : 'Login'; ?></a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="social-links">
                                    <ul>
                                        <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                                        <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                                        <li><a href="#"><i class="fa fa-behance"></i></a></li>
                                        <li><a href="#"><i class="fa fa-instagram"></i></a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/jquery-2.1.0.min.js"></script>
    <script src="assets/js/popper.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    
    <script>
    // Simplified custom.js content
    $(document).ready(function() {
        // Preloader
        $(window).on("load", function() {
            $('.js-preloader').addClass('loaded');
        });

        // Mobile menu toggle
        $('.menu-trigger').on('click', function() {
            $('.header-area .nav').slideToggle(200);
        });

        // Header scroll effect
        $(window).on('scroll', function() {
            if ($(window).scrollTop() > 50) {
                $('.header-area').addClass('header-sticky');
            } else {
                $('.header-area').removeClass('header-sticky');
            }
        });

        // Smooth scrolling for anchor links
        $('a[href*="#"]').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            var headerHeight = $('.header-area.header-sticky').height() || 80; // Fallback to 80px if not sticky
            $('html, body').animate(
                {
                    scrollTop: $(target).offset().top - headerHeight
                },
                500,
                'linear'
            );
        });
    });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>